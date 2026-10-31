<?php

namespace App\Exceptions;

use RuntimeException;

class SlotTerisi extends RuntimeException
{
    public function __construct(
        public readonly string $tanggal,
        public readonly string $jamMulai,
    ) {
        parent::__construct("Slot {$tanggal} pukul {$jamMulai} sudah terisi.");
    }
}
