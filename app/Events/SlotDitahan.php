<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Str;

/**
 * Event domain: sebuah slot baru saja ditahan.
 * Ditulis dalam bentuk lampau karena menyatakan fakta yang sudah terjadi.
 */
class SlotDitahan
{
    use Dispatchable;

    public readonly string $id;

    public readonly string $terjadiPada;

    public function __construct(
        public readonly string $kode,
        public readonly string $tanggal,
        public readonly string $jamMulai,
        public readonly string $namaPelanggan,
        public readonly string $sumber,
    ) {
        $this->id = (string) Str::uuid();
        $this->terjadiPada = now()->toIso8601String();
    }

    /** Isi pesan: kontrak antara layanan antrean dan penerimanya. */
    public function payload(): array
    {
        return [
            'event_id' => $this->id,
            'terbit_pada' => $this->terjadiPada,
            'kode' => $this->kode,
            'tanggal' => $this->tanggal,
            'jam_mulai' => $this->jamMulai,
            'nama_pelanggan' => $this->namaPelanggan,
            'sumber' => $this->sumber,
        ];
    }
}
