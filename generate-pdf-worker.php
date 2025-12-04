<?php
/**
 * PDF Generation Worker
 * Usage: php generate-pdf-worker.php <draft_id>
 * Or called via job queue
 */

require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';

@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '300');

define('APP_INIT', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/image-optimizer.php';

// Get draft ID
$draftId = $argv[1] ?? $_POST['draft_id'] ?? null;

if (!$draftId) {
    die(json_encode(['success' => false, 'message' => 'Draft ID required']));
}

try {
    // Load draft
    $draftFile = DirectoryManager::getAbsolutePath('uploads/drafts/' . $draftId . '.json');
    
    if (!file_exists($draftFile)) {
        throw new Exception('Draft not found');
    }
    
    $draftData = json_decode(file_get_contents($draftFile), true);
    
    // Prepare data for PDF
    $pdfData = array_merge(
        $draftData['form_data'] ?? [],
        []
    );
    
    // Add uploaded file paths
    foreach ($draftData['uploaded_files'] ?? [] as $fieldName => $path) {
        $pdfData[$fieldName . '_path'] = $path;
    }
    
    // Generate PDF using existing generator
    require_once __DIR__ . '/generate-pdf.php';
    $pdfPath = generatePDF($pdfData);
    
    if (!$pdfPath) {
        throw new Exception('PDF generation failed');
    }
    
    // Update draft with PDF path
    $draftData['pdf_path'] = $pdfPath;
    $draftData['pdf_generated_at'] = time();
    file_put_contents($draftFile, json_encode($draftData, JSON_PRETTY_PRINT));
    
    $result = [
        'success' => true,
        'message' => 'PDF generated successfully',
        'pdf_path' => $pdfPath,
        'draft_id' => $draftId
    ];
    
    echo json_encode($result);
    
} catch (Exception $e) {
    $result = [
        'success' => false,
        'message' => $e->getMessage(),
        'draft_id' => $draftId
    ];
    
    error_log('PDF Worker Error: ' . $e->getMessage());
    echo json_encode($result);
}
