<?php

namespace App\Enums;

enum AssetCondition: string
{
    case Baik = 'baik';
    case RusakRingan = 'rusak_ringan';
    case RusakBerat = 'rusak_berat';
}
