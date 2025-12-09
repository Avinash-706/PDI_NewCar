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
    
    // Load form schema to identify text fields
    $formSchema = require __DIR__ . '/../form-schema.php';
    
    // Convert text fields to uppercase
    if (isset($draftData['form_data'])) {
        foreach ($draftData['form_data'] as $fieldName => $value) {
            // Find field type in schema
            $fieldType = null;
            foreach ($formSchema as $step) {
                if (isset($step['fields'][$fieldName])) {
                    $fieldType = $step['fields'][$fieldName]['type'];
                    break;
                }
            }
            
            // Convert text and textarea fields to uppercase
            if (($fieldType === 'text' || $fieldType === 'textarea') && is_string($value)) {
                $draftData['form_data'][$fieldName] = strtoupper($value);
            }
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'Draft loaded successfully';
    $response['draft_data'] = $draftData;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
