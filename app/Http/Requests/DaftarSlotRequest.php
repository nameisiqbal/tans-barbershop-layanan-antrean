<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DaftarSlotRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
