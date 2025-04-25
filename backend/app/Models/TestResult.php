<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestResult extends Model
{
    use HasFactory;

    /**
     * Имя таблицы, связанной с моделью.
     *
     * @var string
     */
    protected $table = 'test_results';

    /**
     * Атрибуты, которые должны быть преобразованы к нативным типам.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metrics' => 'array',
    ];

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'test_id',
        'status',
        'output',
        'report_path',
        'metrics',
    ];

    /**
     * Получить тест, к которому относится результат.
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
