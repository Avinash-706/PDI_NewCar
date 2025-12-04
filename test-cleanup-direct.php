<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'auto-config.php';
require_once 'init-directories.php';
require_once 'cleanup-after-email.php';

echo "Testing cleanupAfterEmail function...\n";

$result = cleanupAfterEmail('pdfs/test.pdf', ['booking_id' => 'TEST']);

echo "Result:\n";
print_r($result);
