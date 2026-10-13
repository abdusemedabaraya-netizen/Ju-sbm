<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'submitted', 'under_review', 'assigned', 'investigating',
        'resolved', 'closed', 'rejected',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    /** A report is "still open" unless it is resolved, closed or rejected. */
    public const CLOSED_STATUSES = ['resolved', 'closed', 'rejected'];

    protected $fillable = [
        'tracking_number', 'access_code_hash', 'user_id', 'department_id',
        'subject', 'description', 'incident_date', 'location',
        'category', 'priority', 'status', 'anonymous',
    ];

    protected $hidden = ['access_code_hash'];

    protected function casts(): array
    {
        return ['incident_date' => 'date', 'anonymous' => 'boolean'];
    }

    public function department()    { return $this->belongsTo(Department::class); }
    public function user()          { return $this->belongsTo(User::class); }
    public function investigation() { return $this->hasOne(Investigation::class); }
    public function evidences()     { return $this->hasMany(Evidence::class, 'report_id'); }
    public function statusHistories() { return $this->hasMany(ReportStatusHistory::class)->latest(); }
    public function comments()      { return $this->hasMany(CaseComment::class)->latest(); }

    /**
     * BR-08: admins see everything, an investigator only the reports that are
     * assigned to them. Every role-scoped query goes through this scope.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return $query;
        }

        return $query->whereHas('investigation', fn (Builder $q) => $q->where('investigator_id', $user->id));
    }
}