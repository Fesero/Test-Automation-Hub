<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\ProjectFileStateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Роуты для Проектов
Route::apiResource('projects', ProjectController::class);
// Дополнительный роут для регенерации токена
Route::post('projects/{project}/regenerate-token', [ProjectController::class, 'regenerateToken'])->name('projects.regenerateToken');

Route::prefix('tests')->group(function () {
    Route::get('/', [TestController::class, 'index']);
    Route::post('/', [TestController::class, 'store']);
    Route::get('/{test}', [TestController::class, 'show']);
    Route::put('/{test}', [TestController::class, 'update']);
    Route::delete('/{test}', [TestController::class, 'destroy']);

    // Роут для сохранения результатов теста
    Route::post('/{test}/results', [TestResultController::class, 'store'])->name('tests.results.store');

    // Роуты для приема результатов от TAHAnalyzer плагина (возвращаем на TestResultController)
    Route::post('/sniffer', [TestResultController::class, 'storeFromPlugin'])
        // ->middleware('auth:sanctum') // Auth still removed for now
        ->name('results.store.sniffer');
    Route::post('/static_analysis', [TestResultController::class, 'storeFromPlugin'])
        // ->middleware('auth:sanctum') // Auth still removed for now
        ->name('results.store.phpstan');
});

// Роуты для статусов файлов проекта
Route::prefix('projects/{projectId}/file-states')->group(function () {
    Route::get('/', [ProjectFileStateController::class, 'index'])->name('project-file-states.index');
    Route::get('/{id}', [ProjectFileStateController::class, 'show'])->name('project-file-states.show');
});
