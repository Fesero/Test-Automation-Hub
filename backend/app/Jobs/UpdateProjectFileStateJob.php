<?php

namespace App\Jobs;

use App\Models\ProjectFileState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; // Для логирования ошибок
use Throwable;

class UpdateProjectFileStateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Создать новый экземпляр задачи.
     */
    public function __construct(
        public int $projectId,
        public string $filePath,
        public int $errorCount,
        public int $warningCount,
        public ?int $testId, // Может быть null, если сохранение родительского теста не удалось
        public \DateTimeInterface $analyzedAt
    ) {
        // $this->onQueue('обработка-файлов');
    }

    /**
     * Выполнить задачу.
     */
    public function handle(): void
    {
        try {
            // Определяем статус на основе счетчиков
            $status = 'ok';
            if ($this->errorCount > 0) {
                $status = 'error';
            } elseif ($this->warningCount > 0) {
                $status = 'warning';
            }

            ProjectFileState::updateOrCreate(
                [
                    'project_id' => $this->projectId,
                    'file_path' => $this->filePath,
                ],
                [
                    'status' => $status,
                    'error_count' => $this->errorCount,
                    'warning_count' => $this->warningCount,
                    'last_test_id' => $this->testId,
                    'last_analyzed_at' => $this->analyzedAt,
                ]
            );
        } catch (Throwable $e) {
            // Обработка потенциальных ошибок во время update/create
            Log::error("Не удалось обновить состояние файла проекта project {$this->projectId}, file {$this->filePath}", [
                'exception' => $e,
                'test_id' => $this->testId,
            ]);

            // Опционально: решить, должна ли задача быть возвращена в очередь для повторной попытки
            // Например, при взаимной блокировке или временной недоступности БД:
            // if ($this->attempts() < 3) { // Проверить количество попыток
            //     $this->release(60); // Вернуть в очередь с задержкой (например, 60 секунд)
            // } else {
            //     $this->fail($e); // Пометить задачу как окончательно неудавшуюся после достаточного количества попыток
            // }
            // Или просто позволить ей завершиться с ошибкой, если ошибка, скорее всего, постоянная
            $this->fail($e);
        }
    }
}
