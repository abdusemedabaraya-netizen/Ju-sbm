<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;

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
    public function evidences()     { return $this->hasMany(Evidence::class); }
    public function statusHistories() { return $this->hasMany(ReportStatusHistory::class)->latest(); }
}