<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Test extends Model
{
    /** @use HasFactory<\Database\Factories\TestFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'project_id', 'description', 'status', 'execution_time', 'type'
    ];

    /**
     * Получить проект, которому принадлежит тест.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Получить результаты теста.
     */
    public function results(): HasMany
    {
        return $this->hasMany(TestResult::class);
    }
}
