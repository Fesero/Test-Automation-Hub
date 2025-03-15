<?php

use App\Http\Controllers\Api\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('tests')->group(function () {
    Route::get('/', [TestController::class, 'index']);
    Route::post('/', [TestController::class, 'store']);
    Route::get('/{test}', [TestController::class, 'show']);
    Route::put('/{test}', [TestController::class, 'update']);
    Route::delete('/{test}', [TestController::class, 'destroy']);

    // Для сниффера
    Route::post('/sniffer', [TestController::class, 'storeSnifferResults']);

    // Для PHPStan
    Route::post('/phpstan', [TestController::class, 'storePHPStanResults']);
});
