<?php

namespace TaskApp;

use RuntimeException;

class TaskManager
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        $this->storagePath = $storagePath;
        $storageDir = dirname($storagePath);

        if (!is_dir($storageDir) && !mkdir($storageDir, 0777, true) && !is_dir($storageDir)) {
            throw new RuntimeException('Unable to create the storage directory.');
        }

        if (!is_file($storagePath)) {
            $this->saveTasks([]);
        }
    }

    public function addTask(string $title): array
    {
        $title = trim($title);

        if ($title === '') {
            throw new RuntimeException('Task title cannot be empty.');
        }

        $tasks = $this->loadTasks();
        $nextId = empty($tasks) ? 1 : (max(array_column($tasks, 'id')) + 1);

        $task = [
            'id' => $nextId,
            'title' => $title,
            'completed' => false,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $tasks[] = $task;
        $this->saveTasks($tasks);

        return $task;
    }

    public function listTasks(): array
    {
        return $this->loadTasks();
    }

    public function completeTask(int $id): array
    {
        $tasks = $this->loadTasks();

        foreach ($tasks as &$task) {
            if ($task['id'] === $id) {
                $task['completed'] = true;
                $this->saveTasks($tasks);

                return $task;
            }
        }

        throw new RuntimeException("Task with ID {$id} was not found.");
    }

    public function deleteTask(int $id): void
    {
        $tasks = $this->loadTasks();
        $filteredTasks = array_values(array_filter($tasks, fn (array $task): bool => $task['id'] !== $id));

        if (count($filteredTasks) === count($tasks)) {
            throw new RuntimeException("Task with ID {$id} was not found.");
        }

        $this->saveTasks($filteredTasks);
    }

    private function loadTasks(): array
    {
        $raw = file_get_contents($this->storagePath);

        if ($raw === false) {
            throw new RuntimeException('Unable to read the storage file.');
        }

        $tasks = json_decode($raw, true);

        if (!is_array($tasks)) {
            throw new RuntimeException('The storage file is corrupted.');
        }

        return $tasks;
    }

    private function saveTasks(array $tasks): void
    {
        $encodedTasks = json_encode($tasks, JSON_PRETTY_PRINT);

        if ($encodedTasks === false) {
            throw new RuntimeException('Unable to encode tasks for storage.');
        }

        if (file_put_contents($this->storagePath, $encodedTasks . PHP_EOL) === false) {
            throw new RuntimeException('Unable to write the storage file.');
        }
    }
}
