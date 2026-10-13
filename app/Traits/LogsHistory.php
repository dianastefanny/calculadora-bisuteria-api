<?php

namespace App\Traits;

use App\Models\History;

use Illuminate\Database\Eloquent\Model;


/**
 * Registra automáticamente en la bitácora del usuario (tabla histories) la
 * creación, edición y eliminación de los modelos que usan este trait.
 *
 * Laravel ejecuta bootLogsHistory() al inicializar cada modelo que lo
 * incluye, de modo que basta con agregar el trait para auditar un modelo,
 * sin escribir código en los controladores.
 *
 * @mixin Model
 */

trait LogsHistory
{
    public static function bootLogsHistory(): void
    {
        static::created(function ($model) {
            $model->recordHistory('created');
        });

        static::updated(function ($model) {
            $model->recordHistory('updated');
        });

        static::deleted(function ($model) {
            $model->recordHistory('deleted');
        });
    }

    /**
     * Guarda una entrada de historial para el modelo. El dueño se toma del
     * propio registro (user_id) o, si no lo tiene, del usuario autenticado;
     * sin ninguno de los dos (por ejemplo, en procesos de consola) no se
     * registra nada.
     */
    protected function recordHistory(string $action): void
    {
        $userId = $this->user_id ?? auth()->id();

        if (! $userId) {
            return;
        }

        History::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => class_basename($this),
            'subject_id' => $this->id,
            'description' => $this->historyDescription($action),
        ]);
    }

    /**
     * Texto legible de la entrada, con el nombre del registro o, si el modelo
     * no tiene nombre, su id.
     */
    protected function historyDescription(string $action): string
    {
        $name = $this->name ?? $this->id;
        $subject = class_basename($this);

        return match ($action) {
            'created' => "{$subject} \"{$name}\" was created.",
            'updated' => "{$subject} \"{$name}\" was updated.",
            'deleted' => "{$subject} \"{$name}\" was deleted.",
            default => "{$subject} \"{$name}\" changed.",
        };
    }
}
