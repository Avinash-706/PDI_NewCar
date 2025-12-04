<?php
/**
 * Create new draft
 * POST /drafts/create.php
 */

require_once __DIR__ . '/../auto-config.php';

// Force high limits for draft operations
@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '600');
@ini_set('upload_max_filesize', '200M');
@ini_set('post_max_size', '500M');
@ini_set('max_file_uploads', '500');
@ini_set('max_input_vars', '5000');

define('APP_INIT', true);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $draftId = uniqid('draft_', true);
    $userId = $_POST['user_id'] ?? $_POST['expert_id'] ?? 'guest';
    $timestamp = time();
    
    $draftData = [
        'draft_id' => $draftId,
        'version' => 1,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
        'owner_id' => $userId,
        'archived' => false,
        'current_step' => $_POST['current_step'] ?? 1,
        'form_data' => [],
        'uploaded_files' => []
    ];
    
    // Save draft
    $draftFile = __DIR__ . '/../uploads/drafts/' . $draftId . '.json';
    file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT));
    
    $response['success'] = true;
    $response['message'] = 'Draft created successfully';
    $response['draft_id'] = $draftId;
    $response['version'] = 1;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
