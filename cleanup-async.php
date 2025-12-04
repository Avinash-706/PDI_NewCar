<?php
/**
 * Async Cleanup Handler
 * Called separately after submission to ensure cleanup runs even if user navigates away
 */

// Ignore user abort - continue running even if connection is closed
ignore_user_abort(true);
set_time_limit(300); // 5 minutes max

require_once 'auto-config.php';
require_once 'init-directories.php';

// Get parameters
$draftId = $_GET['draft_id'] ?? $_POST['draft_id'] ?? null;
$pdfPath = $_GET['pdf_path'] ?? $_POST['pdf_path'] ?? null;
$submissionFilesJson = $_GET['submission_files'] ?? $_POST['submission_files'] ?? null;

// Decode submission files
$submissionFiles = [];
if ($submissionFilesJson) {
    $submissionFiles = json_decode($submissionFilesJson, true) ?? [];
}

error_log("Async Cleanup: Started for draft: $draftId, PDF: $pdfPath");

// Return immediate response
header('Content-Type: application/json');
echo json_encode(['status' => 'cleanup_started']);

// Close connection but continue script
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ob_end_flush();
    flush();
}

// Now perform cleanup
$result = [
    'draft_cleanup' => false,
    'submission_cleanup' => false,
    'pdf_cleanup' => false
];

try {
    // Step 1: Delete draft and all original images
    if ($draftId) {
        error_log('Async Cleanup: STEP 1 - Starting draft cleanup for: ' . $draftId);
        require_once 'cleanup-after-submission.php';
        $draftCleanupResult = cleanupAfterSubmission($draftId, []);
        
        if ($draftCleanupResult['success']) {
            $result['draft_cleanup'] = true;
            error_log('Async Cleanup: Draft deleted - ' .
                     "Deleted {$draftCleanupResult['deleted_images']} images, " .
                     "Freed " . formatBytes($draftCleanupResult['freed_space']));
        } else {
            error_log('Async Cleanup: Draft cleanup failed - ' . $draftCleanupResult['message']);
        }
        error_log('Async Cleanup: STEP 1 COMPLETE');
    }
    
    // Step 2: Delete submission files (car_photo_* in uploads/)
    if (!empty($submissionFiles)) {
        error_log('Async Cleanup: STEP 2 - Deleting submission files');
        $deletedSubmissionFiles = 0;
        $freedSubmissionSpace = 0;
        
        foreach ($submissionFiles as $fieldName => $filePath) {
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                if (@unlink($filePath)) {
                    $deletedSubmissionFiles++;
                    $freedSubmissionSpace += $fileSize;
                    error_log("Async Cleanup: Deleted submission file: $filePath");
                } else {
                    error_log("Async Cleanup: Failed to delete submission file: $filePath");
                }
            }
        }
        
        $result['submission_cleanup'] = true;
        error_log("Async Cleanup: STEP 2 COMPLETE - Deleted $deletedSubmissionFiles submission files, Freed " . formatBytes($freedSubmissionSpace));
    }
    
    // Step 3: Delete PDF and remaining compressed/uniform images
    if ($pdfPath) {
        error_log('Async Cleanup: STEP 3 - Starting PDF and processing files cleanup');
        require_once 'cleanup-after-email.php';
        $postCleanupResult = cleanupAfterEmail($pdfPath, []);
        
        if ($postCleanupResult['success']) {
            $result['pdf_cleanup'] = true;
            error_log('Async Cleanup: PDF and processing files deleted - ' .
                     'PDF: ' . ($postCleanupResult['deleted_pdf'] ? 'Yes' : 'No') . ', ' .
                     'Compressed: ' . $postCleanupResult['deleted_compressed'] . ', ' .
                     'Uniform: ' . $postCleanupResult['deleted_uniform'] . ', ' .
                     'Freed: ' . formatBytes($postCleanupResult['freed_space']));
        } else {
            error_log('Async Cleanup: Failed - ' . $postCleanupResult['message']);
        }
    }
    
    error_log('Async Cleanup: COMPLETE - Draft: ' . ($result['draft_cleanup'] ? 'Yes' : 'No') . 
             ', Submission: ' . ($result['submission_cleanup'] ? 'Yes' : 'No') . 
             ', PDF: ' . ($result['pdf_cleanup'] ? 'Yes' : 'No'));
    
} catch (Exception $e) {
    error_log('Async Cleanup: Exception - ' . $e->getMessage());
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
