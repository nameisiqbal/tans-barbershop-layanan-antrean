<?php

namespace App\Http\Requests;

use App\Support\JadwalSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TahanSlotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'jam_mulai' => ['required', Rule::in(JadwalSlot::semuaJam())],
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'sumber' => ['required', Rule::in(['online', 'walk-in'])],
        ];
    }
}
