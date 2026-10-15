<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Investigation extends Model
{
    protected $fillable = [
        'report_id',
        'investigator_id',
        'findings',
        'recommendation',
        'recommendations',
        'investigation_date',
        'status',
    ];

    protected $casts = [
        'investigation_date' => 'date',
    ];

    // Accessor: "recommendations" (plural) reads the singular column.
    public function getRecommendationsAttribute($value)
    {
        return $value ?? $this->recommendation;
    }

    // Mutator: the table only has a "recommendation" column. Writing the
    // phantom "recommendations" attribute here caused "Unknown column"
    // errors on save.
    public function setRecommendationsAttribute($value)
    {
        $this->attributes['recommendation'] = $value;
    }

    public function report()
    {
        return $this->belongsTo(
            Report::class
        );
    }

    public function investigator()
    {
        return $this->belongsTo(
            User::class,
            'investigator_id'
        );
    }

    /**
     * BR-08: admins see every case, an investigator only their own. Every
     * role-scoped case query goes through this scope.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('investigator_id', $user->id);
    }
}
