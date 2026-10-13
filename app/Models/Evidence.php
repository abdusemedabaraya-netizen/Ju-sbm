<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    protected $table = 'evidences';

    protected $fillable = [
        'report_id',
        'uploaded_by',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
        'sha256',
        'metadata_stripped',
    ];

    protected $casts = [
        'metadata_stripped' => 'boolean',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
