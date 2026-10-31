<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 8)->nullable()->unique();
            $table->date('tanggal');
            $table->string('jam_mulai', 5);
            $table->string('nama_pelanggan', 100);
            $table->string('sumber', 10);
            $table->timestamps();

            // Penjaga terakhir: satu slot tidak mungkin terpesan dua kali,
            // walau dua permintaan datang pada detik yang sama.
            $table->unique(['tanggal', 'jam_mulai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservasi');
    }
};
