<?php

namespace App\Enums;

enum MaterialUnit: string
{
    case Unit = 'unit';
    case Gram = 'gram';
    case Kilogram = 'kilogram';
    case Meter = 'meter';
    case Centimeter = 'centimeter';
    case Package = 'package';
    case Pair = 'pair';
}
