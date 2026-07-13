<?php

namespace App\Models;

use Database\Factories\StatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'position', 'is_default', 'is_completed', 'is_cancelled'])]
class Status extends Model
{
    /** @use HasFactory<StatusFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_completed' => 'boolean',
            'is_cancelled' => 'boolean',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    protected static function booted(): void
    {
        static::saved(function (Status $status) {
            if ($status->is_default) {
                static::where('id', '!=', $status->id)->update(['is_default' => false]);
            }
        });
    }
}
