<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['slug', 'name', 'default_priority', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Automatic priority for a new report (business rules: bribery = high, fraud = critical ...). */
    public static function priorityFor(string $slug): string
    {
        return static::where('slug', $slug)->value('default_priority') ?? 'medium';
    }
}
