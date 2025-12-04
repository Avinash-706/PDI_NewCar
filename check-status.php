<?php
require_once 'auto-config.php';
require_once 'init-directories.php';

echo "=== CURRENT CLEANUP STATUS ===\n\n";

// Check draft JSON files
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts');
$draftFiles = glob($draftDir . '/*.json');
echo "Draft JSON files: " . count($draftFiles) . "\n";
if (!empty($draftFiles)) {
    foreach ($draftFiles as $file) {
        echo "  - " . basename($file) . "\n";
    }
}

// Check PDF files
$pdfDir = DirectoryManager::getAbsolutePath('pdfs');
$pdfFiles = glob($pdfDir . '/*.pdf');
echo "\nPDF files: " . count($pdfFiles) . "\n";
if (!empty($pdfFiles)) {
    foreach ($pdfFiles as $file) {
        echo "  - " . basename($file) . "\n";
    }
}

// Check uniform images
$uniformDir = DirectoryManager::getAbsolutePath('uploads/drafts/uniform');
$uniformFiles = glob($uniformDir . '/*');
$uniformFiles = array_filter($uniformFiles, function($f) { return basename($f) !== '.gitkeep'; });
echo "\nUniform images (correct location): " . count($uniformFiles) . "\n";

$uniformDirAlt = DirectoryManager::getAbsolutePath('uploads/drafts/compressed/uniform');
$uniformFilesAlt = glob($uniformDirAlt . '/*');
$uniformFilesAlt = array_filter($uniformFilesAlt, function($f) { return basename($f) !== '.gitkeep'; });
echo "Uniform images (wrong location): " . count($uniformFilesAlt) . "\n";

// Check compressed images
$compressedDir = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');
$compressedFiles = glob($compressedDir . '/*');
$compressedFiles = array_filter($compressedFiles, function($f) { 
    return is_file($f) && basename($f) !== '.gitkeep'; 
});
echo "Compressed images: " . count($compressedFiles) . "\n";

// Check submission files (car_photo_* in uploads/)
$uploadsDir = DirectoryManager::getAbsolutePath('uploads');
$submissionFiles = glob($uploadsDir . '/car_photo_*');
echo "Submission files (car_photo_*): " . count($submissionFiles) . "\n";
if (!empty($submissionFiles)) {
    foreach ($submissionFiles as $file) {
        echo "  - " . basename($file) . " (" . round(filesize($file)/1024, 2) . " KB)\n";
    }
}

$isClean = count($draftFiles) == 0 && count($pdfFiles) == 0 && count($uniformFiles) == 0 && count($uniformFilesAlt) == 0 && count($compressedFiles) == 0 && count($submissionFiles) == 0;
echo "\n=== STATUS: " . ($isClean ? "CLEAN ✓" : "NEEDS CLEANUP ✗") . " ===\n";
