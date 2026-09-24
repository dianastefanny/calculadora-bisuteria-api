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

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unit',
            self::Gram => 'Gram',
            self::Kilogram => 'Kilogram',
            self::Meter => 'Meter',
            self::Centimeter => 'Centimeter',
            self::Package => 'Package',
            self::Pair => 'Pair',
        };
    }
}
