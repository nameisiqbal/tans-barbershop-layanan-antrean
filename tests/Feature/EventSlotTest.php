<?php

namespace Tests\Feature;

use App\Events\SlotDitahan;
use App\Listeners\TerbitkanSlotDitahan;
use App\Messaging\RabbitPublisher;
use App\Models\Reservasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventSlotTest extends TestCase
{
    use RefreshDatabase;

    private function permintaan(string $nama = 'Raka', string $sumber = 'online'): array
    {
        return [
            'tanggal' => '2026-11-02',
            'jam_mulai' => '10:00',
            'nama_pelanggan' => $nama,
            'sumber' => $sumber,
        ];
    }

    public function test_menahan_slot_menerbitkan_event_slot_ditahan(): void
    {
        Event::fake([SlotDitahan::class]);

        $this->postJson('/api/v1/reservasi', $this->permintaan())->assertCreated();

        Event::assertDispatched(SlotDitahan::class, fn (SlotDitahan $e) => $e->tanggal === '2026-11-02'
            && $e->jamMulai === '10:00'
            && $e->namaPelanggan === 'Raka'
            && $e->sumber === 'online'
            && preg_match('/^RSV-[0-9]{4}$/', $e->kode) === 1);
    }

    public function test_slot_yang_terisi_tidak_menerbitkan_event(): void
    {
        Event::fake([SlotDitahan::class]);

        Reservasi::create([
            'kode' => 'RSV-0001',
            'tanggal' => '2026-11-02',
            'jam_mulai' => '10:00',
            'nama_pelanggan' => 'Raka',
            'sumber' => 'online',
        ]);

        $this->postJson('/api/v1/reservasi', $this->permintaan('Dimas', 'walk-in'))->assertStatus(409);

        Event::assertNotDispatched(SlotDitahan::class);
    }

    public function test_listener_meneruskan_event_ke_publisher_dengan_routing_key_slot_ditahan(): void
    {
        $event = new SlotDitahan('RSV-0001', '2026-11-02', '10:00', 'Raka', 'online');

        $publisher = $this->mock(RabbitPublisher::class, function ($mock) use ($event) {
            $mock->shouldReceive('publish')
                ->once()
                ->withArgs(fn (string $kunci, array $data, string $id, string $jenis) => $kunci === 'slot.ditahan'
                    && $data['kode'] === 'RSV-0001'
                    && $data['event_id'] === $event->id
                    && $id === $event->id
                    && $jenis === 'SlotDitahan');
        });

        (new TerbitkanSlotDitahan($publisher))->handle($event);
    }
}
