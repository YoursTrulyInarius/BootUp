<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

if (!isset($_SESSION['player_game_id'])) {
    header('Location: /BootUp/');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Invalid request. Please go back and try again.');
}

$name = trim((string) ($_POST['player_name'] ?? ''));
if ($name === '' || mb_strlen($name) > 24) {
    $_SESSION['player_error'] = 'Choose a name between 1 and 24 characters.';
    header('Location: /BootUp/player/register.php');
    exit;
}

$gameId = (int) $_SESSION['player_game_id'];
$gameCheck = db()->prepare("SELECT id FROM game_sessions WHERE id = :id AND status = 'lobby'");
$gameCheck->execute(['id' => $gameId]);
if (!$gameCheck->fetch()) {
    unset($_SESSION['player_game_id']);
    http_response_code(410);
    exit('This game is no longer accepting players. Ask your host for an open game PIN.');
}

try {
    if (isset($_SESSION['player_id'])) {
        $statement = db()->prepare(
            'UPDATE players SET player_name = :player_name WHERE id = :id AND game_session_id = :game_session_id'
        );
        $statement->execute([
            'player_name' => $name,
            'id' => $_SESSION['player_id'],
            'game_session_id' => $gameId,
        ]);
    } else {
        $statement = db()->prepare(
            'INSERT INTO players (game_session_id, player_name) VALUES (:game_session_id, :player_name)'
        );
        $statement->execute(['game_session_id' => $gameId, 'player_name' => $name]);
        $_SESSION['player_id'] = (int) db()->lastInsertId();
    }
    $_SESSION['player_name'] = $name;
    header('Location: /BootUp/player/lobby.php');
    exit;
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['player_error'] = 'That name is already in this game. Choose another one.';
        header('Location: /BootUp/player/register.php');
        exit;
    }
    error_log('BootUp player registration failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Could not join this game due to a database error.');
}
