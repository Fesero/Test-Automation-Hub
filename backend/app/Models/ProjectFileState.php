<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFileState extends Model
{
    protected $fillable = [
        'project_id',
        'file_path',
        'status',
        'error_count',
        'warning_count',
        'last_test_id',
        'last_analyzed_at'
    ];

    protected $casts = [
        'last_analyzed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lastTest(): BelongsTo
    {
        return $this->belongsTo(Test::class, 'last_test_id');
    }
}
