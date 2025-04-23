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
        Schema::create('project_file_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('file_path');
            $table->string('status')->default('ok'); // 'ok', 'error', 'warning'
            $table->integer('error_count')->default(0);
            $table->integer('warning_count')->default(0);
            $table->unsignedBigInteger('last_test_id')->nullable();
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('last_test_id')->references('id')->on('tests')->onDelete('set null');

            // Unique index
            $table->unique(['project_id', 'file_path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_file_states');
    }
};
