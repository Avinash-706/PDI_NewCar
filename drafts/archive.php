<?php
/**
 * Archive draft (don't delete)
 * POST /drafts/archive.php
 */

require_once __DIR__ . '/../auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $draftId = $_POST['draft_id'] ?? null;
    
    if (!$draftId) {
        throw new Exception('Draft ID required');
    }
    
    $draftFile = __DIR__ . '/../uploads/drafts/' . $draftId . '.json';
    
    if (!file_exists($draftFile)) {
        throw new Exception('Draft not found');
    }
    
    // Load and update
    $draftData = json_decode(file_get_contents($draftFile), true);
    
    $draftData['archived'] = true;
    $draftData['archived_at'] = time();
    $draftData['archived_by'] = $_POST['user_id'] ?? 'system';
    $draftData['submission_id'] = $_POST['submission_id'] ?? null;
    
    // Save
    file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT));
    
    $response['success'] = true;
    $response['message'] = 'Draft archived successfully';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
