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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name'); // Название теста
            $table->string('type'); // CRUD, sniffer, load, etc...
            $table->text('description')->nullable(); // Описание теста
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending'); // Статус теста
            $table->float('execution_time')->nullable(); // Время выполнения
            $table->json('result')->nullable(); // Результаты выполнения JSON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
