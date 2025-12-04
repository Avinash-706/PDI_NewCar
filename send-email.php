<?php
/**
 * Email sending using PHPMailer
 * Install PHPMailer via Composer: composer require phpmailer/phpmailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/vendor/autoload.php';

function sendEmail($pdfPath, $formData) {
    $mail = null;
    
    try {
        // Validate PDF exists before attempting email
        if (!file_exists($pdfPath)) {
            error_log('Email sending aborted: PDF file not found at ' . $pdfPath);
            return false;
        }
        
        $mail = new PHPMailer(true);
        
        // SMTP Configuration with TIMEOUT settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        // Set timeouts to prevent hanging (30 seconds max)
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Disable debug output (important for background execution)
        $mail->SMTPDebug = 0;
        
        // Additional safety settings
        $mail->SMTPAutoTLS = true;
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Sender and recipient
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress(SMTP_TO_EMAIL, SMTP_TO_NAME);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'PDI (New Car Inspection) Report - Booking ID: ' . ($formData['booking_id'] ?? 'N/A');
        
        $emailBody = generateEmailBody($formData);
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);
        
        // Attach PDF
        $mail->addAttachment($pdfPath, 'inspection_report.pdf');
        
        // Send email
        $mail->send();
        
        return true;
        
    } catch (Exception $e) {
        // Safe error logging even if $mail is not initialized
        if ($mail !== null && isset($mail->ErrorInfo)) {
            error_log('Email sending error (PHPMailer): ' . $mail->ErrorInfo);
        }
        error_log('Email exception: ' . $e->getMessage());
        error_log('Email exception trace: ' . $e->getTraceAsString());
        
        // Log configuration for debugging (without password)
        if (defined('SMTP_HOST')) {
            error_log('SMTP Config - Host: ' . SMTP_HOST . ':' . (defined('SMTP_PORT') ? SMTP_PORT : 'undefined'));
            error_log('SMTP Config - User: ' . (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'undefined'));
        }
        
        return false;
    }
}

function generateEmailBody($data) {
    $bookingId = htmlspecialchars($data['booking_id'] ?? 'N/A');
    $customerName = htmlspecialchars($data['customer_name'] ?? 'N/A');
    $car = htmlspecialchars($data['car'] ?? 'N/A');
    $inspectionDate = htmlspecialchars($data['inspection_date'] ?? 'N/A');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #2196F3; }
            .info-row { margin: 10px 0; }
            .label { font-weight: bold; color: #555; }
            .value { color: #000; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚗 PDI (New Car Inspection) Report</h1>
                <p>Pre-Delivery Inspection Report</p>
            </div>
            <div class="content">
                <p>Dear Team,</p>
                <p>A new car inspection has been completed. Please find the details below:</p>
                
                <div class="info-box">
                    <div class="info-row">
                        <span class="label">Booking ID:</span>
                        <span class="value">' . $bookingId . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Customer Name:</span>
                        <span class="value">' . $customerName . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Car:</span>
                        <span class="value">' . $car . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Inspection Date:</span>
                        <span class="value">' . $inspectionDate . '</span>
                    </div>
                </div>
                
                <p>The complete inspection report is attached as a PDF document.</p>
                
                <div class="footer">
                    <p>This is an automated email from Car Inspection Expert System</p>
                    <p>Generated on ' . date('d-m-Y H:i:s') . '</p>
                </div>
            </div>
        </div>
    </body>
    </html>
';
    
    return $html;
}
?>
