<?php

namespace App\Traits;

use App\Models\History;

use Illuminate\Database\Eloquent\Model;


/**
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
