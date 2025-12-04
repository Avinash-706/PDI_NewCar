<?php
/**
 * Test Directory System
 * Verifies all required directories exist and are writable
 */

require_once 'auto-config.php';
require_once 'init-directories.php';

header('Content-Type: application/json');

$response = [
    'success' => true,
    'directories' => [],
    'errors' => []
];

// Directories to test
$directories = [
    'uploads',
    'uploads/drafts',
    'uploads/drafts/compressed',
    'uploads/drafts/uniform',
    'pdfs',
    'logs',
    'tmp',
    'drafts/audit',
    'drafts/logs',
    'drafts/pdfs',
    'drafts/uploads'
];

foreach ($directories as $dir) {
    $absolutePath = DirectoryManager::getAbsolutePath($dir);
    $exists = is_dir($absolutePath);
    $writable = $exists ? is_writable($absolutePath) : false;
    
    $response['directories'][$dir] = [
        'path' => $absolutePath,
        'exists' => $exists,
        'writable' => $writable,
        'status' => ($exists && $writable) ? 'OK' : 'ERROR'
    ];
    
    if (!$exists) {
        $response['errors'][] = "Directory does not exist: $dir";
        $response['success'] = false;
    } elseif (!$writable) {
        $response['errors'][] = "Directory is not writable: $dir";
        $response['success'] = false;
    }
}

echo json_encode($response, JSON_PRETTY_PRINT);
