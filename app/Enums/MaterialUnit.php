<?php

namespace App\Enums;

/**
 * Unidades de medida admitidas para los materiales. El valor se guarda tal
 * cual en la base de datos y la app lo traduce a su etiqueta en español.
 */
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
