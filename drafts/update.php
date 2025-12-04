<?php
/**
 * Update existing draft
 * POST /drafts/update.php
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
    $draftId = $_POST['draft_id'] ?? null;
    
    if (!$draftId) {
        throw new Exception('Draft ID required');
    }
    
    $draftFile = __DIR__ . '/../uploads/drafts/' . $draftId . '.json';
    
    if (!file_exists($draftFile)) {
        throw new Exception('Draft not found');
    }
    
    // Acquire lock
    $lockFile = $draftFile . '.lock';
    $lock = fopen($lockFile, 'w');
    if (!flock($lock, LOCK_EX)) {
        throw new Exception('Could not acquire lock');
    }
    
    try {
        // Load existing draft
        $draftData = json_decode(file_get_contents($draftFile), true);
        
        // Store previous version
        $prevVersion = $draftData['version'] ?? 1;
        $prevVersionFile = str_replace('.json', ".v{$prevVersion}.json", $draftFile);
        copy($draftFile, $prevVersionFile);
        
        // Update data
        if (isset($_POST['form_data'])) {
            $draftData['form_data'] = array_merge(
                $draftData['form_data'] ?? [],
                $_POST['form_data']
            );
        }
        
        if (isset($_POST['current_step'])) {
            $draftData['current_step'] = $_POST['current_step'];
        }
        
        // Increment version
        $draftData['version'] = $prevVersion + 1;
        $draftData['updated_at'] = time();
        $draftData['previous_version_path'] = $prevVersionFile;
        
        // Save
        file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT));
        
        $response['success'] = true;
        $response['message'] = 'Draft updated successfully';
        $response['version'] = $draftData['version'];
        
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
        @unlink($lockFile);
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
