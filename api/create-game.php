<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
require_admin_api();
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
$quizId = is_array($payload) ? filter_var($payload['quiz_id'] ?? null, FILTER_VALIDATE_INT) : false;
if (!$quizId || $quizId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Choose a valid quiz.']);
    exit;
}

$quizStatement = db()->prepare(
    'SELECT id FROM quizzes WHERE id = :id AND admin_id = :admin_id AND status = :status'
);
$quizStatement->execute([
    'id' => $quizId,
    'admin_id' => $_SESSION['admin_id'],
    'status' => 'published',
]);
if (!$quizStatement->fetch()) {
    http_response_code(404);
    echo json_encode(['error' => 'Publish this quiz before hosting it.']);
    exit;
}
$questionStatement = db()->prepare('SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = :quiz_id');
$questionStatement->execute(['quiz_id' => $quizId]);
if ((int) $questionStatement->fetchColumn() === 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Add at least one question before hosting this quiz.']);
    exit;
}

$insert = db()->prepare(
    'INSERT INTO game_sessions (quiz_id, admin_id, game_code) VALUES (:quiz_id, :admin_id, :game_code)'
);
for ($attempt = 0; $attempt < 5; $attempt++) {
    $code = (string) random_int(100000, 999999);
    try {
        $insert->execute([
            'quiz_id' => $quizId,
            'admin_id' => $_SESSION['admin_id'],
            'game_code' => $code,
        ]);
        $gameId = (int) db()->lastInsertId();
        echo json_encode(['id' => $gameId, 'redirect' => '/BootUp/admin/host-lobby.php?id=' . $gameId]);
        exit;
    } catch (PDOException $exception) {
        if ($exception->getCode() !== '23000') {
            error_log('BootUp create game failed: ' . $exception->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'The game could not be created. Please try again.']);
            exit;
        }
    }
}

http_response_code(503);
echo json_encode(['error' => 'Could not generate an available game PIN. Please try again.']);
