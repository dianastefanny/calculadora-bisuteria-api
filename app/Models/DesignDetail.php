<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
