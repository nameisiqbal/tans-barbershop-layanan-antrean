<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representasi reservasi yang dijanjikan kontrak (schema Reservasi di openapi.yaml).
 * Kolom internal seperti id dan updated_at sengaja tidak ikut ditampilkan.
 */
class ReservasiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'tanggal' => $this->tanggal->format('Y-m-d'),
            'jam_mulai' => $this->jam_mulai,
            'nama_pelanggan' => $this->nama_pelanggan,
            'sumber' => $this->sumber,
            'dibuat_pada' => $this->created_at?->toIso8601String(),
        ];
    }
}
