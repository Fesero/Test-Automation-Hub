<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestResult;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

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

    /**
     * Store results from TAHAnalyzer plugin.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFromPlugin(Request $request): JsonResponse
    {
        // Manual Validation
        $validator = Validator::make($request->all(), [
            'projectName' => 'required|string|max:255',
            'totals' => 'required|array',
            /*'totals.errors' => 'required|integer|min:0',
            'totals.warnings' => 'required|integer|min:0',
            'totals.fixable' => 'required|integer|min:0',*/
            'files' => 'nullable|array',
            /*'files.*' => 'required|array',
            'files.*.errors' => 'required|integer|min:0',
            'files.*.warnings' => 'required|integer|min:0',
            'files.*.messages' => 'required|array',
            'files.*.messages.*.message' => 'required|string',
            'files.*.messages.*.source' => 'required|string',
            'files.*.messages.*.severity' => 'required|integer',
            'files.*.messages.*.type' => ['required', 'string', Rule::in(['ERROR', 'WARNING'])],
            'files.*.messages.*.line' => 'required|integer|min:1',
            'files.*.messages.*.column' => 'required|integer|min:1',
            'files.*.messages.*.fixable' => 'required|boolean',*/
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated(); // Get validated data

        // --- Original Logic Start ---
        $projectName = $validatedData['projectName'];
        // Определяем тип теста из URL
        $testType = $request->segment(count($request->segments())); // Последний сегмент URL (sniffer или phpstan)

        if (!in_array($testType, ['sniffer', 'phpstan'])) {
            Log::error('Неверный тип теста определен из сегмента URL', ['segment' => $testType]);
            return response()->json(['error' => 'Указан неверный тип анализа в URL.'], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Найти или создать проект
            $project = Project::firstOrCreate(
                ['name' => $projectName]
                // Можно добавить значения по умолчанию при создании, например, пустой URL
                // ['url' => '']
            );

            // 2. Создать родительскую запись Test для этого запуска анализа
            $test = Test::create([
                'project_id' => $project->id,
                'name' => 'Анализ (' . ucfirst($testType) . ') от ' . now()->format('d.m.Y H:i'), // Русское название
                'type' => $testType,
                'status' => 'running', // Начинаем со статуса "running"
            ]);

            $overallStatus = 'passed'; // Изначально считаем, что все прошло успешно
            $errorOccurred = false; // Флаг для внутренних ошибок обработки

            // 3. Обработать и сохранить результаты для каждого файла
            Log::info('Processing files...', ['file_count' => count($validatedData['files'])]);
            foreach ($validatedData['files'] as $filePath => $fileData) {
                Log::info('Processing file:', ['path' => $filePath]);
                $messages = $fileData['messages'] ?? [];
                $fileStatus = 'passed';

                if (!empty($messages)) {
                    $hasErrors = collect($messages)->contains(fn($msg) => strtoupper($msg['type']) === 'ERROR');
                    if ($hasErrors) {
                        $fileStatus = 'failed';
                        $overallStatus = 'failed';
                    } else {
                        $fileStatus = 'passed';
                    }
                }

                $resultData = [
                    'test_id' => $test->id,
                    'status' => $fileStatus,
                    'output' => !empty($messages) ? json_encode($messages) : null,
                    'metrics' => json_encode([
                        'file_path' => $filePath,
                        'errors' => $fileData['errors'] ?? 0,
                        'warnings' => $fileData['warnings'] ?? 0,
                    ]),
                ];

                Log::info('Attempting to create TestResult:', $resultData);
                try {
                    TestResult::create($resultData);
                    Log::info('TestResult created successfully for:', ['path' => $filePath]);
                } catch (\Throwable $e) {
                    Log::error('Failed to create TestResult for file:', [
                        'path' => $filePath,
                        'error' => $e->getMessage(),
                        'data' => $resultData,
                        'exception' => $e
                    ]);
                    // Mark overall status as error if result creation fails
                    $overallStatus = 'error'; 
                    $errorOccurred = true; // Set flag for internal error
                    // Optionally re-throw or break the loop depending on desired behavior
                    // break; // Stop processing further files on error
                }
            }
            Log::info('Finished processing files.');

            // 4. Обновить статус родительского теста Test
            if ($errorOccurred) { // Если произошла внутренняя ошибка (пока не реализовано)
                 $test->update(['status' => 'error']);
            } else {
                 // Статус 'completed' если все 'passed', иначе 'failed'
                 $test->update(['status' => ($overallStatus === 'failed' ? 'failed' : 'completed')]);
            }

            DB::commit();

            // Возвращаем созданный Test с загруженными результатами
            return response()->json($test->load('results'), 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Ошибка при сохранении результатов плагина: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all() // Логируем входные данные
            ]);
            return response()->json(['error' => 'Произошла внутренняя ошибка сервера при сохранении результатов.', 'details' => $e->getMessage()], 500);
        }
        // --- Original Logic End ---
    }
}
