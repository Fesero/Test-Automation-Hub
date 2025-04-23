<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\Test;
use App\Models\TestResult;
use App\Jobs\UpdateProjectFileStateJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPluginResultsAction
{
    public function run(string $projectName, string $typeTest, array $filesData, array $totals)
    {
        return DB::transaction(function () use ($projectName, $typeTest, $filesData, $totals) {
            $project = Project::firstOrCreate(
                ['name' => $projectName]
            );

            $test = Test::create([
                'project_id' => $project->id,
                'name' => 'Анализ (' . ucfirst($typeTest) . ') от ' . now()->format('d.m.Y H:i'),
                'type' => $typeTest,
                'status' => 'running',
            ]);

            $analysisTimestamp = now();
            $errorOccurredInLoop = false;

            foreach ($filesData as $filePath => $fileData) {
                $errorCount = $fileData['errors'] ?? 0;
                $warningCount = $fileData['warnings'] ?? 0;

                Log::info('Processing file:', ['path' => $filePath]);
                $messages = $fileData['messages'] ?? [];
                $fileStatus = 'passed';

                if (!empty($messages)) {
                    $hasErrors = collect($messages)->contains(fn($msg) => isset($msg['type']) && strtoupper($msg['type']) === 'ERROR');
                    if ($hasErrors) {
                        $fileStatus = 'failed';
                    }
                }

                $resultData = [
                    'test_id' => $test->id,
                    'status' => $fileStatus,
                    'output' => !empty($messages) ? json_encode($messages) : null,
                    'metrics' => json_encode([
                        'file_path' => $filePath,
                        'errors' => $errorCount,
                        'warnings' => $warningCount,
                    ]),
                ];

                Log::info('Attempting to create TestResult:', ['path' => $filePath]);
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
                    $errorOccurredInLoop = true;
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

            $finalStatus = 'completed';
            if ($errorOccurredInLoop) {
                $finalStatus = 'error';
            } elseif (($totals['errors'] ?? 0) > 0) {
                $finalStatus = 'failed';
            }

            $test->update(['status' => $finalStatus]);

            return $test;
        });
    }
}