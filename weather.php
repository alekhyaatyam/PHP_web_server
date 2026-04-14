#!/usr/bin/env php
<?php

use TaskApp\TaskManager;

require_once __DIR__ . '/vendor/autoload.php';

function printUsage(): void
{
    echo "Task Manager CLI\n";
    echo "Usage:\n";
    echo "  php weather.php list\n";
    echo "  php weather.php add \"Task title\"\n";
    echo "  php weather.php complete <id>\n";
    echo "  php weather.php delete <id>\n";
}

try {
    $manager = new TaskManager(__DIR__ . '/storage/tasks.json');
    $command = $argv[1] ?? null;

    if ($command === null) {
        printUsage();
        exit(1);
    }

    switch ($command) {
        case 'list':
            $tasks = $manager->listTasks();

            if ($tasks === []) {
                echo "No tasks yet.\n";
                break;
            }

            foreach ($tasks as $task) {
                $status = $task['completed'] ? '[x]' : '[ ]';
                echo "{$status} {$task['id']}. {$task['title']} ({$task['created_at']})\n";
            }
            break;

        case 'add':
            $title = $argv[2] ?? '';
            $task = $manager->addTask($title);
            echo "Added task #{$task['id']}: {$task['title']}\n";
            break;

        case 'complete':
            $id = isset($argv[2]) ? (int) $argv[2] : 0;

            if ($id <= 0) {
                throw new RuntimeException('Please provide a valid task ID to complete.');
            }

            $task = $manager->completeTask($id);
            echo "Completed task #{$task['id']}: {$task['title']}\n";
            break;

        case 'delete':
            $id = isset($argv[2]) ? (int) $argv[2] : 0;

            if ($id <= 0) {
                throw new RuntimeException('Please provide a valid task ID to delete.');
            }

            $manager->deleteTask($id);
            echo "Deleted task #{$id}\n";
            break;

        default:
            printUsage();
            exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
