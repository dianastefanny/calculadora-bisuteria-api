<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
   use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function materials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function materialCategories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MaterialCategory::class);
    }

    public function designs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function configuration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Configuration::class);
    }

    public function costTypes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CostType::class);
    }

    public function indirectCosts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IndirectCost::class);
    }

    public function benefitTypes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BenefitType::class);
    }

public function benefits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Benefit::class);
    }

public function calculations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Calculation::class);
    }

public function packagings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Packaging::class);
    }

    public function histories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(History::class);
    }
}
