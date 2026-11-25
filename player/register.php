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
    <script src="/BootUp/assets/js/theme.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-card-top">
            <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
            <a class="auth-nav-link" href="/BootUp/" aria-label="Return to homepage">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"></path>
                </svg>
                <span>Home</span>
            </a>
        </div>
        <p class="eyebrow">PIN <?= h($game['game_code']) ?> · YOU’RE IN</p>
        <h1>Pick your player name.</h1>
        <p class="muted">Joining <strong><?= h($game['title']) ?></strong>. Your name will be shown in the game lobby.</p>
        <?php if ($error !== ''): ?><div class="notice notice-error"><?= h($error) ?></div><?php endif; ?>
        <form method="post" action="/BootUp/api/register-player.php" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <label>Player name
                <input class="form-control" name="player_name" required maxlength="24" autocomplete="nickname" value="<?= h((string) ($_SESSION['player_name'] ?? '')) ?>" placeholder="What should we call you?">
            </label>
            <button class="button button-primary button-full" type="submit">Join the lobby <span aria-hidden="true">→</span></button>
        </form>
        <p class="auth-footnote"><a href="/BootUp/">← Use a different PIN</a></p>
    </main>
</body>
</html>
