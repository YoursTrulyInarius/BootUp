<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_admin_page();

$quiz = [
    'id' => null,
    'title' => '',
    'description' => '',
    'status' => 'draft',
    'visibility' => 'private',
    'default_timer' => 30,
    'base_points' => 1000,
    'randomize_questions' => 0,
    'randomize_answers' => 0,
    'show_leaderboard' => 1,
];
$questions = [];
$quizId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($quizId) {
    $statement = db()->prepare('SELECT * FROM quizzes WHERE id = :id AND admin_id = :admin_id');
    $statement->execute(['id' => $quizId, 'admin_id' => $_SESSION['admin_id']]);
    $existingQuiz = $statement->fetch();
    if (!$existingQuiz) {
        http_response_code(404);
        exit('Quiz not found.');
    }
    $quiz = $existingQuiz;
    $statement = db()->prepare('SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY position, id');
    $statement->execute(['quiz_id' => $quizId]);
    foreach ($statement->fetchAll() as $row) {
        $questions[] = [
            'question_text' => $row['question_text'],
            'question_type' => $row['question_type'],
            'options' => $row['options_json'] ? json_decode($row['options_json'], true) : [],
            'accepted_answers' => $row['accepted_answers_json'] ? json_decode($row['accepted_answers_json'], true) : [],
            'answer' => $row['answer_key'],
            'case_sensitive' => (bool) $row['case_sensitive'],
            'explanation' => $row['explanation'],
            'timer_seconds' => $row['timer_seconds'],
            'points' => (int) $row['points'],
        ];
    }
}

$initialData = json_encode(
    ['quiz' => $quiz, 'questions' => $questions],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
    <title><?= $quiz['id'] ? 'Edit quiz' : 'Create quiz' ?> — BootUp</title>
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
    <script defer src="/BootUp/assets/js/quiz-editor.js"></script>
</head>
<body class="dashboard">
    <header class="site-header dashboard-header">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <div class="header-actions">
            <a class="button button-quiet" href="/BootUp/admin/quizzes.php">← All quizzes</a>
            <form method="post" action="/BootUp/admin/logout.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <button class="button button-quiet" type="submit">Sign out</button>
            </form>
        </div>
    </header>
    <main class="content editor-content">
        <div class="editor-heading">
            <div>
                <p class="eyebrow">QUIZ BUILDER</p>
                <h1><?= $quiz['id'] ? 'Make it your quiz.' : 'Create your quiz.' ?></h1>
                <p class="muted">Mix question styles and tune the rules to fit your game.</p>
            </div>
            <div class="save-actions">
                <?php if ($quiz['id']): ?>
                    <button class="button button-danger-outline" id="delete-quiz" type="button">Delete quiz</button>
                <?php endif; ?>
                <button class="button button-primary" id="save-quiz" type="button">Save quiz</button>
            </div>
        </div>
        <div id="editor-message" class="notice" hidden role="status"></div>
        <section class="editor-panel">
            <div class="section-title"><span class="step-number">01</span><div><h2>Quiz details</h2><p>Give your quiz its own identity.</p></div></div>
            <div class="form-grid">
                <label class="field-wide">Quiz title
                    <input id="quiz-title" maxlength="120" placeholder="e.g. The Ultimate Space Quiz" value="<?= h((string) $quiz['title']) ?>" required>
                </label>
                <label class="field-wide">Description <span class="optional">Optional</span>
                    <textarea id="quiz-description" rows="3" maxlength="2000" placeholder="What will players be quizzed on?"><?= h((string) $quiz['description']) ?></textarea>
                </label>
                <label>Visibility
                    <select id="quiz-visibility">
                        <option value="private" <?= $quiz['visibility'] === 'private' ? 'selected' : '' ?>>Private</option>
                        <option value="public" <?= $quiz['visibility'] === 'public' ? 'selected' : '' ?>>Public</option>
                    </select>
                </label>
                <label>Status
                    <select id="quiz-status">
                        <option value="draft" <?= $quiz['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $quiz['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    </select>
                </label>
            </div>
        </section>
        <section class="editor-panel">
            <div class="section-title"><span class="step-number">02</span><div><h2>Game settings</h2><p>Set the defaults. You can override timer and points on each question.</p></div></div>
            <div class="form-grid settings-grid">
                <label>Default timer
                    <div class="input-suffix"><input id="quiz-default-timer" type="number" min="0" max="300" step="1" value="<?= (int) $quiz['default_timer'] ?>"><span>seconds · 0 = no timer</span></div>
                </label>
                <label>Base points
                    <div class="input-suffix"><input id="quiz-base-points" type="number" min="0" max="100000" step="50" value="<?= (int) $quiz['base_points'] ?>"><span>points per question</span></div>
                </label>
                <div class="toggle-list">
                    <label class="toggle-row"><input id="randomize-questions" type="checkbox" <?= $quiz['randomize_questions'] ? 'checked' : '' ?>><span>Randomize question order</span></label>
                    <label class="toggle-row"><input id="randomize-answers" type="checkbox" <?= $quiz['randomize_answers'] ? 'checked' : '' ?>><span>Randomize answer order</span></label>
                    <label class="toggle-row"><input id="show-leaderboard" type="checkbox" <?= $quiz['show_leaderboard'] ? 'checked' : '' ?>><span>Show leaderboard between rounds</span></label>
                </div>
            </div>
        </section>
        <section class="questions-section">
            <div class="section-title questions-title"><span class="step-number">03</span><div><h2>Questions</h2><p>Choose the format and set the correct answer for each one.</p></div><span class="question-count" id="question-count">0 questions</span></div>
            <div id="questions-list" class="questions-list"></div>
            <button class="add-question" id="add-question" type="button"><span>+</span> Add a question</button>
        </section>
        <div class="editor-bottom">
            <a class="text-link" href="/BootUp/admin/quizzes.php">Cancel and return to library</a>
            <button class="button button-primary" id="save-quiz-bottom" type="button">Save quiz</button>
        </div>
    </main>
    <script>window.bootUpInitial = <?= $initialData ?>;</script>
</body>
</html>
