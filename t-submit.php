<?php
/**
 * T-SUBMIT: Test PDF Generation Handler
 * Generates PDF with only the steps filled so far
 * For debugging and quick testing purposes
 */

// Auto-configure PHP settings
require_once 'auto-config.php';
require_once 'init-directories.php';

// Force high memory and time
@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '600');
@ini_set('max_file_uploads', '500');
@ini_set('max_input_vars', '5000');
@set_time_limit(600);

define('APP_INIT', true);
require_once 'config.php';

// Prevent any output before JSON
ob_start();

// Suppress all errors/warnings in output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if this is test mode
    if (!isset($_POST['test_mode']) || $_POST['test_mode'] !== 'true') {
        throw new Exception('Not in test mode');
    }
    
    $currentStep = isset($_POST['current_step']) ? (int)$_POST['current_step'] : 1;
    $totalSteps = isset($_POST['total_steps']) ? (int)$_POST['total_steps'] : 13;
    
    error_log("T-SUBMIT: Generating test PDF for steps 1-{$currentStep}");
    
    // Handle file uploads from existing uploads
    $uploadedFiles = [];
    $fileCount = 0;
    
    // Check for existing_ fields in POST (from progressive upload)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'existing_') === 0 && !empty($value)) {
            $fieldName = str_replace('existing_', '', $key);
            $pathKey = $fieldName . '_path';
            
            $absolutePath = DirectoryManager::getAbsolutePath($value);
            if (file_exists($absolutePath)) {
                $uploadedFiles[$pathKey] = $absolutePath;
                $fileCount++;
            }
        }
    }
    
    error_log("T-SUBMIT: Found {$fileCount} uploaded images");
    
    // Prepare data for PDF
    $formData = $_POST;
    
    // Remove test mode flags
    unset($formData['test_mode']);
    unset($formData['current_step']);
    unset($formData['total_steps']);
    
    // Merge uploaded file paths
    $formData = array_merge($formData, $uploadedFiles);
    
    // Add test mode info to data
    $formData['_test_mode'] = true;
    $formData['_current_step'] = $currentStep;
    $formData['_total_steps'] = $totalSteps;
    
    // Log data being sent to PDF
    error_log("T-SUBMIT: Generating PDF with " . count($uploadedFiles) . " images");
    error_log("T-SUBMIT: Steps to include: 1-{$currentStep}");
    
    // Generate PDF using test generator
    if (!file_exists('generate-test-pdf.php')) {
        throw new Exception('generate-test-pdf.php not found');
    }
    
    require_once 'generate-test-pdf.php';
    
    if (!function_exists('generateTestPDF')) {
        throw new Exception('generateTestPDF function not found');
    }
    
    $pdfPath = generateTestPDF($formData, $currentStep);
    
    if (!$pdfPath || !file_exists($pdfPath)) {
        throw new Exception('Failed to generate test PDF');
    }
    
    error_log("T-SUBMIT: PDF generated successfully: {$pdfPath}");
    
    // Convert absolute path to relative web path
    $webPath = str_replace(DirectoryManager::getAbsolutePath(''), '', $pdfPath);
    $webPath = str_replace('\\', '/', $webPath); // Convert Windows paths
    
    $response['success'] = true;
    $response['message'] = "Test PDF generated successfully for steps 1-{$currentStep}";
    $response['pdf_path'] = $webPath;
    $response['steps_included'] = $currentStep;
    $response['images_processed'] = $fileCount;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['error_details'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log('T-SUBMIT error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
} catch (Error $e) {
    $response['message'] = 'PHP Error: ' . $e->getMessage();
    $response['error_details'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    error_log('T-SUBMIT PHP error: ' . $e->getMessage());
}

// Clean output and send JSON
$output = ob_get_clean();
if (!empty($output)) {
    error_log('T-SUBMIT: Unexpected output before JSON: ' . $output);
}

echo json_encode($response);
exit;
