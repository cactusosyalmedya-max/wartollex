<?php
declare(strict_types=1);

namespace Wartollex\Services;

use DateTimeImmutable;
use PDO;
use RuntimeException;
use Wartollex\Database;

final class TaskService
{
    public function __construct(private PDO $pdo, private string $taskFile)
    {
        if (!is_file($taskFile)) {
            throw new RuntimeException('Task file not found: ' . $taskFile);
        }
    }

    public function tasksForDate(DateTimeImmutable $date): array
    {
        $tasks = $this->loadTasks();
        $dayKey = $date->format('Y-m-d');

        if (isset($tasks[$dayKey])) {
            return $tasks[$dayKey];
        }

        return $tasks['default'] ?? [];
    }

    public function completionStatus(int $userId, DateTimeImmutable $date): array
    {
        $statement = $this->pdo->prepare(
            'SELECT task_id, completed FROM task_progress WHERE user_id = :user_id AND task_date = :task_date'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':task_date' => $date->format('Y-m-d'),
        ]);

        $status = [];
        foreach ($statement->fetchAll() as $row) {
            $status[$row['task_id']] = (bool) $row['completed'];
        }

        return $status;
    }

    public function completeTask(int $userId, string $taskId, DateTimeImmutable $date): bool
    {
        $taskDate = $date->format('Y-m-d');
        $statement = $this->pdo->prepare(
            'SELECT completed FROM task_progress WHERE user_id = :user_id AND task_id = :task_id AND task_date = :task_date'
        );
        $statement->execute([
            ':user_id' => $userId,
            ':task_id' => $taskId,
            ':task_date' => $taskDate,
        ]);

        $existing = $statement->fetch();
        if ($existing !== false && (int) $existing['completed'] === 1) {
            return false;
        }

        $now = Database::now();
        $upsert = $this->pdo->prepare(
            'INSERT INTO task_progress (user_id, task_id, task_date, completed, completed_at)
             VALUES (:user_id, :task_id, :task_date, 1, :completed_at)
             ON CONFLICT(user_id, task_id, task_date) DO UPDATE SET completed = 1, completed_at = :completed_at'
        );
        $upsert->execute([
            ':user_id' => $userId,
            ':task_id' => $taskId,
            ':task_date' => $taskDate,
            ':completed_at' => $now,
        ]);

        return true;
    }

    private function loadTasks(): array
    {
        $contents = file_get_contents($this->taskFile);
        if ($contents === false) {
            return [];
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }
}
