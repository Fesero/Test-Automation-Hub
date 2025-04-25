<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use App\Actions\ProcessPluginResultsAction;
use App\Http\Requests\StorePluginResultRequest;
use Illuminate\Http\Response;

class TestResultController extends Controller
{
    /**
     * Отображение списка ресурсов (результатов тестов).
     * TODO: сделать
     */
    public function index(): void
    {

    }

    /**
     * Сохранение нового ресурса (результата теста).
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Test $test
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Test $test): Response
    {
        $validator = Validator::make($request->all(), [
            // Определяем возможные статусы для результатов
            'status' => ['required', 'string', Rule::in(['passed', 'failed', 'error', 'skipped', 'pending', 'running'])],
            'output' => 'nullable|string',
            'report_path' => 'nullable|string|max:1024',
            'metrics' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        if (isset($validatedData['metrics']) && is_string($validatedData['metrics'])) {
            $decodedMetrics = json_decode($validatedData['metrics'], true);
            // Проверяем на ошибку декодирования
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response(['errors' => ['metrics' => ['Неверный формат JSON.']]], 422);
            }
            $validatedData['metrics'] = $decodedMetrics;
        }

        // Создаем результат, связанный с тестом
        $testResult = $test->results()->create($validatedData);

        $finalTestStatus = match ($validatedData['status']) {
            'passed' => 'completed',
            'failed', 'error' => 'failed',
            default => $test->status,
        };
        if ($test->status !== $finalTestStatus) {
            $test->update(['status' => $finalTestStatus]);
        }

        return response($testResult, 201);
    }

    /**
     * Отображение указанного ресурса (результата теста).
     * @param TestResult $testresult
     * @return TestResult
     */
    public function show(TestResult $testresult): TestResult
    {
        return $testresult;
    }

    /**
     * Обновление указанного ресурса (результата теста) в хранилище.
     * TODO: сделать обновление либо удалить
     */
    public function update(Request $request, TestResult $testresult): void
    {

    }

    /**
     * Удаление указанного ресурса (результата теста) из хранилища.
     * TODO: сделать удаление
     */
    public function destroy(TestResult $testresult): void
    {

    }

    /**
     * Сохранение результатов от плагина TAHAnalyzer.
     *
     * @param StorePluginResultRequest $request
     * @param string $typeTest
     * @param ProcessPluginResultsAction $processPluginResultsAction
     * @return JsonResponse
     */
    public function storeFromPlugin(
        StorePluginResultRequest $request,
        string $typeTest,
        ProcessPluginResultsAction $processPluginResultsAction
        ): JsonResponse
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'projectName' => 'required|string|max:255',
            'totals' => 'required|array',
            'files' => 'nullable|array',
            'files.*' => 'sometimes|array',
            'files.*.errors' => 'required_with:files.*|integer|min:0',
            'files.*.warnings' => 'required_with:files.*|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $projectName = $validatedData['projectName'];
        $filesData = $validatedData['files'] ?? [];
        $totals = $validatedData['totals'];

        if (!in_array($typeTest, ['sniffer', 'static_analysis'])) {
            Log::warning('Неверный тип теста получен из URL', ['type' => $typeTest]);
            return response()->json(['error' => 'Указан неверный тип анализа в URL.'], 400);
        }

        try {
            $test = $processPluginResultsAction->run(
                projectName: $projectName,
                typeTest: $typeTest,
                filesData: $filesData,
                totals: $totals
            );

            return response()->json($test->load('project'), 201);

        } catch (\Throwable $e) {
            Log::error("Ошибка при обработке результатов плагина: " . $e->getMessage(), [
                'exception' => $e,
                'projectName' => $projectName,
                'testType' => $typeTest,
            ]);
            
            return response()->json(['error' => 'Произошла внутренняя ошибка сервера при обработке результатов.'], 500);
        }
    }
}
