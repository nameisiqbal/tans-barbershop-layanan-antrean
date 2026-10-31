<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\SlotTerisi;
use App\Http\Controllers\Controller;
use App\Http\Requests\DaftarSlotRequest;
use App\Http\Requests\TahanSlotRequest;
use App\Http\Resources\ReservasiResource;
use App\Models\Reservasi;
use App\Support\JadwalSlot;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ReservasiController extends Controller
{
    // GET /api/v1/slot?tanggal=YYYY-MM-DD
    public function slot(DaftarSlotRequest $request)
    {
        $tanggal = $request->validated()['tanggal'];

        $terisi = Reservasi::whereDate('tanggal', $tanggal)->pluck('jam_mulai')->all();

        $data = array_map(fn (string $jam) => [
            'tanggal' => $tanggal,
            'jam_mulai' => $jam,
            'status' => in_array($jam, $terisi, true) ? 'terisi' : 'kosong',
        ], JadwalSlot::semuaJam());

        return response()->json(['data' => $data]);
    }

    // POST /api/v1/reservasi
    public function tahan(TahanSlotRequest $request)
    {
        $data = $request->validated();

        $reservasi = DB::transaction(function () use ($data) {
            $sudahAda = Reservasi::whereDate('tanggal', $data['tanggal'])
                ->where('jam_mulai', $data['jam_mulai'])
                ->exists();

            if ($sudahAda) {
                throw new SlotTerisi($data['tanggal'], $data['jam_mulai']);
            }

            try {
                $baru = Reservasi::create($data);
            } catch (UniqueConstraintViolationException) {
                // Dua permintaan datang bersamaan: penjaga di basis data menolak yang kedua.
                throw new SlotTerisi($data['tanggal'], $data['jam_mulai']);
            }

            $baru->update(['kode' => sprintf('RSV-%04d', $baru->id)]);

            return $baru;
        });

        return (new ReservasiResource($reservasi))
            ->response()
            ->setStatusCode(201)
            ->header('Location', url("/api/v1/reservasi/{$reservasi->kode}"));
    }

    // GET /api/v1/reservasi/{kode}
    public function show(Reservasi $reservasi)
    {
        return new ReservasiResource($reservasi);
    }
}
