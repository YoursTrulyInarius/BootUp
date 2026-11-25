<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

if (!isset($_SESSION['player_game_id'], $_SESSION['player_id'])) {
    header('Location: /BootUp/');
    exit;
}
$statement = db()->prepare(
    "SELECT gs.game_code, gs.status, q.title FROM game_sessions gs
     JOIN quizzes q ON q.id = gs.quiz_id
     WHERE gs.id = :id"
);
$statement->execute(['id' => $_SESSION['player_game_id']]);
$game = $statement->fetch();
if (!$game) {
    unset($_SESSION['player_game_id'], $_SESSION['player_id']);
    header('Location: /BootUp/');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Waiting for the host — BootUp</title>
    <script src="/BootUp/assets/js/theme.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-yEPZ7jP9S0QKiUQ0m3jjsO4ProIW7wvR9gXkI7JOGa2XVsw53Yke2bsdkBbjmmiX" crossorigin="anonymous">
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="join-page">
    <header class="site-header join-header">
        <a class="brand" href="/BootUp/">BOOTUP<span>.</span></a>
        <a class="host-link" href="/BootUp/">Leave game</a>
    </header>
    <main class="join-main">
        <div class="join-mark waiting-mark" aria-hidden="true">✦</div>
        <p class="eyebrow">YOU’RE IN · PIN <?= h($game['game_code']) ?></p>
        <h1>Nice to meet you,<br><span><?= h((string) $_SESSION['player_name']) ?>.</span></h1>
        <p class="join-copy">You joined <strong><?= h($game['title']) ?></strong>. Keep this page open while you wait for your host.</p>
        <div class="join-footer"><span class="live-dot"></span> <?= $game['status'] === 'lobby' ? 'Waiting for the host to start' : 'Your host has closed this lobby' ?></div>
        <p class="lobby-note">The player lobby is connected to this game. Live host updates and synchronized gameplay are not available yet.</p>
    </main>
</body>
</html>
