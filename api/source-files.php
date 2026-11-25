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

$file = $_FILES['source_file'] ?? null;
if (!is_array($file)) {
    http_response_code(400);
    echo json_encode(['error' => 'Choose a file to import.']);
    exit;
}
$uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError !== UPLOAD_ERR_OK) {
    http_response_code(in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 413 : 400);
    echo json_encode(['error' => $uploadError === UPLOAD_ERR_NO_FILE
        ? 'Choose a file to import.'
        : 'The file upload did not complete. Try again.']);
    exit;
}

$temporaryPath = (string) ($file['tmp_name'] ?? '');
$fileSize = (int) ($file['size'] ?? 0);
if (!is_uploaded_file($temporaryPath)) {
    http_response_code(400);
    echo json_encode(['error' => 'The uploaded file could not be verified.']);
    exit;
}
if ($fileSize < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'The selected file is empty.']);
    exit;
}
if ($fileSize > SOURCE_FILE_MAX_SIZE) {
    http_response_code(413);
    echo json_encode(['error' => 'Files must be 20 MB or smaller.']);
    exit;
}

$clientName = str_replace('\\', '/', (string) ($file['name'] ?? ''));
$originalName = basename($clientName);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if (!in_array($extension, SOURCE_FILE_EXTENSIONS, true)) {
    http_response_code(415);
    echo json_encode(['error' => 'Choose a PDF, PPTX, DOCX, TXT, Markdown, or CSV file.']);
    exit;
}
$originalName = preg_replace('/[\x00-\x1f\x7f]/', '', trim($originalName)) ?? '';
$originalName = mb_substr($originalName, 0, 255);
if ($originalName === '') {
    http_response_code(400);
    echo json_encode(['error' => 'The file name is invalid.']);
    exit;
}

$handle = fopen($temporaryPath, 'rb');
if ($handle === false) {
    http_response_code(400);
    echo json_encode(['error' => 'The uploaded file could not be read.']);
    exit;
}
$signature = fread($handle, 5);
fclose($handle);
$validSignature = match ($extension) {
    'pdf' => str_starts_with($signature, '%PDF-'),
    'pptx', 'docx' => str_starts_with($signature, 'PK'),
    default => true,
};
if (!$validSignature) {
    http_response_code(422);
    echo json_encode(['error' => 'The file content does not match its extension.']);
    exit;
}

$adminId = (int) $_SESSION['admin_id'];
$pdo = db();
$usage = $pdo->prepare('SELECT COALESCE(SUM(size_bytes), 0) FROM source_files WHERE admin_id = :admin_id');
$usage->execute(['admin_id' => $adminId]);
$usedBytes = (int) $usage->fetchColumn();
if ($usedBytes + $fileSize > SOURCE_FILE_QUOTA_SIZE) {
    http_response_code(413);
    echo json_encode(['error' => 'This account has reached its 100 MB study-file storage limit. Delete a file to make room.']);
    exit;
}

try {
    $directory = ensure_source_files_directory();
} catch (RuntimeException $exception) {
    error_log('BootUp source-file storage unavailable: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Private file storage is unavailable. Check the server configuration.']);
    exit;
}

$storedName = bin2hex(random_bytes(16)) . '.' . $extension;
$destination = $directory . DIRECTORY_SEPARATOR . $storedName;
if (!move_uploaded_file($temporaryPath, $destination)) {
    error_log('BootUp source-file upload could not be moved into private storage.');
    http_response_code(500);
    echo json_encode(['error' => 'The file could not be saved to private storage.']);
    exit;
}

try {
    $insert = $pdo->prepare(
        'INSERT INTO source_files (admin_id, original_name, extension, storage_name, size_bytes)
         VALUES (:admin_id, :original_name, :extension, :storage_name, :size_bytes)'
    );
    $insert->execute([
        'admin_id' => $adminId,
        'original_name' => $originalName,
        'extension' => $extension,
        'storage_name' => $storedName,
        'size_bytes' => $fileSize,
    ]);
} catch (PDOException $exception) {
    if (is_file($destination) && !unlink($destination)) {
        error_log('BootUp could not remove an unregistered uploaded source file.');
    }
    error_log('BootUp source-file record could not be saved: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'The file could not be added to your library.']);
    exit;
}

http_response_code(201);
echo json_encode([
    'file' => [
        'id' => (int) $pdo->lastInsertId(),
        'name' => $originalName,
        'extension' => $extension,
        'size_bytes' => $fileSize,
    ],
]);
