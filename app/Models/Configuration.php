<?php

namespace App\Models;

use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuration extends Model
{
    use LogsHistory;

    protected $fillable = [
        'user_id',
        'currency',
        'default_margin',
        'theme',
        'monthly_salary',
        'monthly_working_hours',
        'monthly_production',
    ];

    protected function casts(): array
    {
        return [
            'default_margin' => 'decimal:2',
            'monthly_salary' => 'decimal:2',
            'monthly_working_hours' => 'decimal:2',
            'monthly_production' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Minutos productivos mensuales, derivados automáticamente de las horas laborales.
     */
    public function getMonthlyProductiveMinutesAttribute(): float
    {
        return (float) $this->monthly_working_hours * 60;
    }

    /**
     * Costo de mano de obra por minuto: salario mensual / minutos productivos.
     */
    public function getCostPerMinuteAttribute(): float
    {
        $minutes = $this->monthly_productive_minutes;

        if ($minutes <= 0) {
            return 0;
        }

        return round((float) $this->monthly_salary / $minutes, 4);
    }
}
