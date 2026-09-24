<?php

namespace App\Models;

use App\Enums\MaterialUnit;
use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Material extends Model
{
    use HasFactory, LogsHistory;


    protected $fillable = [
        'user_id',
        'material_category_id',
        'name',
        'unit',
        'unit_cost',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit' => MaterialUnit::class,
            'unit_cost' => 'decimal:2',
            'stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }


}
