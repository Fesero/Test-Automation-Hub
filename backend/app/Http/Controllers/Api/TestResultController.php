<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TestResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Test $test
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Test $test)
    {
        $validator = Validator::make($request->all(), [
            // Определяем возможные статусы для результатов
            'status' => ['required', 'string', Rule::in(['passed', 'failed', 'error', 'skipped', 'pending', 'running'])],
            'output' => 'nullable|string',
            'report_path' => 'nullable|string|max:1024',
            'metrics' => 'nullable|json', // Принимаем JSON строку или массив
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Если metrics пришел как JSON строка, декодируем его
        // Если как массив, оставляем как есть
        // (Зависит от того, как клиент будет отправлять)
        if (isset($validatedData['metrics']) && is_string($validatedData['metrics'])) {
            $decodedMetrics = json_decode($validatedData['metrics'], true);
            // Проверяем на ошибку декодирования
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response(['errors' => ['metrics' => ['Invalid JSON format.']]], 422);
            }
            $validatedData['metrics'] = $decodedMetrics;
        }

        // Создаем результат, связанный с тестом
        $testResult = $test->results()->create($validatedData);

        // Опционально: Обновляем статус родительского теста
        // Здесь можно добавить логику: если status != 'running'/'pending', то тест завершен
        $finalTestStatus = match ($validatedData['status']) {
            'passed' => 'completed',
            'failed', 'error' => 'failed',
            default => $test->status, // Оставляем текущий статус теста для pending/running/skipped
        };
        if ($test->status !== $finalTestStatus) {
            $test->update(['status' => $finalTestStatus]);
        }

        return response($testResult, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TestResult $testresult)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TestResult $testresult)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TestResult $testresult)
    {
        //
    }
}
