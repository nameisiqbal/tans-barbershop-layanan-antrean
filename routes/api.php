<?php

use App\Http\Controllers\Api\V1\ReservasiController;
use Illuminate\Support\Facades\Route;

// Awalan /api ditambahkan otomatis oleh Laravel; /v1 adalah versi MAJOR kontrak.
Route::prefix('v1')->group(function () {
    Route::get('/slot', [ReservasiController::class, 'slot']);
    Route::post('/reservasi', [ReservasiController::class, 'tahan']);
    Route::get('/reservasi/{reservasi:kode}', [ReservasiController::class, 'show']);
});
