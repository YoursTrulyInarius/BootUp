<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/source-files.php';

require_admin_api();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$fileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$fileId || $fileId < 1) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Choose a valid file.']);
    exit;
}

$statement = db()->prepare(
    'SELECT original_name, extension, storage_name
     FROM source_files WHERE id = :id AND admin_id = :admin_id'
);
$statement->execute(['id' => $fileId, 'admin_id' => $_SESSION['admin_id']]);
$file = $statement->fetch();
if (!$file) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'File not found.']);
    exit;
}

$path = source_file_path((string) $file['storage_name']);
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'The stored file is unavailable.']);
    exit;
}

$fallbackName = 'study-file.' . $file['extension'];
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode((string) $file['original_name']));
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
readfile($path);
