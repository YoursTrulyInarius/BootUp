<?php
declare(strict_types=1);

const SOURCE_FILE_MAX_SIZE = 20 * 1024 * 1024;
const SOURCE_FILE_QUOTA_SIZE = 100 * 1024 * 1024;
const SOURCE_FILE_EXTENSIONS = ['pdf', 'pptx', 'docx', 'txt', 'md', 'csv'];

function source_files_directory(): string
{
    $applicationRoot = realpath(dirname(__DIR__));
    if ($applicationRoot === false) {
        throw new RuntimeException('The application directory could not be resolved.');
    }

    $privateParent = dirname(dirname($applicationRoot));
    return $privateParent . DIRECTORY_SEPARATOR . basename($applicationRoot) . '-private-files';
}

function ensure_source_files_directory(): string
{
    $directory = source_files_directory();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('The private file directory could not be created.');
    }
    if (is_link($directory) || !is_writable($directory)) {
        throw new RuntimeException('The private file directory is not writable.');
    }

    return $directory;
}

function source_file_path(string $storedName): string
{
    if (!preg_match('/\A[a-f0-9]{32}\.(?:pdf|pptx|docx|txt|md|csv)\z/', $storedName)) {
        throw new RuntimeException('The stored file name is invalid.');
    }

    return source_files_directory() . DIRECTORY_SEPARATOR . $storedName;
}
