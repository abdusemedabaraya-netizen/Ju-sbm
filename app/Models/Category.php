<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * Build a stable, unique slug. A name written only in Amharic script
     * transliterates to an empty slug, so a random fallback is used, and a
     * non-guessable suffix resolves collisions.
     */
    public static function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name, '_');

        if ($slug === '') {
            $slug = 'category_' . Str::lower(Str::random(6));
        }

        $base = $slug;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '_' . Str::lower(Str::random(4));
        }

        return Str::limit($slug, 50, '');
    }
}
