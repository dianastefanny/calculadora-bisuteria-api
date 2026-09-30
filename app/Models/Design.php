<?php

namespace App\Models;

use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Design extends Model
{
    use HasFactory, LogsHistory;
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'reference',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DesignDetail::class);
    }

    public function materials()
    {
        return $this->belongsToMany(Material::class, 'design_details')
            ->withPivot(['quantity', 'subtotal'])
            ->withTimestamps();
    }

    public function getTotalMaterialsCostAttribute(): float
    {
        return (float) $this->details()->sum('subtotal');
    }
}
