<?php
/**
 * Load draft
 * GET /drafts/load.php?draft_id=xxx
 */

require_once __DIR__ . '/../auto-config.php';
define('APP_INIT', true);
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $draftId = $_GET['draft_id'] ?? null;
    
    if (!$draftId) {
        throw new Exception('Draft ID required');
    }
    
    $draftFile = __DIR__ . '/../uploads/drafts/' . $draftId . '.json';
    
    if (!file_exists($draftFile)) {
        throw new Exception('Draft not found');
    }
    
    $draftData = json_decode(file_get_contents($draftFile), true);
    
    if (!$draftData) {
        throw new Exception('Invalid draft data');
    }
    
    $response['success'] = true;
    $response['message'] = 'Draft loaded successfully';
    $response['draft_data'] = $draftData;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
