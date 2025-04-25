<?php
declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use App\Models\Test;
use App\Models\TestResult;
use App\Jobs\UpdateProjectFileStateJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessPluginResultsAction
{
    /**
     * Summary of run
     * @param string $projectName
     * @param string $typeTest
     * @param array $filesData
     * @param array $totals
     * @return mixed
     */
    public function run(string $projectName, string $typeTest, array $filesData, array $totals): mixed
    {
        return DB::transaction(function () use ($projectName, $typeTest, $filesData, $totals): Test 
        {
            $project = $this->createProject(projectName: $projectName);

            $test = $this->createTest(projectID: $project->id, typeTest: $typeTest);

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

                $output = !empty($messages) ? json_encode($messages) : null;

                $metrics = json_encode([
                    'file_path' => $filePath,
                    'errors' => $errorCount,
                    'warnings' => $warningCount,
                ]);

                $resultData = [
                    'test_id' => $test->id,
                    'status' => $fileStatus,
                    'output' => $output,
                    'metrics' => $metrics,
                ];

                Log::info('Attempting to create TestResult:', ['path' => $filePath]);
                try {
                    $this->createTestResult($resultData);
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

            if ($errorOccurredInLoop) {
                $finalStatus = 'error';
            } elseif (($totals['errors'] ?? 0) > 0) {
                $finalStatus = 'failed';
            } else {
                $finalStatus = 'completed';
            }

            $test->update(['status' => $finalStatus]);

            return $test;
        });
    }

    private function createProject(string $projectName): Project
    {
        return Project::firstOrCreate(
            ['name' => $projectName]
        );
    }

    private function createTest(int $projectID, string $typeTest): Test
    {
        return Test::create([
            'project_id' => $projectID,
            'name' => 'Анализ (' . ucfirst($typeTest) . ') от ' . now()->format('d.m.Y H:i'),
            'type' => $typeTest,
            'status' => 'running',
        ]);
    }

    private function createTestResult(array $data): TestResult
    {
        return TestResult::create($data);
    }
}