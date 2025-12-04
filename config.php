<?php
/**
 * Configuration File
 * Centralized configuration for the Car Inspection Expert System
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    die('Direct access not permitted');
}

// Application Settings
define('APP_NAME', 'Car Inspection Expert System');
define('APP_VERSION', '1.0.0');
define('APP_TITLE', 'PDI (New Car Inspection) Report');

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'avunashdhanuka@gmail.com');
define('SMTP_PASSWORD', 'ulot wkxs khzl ldkb');
define('SMTP_FROM_EMAIL', 'avunashdhanuka@gmail.com');
define('SMTP_FROM_NAME', 'Car Inspection Expert');
define('SMTP_TO_EMAIL', 'avunashdhanuka@gmail.com');
define('SMTP_TO_NAME', 'Inspection Team');

// File Upload Settings
define('UPLOAD_DIR', 'uploads/');
define('PDF_DIR', 'pdfs/');
define('LOG_DIR', 'logs/');
define('MAX_FILE_SIZE', 15728640); // 15MB in bytes
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// PDF Settings
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_MARGIN_TOP', 20);
define('PDF_MARGIN_BOTTOM', 20);
define('PDF_IMAGE_QUALITY', 75); // JPEG quality 0-100
define('PDF_MAX_IMAGE_WIDTH', 800); // Max width in pixels

// Form Settings
define('TOTAL_STEPS', 13);
define('ENABLE_DRAFT_SAVE', true);
define('ENABLE_AUTO_SAVE', true);
define('AUTO_SAVE_INTERVAL', 30000); // milliseconds

// Security Settings
define('ENABLE_CSRF_PROTECTION', false); // Set to true in production
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// Error Handling
define('DISPLAY_ERRORS', false); // Set to false in production
define('LOG_ERRORS', true);
define('ERROR_LOG_FILE', LOG_DIR . 'error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata'); // Change as needed

// Database Settings (if needed in future)
define('DB_HOST', 'localhost');
define('DB_NAME', 'car_inspection');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// API Keys (if needed)
define('GOOGLE_MAPS_API_KEY', ''); // For enhanced location features

// Cleanup Settings
define('AUTO_DELETE_OLD_FILES', true);
define('FILE_RETENTION_DAYS', 30); // Delete files older than 30 days

// Notification Settings
define('SEND_CONFIRMATION_EMAIL', true);
define('SEND_ADMIN_NOTIFICATION', true);

// Feature Flags
define('ENABLE_GEOLOCATION', true);
define('ENABLE_IMAGE_COMPRESSION', true);
define('ENABLE_PDF_WATERMARK', false);

// Custom Messages
define('SUCCESS_MESSAGE', 'Inspection submitted successfully! PDF has been generated and sent to the email.');
define('ERROR_MESSAGE', 'An error occurred while processing your submission. Please try again.');
define('VALIDATION_ERROR', 'Please fill in all required fields.');
define('UPLOAD_ERROR', 'Failed to upload image. Please ensure the file is under 15MB and is a valid image format.');

// Helper Functions
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function isProduction() {
    return !DISPLAY_ERRORS;
}

function logError($message, $context = []) {
    if (LOG_ERRORS) {
        $logMessage = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $logMessage .= ' - Context: ' . json_encode($context);
        }
        error_log($logMessage . PHP_EOL, 3, ERROR_LOG_FILE);
    }
}

// Initialize error handling
if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (LOG_ERRORS) {
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
}

// Create required directories
$requiredDirs = [UPLOAD_DIR, PDF_DIR, LOG_DIR];
foreach ($requiredDirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}
?>
