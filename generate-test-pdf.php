<?php
/**
 * Test PDF Generation - Only includes filled steps
 * Standalone version without dependencies
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/init-directories.php';
require_once __DIR__ . '/image-optimizer.php';

function generateTestPDF($data, $maxStep = 23) {
    try {
        // Create mPDF
        $tmpDir = DirectoryManager::getAbsolutePath('tmp');
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'tempDir' => $tmpDir,
        ]);
        
        $mpdf->SetTitle('Test Report - Steps 1-' . $maxStep);
        
        // Generate HTML
        $html = testGenerateHTML($data, $maxStep);
        $mpdf->WriteHTML($html);
        
        // Save PDF
        $pdfFilename = 'TEST_step' . $maxStep . '_' . time() . '.pdf';
        $pdfPath = DirectoryManager::getAbsolutePath('pdfs/' . $pdfFilename);
        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);
        
        return $pdfPath;
        
    } catch (Exception $e) {
        error_log('Test PDF Error: ' . $e->getMessage());
        throw $e;
    }
}

function generateTestHeader($data, $maxStep) {
    $booking_id = htmlspecialchars($data['booking_id'] ?? 'TEST');
    $expert_name = htmlspecialchars($data['expert_name'] ?? 'N/A');
    $customer_name = htmlspecialchars($data['customer_name'] ?? $data['booking_id'] ?? 'N/A');
    
    $headerHTML = '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #FF9800; padding: 25px 30px; margin: 0 0 0 0;">
        <tr>
            <td width="30%" style="vertical-align: middle; padding: 0; margin: 0">
                <img src="logo.png" style="width: 230px; height: 160px; display: block;" />
            </td>
            <td width="70%" style="vertical-align: middle; text-align: right; padding: 0 0 0 20px; margin: 0;">
                <div style="color: #ffffff; font-family: Arial, Helvetica, sans-serif; line-height: 1.8;">
                    <div style="font-size: 16pt; font-weight: bold; margin-bottom: 10px; letter-spacing: 0.5px;">TEST - PDI (New Car Inspection) Report</div>
                    <div style="font-size: 11pt; margin-bottom: 5px;">Steps: 1-' . $maxStep . '</div>
                    <div style="font-size: 11pt; margin-bottom: 5px;">ID: ' . $booking_id . '</div>
                    <div style="font-size: 11pt; margin-bottom: 5px;">Assigned Expert Name: ' . $expert_name . '</div>
                    <div style="font-size: 11pt;">Customer Name: ' . $customer_name . '</div>
                </div>
            </td>
        </tr>
    </table>
    <div style="text-align: center; padding: 15px 0; margin: 0 0 30px 0; background-color: #fff3e0; border-bottom: 2px solid #FF9800;">
        <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #666;">Generated: ' . date('Y-m-d H:i:s') . '</p>
    </div>
    ';
    
    return $headerHTML;
}

function testGenerateHTML($data, $maxStep) {
    // Use same styles as production but with ORANGE theme
    $html = '<style>
        /* Base styles - Same as production */
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.6; margin: 0; padding: 0; }
        
        /* Step header - ORANGE theme for test */
        .step-header { background: #fff3e0; padding: 12px 15px; font-size: 15.6px; font-weight: bold; margin: 20px 0 15px 0; border-left: 5px solid #FF9800; page-break-after: avoid; color: #f57c00; }
        
        /* Field rows - Same as production */
        .field-row { margin: 6px 0; padding: 6px 0; border-bottom: 1px solid #e0e0e0; page-break-inside: avoid; }
        .field-label { font-weight: bold; color: #333; display: inline-block; width: 40%; font-size: 12px; }
        .field-value { color: #000; display: inline-block; width: 58%; font-size: 12px; }
        .field-value.missing { color: #ff9800; font-weight: bold; }
        
        /* Image grid table - Same as production */
        .image-grid { width: 100%; border-collapse: separate; border-spacing: 10px; margin: 20px 0; }
        
        /* Individual image cell - Same as production */
        .image-grid td { width: 33.333%; vertical-align: top; text-align: center; padding: 5px; }
        
        /* Image label - ORANGE theme for test */
        .image-label { font-size: 14px; font-weight: bold; color: #f57c00; margin-bottom: 8px; text-align: center; line-height: 1.4; min-height: 32px; display: block; }
        
        /* Image styling - Increased size */
        .image-grid img { width: 300px !important; height: 225px !important; border: none; display: block; margin: 0 auto; }
        
        /* Location section - ORANGE theme for test */
        .location-section { background: #fff3e0; padding: 12px; margin: 12px 0; border-left: 4px solid #FF9800; font-size: 12px; }
        .section-label { font-size: 13.2px; color: #f57c00; font-weight: bold; }
        
        /* Footer - ORANGE theme for test */
        .footer { text-align: center; margin-top: 25px; padding-top: 18px; border-top: 2px solid #FF9800; font-size: 10.8px; color: #666; }
    </style>';
    
    $html .= generateTestHeader($data, $maxStep);
    
    // Step 1
    if ($maxStep >= 1) {
        $html .= '<div class="step-header">STEP 1 — BOOKING DETAILS</div>';
        $html .= testField('Booking ID', $data['booking_id'] ?? '');
    }
    
    // Step 2
    if ($maxStep >= 2) {
        $html .= '<div class="step-header">STEP 2 — EXPERT DETAILS</div>';
        $html .= testField('Inspection Delayed', $data['inspection_delayed'] ?? '');
        
        // Image
        $images = [];
        $images[] = testGenerateImage('Your photo with car\'s number plate', $data['car_photo_path'] ?? '');
        $html .= testGenerateImageGrid($images);
    }
    
    // Step 3
    if ($maxStep >= 3) {
        $html .= '<div class="step-header">STEP 3 — CAR DETAILS</div>';
        $html .= testField('Car Company', $data['car_company'] ?? '');
        $html .= testField('Registration Number', $data['car_registration_number'] ?? '');
        $html .= testField('Fuel Type', testFormatArray($data['fuel_type'] ?? []));
        $html .= testField('Car Colour', $data['car_colour'] ?? '');
        $html .= testField('Car KM Reading', $data['car_km_reading'] ?? '');
        $html .= testField('Chassis Number', $data['chassis_number'] ?? '');
        $html .= testField('Engine Number', $data['engine_number'] ?? '');
        
        // Images
        $images = [];
        $images[] = testGenerateImage('Car KM Reading Photo', $data['car_km_photo_path'] ?? '');
        $images[] = testGenerateImage('Chassis No Plate', $data['chassis_plate_photo_path'] ?? '');
        $html .= testGenerateImageGrid($images);
    }
    
    // Step 4
    if ($maxStep >= 4) {
        $html .= '<div class="step-header">STEP 4 — CAR DOCUMENTS</div>';
        $html .= testField('Registration Certificate', testFormatArray($data['registration_certificate'] ?? []));
        $html .= testField('Car Insurance', testFormatArray($data['car_insurance'] ?? []));
        $html .= testField('Car Finance NOC', testFormatArray($data['car_finance_noc'] ?? []));
        $html .= testField('Car Purchase Invoice', testFormatArray($data['car_purchase_invoice'] ?? []));
        $html .= testField('Bi-Fuel Certification', testFormatArray($data['bifuel_certification'] ?? []));
    }
    
    // Step 5
    if ($maxStep >= 5) {
        $html .= '<div class="step-header">STEP 5 — BODY FRAME CHECKLIST</div>';
        $html .= testField('Radiator Core Support', testFormatArray($data['radiator_core'] ?? []));
        $html .= testField('Match Chassis', $data['match_chassis'] ?? '');
        $html .= testField('Driver Strut', testFormatArray($data['driver_strut'] ?? []));
        $html .= testField('Passenger Strut', testFormatArray($data['passenger_strut'] ?? []));
        $html .= testField('Front Bonnet', testFormatArray($data['front_bonnet'] ?? []));
        $html .= testField('Boot Floor', testFormatArray($data['boot_floor'] ?? []));
        
        // Images
        $images = [];
        $images[] = testGenerateImage('Radiator Core Support', $data['radiator_core_image_path'] ?? '');
        $images[] = testGenerateImage('Driver Side Strut Tower Apron', $data['driver_strut_image_path'] ?? '');
        $images[] = testGenerateImage('Passenger Strut Tower Apron', $data['passenger_strut_image_path'] ?? '');
        $images[] = testGenerateImage('Front Bonnet UnderBody', $data['front_bonnet_image_path'] ?? '');
        $images[] = testGenerateImage('Boot Floor', $data['boot_floor_image_path'] ?? '');
        $html .= testGenerateImageGrid($images);
    }
    
    // Step 6
    if ($maxStep >= 6) {
        $html .= '<div class="step-header">STEP 6 — ENGINE COMPARTMENT</div>';
        $html .= testField('Car Start', testFormatArray($data['car_start'] ?? []));
        $html .= testField('Wiring', testFormatArray($data['wiring'] ?? []));
        $html .= testField('Engine Oil Quality', testFormatArray($data['engine_oil'] ?? []));
        $html .= testField('Engine Oil Cap', testFormatArray($data['engine_oil_cap'] ?? []));
        
        // Images
        $images = [];
        $images[] = testGenerateImage('Car Start', $data['car_start_image_path'] ?? '');
        $images[] = testGenerateImage('Wiring', $data['wiring_image_path'] ?? '');
        $images[] = testGenerateImage('Engine Oil Quality', $data['engine_oil_image_path'] ?? '');
        $html .= testGenerateImageGrid($images);
    }
    
    // Step 7
    if ($maxStep >= 7) {
        $html .= '<div class="step-header">STEP 7 — EXHAUST SMOKE</div>';
        $html .= testField('Smoke Emission', testFormatArray($data['smoke_emission'] ?? []));
        
        // Image
        $images = [];
        $images[] = testGenerateImage('Smoke Emission', $data['smoke_emission_image_path'] ?? '');
        $html .= testGenerateImageGrid($images);
    }
    
    // Step 8
    if ($maxStep >= 8) {
        $html .= '<div class="step-header">STEP 8 — OBD SCAN</div>';
        $html .= testField('Fault Codes', $data['fault_codes'] ?? '');
        
        // Image
        $images = [];
        $images[] = testGenerateImage('OBD Scan Photo', $data['obd_scan_photo_path'] ?? '');
        $html .= testGenerateImageGrid($images);
    }
    
    // Steps 9-22 (simplified - no images)
    for ($i = 9; $i <= 22 && $i <= $maxStep; $i++) {
        $html .= '<div class="step-header">STEP ' . $i . '</div>';
        $html .= '<p style="padding: 10px; color: #666;">Step ' . $i . ' data included (text fields only)...</p>';
    }
    
    // Step 23 with PAYMENT SCREENSHOT and OTHER IMAGES support
    if ($maxStep >= 23) {
        $html .= '<div class="step-header">STEP 23 — PAYMENT DETAILS</div>';
        $html .= testField('Taking Payment', $data['taking_payment'] ?? '');
        
        // Payment Screenshot (if payment was made)
        if (isset($data['taking_payment']) && $data['taking_payment'] === 'Yes') {
            if (!empty($data['payment_screenshot_path'])) {
                $paymentImages = [];
                $paymentImages[] = testGenerateImage('Payment Screenshot', $data['payment_screenshot_path']);
                $html .= '<div style="margin-top: 15px;">';
                $html .= testGenerateImageGrid($paymentImages);
                $html .= '</div>';
            }
        }
        
        // OTHER IMAGES (Optional - only show if images exist)
        $otherImages = [];
        for ($i = 1; $i <= 5; $i++) {
            $fieldName = 'other_image_' . $i . '_path';
            if (!empty($data[$fieldName])) {
                $otherImages[] = testGenerateImage('Other Image ' . $i, $data[$fieldName]);
            }
        }
        
        // Only display OTHER IMAGES section if at least one image exists
        if (!empty($otherImages)) {
            $html .= '<div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #ffcc80;">';
            $html .= '<h3 style="color: #ff9800; font-size: 14px; margin-bottom: 15px;">OTHER IMAGES</h3>';
            $html .= testGenerateImageGrid($otherImages);
            $html .= '</div>';
        }
    }
    
    $html .= '<div style="text-align: center; margin-top: 20px; padding: 15px; border-top: 2px solid #ff9800;">
        <p><strong>TEST MODE - Steps 1-' . $maxStep . '</strong></p>
        <p>Generated: ' . date('Y-m-d H:i:s') . '</p>
    </div>';
    
    return $html;
}

function testGenerateImage($label, $path) {
    if (empty($path) || !file_exists($path)) {
        return null;
    }
    
    // Resize to uniform dimensions - INCREASED SIZE (300x225)
    $uniformPath = ImageOptimizer::resizeToUniform($path, 300, 225, 70);
    
    return [
        'label' => htmlspecialchars($label),
        'path' => $uniformPath
    ];
}

function testGenerateImageGrid($images) {
    // Filter out null/empty images
    $images = array_filter($images, function($img) {
        return !empty($img) && is_array($img);
    });
    
    if (empty($images)) {
        return '';
    }
    
    // Build table with 3 columns
    $html = '<table class="image-grid" cellpadding="0" cellspacing="0" border="0">';
    
    $chunks = array_chunk($images, 3);
    foreach ($chunks as $row) {
        $html .= '<tr>';
        
        foreach ($row as $image) {
            $html .= '<td>';
            $html .= '<div class="image-label">' . $image['label'] . '</div>';
            $html .= '<img src="' . $image['path'] . '" alt="' . $image['label'] . '" width="300" height="225">';
            $html .= '</td>';
        }
        
        // Fill empty cells if row has less than 3 images
        $remaining = 3 - count($row);
        for ($i = 0; $i < $remaining; $i++) {
            $html .= '<td></td>';
        }
        
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    return $html;
}

function testField($label, $value) {
    $value = (string)$value;
    
    if ($value === '') {
        $value = '<span class="missing">Not Selected</span>';
    } else {
        $value = htmlspecialchars($value);
    }
    
    return '<div class="field-row">
        <span class="field-label">' . htmlspecialchars($label) . ':</span>
        <span class="field-value">' . $value . '</span>
    </div>';
}

function testFormatArray($value) {
    if (is_array($value)) {
        $filtered = array_filter($value, function($v) {
            return $v !== '' && $v !== null && $v !== false;
        });
        
        if (!empty($filtered)) {
            return implode(', ', $filtered);
        }
        
        return '';
    }
    
    return (string)$value;
}
