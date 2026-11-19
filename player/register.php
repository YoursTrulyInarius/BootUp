<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

if (!isset($_SESSION['player_game_id'])) {
    header('Location: /BootUp/');
    exit;
}
$statement = db()->prepare(
    "SELECT gs.game_code, q.title FROM game_sessions gs
     JOIN quizzes q ON q.id = gs.quiz_id
     WHERE gs.id = :id AND gs.status = 'lobby'"
);
$statement->execute(['id' => $_SESSION['player_game_id']]);
$game = $statement->fetch();
if (!$game) {
    unset($_SESSION['player_game_id']);
    header('Location: /BootUp/');
    exit;
}
$error = (string) ($_SESSION['player_error'] ?? '');
unset($_SESSION['player_error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Join <?= h($game['title']) ?> — BootUp</title>
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <p class="eyebrow">PIN <?= h($game['game_code']) ?> · YOU’RE IN</p>
        <h1>Pick your player name.</h1>
        <p class="muted">Joining <strong><?= h($game['title']) ?></strong>. Your name will be shown in the game lobby.</p>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" action="/BootUp/api/register-player.php" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Player name
                <input name="player_name" required maxlength="24" autocomplete="nickname" value="<?= h((string) ($_SESSION['player_name'] ?? '')) ?>" placeholder="What should we call you?">
            </label>
            <button class="button button-primary button-full" type="submit">Join the lobby <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-footnote"><a href="/BootUp/">← Use a different PIN</a></p>
    </main>
</body>
</html>
