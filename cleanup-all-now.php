<?php
/**
 * EMERGENCY CLEANUP - Delete ALL drafts, PDFs, and processing files
 * Run this manually to clean up everything
 */

require_once 'auto-config.php';
require_once 'init-directories.php';

echo "=== EMERGENCY CLEANUP ===\n\n";

$stats = [
    'drafts' => 0,
    'pdfs' => 0,
    'compressed' => 0,
    'uniform' => 0,
    'freed' => 0
];

// 1. Delete all draft JSON files
echo "1. Cleaning draft JSON files...\n";
$draftDir = DirectoryManager::getAbsolutePath('uploads/drafts');
$draftFiles = glob($draftDir . '/*.json');
foreach ($draftFiles as $file) {
    $size = filesize($file);
    if (unlink($file)) {
        $stats['drafts']++;
        $stats['freed'] += $size;
        echo "  ✓ Deleted: " . basename($file) . "\n";
    } else {
        echo "  ✗ Failed: " . basename($file) . "\n";
    }
}
echo "  Total: {$stats['drafts']} drafts deleted\n\n";

// 2. Delete all PDFs
echo "2. Cleaning PDF files...\n";
$pdfDir = DirectoryManager::getAbsolutePath('pdfs');
$pdfFiles = glob($pdfDir . '/*.pdf');
foreach ($pdfFiles as $file) {
    $size = filesize($file);
    if (unlink($file)) {
        $stats['pdfs']++;
        $stats['freed'] += $size;
        echo "  ✓ Deleted: " . basename($file) . " (" . round($size/1024/1024, 2) . " MB)\n";
    } else {
        echo "  ✗ Failed: " . basename($file) . "\n";
    }
}
echo "  Total: {$stats['pdfs']} PDFs deleted\n\n";

// 3. Delete compressed images
echo "3. Cleaning compressed images...\n";
$compressedDir = DirectoryManager::getAbsolutePath('uploads/drafts/compressed');
if (is_dir($compressedDir)) {
    $compressedFiles = glob($compressedDir . '/*');
    foreach ($compressedFiles as $file) {
        if (is_file($file) && basename($file) !== '.gitkeep') {
            $size = filesize($file);
            if (unlink($file)) {
                $stats['compressed']++;
                $stats['freed'] += $size;
                echo "  ✓ Deleted: " . basename($file) . "\n";
            }
        }
    }
}
echo "  Total: {$stats['compressed']} compressed images deleted\n\n";

// 4. Delete uniform images (both locations)
echo "4. Cleaning uniform images...\n";
$uniformDirs = [
    DirectoryManager::getAbsolutePath('uploads/drafts/uniform'),
    DirectoryManager::getAbsolutePath('uploads/drafts/compressed/uniform')
];

foreach ($uniformDirs as $uniformDir) {
    if (is_dir($uniformDir)) {
        $uniformFiles = glob($uniformDir . '/*');
        foreach ($uniformFiles as $file) {
            if (is_file($file) && basename($file) !== '.gitkeep') {
                $size = filesize($file);
                if (unlink($file)) {
                    $stats['uniform']++;
                    $stats['freed'] += $size;
                    echo "  ✓ Deleted: " . basename($file) . "\n";
                }
            }
        }
    }
}
echo "  Total: {$stats['uniform']} uniform images deleted\n\n";

// 5. Delete submission files (car_photo_* in uploads/)
echo "5. Cleaning submission files (car_photo_*)...\n";
$uploadsDir = DirectoryManager::getAbsolutePath('uploads');
$submissionFiles = glob($uploadsDir . '/car_photo_*');
$submissionCount = 0;
foreach ($submissionFiles as $file) {
    if (is_file($file)) {
        $size = filesize($file);
        if (unlink($file)) {
            $submissionCount++;
            $stats['freed'] += $size;
            echo "  ✓ Deleted: " . basename($file) . " (" . round($size/1024, 2) . " KB)\n";
        } else {
            echo "  ✗ Failed: " . basename($file) . "\n";
        }
    }
}
echo "  Total: $submissionCount submission files deleted\n\n";

// 5b. Delete orphaned thumbnails in drafts
echo "5b. Cleaning orphaned thumbnails...\n";
$draftsDir = DirectoryManager::getAbsolutePath('uploads/drafts');
$thumbFiles = glob($draftsDir . '/thumb_*');
$thumbCount = 0;
foreach ($thumbFiles as $file) {
    if (is_file($file)) {
        // Check if original image exists
        $originalFile = str_replace('thumb_', '', $file);
        if (!file_exists($originalFile)) {
            // Orphaned thumbnail - delete it
            $size = filesize($file);
            if (unlink($file)) {
                $thumbCount++;
                $stats['freed'] += $size;
                echo "  ✓ Deleted orphaned thumb: " . basename($file) . "\n";
            }
        }
    }
}
echo "  Total: $thumbCount orphaned thumbnails deleted\n\n";

// 6. Delete audit logs
echo "6. Cleaning audit logs...\n";
$auditDir = DirectoryManager::getAbsolutePath('drafts/audit');
if (is_dir($auditDir)) {
    $auditFiles = glob($auditDir . '/*.log');
    $auditCount = 0;
    foreach ($auditFiles as $file) {
        $size = filesize($file);
        if (unlink($file)) {
            $auditCount++;
            $stats['freed'] += $size;
        }
    }
    echo "  Total: $auditCount audit logs deleted\n\n";
}

// Summary
echo "=== CLEANUP COMPLETE ===\n";
echo "Drafts deleted: {$stats['drafts']}\n";
echo "PDFs deleted: {$stats['pdfs']}\n";
echo "Compressed images deleted: {$stats['compressed']}\n";
echo "Uniform images deleted: {$stats['uniform']}\n";
echo "Total space freed: " . round($stats['freed']/1024/1024, 2) . " MB\n";
