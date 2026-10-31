<?php

namespace App\Support;

/**
 * Jadwal tetap: slot 30 menit, pukul 10.00 sampai 20.00 (20 slot per hari).
 */
class JadwalSlot
{
    public const MULAI = 10;

    public const SELESAI = 20;

    /** @return list<string> */
    public static function semuaJam(): array
    {
        $jam = [];

        for ($j = self::MULAI; $j < self::SELESAI; $j++) {
            $jam[] = sprintf('%02d:00', $j);
            $jam[] = sprintf('%02d:30', $j);
        }

        return $jam;
    }
}
