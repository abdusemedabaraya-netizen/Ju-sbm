<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CaseComment extends Model
{
    protected $fillable = [
        'report_id',
        'user_id',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function report()
    {
        return $this->belongsTo(
            Report::class
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    /** Internal notes are never shown to the reporter. */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }
}
