<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $table = 'reservasi';

    protected $fillable = ['kode', 'tanggal', 'jam_mulai', 'nama_pelanggan', 'sumber'];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
