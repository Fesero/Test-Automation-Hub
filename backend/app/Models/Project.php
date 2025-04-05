<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'url',
        'api_token',
    ];

    /**
     * Получить тесты, связанные с проектом.
     */
    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }
}
