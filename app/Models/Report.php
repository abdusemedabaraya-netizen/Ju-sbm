<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'tracking_number',
        'access_code_hash',
        'user_id',
        'department_id',
        'subject',
        'description',
        'incident_date',
        'location',
        'category',
        'priority',
        'status',
        'anonymous',
    ];

    protected $hidden = [
        'access_code_hash',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'anonymous' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function evidences()
    {
        return $this->hasMany(Evidence::class);
    }

    public function investigation()
    {
        return $this->hasOne(Investigation::class);
    }

    public function comments()
    {
        return $this->hasMany(CaseComment::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(ReportStatusHistory::class);
    }
}