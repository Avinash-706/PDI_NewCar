<?php
/**
 * Auto Configuration for Production
 * This file automatically configures PHP settings for optimal performance
 * Include this at the top of every entry point file
 */

// Set optimal PHP configuration (try multiple methods)
// CRITICAL: Support for 500+ image uploads
@ini_set('max_execution_time', '600');
@ini_set('max_input_time', '600');
@ini_set('memory_limit', '2048M'); // Increased for PDF generation
@ini_set('post_max_size', '500M'); // Increased for many images
@ini_set('upload_max_filesize', '200M'); // Increased per file
@ini_set('max_file_uploads', '500'); // Support 500+ images
@ini_set('max_input_vars', '5000'); // Support many form fields

// Also try set_time_limit
@set_time_limit(600);

// Disable output buffering for better performance
@ini_set('output_buffering', 'Off');
@ini_set('zlib.output_compression', 'Off');

// Error handling for production
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Session configuration
@ini_set('session.gc_maxlifetime', '3600');

// Timezone
@date_default_timezone_set('Asia/Kolkata');

// Create required directories
$dirs = ['uploads', 'uploads/drafts', 'pdfs', 'logs'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Return true to indicate successful configuration
return true;
