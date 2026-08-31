<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    public function causerName(): string
    {
        return $this->causer?->name ?? 'System';
    }

    public function feedSentence(): string
    {
        return "{$this->causerName()} {$this->description}";
    }

    public function eventIcon(): string
    {
        if ($this->log_name === 'comment') {
            return 'heroicon-o-chat-bubble-left-right';
        }

        return match ($this->event) {
            'created' => 'heroicon-o-plus-circle',
            'updated' => 'heroicon-o-pencil-square',
            'deleted' => 'heroicon-o-trash',
            default => 'heroicon-o-bolt',
        };
    }

    public function eventColor(): string
    {
        if ($this->log_name === 'comment') {
            return 'info';
        }

        return match ($this->event) {
            'created' => 'success',
            'updated' => 'warning',
            'deleted' => 'danger',
            default => 'gray',
        };
    }

    public function subjectLabel(): string
    {
        if ($this->subject === null) {
            return Str::headline($this->subject_type ?? 'record').' (deleted)';
        }

        return $this->subject->title
            ?? $this->subject->name
            ?? Str::headline(class_basename($this->subject)).' #'.$this->subject->getKey();
    }

    /**
     * @return array<int, string>
     */
    public function changeSummary(): array
    {
        $attributes = $this->attribute_changes?->get('attributes') ?? [];
        $old = $this->attribute_changes?->get('old') ?? [];

        if (empty($attributes)) {
            return [];
        }

        $lines = [];

        foreach ($attributes as $field => $newValue) {
            $label = $this->fieldLabel($field);
            $newDisplay = $this->displayValue($field, $newValue);

            if (array_key_exists($field, $old)) {
                $oldDisplay = $this->displayValue($field, $old[$field]);
                $lines[] = "{$label}: {$oldDisplay} → {$newDisplay}";
            } else {
                $lines[] = "{$label}: {$newDisplay}";
            }
        }

        return $lines;
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'status_id' => 'Status',
            'assignee_id' => 'Assignee',
            'reporter_id' => 'Reporter',
            'owner_id' => 'Owner',
            default => Str::headline($field),
        };
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $relatedModel = match ($field) {
            'status_id' => Status::class,
            'assignee_id', 'reporter_id', 'owner_id' => User::class,
            default => null,
        };

        if ($relatedModel !== null) {
            return $relatedModel::find($value)?->name ?? "#{$value}";
        }

        return (string) $value;
    }
}
