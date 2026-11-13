<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Concerns\InteractsWithJwt;
use Tests\TestCase;

class KeamananApiTest extends TestCase
{
    use InteractsWithJwt;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanJwksPalsu();
    }

    public function test_permintaan_tanpa_token_ditolak_401_problem_json(): void
    {
        $res = $this->getJson('/api/v1/slot?tanggal=2026-11-20');

        $res->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertHeader('WWW-Authenticate', 'Bearer realm="layanan-antrean"')
            ->assertJsonPath('status', 401)
            ->assertJsonPath('title', 'Tidak Terautentikasi');
    }

    public function test_token_kedaluwarsa_ditolak_401(): void
    {
        $token = $this->buatToken('antrean:baca', 'monitor', ['exp' => time() - 60]);

        $this->withToken($token)
            ->getJson('/api/v1/slot?tanggal=2026-11-20')
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_token_dengan_audience_salah_ditolak_401(): void
    {
        $token = $this->buatToken('antrean:baca', 'monitor', ['aud' => ['layanan-lain']]);

        $this->withToken($token)
            ->getJson('/api/v1/slot?tanggal=2026-11-20')
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_klien_monitor_hanya_baca_ditolak_403_saat_menahan_slot(): void
    {
        $tokenMonitor = $this->buatToken('antrean:baca', 'monitor');

        $this->withToken($tokenMonitor)
            ->postJson('/api/v1/reservasi', [
                'tanggal' => '2026-11-20',
                'jam_mulai' => '10:00',
                'nama_pelanggan' => 'Budi Santoso',
                'sumber' => 'online',
            ])
            ->assertStatus(403)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 403)
            ->assertJsonPath('title', 'Akses Ditolak');
    }

    public function test_klien_kiosk_dengan_scope_lengkap_bisa_baca_dan_tulis(): void
    {
        Event::fake();
        $tokenKiosk = $this->buatToken('antrean:baca antrean:tulis', 'kiosk');

        $this->withToken($tokenKiosk)
            ->postJson('/api/v1/reservasi', [
                'tanggal' => '2026-11-20',
                'jam_mulai' => '10:00',
                'nama_pelanggan' => 'Budi Santoso',
                'sumber' => 'online',
            ])
            ->assertStatus(201);

        $this->withToken($tokenKiosk)
            ->getJson('/api/v1/slot?tanggal=2026-11-20')
            ->assertStatus(200);
    }

    public function test_rate_limiter_per_klien_mengembalikan_429_problem_json(): void
    {
        $token = $this->buatToken('antrean:baca', 'klien-spam');

        for ($i = 0; $i < 60; $i++) {
            $this->withToken($token)->getJson('/api/v1/slot?tanggal=2026-11-20')->assertStatus(200);
        }

        $this->withToken($token)
            ->getJson('/api/v1/slot?tanggal=2026-11-20')
            ->assertStatus(429)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 429)
            ->assertJsonPath('title', 'Terlalu Banyak Permintaan');
    }
}
