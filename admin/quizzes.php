<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_admin_page();

$statement = db()->prepare(
    'SELECT id, title, description, status, updated_at
     FROM quizzes WHERE admin_id = :admin_id ORDER BY updated_at DESC'
);
$statement->execute(['admin_id' => $_SESSION['admin_id']]);
$quizzes = $statement->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <title>Your quizzes — BootUp</title>
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="dashboard">
    <header class="site-header dashboard-header">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <div class="header-actions">
            <span class="user-label"><?= h((string) $_SESSION['admin_username']) ?></span>
            <form method="post" action="/BootUp/admin/logout.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="button button-quiet" type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="content">
        <div class="page-heading">
            <div>
                <p class="eyebrow">HOST CONSOLE</p>
                <h1>Your quiz library</h1>
                <p class="muted">Create, edit, and fine-tune the questions and settings for your next game.</p>
            </div>
            <a class="button button-primary" href="/BootUp/admin/quiz-edit.php">+ Create quiz</a>
        </div>
        <?php if (!$quizzes): ?>
            <section class="empty-state">
                <div class="empty-icon" aria-hidden="true">✦</div>
                <h2>Your first quiz starts here</h2>
                <p class="muted">Add questions, choose the answer format, and set timers and points.</p>
                <a class="button button-primary" href="/BootUp/admin/quiz-edit.php">Build your first quiz</a>
            </section>
        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quizzes as $quiz): ?>
                    <article class="quiz-card">
                        <div class="quiz-card-top">
                            <span class="status-pill status-<?= h($quiz['status']) ?>"><?= h(ucfirst($quiz['status'])) ?></span>
                            <span class="card-dots" aria-hidden="true">•••</span>
                        </div>
                        <h2><?= h($quiz['title']) ?></h2>
                        <p class="quiz-description"><?= h($quiz['description'] ?: 'No description added yet.') ?></p>
                        <div class="quiz-card-bottom">
                            <span>Edited <?= h(date('M j, Y', strtotime($quiz['updated_at']))) ?></span>
                            <div class="quiz-card-actions">
                                <?php if ($quiz['status'] === 'published'): ?>
                                    <button class="text-link host-game-button" type="button" data-quiz-id="<?= (int) $quiz['id'] ?>">Host game <span aria-hidden="true">→</span></button>
                                <?php endif; ?>
                                <a class="text-link" href="/BootUp/admin/quiz-edit.php?id=<?= (int) $quiz['id'] ?>">Edit <span aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="feature-note"><strong>Question formats:</strong> Multiple choice · True or false · Fill in the blank</div>
        <div id="host-game-message" class="notice notice-error host-game-message" role="status" hidden></div>
    </main>
    <script defer src="/BootUp/assets/js/admin-dashboard.js"></script>
</body>
</html>
