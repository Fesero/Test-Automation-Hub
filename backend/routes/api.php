<?php
declare(strict_types=1);

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\ProjectFileStateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn(Request $request): mixed => $request->user())->middleware('auth:sanctum');

// Роуты для Проектов
Route::apiResource('projects', ProjectController::class);
// Дополнительный роут для регенерации токена
Route::post('projects/{project}/regenerate-token', [ProjectController::class, 'regenerateToken'])->name('projects.regenerateToken');

Route::prefix('tests')->group(function (): void {
    Route::get('/', [TestController::class, 'index']);
    Route::post('/', [TestController::class, 'store']);
    Route::get('/{test}', [TestController::class, 'show']);
    Route::put('/{test}', [TestController::class, 'update']);
    Route::delete('/{test}', [TestController::class, 'destroy']);

    Route::post('/{testType}/results', [TestResultController::class, 'storeFromPlugin'])
    ->name('results.store_from_plugin');
});

// Роуты для статусов файлов проекта
Route::prefix('projects/{projectId}/file-states')->group(function (): void {
    Route::get('/', [ProjectFileStateController::class, 'index'])->name('project-file-states.index');
    Route::get('/{id}', [ProjectFileStateController::class, 'show'])->name('project-file-states.show');
});
