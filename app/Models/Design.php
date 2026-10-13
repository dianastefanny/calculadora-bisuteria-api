<?php

namespace App\Models;

use App\Traits\LogsHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Diseño de una pieza: su receta de materiales (DesignDetail) y una foto
 * opcional almacenada en el disco público.
 *
 * La ruta interna de la foto se oculta en las respuestas; en su lugar se
 * expone image_url con la dirección pública lista para mostrar.
 */
class Design extends Model
{
    use HasFactory, LogsHistory;
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'reference',
        'image_path',
        'is_active',
    ];

    protected $hidden = ['image_path'];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * URL pública de la foto, o null si el diseño no tiene.
     *
     * Se construye con asset(), que toma el host de la petición en curso y no
     * APP_URL: así la dirección es válida tanto en la PC como desde el
     * celular conectado por la IP de la red local.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DesignDetail::class);
    }
}
