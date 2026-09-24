<?php

namespace App\Models;

use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Packaging extends Model
{
    use LogsHistory;
    protected $fillable = [
        'user_id',
        'name',
        'unit_cost',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
            'stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
