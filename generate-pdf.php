<?php
/**
 * Complete PDF Generation - All 13 Steps, All Fields
 * Ensures NO field is ever missing
 */

// Auto-configure PHP settings
require_once __DIR__ . '/auto-config.php';
require_once __DIR__ . '/init-directories.php';

// CRITICAL: Support for 500+ image uploads in PDF
@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', '600');
@ini_set('max_file_uploads', '500');
@ini_set('max_input_vars', '5000');
@set_time_limit(600);

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
    require_once __DIR__ . '/config.php';
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/image-optimizer.php';

function generatePDF($data) {
    try {
        // Compress all images first (optimized for speed)
        $data = compressAllImages($data);
        
        // Get temp directory
        $tmpDir = DirectoryManager::getAbsolutePath('tmp');
        
        // Create mPDF with OPTIMIZED settings for speed
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'tempDir' => $tmpDir,
            'useSubstitutions' => false,
            'simpleTables' => true,
            'packTableData' => true,
            'dpi' => 72,  // Reduced for faster processing
            'img_dpi' => 72,  // Reduced for faster processing
            'compress' => true,  // Enable PDF compression
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
        ]);
        
        $mpdf->use_kwt = false;
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->SetTitle('PDI (New Car Inspection) Report');
        $mpdf->SetAuthor('Car Inspection Expert');
        $mpdf->SetCompression(true);  // Enable compression
        
        // Generate complete HTML
        $html = generateCompleteHTML($data);
        $mpdf->WriteHTML($html);
        
        // Save PDF
        $pdfFilename = 'inspection_' . ($data['booking_id'] ?? 'unknown') . '_' . time() . '.pdf';
        $pdfPath = DirectoryManager::getAbsolutePath('pdfs/' . $pdfFilename);
        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);
        
        return $pdfPath;
        
    } catch (Exception $e) {
        error_log('PDF Generation Error: ' . $e->getMessage());
        return false;
    }
}

function generateCompleteHTML($data) {
    $html = generateStyles();
    $html .= generateHeader($data);
    
    // ========================================================================
    // STEP 1: Car Inspection
    // ========================================================================
    $html .= generateStepHeader(1, 'Car Inspection');
    
    // Mandatory fields
    $html .= generateField('Booking ID', $data['booking_id'] ?? '', true);
    $html .= generateField('Assigned Expert Name', $data['expert_name'] ?? '', true);
    $html .= generateField('Inspection Expert City', $data['expert_city'] ?? '', true);
    $html .= generateField('Customer Name', $data['customer_name'] ?? '', true);
    
    // Optional field
    $html .= generateField('Customer Phone Number', $data['customer_phone'] ?? '', false);
    
    // Mandatory fields continued
    $html .= generateField('Date', $data['inspection_date'] ?? '', true);
    $html .= generateField('Time', $data['inspection_time'] ?? '', true);
    $html .= generateField('Inspection Address', $data['inspection_address'] ?? '', true);
    $html .= generateField('OBD Scanning', $data['obd_scanning'] ?? '', true);
    
    // Optional fields
    $html .= generateField('Car', $data['car'] ?? '', false);
    $html .= generateField('Lead Owner', $data['lead_owner'] ?? '', false);
    $html .= generateField('Pending Amount', $data['pending_amount'] ?? '', false);
    
    // ========================================================================
    // STEP 2: Payment Taking
    // ========================================================================
    $html .= generateStepHeader(2, 'Payment Taking');
    
    $html .= generateField('Payment', $data['payment_taken'] ?? '', true);
    
    // Show payment details if payment was taken
    if (($data['payment_taken'] ?? '') === 'Yes') {
        $html .= generateField('Payment Type', $data['payment_type'] ?? '', true);
        $html .= generateField('Amount Paid', $data['amount_paid'] ?? '', true);
        
        // Show payment proof image if online payment
        if (($data['payment_type'] ?? '') === 'Online') {
            $images = [];
            $images[] = generateImage('Payment Proof', $data['payment_proof_path'] ?? '', true);
            $html .= generateImageGrid($images);
        }
    }
    
    // ========================================================================
    // STEP 3: Expert Details
    // ========================================================================
    $html .= generateStepHeader(3, 'Expert Details');
    
    // Mandatory fields
    $html .= generateField('Inspection 45 Minutes Delayed?', $data['inspection_delayed'] ?? '', true);
    
    // Image in grid
    $images = [];
    $images[] = generateImage('Your Photo with car number plate', $data['car_photo_path'] ?? '', true);
    $html .= generateImageGrid($images);
    
    // ========================================================================
    // STEP 4: Car Images
    // ========================================================================
    $html .= generateStepHeader(4, 'Car Images');
    
    // All car images in grid
    $images = [];
    $images[] = generateImage('Front', $data['car_image_front_path'] ?? '', true);
    $images[] = generateImage('Back', $data['car_image_back_path'] ?? '', true);
    $images[] = generateImage('Driver Side', $data['car_image_driver_side_path'] ?? '', true);
    $images[] = generateImage('Passenger Side', $data['car_image_passenger_side_path'] ?? '', true);
    $images[] = generateImage('Front Dashboard', $data['car_image_dashboard_path'] ?? '', true);
    $html .= generateImageGrid($images);
    
    // ========================================================================
    // STEP 5: Car Details
    // ========================================================================
    $html .= generateStepHeader(5, 'Car Details');
    
    // Mandatory fields
    $html .= generateField('Car Company', $data['car_company'] ?? '', true);
    $html .= generateField('Car Variant', $data['car_variant'] ?? '', true);
    $html .= generateField('Car Registered State', $data['car_registered_state'] ?? '', true);
    
    // Optional field
    $html .= generateField('Car Registered City', $data['car_registered_city'] ?? '', false);
    
    // Mandatory fields continued - fuel_type is now single value, not array
    $fuelType = $data['fuel_type'] ?? '';
    // Handle both old format (array) and new format (string) for backward compatibility
    if (is_array($fuelType)) {
        $fuelType = implode(', ', $fuelType);
    }
    $html .= generateField('Fuel Type', $fuelType, true);
    
    $html .= generateField('Engine Capacity (in CC)', $data['engine_capacity'] ?? '', true);
    $html .= generateField('Transmission Type', $data['transmission'] ?? '', true);
    $html .= generateField('Car Color', $data['car_colour'] ?? '', true);
    $html .= generateField('Car KM Current Reading', $data['car_km_reading'] ?? '', true);
    $html .= generateField('Number of Car Keys Available', $data['car_keys_available'] ?? '', true);
    $html .= generateField('Chassis Number', $data['chassis_number'] ?? '', true);
    $html .= generateField('Engine Number', $data['engine_number'] ?? '', true);
    
    // Images in grid
    $images = [];
    $images[] = generateImage('Car KM Reading Photo', $data['car_km_photo_path'] ?? '', true);
    $images[] = generateImage('Chassis No. Plate', $data['chassis_plate_photo_path'] ?? '', true);
    $html .= generateImageGrid($images);
    
    // ========================================================================
    // STEP 6: Exterior Body (52 fields with conditional images)
    // ========================================================================
    $html .= generateStepHeader(6, 'Exterior Body');
    
    // Define all 52 exterior body fields
    $exteriorFields = [
        'driver_front_door', 'driver_front_fender', 'driver_front_door_window', 'driver_side_view_mirror_housing',
        'driver_side_view_mirror_glass', 'driver_indicator_front', 'driver_front_wheel_arch', 'driver_front_mud_flap',
        'driver_front_cladding', 'driver_roof_rail', 'driver_door_sill', 'driver_back_door', 'driver_back_door_window',
        'driver_rear_cladding', 'driver_rear_wheel_arch', 'driver_back_mud_flap', 'driver_back_quarter_panel',
        'driver_back_indicated', 'passenger_back_indicated', 'rear_windshield', 'connected_taillights',
        'driver_taillights', 'passenger_taillights', 'rear_number_plate', 'rear_bumper', 'boot_space_door',
        'passenger_back_door', 'passenger_back_door_window', 'fuel_filter_flap', 'passenger_door_sill',
        'passenger_back_quarter_panel', 'passenger_front_door', 'passenger_front_door_window',
        'passenger_side_view_mirror_housing', 'passenger_side_view_mirror_glass', 'passenger_front_indicator',
        'passenger_front_fender', 'windshields', 'match_all_glasses_serial_number', 'front_bonnet_top',
        'front_grill', 'fog_lights', 'driver_headlight', 'passenger_headlight', 'front_number_plate',
        'car_roof_outside'
    ];
    
    $exteriorLabels = [
        'driver_front_door' => 'Driver Front Door',
        'driver_front_fender' => 'Driver - Front Fender',
        'driver_front_door_window' => 'Driver - Front Door Window',
        'driver_side_view_mirror_housing' => 'Driver - Side View Mirror Housing',
        'driver_side_view_mirror_glass' => 'Driver - Side View Mirror Glass',
        'driver_indicator_front' => 'Driver - Indicator Front',
        'driver_front_wheel_arch' => 'Driver - Front Wheel Arch/ Fender Lining',
        'driver_front_mud_flap' => 'Driver - Front Mud Flap',
        'driver_front_cladding' => 'Driver - Front Cladding',
        'driver_roof_rail' => 'Driver - Roof Rail',
        'driver_door_sill' => 'Driver - Door Sill',
        'driver_back_door' => 'Driver -Back Door',
        'driver_back_door_window' => 'Driver - Back Door Window',
        'driver_rear_cladding' => 'Driver - Rear Cladding',
        'driver_rear_wheel_arch' => 'Driver - Rear Wheel Arch / Fender Lining',
        'driver_back_mud_flap' => 'Driver - Back Mud Flap',
        'driver_back_quarter_panel' => 'Driver - Back Quarter Panel',
        'driver_back_indicated' => 'Driver - Back Indicated',
        'passenger_back_indicated' => 'Passenger - Back Indicated',
        'rear_windshield' => 'Rear Windshield',
        'connected_taillights' => 'Connected Taillights',
        'driver_taillights' => 'Driver Taillights',
        'passenger_taillights' => 'Passenger Taillights',
        'rear_number_plate' => 'Rear Number Plate',
        'rear_bumper' => 'Rear Bumper',
        'boot_space_door' => 'Boot Space Door/ Backdoor',
        'passenger_back_door' => 'Passenger - Back Door',
        'passenger_back_door_window' => 'Passenger - Back Door Window',
        'fuel_filter_flap' => 'Fuel Filter Flap',
        'passenger_door_sill' => 'Passenger Door Sill',
        'passenger_back_quarter_panel' => 'Passenger Back Quarter Panel',
        'passenger_front_door' => 'Passenger Front Door',
        'passenger_front_door_window' => 'Passenger Front Door Window',
        'passenger_side_view_mirror_housing' => 'Passenger Side View Mirror Housing',
        'passenger_side_view_mirror_glass' => 'Passenger Side View Mirror Glass',
        'passenger_front_indicator' => 'Passenger Front Indicator',
        'passenger_front_fender' => 'Passenger Front Fender',
        'windshields' => 'Windshields',
        'match_all_glasses_serial_number' => 'Match all Glasses Serial Number',
        'front_bonnet_top' => 'Front Bonnet Top',
        'front_grill' => 'Front Grill',
        'fog_lights' => "Fog Light's",
        'driver_headlight' => 'Driver Headlight',
        'passenger_headlight' => 'Passenger Headlight',
        'front_number_plate' => 'Front Number Plate',
        'car_roof_outside' => 'Car Roof Outside',
    ];
    
    // Generate fields and images for each exterior body part
    foreach ($exteriorFields as $field) {
        $label = $exteriorLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
        $html .= generateField($label, formatArray($data[$field] ?? []), true);
        
        // Add image if exists (only shown if not "Ok")
        $imagePath = $data[$field . '_image_path'] ?? '';
        if (!empty($imagePath)) {
            $images = [];
            $images[] = generateImage($label . ' Image', $imagePath, false);
            $html .= generateImageGrid($images);
        }
    }
    
    // ========================================================================
    // STEP 7: OBD Scan
    // ========================================================================
    $html .= generateStepHeader(7, 'OBD Scan');
    
    $html .= generateField('Any Fault Code Present', $data['fault_code_present'] ?? '', true);
    
    // OBD Scan Photo
    $images = [];
    $images[] = generateImage('OBD Scan Photo', $data['obd_scan_photo_path'] ?? '', true);
    $html .= generateImageGrid($images);
    
    // ========================================================================
    // STEP 8: Electrical and Interior
    // ========================================================================
    $html .= generateStepHeader(8, 'Electrical and Interior');
    
    $html .= generateField('Central Lock Working', $data['central_lock_working'] ?? '', true);
    $html .= generateField('Ignition Switch / Push Button', $data['ignition_switch'] ?? '', true);
    $html .= generateField('Driver - Front Indicator', $data['driver_front_indicator_elec'] ?? '', true);
    $html .= generateField('Passenger - Front Indicator', $data['passenger_front_indicator_elec'] ?? '', true);
    $html .= generateField('Driver Headlight', $data['driver_headlight_working'] ?? '', true);
    $html .= generateField('Passenger Headlight', $data['passenger_headlight_working'] ?? '', true);
    $html .= generateField('Driver Headlight Highbeam', $data['driver_headlight_highbeam'] ?? '', true);
    $html .= generateField('Passenger Headlight Highbeam', $data['passenger_headlight_highbeam'] ?? '', true);
    $html .= generateField('Front Number Plate Light', $data['front_number_plate_light'] ?? '', true);
    $html .= generateField('Driver Back Indicator', $data['driver_back_indicator_elec'] ?? '', true);
    $html .= generateField('Passenger Back Indicator', $data['passenger_back_indicator_elec'] ?? '', true);
    $html .= generateField('Back Number Plate Light', $data['back_number_plate_light'] ?? '', true);
    $html .= generateField('Brake Light Driver', $data['brake_light_driver'] ?? '', true);
    $html .= generateField('Brake Light Passenger', $data['brake_light_passenger'] ?? '', true);
    $html .= generateField('Driver Tail Light', $data['driver_tail_light'] ?? '', true);
    $html .= generateField('Passenger Tail Light', $data['passenger_tail_light'] ?? '', true);
    $html .= generateField('Steering Wheel Condition', $data['steering_wheel_condition'] ?? '', true);
    $html .= generateField('Steering Mountain Controls', $data['steering_mountain_controls'] ?? '', true);
    $html .= generateField('Back Camera', $data['back_camera'] ?? '', true);
    $html .= generateField('Reverse Parking Sensor', $data['reverse_parking_sensor'] ?? '', true);
    $html .= generateField('Car Horn', $data['car_horn'] ?? '', true);
    $html .= generateField('Entertainment System', $data['entertainment_system'] ?? '', true);
    $html .= generateField('Cruise Control', $data['cruise_control'] ?? '', true);
    $html .= generateField('Interior Lights', $data['interior_lights'] ?? '', true);
    $html .= generateField('Sun Roof', $data['sun_roof'] ?? '', true);
    $html .= generateField('Bonnet Release Operation', $data['bonnet_release_operation'] ?? '', true);
    $html .= generateField('Fuel Cap Release Operation', $data['fuel_cap_release_operation'] ?? '', true);
    $html .= generateField('Check Onboard Computer ADBlue Level- Diesel Cars', $data['adblue_level'] ?? '', true);
    $html .= generateField('Window Safety Lock', $data['window_safety_lock'] ?? '', true);
    $html .= generateField('Driver ORVM Controls', $data['driver_orvm_controls'] ?? '', true);
    $html .= generateField('Passenger ORVM Controls', $data['passenger_orvm_controls'] ?? '', true);
    $html .= generateField('Glove Box', $data['glove_box'] ?? '', true);
    $html .= generateField('Wiper', $data['wiper'] ?? '', true);
    $html .= generateField('Rear View Mirror', $data['rear_view_mirror'] ?? '', true);
    $html .= generateField('Dashboard Condition', $data['dashboard_condition'] ?? '', true);
    $html .= generateField('Window Passenger Side', $data['window_passenger_side'] ?? '', true);
    $html .= generateField('Seat Adjustment Passenger Rear Side', $data['seat_adjustment_passenger_rear'] ?? '', true);
    $html .= generateField('Check All Buttons', $data['check_all_buttons'] ?? '', false);
    
    // ========================================================================
    // STEP 9: Air Conditioning
    // ========================================================================
    $html .= generateStepHeader(9, 'Air Conditioning');
    
    $html .= generateField('Air Conditioning Turning On', $data['ac_turning_on'] ?? '', true);
    $html .= generateField('AC Cool Temperature', $data['ac_cool_temperature'] ?? '', false);
    $html .= generateField('AC Hot Temperature', $data['ac_hot_temperature'] ?? '', false);
    $html .= generateField('Air Condition Direction Mode Working', $data['ac_direction_mode'] ?? '', true);
    $html .= generateField('De Fogger Front Vent Working', $data['defogger_front_vent'] ?? '', true);
    $html .= generateField('De Fogger rear Vent Working', $data['defogger_rear_vent'] ?? '', true);
    $html .= generateField('Air Conditioning All Vents', $data['ac_all_vents'] ?? '', true);
    $html .= generateField('AC Abnormal Vibration', $data['ac_abnormal_vibration'] ?? '', true);
    
    // AC Image (if uploaded)
    if (!empty($data['ac_image_path'])) {
        $images = [];
        $images[] = generateImage('Air Condition Image at Fan Max Speed', $data['ac_image_path'] ?? '', false);
        $html .= generateImageGrid($images);
    }
    
    // ========================================================================
    // STEP 10: Tyres
    // ========================================================================
    $html .= generateStepHeader(10, 'Tyres');
    
    $html .= generateField('Tyre Size', $data['tyre_size'] ?? '', true);
    $html .= generateField('Tyre Type', $data['tyre_type'] ?? '', true);
    $html .= generateField('Rim Type', $data['rim_type'] ?? '', true);
    
    // Driver Front Tyre
    $html .= generateField('Driver Front Tyre Depth Check', $data['driver_front_tyre_depth_check'] ?? '', true);
    $html .= generateField('Driver Front Tyre Manufacturing Date', $data['driver_front_tyre_date'] ?? '', false);
    $html .= generateField('Driver Front Tyre Shape', $data['driver_front_tyre_shape'] ?? '', true);
    
    // Driver Back Tyre
    $html .= generateField('Driver Back Tyre Depth Check', $data['driver_back_tyre_depth_check'] ?? '', true);
    $html .= generateField('Driver Back Tyre Manufacturing Date', $data['driver_back_tyre_date'] ?? '', false);
    $html .= generateField('Driver Back Tyre Shape', $data['driver_back_tyre_shape'] ?? '', true);
    
    // Passenger Back Tyre
    $html .= generateField('Passenger Back Tyre Depth Check', $data['passenger_back_tyre_depth_check'] ?? '', true);
    $html .= generateField('Passenger Back Tyre Manufacturing Date', $data['passenger_back_tyre_date'] ?? '', false);
    $html .= generateField('Passenger Back Tyre Shape', $data['passenger_back_tyre_shape'] ?? '', true);
    
    // Passenger Front Tyre
    $html .= generateField('Passenger Front Tyre Depth Check', $data['passenger_front_tyre_depth_check'] ?? '', true);
    $html .= generateField('Passenger Front Tyre Manufacturing Date', $data['passenger_front_tyre_date'] ?? '', false);
    $html .= generateField('Passenger Front Tyre Shape', $data['passenger_front_tyre_shape'] ?? '', true);
    
    // Stepney Tyre
    $html .= generateField('Stepney Tyre Depth Check', $data['stepney_tyre_depth_check'] ?? '', true);
    $html .= generateField('Stepney Tyre Manufacturing Date', $data['stepney_tyre_date'] ?? '', false);
    $html .= generateField('Stepney Front Tyre Shape', $data['stepney_tyre_shape'] ?? '', true);
    
    $html .= generateField('Sign of Camber Issue', $data['sign_of_camber_issue'] ?? '', true);
    
    // Tyre Images in grid
    $images = [];
    $images[] = generateImage('Driver Front Tyre Tread Depth', $data['driver_front_tyre_tread_depth_path'] ?? '', true);
    $images[] = generateImage('Driver Back Tyre Tread Depth', $data['driver_back_tyre_tread_depth_path'] ?? '', true);
    $images[] = generateImage('Passenger Back Tyre Tread Depth', $data['passenger_back_tyre_tread_depth_path'] ?? '', true);
    $images[] = generateImage('Passenger Front Tyre Tread Depth', $data['passenger_front_tyre_tread_depth_path'] ?? '', true);
    $images[] = generateImage('Stepney Tyre Tread Depth', $data['stepney_tyre_tread_depth_path'] ?? '', true);
    $html .= generateImageGrid($images);
    
    // ========================================================================
    // STEP 11: Under Body
    // ========================================================================
    $html .= generateStepHeader(11, 'Under Body');
    
    $html .= generateField('Any Fuel Leaks under Body', $data['fuel_leaks_under_body'] ?? '', true);
    
    // Underbody Images in grid
    $images = [];
    $images[] = generateImage('Underbody Left', $data['underbody_left_path'] ?? '', true);
    $images[] = generateImage('Underbody Rear', $data['underbody_rear_path'] ?? '', true);
    $images[] = generateImage('Underbody Front', $data['underbody_front_path'] ?? '', true);
    $images[] = generateImage('Underbody Right', $data['underbody_right_path'] ?? '', true);
    $html .= generateImageGrid($images);
    
    // ========================================================================
    // STEP 12: Equipments
    // ========================================================================
    $html .= generateStepHeader(12, 'Equipment\'s');
    
    $html .= generateField('Tool Kit', $data['tool_kit'] ?? '', true);
    
    // Tool Kit Image (if present)
    if (!empty($data['tool_kit_image_path'])) {
        $images = [];
        $images[] = generateImage('Tool Kit Image', $data['tool_kit_image_path'] ?? '', false);
        $html .= generateImageGrid($images);
    }
    
    // ========================================================================
    // STEP 13: Final Result
    // ========================================================================
    $html .= generateStepHeader(13, 'Final Result');
    
    $html .= generateField('Any Issues Found in Car', $data['issues_found_in_car'] ?? '', true);
    
    // Photo of Issues (if present)
    if (!empty($data['photo_of_issues_path'])) {
        $images = [];
        $images[] = generateImage('Photo of Issues', $data['photo_of_issues_path'] ?? '', false);
        $html .= generateImageGrid($images);
    }
    
    $html .= generateFooter();
    
    return $html;
}

// ========================================================================
// Styles and Helper Functions
// ========================================================================

function generateStyles() {
    return '
    <style>
        /* Base styles - 20% larger text */
        body { 
            font-family: Arial, Helvetica, sans-serif; 
            font-size: 12px; 
            line-height: 1.6; 
            margin: 0;
            padding: 0;
        }
        
        /* Step header - 20% larger with RED theme */
        .step-header { 
            background: #ffebee; 
            padding: 12px 15px; 
            font-size: 15.6px; 
            font-weight: bold;
            margin: 20px 0 15px 0;
            border-left: 5px solid #D32F2F;
            page-break-after: avoid;
            color: #c62828;
        }
        
        /* Field rows - 20% larger text */
        .field-row { 
            margin: 6px 0;
            padding: 6px 0;
            border-bottom: 1px solid #e0e0e0;
            page-break-inside: avoid;
        }
        .field-label { 
            font-weight: bold; 
            color: #333; 
            display: inline-block; 
            width: 40%; 
            font-size: 12px;
        }
        .field-value { 
            color: #000; 
            display: inline-block; 
            width: 58%; 
            font-size: 12px;
        }
        .field-value.missing { 
            color: #d32f2f; 
            font-weight: bold; 
        }
        
        /* Image grid table - Universal 3-column layout */
        .image-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
            margin: 20px 0;
        }
        
        /* Individual image cell - Consistent 3-column structure */
        .image-grid td {
            width: 33.333%;
            vertical-align: top;
            text-align: center;
            padding: 5px;
        }
        
        /* Image label - Bold and emphasized with RED theme */
        .image-label {
            font-size: 14px;
            font-weight: bold;
            color: #c62828;
            margin-bottom: 8px;
            text-align: center;
            line-height: 1.4;
            min-height: 32px;
            display: block;
        }
        
        /* Image styling - LARGER uniform dimensions, clean and professional */
        .image-grid img {
            width: 300px !important;
            height: 225px !important;
            border: none;
            display: block;
            margin: 0 auto;
        }
        
        /* Location section with RED theme */
        .location-section {
            background: #ffebee;
            padding: 12px;
            margin: 12px 0;
            border-left: 4px solid #D32F2F;
            font-size: 12px;
        }
        .section-label {
            font-size: 13.2px;
            color: #c62828;
            font-weight: bold;
        }
        
        /* Footer with RED theme */
        .footer { 
            text-align: center; 
            margin-top: 25px; 
            padding-top: 18px; 
            border-top: 2px solid #D32F2F; 
            font-size: 10.8px; 
            color: #666; 
        }
    </style>';
}

function generateHeader($data) {
    $booking_id = htmlspecialchars($data['booking_id'] ?? 'N/A');
    $expert_name = htmlspecialchars($data['expert_name'] ?? 'N/A');
    $customer_name = htmlspecialchars($data['customer_name'] ?? $data['booking_id'] ?? 'N/A');
    
    $headerHTML = '
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #D32F2F; padding: 25px 30px; margin: 0 0 0 0;">
        <tr>
            <td width="30%" style="vertical-align: middle; padding: 0; margin: 0;">
                <img src="logo.png" style="width: 230px; height: 160px; display: block;" />
            </td>
            <td width="70%" style="vertical-align: middle; text-align: right; padding: 0 0 0 20px; margin: 0;">
                <div style="color: #ffffff; font-family: Arial, Helvetica, sans-serif; line-height: 1.8;">
                    <div style="font-size: 16pt; font-weight: bold; margin-bottom: 10px; letter-spacing: 0.5px;">PDI (New Car Inspection) Report</div>
                    <div style="font-size: 11pt; margin-bottom: 5px;">ID: ' . $booking_id . '</div>
                    <div style="font-size: 11pt; margin-bottom: 5px;">Assigned Expert Name: ' . $expert_name . '</div>
                    <div style="font-size: 11pt;">Customer Name: ' . $customer_name . '</div>
                </div>
            </td>
        </tr>
    </table>
    <div style="text-align: center; padding: 15px 0; margin: 0 0 30px 0; background-color: #f5f5f5; border-bottom: 2px solid #D32F2F;">
        <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #666;">Generated: ' . date('Y-m-d H:i:s') . '</p>
    </div>
    ';
    
    return $headerHTML;
}

function generateStepHeader($stepNumber, $title) {
    return '<div class="step-header">STEP ' . $stepNumber . ' — ' . strtoupper($title) . '</div>';
}

function generateField($label, $value, $required = false) {
    // Handle arrays (checkbox/multi-select fields)
    if (is_array($value)) {
        // Filter out empty values and join with comma
        $filtered = array_filter($value, function($v) {
            return $v !== '' && $v !== null && $v !== false;
        });
        
        if (!empty($filtered)) {
            $value = implode(', ', $filtered);
        } else {
            $value = ''; // Empty array
        }
    }
    
    // Convert value to string for comparison
    $value = (string)$value;
    
    // Check if value is empty
    if ($value === '' || $value === null) {
        if ($required) {
            return '<div class="field-row">
                <span class="field-label">' . htmlspecialchars($label) . ':</span>
                <span class="field-value missing">Not Selected</span>
            </div>';
        } else {
            return ''; // Skip optional empty fields
        }
    }
    
    return '<div class="field-row">
        <span class="field-label">' . htmlspecialchars($label) . ':</span>
        <span class="field-value">' . nl2br(htmlspecialchars($value)) . '</span>
    </div>';
}

/**
 * Generate image with OPTIMIZED UNIFORM DIMENSIONS
 * 
 * UNIVERSAL IMAGE PROCESSING RULE:
 * - ALL images are resized to EXACTLY 300x225 pixels (larger for clarity)
 * - Compressed to 70% quality for fast PDF generation
 * - Maintains aspect ratio with letterboxing/cropping
 * - Ensures perfect alignment in 3-column table grid
 * - Applied to ALL 13 steps without exception
 * 
 * @param string $label Image label/caption
 * @param string $path Path to image file
 * @param bool $required Whether image is mandatory
 * @return string HTML for image block or error message
 */
function generateImage($label, $path, $required = false) {
    // Convert to absolute path if relative
    $absolutePath = DirectoryManager::getAbsolutePath($path);
    
    if (empty($absolutePath) || !file_exists($absolutePath)) {
        if ($required) {
            return '<div class="field-row">
                <span class="field-label">' . htmlspecialchars($label) . ':</span>
                <span class="field-value missing">⚠️ IMAGE MISSING</span>
            </div>';
        } else {
            return null; // Skip optional missing images
        }
    }
    
    // OPTIMIZED DIMENSIONS: 300x225 pixels (larger, 70% quality for speed)
    // This ensures clear visibility while maintaining fast PDF generation
    $uniformPath = ImageOptimizer::resizeToUniform($absolutePath, 300, 225, 70);
    
    // Return array for table-based grid
    return [
        'label' => htmlspecialchars($label),
        'path' => $uniformPath
    ];
}

/**
 * Generate uniform 3-column table grid for images
 * 
 * UNIVERSAL LAYOUT RULE:
 * - 3 images per row (consistent across ALL steps)
 * - Table-based layout for perfect mPDF compatibility
 * - Automatic row wrapping for any number of images
 * - Applied to ALL steps (1-13) without exception
 * 
 * @param array $images Array of image HTML blocks
 * @return string HTML for table grid container
 */
function generateImageGrid($images) {
    // Filter out null/empty images
    $images = array_filter($images, function($img) {
        return !empty($img) && is_array($img);
    });
    
    if (empty($images)) {
        return '';
    }
    
    // Build HTML table with 3 columns
    $html = '<table class="image-grid" cellpadding="0" cellspacing="0" border="0">';
    
    // Split images into rows of 3
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

function generateFooter() {
    return '<div class="footer">
        <p><strong>Car Inspection Expert System</strong></p>
        <p>This report contains complete inspection data from all 13 steps.</p>
        <p>Report generated on ' . date('Y-m-d H:i:s') . '</p>
    </div>';
}

function formatArray($value) {
    if (is_array($value)) {
        // Filter out empty values
        $filtered = array_filter($value, function($v) {
            return $v !== '' && $v !== null && $v !== false;
        });
        
        if (!empty($filtered)) {
            return implode(', ', $filtered);
        }
        return ''; // Return empty string if no valid values
    }
    return (string)$value;
}

function compressAllImages($data) {
    foreach ($data as $key => $value) {
        if (strpos($key, '_path') !== false && !empty($value)) {
            // Convert to absolute path
            $absolutePath = DirectoryManager::getAbsolutePath($value);
            
            if (file_exists($absolutePath)) {
                $data[$key] = ImageOptimizer::compressToFile($absolutePath, 1200, 65);
            }
        }
    }
    return $data;
}
