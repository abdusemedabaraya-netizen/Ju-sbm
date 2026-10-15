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

    /** Priorities that require extra scrutiny. */
    public const HIGH_RISK_PRIORITIES = ['high', 'critical'];

    /** A report is "still open" unless it is resolved, closed or rejected. */
    public const CLOSED_STATUSES = ['resolved', 'closed', 'rejected'];

    /** Statuses a case occupies while it is actively being worked on. */
    public const ACTIVE_STATUSES = ['assigned', 'investigating'];

    /** Statuses that represent a finished case. */
    public const DONE_STATUSES = ['resolved', 'closed'];

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
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('investigation', fn (Builder $q) => $q->where('investigator_id', $user->id));
    }

    /** Reports that are not yet resolved, closed or rejected. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::CLOSED_STATUSES);
    }

    /** Reports carrying a high or critical priority. */
    public function scopeHighRisk(Builder $query): Builder
    {
        return $query->whereIn('priority', self::HIGH_RISK_PRIORITIES);
    }

    /** Under-review reports that have not been assigned to anyone yet. */
    public function scopeUnassignedUnderReview(Builder $query): Builder
    {
        return $query->where('status', 'under_review')->whereDoesntHave('investigation');
    }

    /** Human-readable status, e.g. "under_review" -> "Under Review". */
    public function statusLabel(): string
    {
        return static::label($this->status);
    }

    /** Human-readable priority, e.g. "low" -> "Low". */
    public function priorityLabel(): string
    {
        return static::label($this->priority);
    }

    /** Human-readable category, e.g. "sexual_harassment" -> "Sexual Harassment". */
    public function categoryLabel(): string
    {
        return static::label($this->category);
    }

    /** Turn a snake_case token into a title-cased label. */
    public static function label(?string $value): string
    {
        return ucwords(str_replace('_', ' ', (string) $value));
    }
}