<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\UpdateProjectFileStateJob;
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
     * Отображение списка ресурсов (результатов тестов).
     * @todo Реализовать, если необходимо
     */
    public function index()
    {
        // Возможно, здесь потребуется фильтрация по Test ID
        // return TestResult::where(\'test_id\', $testId)->get();
    }

    /**
     * Сохранение нового ресурса (результата теста).
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
                return response(['errors' => ['metrics' => ['Неверный формат JSON.']]], 422);
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
     * Отображение указанного ресурса (результата теста).
     * @param TestResult $testresult
     * @return TestResult
     */
    public function show(TestResult $testresult): TestResult
    {
        // Можно добавить загрузку связей, если нужно
        // return $testresult->load(\'test\');
        return $testresult;
    }

    /**
     * Обновление указанного ресурса (результата теста) в хранилище.
     * @todo Реализовать, если необходимо
     */
    public function update(Request $request, TestResult $testresult)
    {
        // Логика обновления TestResult
    }

    /**
     * Удаление указанного ресурса (результата теста) из хранилища.
     * @todo Реализовать, если необходимо
     */
    public function destroy(TestResult $testresult)
    {
        // $testresult->delete();
        // return response()->noContent();
    }

    /**
     * Сохранение результатов от плагина TAHAnalyzer.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeFromPlugin(Request $request): JsonResponse
    {
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'projectName' => 'required|string|max:255',
            'totals' => 'required|array',
            // 'totals.errors' => 'required|integer|min:0',
            // 'totals.warnings' => 'required|integer|min:0',
            // 'totals.fixable' => 'required|integer|min:0',
            'files' => 'nullable|array',
            'files.*' => 'sometimes|array', // Используем sometimes, если files может быть пустым или отсутствовать
            'files.*.errors' => 'required_with:files.*|integer|min:0',
            'files.*.warnings' => 'required_with:files.*|integer|min:0',
            // 'files.*.messages' => 'required_with:files.*|array', // Сообщения не обрабатываем здесь напрямую
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $projectName = $validatedData['projectName'];
        $filesData = $validatedData['files'] ?? []; // Убедимся, что $filesData - массив
        $totals = $validatedData['totals']; // Данные totals могут быть нужны для статуса теста

        // Определяем тип теста из URL
        $testType = $request->segment(count($request->segments())); // Последний сегмент URL (sniffer или static_analysis)

        if (!in_array($testType, ['sniffer', 'static_analysis'])) {
            Log::error('Неверный тип теста определен из сегмента URL', ['segment' => $testType]);
            return response()->json(['error' => 'Указан неверный тип анализа в URL.'], 400);
        }

        $test = null; // Инициализируем переменную для теста

        DB::beginTransaction();
        try {
            // 1. Найти или создать проект
            $project = Project::firstOrCreate(
                ['name' => $projectName]
                // ['url' => ''] // Можно добавить значения по умолчанию
            );

            // 2. Создать родительскую запись Test для этого запуска анализа
            $test = Test::create([
                'project_id' => $project->id,
                'name' => 'Анализ (' . ucfirst($testType) . ') от ' . now()->format('d.m.Y H:i'),
                'type' => $testType,
                'status' => 'running', // Начинаем со статуса "running"
            ]);

            $analysisTimestamp = now(); // Единая временная метка для всех файлов этого анализа

            // 3. Отправить задачи в очередь для обработки каждого файла
            foreach ($filesData as $filePath => $fileData) {
                // Проверка наличия ключей перед доступом
                $errorCount = $fileData['errors'] ?? 0;
                $warningCount = $fileData['warnings'] ?? 0;

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

                UpdateProjectFileStateJob::dispatch(
                    $project->id,
                    $filePath,
                    $errorCount,
                    $warningCount,
                    $test->id,
                    $analysisTimestamp
                );
            }

            // 4. Обновить статус родительского теста Test
            // Статус будет зависеть от общего результата анализа (например, из totals)
            // Простая логика: если были ошибки -> failed, иначе -> completed
            $finalStatus = ($totals['errors'] ?? 0) > 0 ? 'failed' : 'completed';
            $test->update(['status' => $finalStatus]);

            DB::commit();

            // Возвращаем созданный Test (без результатов, т.к. они обрабатываются асинхронно)
            // Можно вернуть только ID теста или базовую информацию
            return response()->json($test, 201); // Или 202 Accepted, т.к. обработка не завершена

        } catch (\Throwable $e) {
            DB::rollBack();

            // Если тест был создан до ошибки, попробуем обновить его статус на 'error'
            if ($test) {
                try {
                    $test->update(['status' => 'error']);
                } catch (\Throwable $updateException) {
                    Log::error('Не удалось обновить статус теста на error после основной ошибки', ['test_id' => $test->id, 'exception' => $updateException]);
                }
            }

            Log::error("Ошибка при обработке результатов плагина: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->except('files') // Логируем без potentially large files array
            ]);
            return response()->json(['error' => 'Произошла внутренняя ошибка сервера при обработке результатов.', 'details' => $e->getMessage()], 500);
        }
    }
}
