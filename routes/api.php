<?php

use App\Http\Controllers\Api\V1\ReservasiController;
use Illuminate\Support\Facades\Route;

// Awalan /api ditambahkan otomatis oleh Laravel; /v1 adalah versi MAJOR kontrak.
Route::prefix('v1')
    ->middleware(['jwt', 'throttle:api'])
    ->group(function () {
        // Endpoint baca membutuhkan scope antrean:baca
        Route::middleware('scope:antrean:baca')->group(function () {
            Route::get('/slot', [ReservasiController::class, 'slot']);
            Route::get('/reservasi/{reservasi:kode}', [ReservasiController::class, 'show']);
        });

        // Endpoint tulis membutuhkan scope antrean:tulis
        Route::middleware('scope:antrean:tulis')->group(function () {
            Route::post('/reservasi', [ReservasiController::class, 'tahan']);
        });
    });
