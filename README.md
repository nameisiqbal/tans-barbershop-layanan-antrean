# Layanan Antrean dan Jadwal — Tan's Barbershop

Proyek akhir Integrasi Sistem dan Komputasi Awan (25IF1504), STMIK Bandung.
Layanan ini mengatur slot 30 menit pukul 10.00–20.00 dan memastikan satu slot
tidak terpesan dua kali, baik oleh pelanggan online maupun walk-in.

## Anggota dan pembagian tugas

| Anggota | NIM | Bagian yang dikerjakan |
| --- | --- | --- |
| Mohammad Iqbal Raharja Dwi Putra | 3222620 | Kontrak OpenAPI, kode layanan antrean, feature test, README |
| Pandu Aria Legawa | 3222651 | (diisi oleh yang bersangkutan) |
| Sandria Adlu Hakim | 3222658 | (diisi oleh yang bersangkutan) |
| Tedy Irawan | 3222631 | (diisi oleh yang bersangkutan) |

## Cara menjalankan dari nol

Prasyarat: Docker Desktop. Di Windows gunakan WSL2 dengan Ubuntu, dan letakkan proyek di folder rumah Linux (bukan di /mnt/c).

1. Salin berkas lingkungan:

        cp .env.example .env

2. Pasang dependensi (tidak perlu PHP di komputer):

        docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/app" composer:2 install --ignore-platform-reqs

3. Nyalakan layanan:

        ./vendor/bin/sail up -d

4. Buat kunci aplikasi dan tabel basis data:

        ./vendor/bin/sail artisan key:generate
        ./vendor/bin/sail artisan migrate

5. Jalankan test (hasil yang diharapkan: seluruhnya lulus):

        ./vendor/bin/sail artisan test

6. Periksa kontrak dengan Spectral (hasil yang diharapkan: tanpa error):

        docker run --rm -v "$(pwd)":/work -w /work stoplight/spectral lint openapi.yaml

Layanan berjalan di http://localhost. Bila port 80 sudah dipakai aplikasi lain, tambahkan
APP_PORT=8000 di .env lalu nyalakan ulang.

## Kontrak API

Kontrak lengkap ada di `openapi.yaml` (OpenAPI 3.1, ditulis design-first, lolos Spectral). Galat memakai format Problem Details (RFC 9457).

| Operasi | Method dan path | Berhasil | Galat |
| --- | --- | --- | --- |
| daftarSlot | GET /api/v1/slot?tanggal=YYYY-MM-DD | 200, 20 slot beserta status | 422 |
| tahanSlot | POST /api/v1/reservasi | 201 + header Location | 409 (slot terisi), 422 |
| detailReservasi | GET /api/v1/reservasi/{kode} | 200 | 404 |

Aturan bisnis utama: satu slot (tanggal + jam) hanya boleh terisi sekali. Permintaan kedua pada slot yang sama dijawab 409 `slot-terisi`.

## Kontrak event (rancangan, belum diimplementasikan)

Jalur asinkron menghubungkan layanan antrean dengan layanan notifikasi (pengingat jadwal lewat WhatsApp).

| Butir | Nilai |
| --- | --- |
| Nama event | SlotDitahan |
| Penerbit | Layanan antrean (setelah tahanSlot berhasil, 201) |
| Penerima | Layanan notifikasi |
| Exchange | antrean.events (tipe topic, durable) |
| Routing key | slot.ditahan |
| Antrean penerima | notifikasi.slot_ditahan |

Isi pesan (JSON):

| Field | Tipe | Keterangan |
| --- | --- | --- |
| event_id | string (UUID) | Pengenal unik pesan, dipakai penerima agar pemrosesan ganda tidak berdampak |
| terbit_pada | string (date-time) | Waktu event diterbitkan |
| kode | string | Kode reservasi, misalnya RSV-0001 |
| tanggal | string (date) | Tanggal slot, YYYY-MM-DD |
| jam_mulai | string | Jam mulai slot, HH:MM |
| nama_pelanggan | string | Nama pelanggan |
| sumber | string | online atau walk-in |
