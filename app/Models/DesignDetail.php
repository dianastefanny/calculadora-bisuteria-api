<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de la receta de un diseño: un material, la cantidad que usa la pieza
 * y el subtotal (cantidad × costo unitario) congelado al guardar el diseño.
 */
class DesignDetail extends Model
{
    protected $fillable = [
        'design_id',
        'material_id',
        'quantity',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
