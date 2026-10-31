<?php

namespace Tests\Feature;

use App\Models\Reservasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Setiap test di bawah ini menerjemahkan satu janji pada openapi.yaml
 * (Layanan Antrean dan Jadwal — Tan's Barbershop) menjadi pemeriksaan.
 */
class ReservasiApiTest extends TestCase
{
    use RefreshDatabase;

    private function reservasiContoh(string $jam = '10:00'): Reservasi
    {
        return Reservasi::create([
            'kode' => 'RSV-0001',
            'tanggal' => '2026-11-02',
            'jam_mulai' => $jam,
            'nama_pelanggan' => 'Raka',
            'sumber' => 'online',
        ]);
    }

    public function test_daftar_slot_mengembalikan_20_slot_pada_satu_tanggal(): void
    {
        $this->getJson('/api/v1/slot?tanggal=2026-11-02')
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonStructure(['data' => [['tanggal', 'jam_mulai', 'status']]])
            ->assertJsonPath('data.0.jam_mulai', '10:00')
            ->assertJsonPath('data.0.status', 'kosong');
    }

    public function test_slot_yang_sudah_ditahan_berstatus_terisi(): void
    {
        $this->reservasiContoh('10:00');

        $this->getJson('/api/v1/slot?tanggal=2026-11-02')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'terisi')
            ->assertJsonPath('data.1.status', 'kosong');
    }

    public function test_menahan_slot_mengembalikan_201_dan_header_location(): void
    {
        $respons = $this->postJson('/api/v1/reservasi', [
            'tanggal' => '2026-11-02',
            'jam_mulai' => '10:00',
            'nama_pelanggan' => 'Raka',
            'sumber' => 'online',
        ])
            ->assertCreated()
            ->assertJsonPath('data.sumber', 'online');

        // Kode dibentuk dari id baris dan id bisa berbeda antar test, jadi cukup periksa polanya.
        $kode = $respons->json('data.kode');
        $this->assertMatchesRegularExpression('/^RSV-[0-9]{4}$/', $kode);
        $respons->assertHeader('Location', url("/api/v1/reservasi/{$kode}"));
    }

    public function test_menahan_slot_yang_sudah_terisi_ditolak_dengan_409(): void
    {
        $this->reservasiContoh('10:00');

        $this->postJson('/api/v1/reservasi', [
            'tanggal' => '2026-11-02',
            'jam_mulai' => '10:00',
            'nama_pelanggan' => 'Dimas',
            'sumber' => 'walk-in',
        ])
            ->assertStatus(409)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'https://tansbarbershop.test/problem/slot-terisi');

        $this->assertSame(1, Reservasi::count());
    }

    public function test_reservasi_tanpa_nama_ditolak_dengan_422_problem_json(): void
    {
        $this->postJson('/api/v1/reservasi', [
            'tanggal' => '2026-11-02',
            'jam_mulai' => '10:00',
            'sumber' => 'online',
        ])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 422)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors' => ['nama_pelanggan']]);
    }

    public function test_daftar_slot_tanpa_tanggal_ditolak_dengan_422(): void
    {
        $this->getJson('/api/v1/slot')
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonStructure(['errors' => ['tanggal']]);
    }

    public function test_detail_reservasi_mengembalikan_200(): void
    {
        $this->reservasiContoh();

        $this->getJson('/api/v1/reservasi/RSV-0001')
            ->assertOk()
            ->assertJsonPath('data.kode', 'RSV-0001')
            ->assertJsonPath('data.nama_pelanggan', 'Raka');
    }

    public function test_kode_yang_tidak_ada_mengembalikan_404_problem_json(): void
    {
        $this->getJson('/api/v1/reservasi/RSV-9999')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 404);
    }
}
