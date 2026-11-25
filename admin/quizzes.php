<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/source-files.php';
require_admin_page();

$view = ($_GET['view'] ?? 'files') === 'quizzes' ? 'quizzes' : 'files';
$adminId = (int) $_SESSION['admin_id'];
$sourceFiles = [];
$quizzes = [];
$storageUsed = 0;

if ($view === 'files') {
    $statement = db()->prepare(
        'SELECT id, original_name, extension, size_bytes, created_at
         FROM source_files WHERE admin_id = :admin_id ORDER BY created_at DESC'
    );
    $statement->execute(['admin_id' => $adminId]);
    $sourceFiles = $statement->fetchAll();

    $statement = db()->prepare('SELECT COALESCE(SUM(size_bytes), 0) FROM source_files WHERE admin_id = :admin_id');
    $statement->execute(['admin_id' => $adminId]);
    $storageUsed = (int) $statement->fetchColumn();
} else {
    $statement = db()->prepare(
        'SELECT id, title, description, status, updated_at,
                (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = quizzes.id) AS question_count
         FROM quizzes WHERE admin_id = :admin_id ORDER BY updated_at DESC'
    );
    $statement->execute(['admin_id' => $adminId]);
    $quizzes = $statement->fetchAll();
}

$storagePercent = min(100, (int) round($storageUsed / SOURCE_FILE_QUOTA_SIZE * 100));
$adminUsername = (string) $_SESSION['admin_username'];
$adminInitial = mb_strtoupper(mb_substr($adminUsername, 0, 1));

function format_file_size(int $bytes): string
{
    if ($bytes === 0) {
        return '0 KB';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 0) . ' KB';
    }

    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <title><?= $view === 'files' ? 'Study library' : 'Your quizzes' ?> — BootUp</title>
    <script src="/BootUp/assets/js/theme.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="dashboard host-dashboard">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a class="brand sidebar-brand" href="/BootUp/admin/quizzes.php">BOOTUP<span>.</span></a>
            <p class="sidebar-caption">HOST WORKSPACE</p>
            <nav class="admin-nav" aria-label="Host navigation">
                <a class="admin-nav-link <?= $view === 'files' ? 'is-active' : '' ?>" href="/BootUp/admin/quizzes.php" <?= $view === 'files' ? 'aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true">▦</span><span>Dashboard</span>
                </a>
                <a class="admin-nav-link <?= $view === 'quizzes' ? 'is-active' : '' ?>" href="/BootUp/admin/quizzes.php?view=quizzes" <?= $view === 'quizzes' ? 'aria-current="page"' : '' ?>>
                    <span class="nav-icon" aria-hidden="true">▤</span><span>My quizzes</span>
                </a>
                <a class="admin-nav-link" href="/BootUp/admin/quiz-edit.php">
                    <span class="nav-icon nav-icon-plus" aria-hidden="true">+</span><span>Create quiz</span>
                </a>
            </nav>
            <div class="sidebar-bottom">
                <div class="sidebar-account">
                    <span class="account-avatar" aria-hidden="true"><?= h($adminInitial) ?></span>
                    <span class="account-name"><?= h($adminUsername) ?><small>Host account</small></span>
                </div>
                <form method="post" action="/BootUp/admin/logout.php">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="sidebar-signout" type="submit"><span aria-hidden="true">↗</span> Sign out</button>
                </form>
            </div>
        </aside>

        <main class="dashboard-main container-fluid">
            <header class="dashboard-heading">
                <div>
                    <p class="eyebrow"><?= $view === 'files' ? 'YOUR MATERIALS' : 'HOST CONSOLE' ?></p>
                    <h1><?= $view === 'files' ? 'Study library' : 'Your quizzes' ?></h1>
                    <p class="dashboard-subtitle"><?= $view === 'files'
                        ? 'Keep your course files here and turn them into editable quiz drafts.'
                        : 'Create, edit, and host quizzes for your next game.' ?></p>
                </div>
                <div class="dashboard-heading-actions d-flex align-items-center">
                    <?php if ($view === 'files'): ?>
                        <input class="visually-hidden" id="source-upload" type="file" accept=".pdf,.pptx,.docx,.txt,.md,.csv" <?= $storageUsed >= SOURCE_FILE_QUOTA_SIZE ? 'disabled' : '' ?>>
                        <label class="button button-primary import-file-button" for="source-upload">
                            <span aria-hidden="true">+</span> Import file
                        </label>
                    <?php else: ?>
                        <a class="button button-primary" href="/BootUp/admin/quiz-edit.php"><span aria-hidden="true">+</span> Create quiz</a>
                    <?php endif; ?>
                </div>
            </header>

            <div id="dashboard-message" class="notice" role="status" aria-live="polite" hidden></div>

            <?php if ($view === 'files'): ?>
                <section class="storage-summary" aria-label="Study file storage">
                    <div class="storage-summary-copy">
                        <span class="storage-icon" aria-hidden="true">▧</span>
                        <div><strong><?= count($sourceFiles) ?> <?= count($sourceFiles) === 1 ? 'file' : 'files' ?> in your library</strong><span>Originals are stored privately for this host account.</span></div>
                    </div>
                    <div class="storage-usage">
                        <div class="storage-usage-label"><span>Storage</span><span><?= h(format_file_size($storageUsed)) ?> / 100 MB</span></div>
                        <div class="storage-meter" role="progressbar" aria-label="Storage used" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $storagePercent ?>"><span style="width: <?= $storagePercent ?>%"></span></div>
                    </div>
                </section>

                <?php if ($sourceFiles): ?>
                    <section class="source-library" aria-labelledby="source-library-title">
                        <div class="library-section-heading">
                            <div><p class="eyebrow">FILE LIBRARY</p><h2 id="source-library-title">Your imported files</h2></div>
                            <span class="library-count"><?= count($sourceFiles) ?> TOTAL</span>
                        </div>
                        <div class="source-grid">
                            <?php foreach ($sourceFiles as $sourceFile): ?>
                                <?php $extension = strtoupper((string) $sourceFile['extension']); ?>
                                <article class="source-card">
                                    <div class="source-card-top">
                                        <span class="source-file-icon source-file-icon-<?= h(strtolower($extension)) ?>" aria-hidden="true">
                                            <span><?= h($extension === 'PPTX' ? 'PPT' : $extension) ?></span>
                                        </span>
                                        <details class="file-menu">
                                            <summary aria-label="Actions for <?= h((string) $sourceFile['original_name']) ?>"><span aria-hidden="true">⋯</span></summary>
                                            <div class="file-menu-panel">
                                                <a href="/BootUp/admin/quiz-edit.php?source_file_id=<?= (int) $sourceFile['id'] ?>">Create quiz</a>
                                                <a href="/BootUp/api/source-file.php?id=<?= (int) $sourceFile['id'] ?>">Download original</a>
                                                <button class="delete-source-file" type="button" data-file-id="<?= (int) $sourceFile['id'] ?>" data-file-name="<?= h((string) $sourceFile['original_name']) ?>">Delete file</button>
                                            </div>
                                        </details>
                                    </div>
                                    <h3 title="<?= h((string) $sourceFile['original_name']) ?>"><?= h((string) $sourceFile['original_name']) ?></h3>
                                    <div class="source-card-meta">
                                        <span><?= h(format_file_size((int) $sourceFile['size_bytes'])) ?></span>
                                        <span aria-hidden="true">·</span>
                                        <span>Added <?= h(date('M j, Y', strtotime((string) $sourceFile['created_at']))) ?></span>
                                    </div>
                                    <a class="source-create-link" href="/BootUp/admin/quiz-edit.php?source_file_id=<?= (int) $sourceFile['id'] ?>">Create quiz from file <span aria-hidden="true">→</span></a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="source-empty">
                        <div class="source-empty-content row align-items-center g-4">
                            <div class="col-12 col-md-5">
                                <div class="empty-world" aria-hidden="true">
                                    <span class="empty-world-sun"></span>
                                    <span class="empty-world-cloud empty-world-cloud-one"></span>
                                    <span class="empty-world-cloud empty-world-cloud-two"></span>
                                    <div class="empty-file-float empty-file-pdf"><span>PDF</span><i></i></div>
                                    <div class="empty-file-float empty-file-slides"><span>PPT</span><i></i></div>
                                    <img src="/BootUp/assets/images/char-cutout.png" alt="" width="395" height="592">
                                    <span class="empty-world-ground"></span>
                                    <span class="empty-world-status">READY FOR FILES</span>
                                </div>
                            </div>
                            <div class="source-empty-copy col-12 col-md-7">
                                <p class="eyebrow">YOUR LIBRARY IS READY</p>
                                <h2>Bring your study files in</h2>
                                <p>Turn your notes, slides, and PDFs into editable quiz drafts. Your originals stay private in your library.</p>
                                <label class="button button-primary" for="source-upload"><span aria-hidden="true">+</span> Choose a file</label>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
                <p class="library-footnote">PDF, PPTX, DOCX, TXT, Markdown, or CSV · 20 MB per file · up to 100 MB per host. Files stay private and are never served as public links.</p>
            <?php else: ?>
                <?php if (!$quizzes): ?>
                    <section class="empty-state dashboard-empty-state">
                        <div class="empty-icon" aria-hidden="true">✦</div>
                        <h2>Your first quiz starts here</h2>
                        <p class="muted">Build one from scratch or start with an imported study file.</p>
                        <a class="button button-primary" href="/BootUp/admin/quiz-edit.php">Create your first quiz</a>
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
                                    <span><?= (int) $quiz['question_count'] ?> questions · <?= h(date('M j, Y', strtotime((string) $quiz['updated_at']))) ?></span>
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
            <?php endif; ?>
            <div id="host-game-message" class="notice notice-error host-game-message" role="status" hidden></div>
        </main>
    </div>
    <script defer src="/BootUp/assets/js/admin-dashboard.js"></script>
</body>
</html>
