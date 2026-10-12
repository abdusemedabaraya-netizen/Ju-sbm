<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    // avatar is NOT fillable: it is only ever set by the profile photo controller.
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'job_title',
        'department_id',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The single source of truth for the avatar URL used by every avatar
     * component (navbar, dropdown, profile page). The version query string
     * busts the cache whenever the profile is updated.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->avatar) {
                return null;
            }

            return route('profile.photo', $this) . '?v=' . ($this->updated_at?->timestamp ?? now()->timestamp);
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Reports submitted by user
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // Investigations assigned
    public function investigations()
    {
        return $this->hasMany(Investigation::class, 'investigator_id');
    }

    // Comments
    public function comments()
    {
        return $this->hasMany(CaseComment::class);
    }

    // Audit Logs
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Note: notifications() comes from the Notifiable trait. Do not redefine it.
}
