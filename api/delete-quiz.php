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
$quizId = is_array($payload) ? filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT) : false;
if (!$quizId || $quizId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid quiz.']);
    exit;
}
$statement = db()->prepare('DELETE FROM quizzes WHERE id = :id AND admin_id = :admin_id');
$statement->execute(['id' => $quizId, 'admin_id' => $_SESSION['admin_id']]);
if ($statement->rowCount() !== 1) {
    http_response_code(404);
    echo json_encode(['error' => 'Quiz not found.']);
    exit;
}
echo json_encode(['message' => 'Quiz deleted.']);
