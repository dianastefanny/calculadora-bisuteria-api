<?php

namespace App\Models;

use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndirectCost extends Model
{
    use HasFactory, LogsHistory;
    protected $fillable = [
        'user_id',
        'cost_type_id',
        'name',
        'monthly_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function costType(): BelongsTo
    {
        return $this->belongsTo(CostType::class);
    }
}
