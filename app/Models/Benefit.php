<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Benefit extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'benefit_type_id',
        'name',
        'percentage',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function benefitType(): BelongsTo
    {
        return $this->belongsTo(BenefitType::class);
    }
}
