<?php

namespace App\Enums;

enum AssetAvailabilityStatus: string
{
    case Tersedia = 'tersedia';
    case Dipesan = 'dipesan';
    case Dipinjam = 'dipinjam';
    case Perbaikan = 'perbaikan';
    case TidakTersedia = 'tidak_tersedia';
}
