<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TestController extends Controller
{
    /**
     * Получение всех тестов
     */
    public function index()
    {
        return Test::all();
    }

    /**
     * Создать новый тест
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,running,completed,failed',
            'type' => 'required|in:crud,sniffer,load',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $test = Test::create($request->all());

        return response($test, 201);
    }

    /**
     * Получить конкретный тест
     */
    public function show(Test $test)
    {
        return $test->load('results');
    }

    /**
     * Обновить тест
     */
    public function update(Request $request, Test $test)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,running,completed,failed',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $test->update($request->all());

        return response($test);
    }

    /**
     * Удалить тест
     */
    public function destroy(Test $test)
    {
        $test->delete();

        return response(['message' => 'Тест удален'], 200);
    }

    /**
     * Сохранение результатов сниффера
     */
    public function storeSnifferResults(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'totals' => 'required|array',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for sniffer results', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);
                return response([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Создаем тест типа "sniffer"
            $test = Test::create([
                'type' => 'sniffer',
                'name' => 'Sniffer Analysis',
                'status' => 'completed',
                'result' => $request->all(),
            ]);

            return response($test, 201);
        } catch (\Exception $e) {
            Log::error('Error storing sniffer results', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response([
                'message' => 'Error storing sniffer results',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Сохранение результатов PHPStan
     */
    public function storePHPStanResults(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'totals' => 'required|array',
            ]);

            if ($validator->fails()) {
                Log::warning('Validation failed for PHPStan results', [
                    'errors' => $validator->errors()->toArray(),
                    'request_data' => $request->all()
                ]);
                return response([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $test = Test::create([
                'type' => 'static_analysis',
                'name' => 'PHPStan Analysis',
                'status' => 'completed',
                'result' => $request->all(),
            ]);

            return response($test, 201);
        } catch (\Exception $e) {
            Log::error('Error storing PHPStan results', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response([
                'message' => 'Error storing PHPStan results',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
