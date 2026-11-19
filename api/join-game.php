<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}
if (!csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Your session expired. Reload the page and try again.']);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
$code = is_array($payload) ? trim((string) ($payload['code'] ?? '')) : '';
if (!preg_match('/^\d{6}$/', $code)) {
    http_response_code(422);
    echo json_encode(['error' => 'Enter a six-digit game PIN.']);
    exit;
}

$statement = db()->prepare(
    "SELECT id FROM game_sessions WHERE game_code = :game_code AND status = 'lobby'"
);
$statement->execute(['game_code' => $code]);
$game = $statement->fetch();
if (!$game) {
    http_response_code(404);
    echo json_encode(['error' => 'We couldn’t find an open game with that PIN. Check the code and try again.']);
    exit;
}

session_regenerate_id(true);
$_SESSION['player_game_id'] = (int) $game['id'];
unset($_SESSION['player_id']);
echo json_encode(['redirect' => '/BootUp/player/register.php']);
