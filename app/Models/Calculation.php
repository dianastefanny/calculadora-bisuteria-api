<?php

namespace App\Models;

use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cotización de una pieza (o de un lote de piezas iguales) con el desglose
 * completo de costos calculado en CalculationController::store.
 *
 * - sale_price: precio de venta según el margen, sin descuento.
 * - final_price: precio que se cobra al cliente, ya con el descuento si lo hay.
 * - valid_until: fin de la vigencia de la cotización (5 días).
 * - is_sold: indica que la venta se registró y el inventario ya se descontó.
 *
 * Los costos se guardan como valores fijos para que la cotización no cambie
 * si después se modifican los precios de los materiales o la configuración.
 */
class Calculation extends Model
{
    use LogsHistory;
    protected $fillable = [
    'user_id',
    'design_id',
    'packaging_id',
    'packaging_quantity',
    'production_time_minutes',
    'quantity',
    'packaging_cost',
    'margin',
    'discount_percentage',
    'materials_cost',
    'labor_cost',
    'benefits_cost',
    'indirect_cost',
    'total_cost',
    'sale_price',
    'final_price',
    'is_sold',
    'valid_until',
];

    protected function casts(): array
{
    return [
        'packaging_quantity' => 'decimal:2',
        'packaging_cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'materials_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'benefits_cost' => 'decimal:2',
        'indirect_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'is_sold' => 'boolean',
        'valid_until' => 'date',
    ];
}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }
}
