<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->enum('type', [
                'unit',         // Юнит-тесты (PHPUnit)
                'integration',  // Интеграционные тесты
                'e2e',          // E2E-тесты (Cypress)
                'load',         // Нагрузочные тесты (JMeter)
                'static_analysis', // Статический анализ (PHPStan/Psalm)
                'security',     // Проверка безопасности (RIPS)
                'sniffer',      // Сниффер кода (PHP_CodeSniffer)
                'js_unit',      // JS-тесты (Jest)
                'performance',  // Тесты производительности
                'acceptance',   // Приемочные тесты
                'mutation'      // Мутационное тестирование (Infection)
            ])->default('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
