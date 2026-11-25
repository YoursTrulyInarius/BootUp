<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/source-files.php';

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
$fileId = is_array($payload) ? filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT) : false;
if (!$fileId || $fileId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Choose a valid file.']);
    exit;
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $select = $pdo->prepare(
        'SELECT storage_name FROM source_files
         WHERE id = :id AND admin_id = :admin_id FOR UPDATE'
    );
    $select->execute(['id' => $fileId, 'admin_id' => $_SESSION['admin_id']]);
    $file = $select->fetch();
    if (!$file) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'File not found.']);
        exit;
    }

    $path = source_file_path((string) $file['storage_name']);
    if (is_file($path) && !unlink($path)) {
        $pdo->rollBack();
        error_log('BootUp could not delete a private source file.');
        http_response_code(500);
        echo json_encode(['error' => 'The file could not be removed from private storage.']);
        exit;
    }
    $delete = $pdo->prepare(
        'DELETE FROM source_files WHERE id = :id AND admin_id = :admin_id'
    );
    $delete->execute(['id' => $fileId, 'admin_id' => $_SESSION['admin_id']]);
    $pdo->commit();
    echo json_encode(['message' => 'File deleted.']);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('BootUp source-file deletion failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'The file could not be deleted.']);
}
