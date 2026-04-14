<?php

declare(strict_types=1);

use TaskApp\TaskManager;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$taskManager = new TaskManager(dirname(__DIR__) . '/storage/tasks.json');
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'add':
                $taskManager->addTask((string) ($_POST['title'] ?? ''));
                break;

            case 'complete':
                $taskManager->completeTask((int) ($_POST['id'] ?? 0));
                break;

            case 'delete':
                $taskManager->deleteTask((int) ($_POST['id'] ?? 0));
                break;

            default:
                throw new RuntimeException('Unknown action.');
        }

        header('Location: /');
        exit;
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}

$tasks = $taskManager->listTasks();
$pendingCount = count(array_filter($tasks, static fn (array $task): bool => !$task['completed']));
$completedCount = count($tasks) - $pendingCount;

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <style>
        :root {
            --bg: linear-gradient(135deg, #f4efe6 0%, #d7e6f2 100%);
            --panel: rgba(255, 252, 247, 0.9);
            --panel-strong: #fffaf2;
            --ink: #1b1b1b;
            --muted: #5c5b57;
            --accent: #0e7490;
            --accent-dark: #155e75;
            --success: #2f855a;
            --danger: #c2410c;
            --border: rgba(27, 27, 27, 0.08);
            --shadow: 0 18px 40px rgba(24, 39, 75, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--ink);
            background: var(--bg);
            display: flex;
            justify-content: center;
            padding: 32px 16px;
        }

        .shell {
            width: min(920px, 100%);
        }

        .hero {
            background: var(--panel);
            backdrop-filter: blur(8px);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .hero-top {
            padding: 32px 32px 20px;
            background:
                radial-gradient(circle at top left, rgba(14, 116, 144, 0.18), transparent 34%),
                radial-gradient(circle at top right, rgba(194, 65, 12, 0.16), transparent 28%);
        }

        h1 {
            margin: 0 0 8px;
            font-size: clamp(2.2rem, 5vw, 4rem);
            line-height: 0.95;
            letter-spacing: -0.04em;
        }

        .tagline {
            margin: 0;
            max-width: 560px;
            color: var(--muted);
            font-size: 1.05rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 0 32px 32px;
        }

        .stat {
            background: var(--panel-strong);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 18px;
        }

        .stat-label {
            display: block;
            color: var(--muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .stat-value {
            display: block;
            margin-top: 8px;
            font-size: 2rem;
        }

        .content {
            display: grid;
            grid-template-columns: 1.1fr 1.4fr;
            gap: 24px;
            padding: 0 32px 32px;
        }

        .panel {
            background: var(--panel-strong);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 24px;
        }

        .panel h2 {
            margin: 0 0 16px;
            font-size: 1.3rem;
        }

        form {
            margin: 0;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: var(--muted);
        }

        input[type="text"] {
            width: 100%;
            border: 1px solid rgba(27, 27, 27, 0.14);
            border-radius: 16px;
            padding: 14px 16px;
            font: inherit;
            background: #fff;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 14px;
        }

        button {
            border: 0;
            border-radius: 999px;
            padding: 11px 16px;
            font: inherit;
            cursor: pointer;
            transition: transform 0.16s ease, opacity 0.16s ease;
        }

        button:hover {
            transform: translateY(-1px);
        }

        .primary {
            background: var(--accent);
            color: #fff;
        }

        .secondary {
            background: rgba(14, 116, 144, 0.1);
            color: var(--accent-dark);
        }

        .danger {
            background: rgba(194, 65, 12, 0.12);
            color: var(--danger);
        }

        .task-list {
            display: grid;
            gap: 14px;
        }

        .task {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: flex-start;
            padding: 18px;
            border-radius: 18px;
            border: 1px solid var(--border);
            background: #fff;
        }

        .task.done {
            background: #f4fbf7;
        }

        .task-title {
            margin: 0;
            font-size: 1.05rem;
        }

        .task.done .task-title {
            text-decoration: line-through;
            color: var(--muted);
        }

        .task-meta {
            margin-top: 6px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .task-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .empty {
            margin: 0;
            color: var(--muted);
        }

        .error {
            margin: 0 32px 24px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(194, 65, 12, 0.22);
            background: rgba(194, 65, 12, 0.1);
            color: #7c2d12;
        }

        @media (max-width: 760px) {
            .stats,
            .content {
                grid-template-columns: 1fr;
            }

            .hero-top,
            .stats,
            .content {
                padding-left: 20px;
                padding-right: 20px;
            }

            .task {
                flex-direction: column;
            }

            .task-buttons {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <section class="hero">
            <div class="hero-top">
                <h1>Task Manager</h1>
                <p class="tagline">A lightweight PHP planner for keeping today's work visible, manageable, and moving.</p>
            </div>

            <div class="stats">
                <article class="stat">
                    <span class="stat-label">All Tasks</span>
                    <span class="stat-value"><?= count($tasks) ?></span>
                </article>
                <article class="stat">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value"><?= $pendingCount ?></span>
                </article>
                <article class="stat">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value"><?= $completedCount ?></span>
                </article>
            </div>

            <?php if ($errorMessage !== null): ?>
                <p class="error"><?= escape($errorMessage) ?></p>
            <?php endif; ?>

            <div class="content">
                <section class="panel">
                    <h2>Add a Task</h2>
                    <form method="post">
                        <input type="hidden" name="action" value="add">
                        <label for="title">What needs to get done?</label>
                        <input id="title" name="title" type="text" placeholder="Write project summary" required>
                        <div class="actions">
                            <button class="primary" type="submit">Add Task</button>
                        </div>
                    </form>
                </section>

                <section class="panel">
                    <h2>Your Tasks</h2>

                    <?php if ($tasks === []): ?>
                        <p class="empty">No tasks yet. Add your first one from the left panel.</p>
                    <?php else: ?>
                        <div class="task-list">
                            <?php foreach ($tasks as $task): ?>
                                <article class="task <?= $task['completed'] ? 'done' : '' ?>">
                                    <div>
                                        <p class="task-title"><?= escape($task['title']) ?></p>
                                        <div class="task-meta">
                                            Task #<?= (int) $task['id'] ?> created at <?= escape($task['created_at']) ?>
                                        </div>
                                    </div>

                                    <div class="task-buttons">
                                        <?php if (!$task['completed']): ?>
                                            <form method="post">
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                                <button class="secondary" type="submit">Mark Complete</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="post">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
                                            <button class="danger" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
