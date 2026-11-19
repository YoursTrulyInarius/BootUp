<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_admin_page();

$gameId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_is_valid($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Invalid request.');
    }
    $gameId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $statement = db()->prepare(
        "UPDATE game_sessions SET status = 'ended', ended_at = CURRENT_TIMESTAMP
         WHERE id = :id AND admin_id = :admin_id AND status = 'lobby'"
    );
    $statement->execute(['id' => $gameId, 'admin_id' => $_SESSION['admin_id']]);
    header('Location: /BootUp/admin/quizzes.php');
    exit;
}

$statement = db()->prepare(
    'SELECT gs.id, gs.game_code, gs.status, q.title
     FROM game_sessions gs JOIN quizzes q ON q.id = gs.quiz_id
     WHERE gs.id = :id AND gs.admin_id = :admin_id'
);
$statement->execute(['id' => $gameId, 'admin_id' => $_SESSION['admin_id']]);
$game = $statement->fetch();
if (!$game) {
    http_response_code(404);
    exit('Game not found.');
}
$statement = db()->prepare('SELECT COUNT(*) FROM players WHERE game_session_id = :game_session_id');
$statement->execute(['game_session_id' => $game['id']]);
$playerCount = (int) $statement->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Game lobby — BootUp</title>
    <link rel="stylesheet" href="/BootUp/assets/css/app.css">
</head>
<body class="join-page">
    <header class="site-header join-header">
        <a class="brand" href="/BootUp/admin/quizzes.php">BOOTUP<span>.</span></a>
        <a class="host-link" href="/BootUp/admin/quizzes.php">Host dashboard <span aria-hidden="true">→</span></a>
    </header>
    <main class="join-main host-lobby-main">
        <p class="eyebrow">HOST LOBBY · <?= h(strtoupper($game['status'])) ?></p>
        <h1><?= h($game['title']) ?></h1>
        <?php if ($game['status'] === 'lobby'): ?>
            <p class="join-copy">Share this PIN with your players so they can join.</p>
            <div class="host-pin" aria-label="Game PIN"><?= h($game['game_code']) ?></div>
            <div class="join-footer"><span class="live-dot"></span> <?= $playerCount ?> <?= $playerCount === 1 ? 'player' : 'players' ?> joined</div>
            <p class="lobby-note">Players can join from the BootUp homepage. The lobby count is a snapshot and does not update live yet.</p>
            <form method="post" class="end-game-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $game['id'] ?>">
                <button class="button button-quiet" type="submit">Close this lobby</button>
            </form>
        <?php else: ?>
            <p class="join-copy">This lobby is closed. Players can no longer join.</p>
            <a class="button button-primary" href="/BootUp/admin/quizzes.php">Back to dashboard</a>
        <?php endif; ?>
    </main>
</body>
</html>
