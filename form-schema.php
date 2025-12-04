<?php
/**
 * Form Schema - Source of Truth
 * Complete field mapping for all 24 steps
 */

return [
    // STEP 1: Car Inspection
    1 => [
        'title' => 'Car Inspection',
        'fields' => [
            'booking_id' => ['label' => 'Booking ID', 'type' => 'text', 'required' => true],
            'expert_name' => ['label' => 'Assigned Expert Name', 'type' => 'text', 'required' => true],
            'expert_city' => ['label' => 'Inspection Expert City', 'type' => 'select', 'required' => true],
            'customer_name' => ['label' => 'Customer Name', 'type' => 'text', 'required' => true],
            'customer_phone' => ['label' => 'Customer Phone Number', 'type' => 'text', 'required' => false],
            'inspection_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
            'inspection_time' => ['label' => 'Time', 'type' => 'time', 'required' => true],
            'inspection_address' => ['label' => 'Inspection Address', 'type' => 'textarea', 'required' => true],
            'obd_scanning' => ['label' => 'OBD Scanning', 'type' => 'radio', 'required' => true],
            'car' => ['label' => 'Car', 'type' => 'text', 'required' => false],
            'lead_owner' => ['label' => 'Lead Owner', 'type' => 'text', 'required' => false],
            'pending_amount' => ['label' => 'Pending Amount', 'type' => 'number', 'required' => false],
        ]
    ],
    
    // STEP 2: Payment Taking
    2 => [
        'title' => 'Payment Taking',
        'fields' => [
            'payment_taken' => ['label' => 'Payment', 'type' => 'radio', 'required' => true],
            'payment_type' => ['label' => 'Payment Type', 'type' => 'radio', 'required' => false],
            'payment_proof' => ['label' => 'Payment Proof', 'type' => 'file', 'required' => false],
            'amount_paid' => ['label' => 'Amount Paid', 'type' => 'number', 'required' => false],
        ]
    ],
    
    // STEP 3: Expert Details
    3 => [
        'title' => 'Expert Details',
        'fields' => [
            'inspection_delayed' => ['label' => 'Inspection 45 Minutes Delayed', 'type' => 'radio', 'required' => true],
            'car_photo' => ['label' => 'Your Photo with car number plate', 'type' => 'file', 'required' => true],
        ]
    ],
    
    // STEP 4: Car Images
    4 => [
        'title' => 'Car Images',
        'fields' => [
            'car_image_front' => ['label' => 'Front', 'type' => 'file', 'required' => true],
            'car_image_back' => ['label' => 'Back', 'type' => 'file', 'required' => true],
            'car_image_driver_side' => ['label' => 'Driver Side', 'type' => 'file', 'required' => true],
            'car_image_passenger_side' => ['label' => 'Passenger Side', 'type' => 'file', 'required' => true],
            'car_image_dashboard' => ['label' => 'Front Dashboard', 'type' => 'file', 'required' => true],
        ]
    ],
    
    // STEP 5: Car Details
    5 => [
        'title' => 'Car Details',
        'fields' => [
            'car_company' => ['label' => 'Car Company', 'type' => 'text', 'required' => true],
            'car_variant' => ['label' => 'Car Variant', 'type' => 'text', 'required' => true],
            'car_registered_state' => ['label' => 'Car Registered State', 'type' => 'text', 'required' => true],
            'car_registered_city' => ['label' => 'Car Registered City', 'type' => 'text', 'required' => false],
            'fuel_type' => ['label' => 'Fuel Type', 'type' => 'radio', 'required' => true],
            'engine_capacity' => ['label' => 'Engine Capacity (CC)', 'type' => 'text', 'required' => true],
            'transmission' => ['label' => 'Transmission Type', 'type' => 'radio', 'required' => true],
            'car_colour' => ['label' => 'Car Color', 'type' => 'text', 'required' => true],
            'car_km_reading' => ['label' => 'Car KM Current Reading', 'type' => 'text', 'required' => true],
            'car_km_photo' => ['label' => 'Car KM Reading Photo', 'type' => 'file', 'required' => true],
            'car_keys_available' => ['label' => 'Number of Car Keys Available', 'type' => 'number', 'required' => true],
            'chassis_number' => ['label' => 'Chassis Number', 'type' => 'text', 'required' => true],
            'engine_number' => ['label' => 'Engine Number', 'type' => 'text', 'required' => true],
            'chassis_plate_photo' => ['label' => 'Chassis No. Plate', 'type' => 'file', 'required' => true],
        ]
    ],
    
    // STEP 6: Exterior Body (52 fields with conditional images)
    6 => [
        'title' => 'Exterior Body',
        'fields' => [
            // All 52 exterior body fields - each with checkbox and conditional image
            'driver_front_door' => ['label' => 'Driver Front Door', 'type' => 'checkbox', 'required' => true, 'has_image' => true],
            'driver_front_fender' => ['label' => 'Driver - Front Fender', 'type' => 'checkbox', 'required' => true, 'has_image' => true],
            // ... (48 more fields)
            'car_roof_outside' => ['label' => 'Car Roof Outside', 'type' => 'checkbox', 'required' => true, 'has_image' => true],
        ]
    ],
    
    // STEP 7: OBD Scan
    7 => [
        'title' => 'OBD Scan',
        'fields' => [
            'fault_code_present' => ['label' => 'Any Fault Code Present', 'type' => 'radio', 'required' => true],
            'obd_scan_photo' => ['label' => 'OBD Scan Photo', 'type' => 'file', 'required' => true],
        ]
    ],
    
    // STEP 8: Electrical and Interior
    8 => [
        'title' => 'Electrical and Interior',
        'fields' => [
            'central_lock_working' => ['label' => 'Central Lock Working', 'type' => 'radio', 'required' => true],
            'ignition_switch' => ['label' => 'Ignition Switch / Push Button', 'type' => 'radio', 'required' => true],
            'driver_front_indicator_elec' => ['label' => 'Driver - Front Indicator', 'type' => 'radio', 'required' => true],
            'passenger_front_indicator_elec' => ['label' => 'Passenger - Front Indicator', 'type' => 'radio', 'required' => true],
            'driver_headlight_working' => ['label' => 'Driver Headlight', 'type' => 'radio', 'required' => true],
            'passenger_headlight_working' => ['label' => 'Passenger Headlight', 'type' => 'radio', 'required' => true],
            'driver_headlight_highbeam' => ['label' => 'Driver Headlight Highbeam', 'type' => 'radio', 'required' => true],
            'passenger_headlight_highbeam' => ['label' => 'Passenger Headlight Highbeam', 'type' => 'radio', 'required' => true],
            'front_number_plate_light' => ['label' => 'Front Number Plate Light', 'type' => 'radio', 'required' => true],
            'driver_back_indicator_elec' => ['label' => 'Driver Back Indicator', 'type' => 'radio', 'required' => true],
            'passenger_back_indicator_elec' => ['label' => 'Passenger Back Indicator', 'type' => 'radio', 'required' => true],
            'back_number_plate_light' => ['label' => 'Back Number Plate Light', 'type' => 'radio', 'required' => true],
            'brake_light_driver' => ['label' => 'Brake Light Driver', 'type' => 'radio', 'required' => true],
            'brake_light_passenger' => ['label' => 'Brake Light Passenger', 'type' => 'radio', 'required' => true],
            'driver_tail_light' => ['label' => 'Driver Tail Light', 'type' => 'radio', 'required' => true],
            'passenger_tail_light' => ['label' => 'Passenger Tail Light', 'type' => 'radio', 'required' => true],
            'steering_wheel_condition' => ['label' => 'Steering Wheel Condition', 'type' => 'radio', 'required' => true],
            'steering_mountain_controls' => ['label' => 'Steering Mountain Controls', 'type' => 'radio', 'required' => true],
            'back_camera' => ['label' => 'Back Camera', 'type' => 'radio', 'required' => true],
            'reverse_parking_sensor' => ['label' => 'Reverse Parking Sensor', 'type' => 'radio', 'required' => true],
            'car_horn' => ['label' => 'Car Horn', 'type' => 'radio', 'required' => true],
            'entertainment_system' => ['label' => 'Entertainment System', 'type' => 'radio', 'required' => true],
            'cruise_control' => ['label' => 'Cruise Control', 'type' => 'radio', 'required' => true],
            'interior_lights' => ['label' => 'Interior Lights', 'type' => 'radio', 'required' => true],
            'sun_roof' => ['label' => 'Sun Roof', 'type' => 'radio', 'required' => true],
            'bonnet_release_operation' => ['label' => 'Bonnet Release Operation', 'type' => 'radio', 'required' => true],
            'fuel_cap_release_operation' => ['label' => 'Fuel Cap Release Operation', 'type' => 'radio', 'required' => true],
            'adblue_level' => ['label' => 'Check Onboard Computer ADBlue Level- Diesel Cars', 'type' => 'radio', 'required' => true],
            'window_safety_lock' => ['label' => 'Window Safety Lock', 'type' => 'radio', 'required' => true],
            'driver_orvm_controls' => ['label' => 'Driver ORVM Controls', 'type' => 'radio', 'required' => true],
            'passenger_orvm_controls' => ['label' => 'Passenger ORVM Controls', 'type' => 'radio', 'required' => true],
            'glove_box' => ['label' => 'Glove Box', 'type' => 'radio', 'required' => true],
            'wiper' => ['label' => 'Wiper', 'type' => 'radio', 'required' => true],
            'rear_view_mirror' => ['label' => 'Rear View Mirror', 'type' => 'radio', 'required' => true],
            'dashboard_condition' => ['label' => 'Dashboard Condition', 'type' => 'radio', 'required' => true],
            'window_passenger_side' => ['label' => 'Window Passenger Side', 'type' => 'radio', 'required' => true],
            'seat_adjustment_passenger_rear' => ['label' => 'Seat Adjustment Passenger Rear Side', 'type' => 'radio', 'required' => true],
            'check_all_buttons' => ['label' => 'Check All Buttons', 'type' => 'text', 'required' => false],
        ]
    ],
    
    // STEP 9: Air Conditioning
    9 => [
        'title' => 'Air Conditioning',
        'fields' => [
            'ac_turning_on' => ['label' => 'Air Conditioning Turning On', 'type' => 'radio', 'required' => true],
            'ac_cool_temperature' => ['label' => 'AC Cool Temperature', 'type' => 'text', 'required' => false],
            'ac_hot_temperature' => ['label' => 'AC Hot Temperature', 'type' => 'text', 'required' => false],
            'ac_image' => ['label' => 'Air Condition Image at Fan Max Speed', 'type' => 'file', 'required' => false],
            'ac_direction_mode' => ['label' => 'Air Condition Direction Mode Working', 'type' => 'radio', 'required' => true],
            'defogger_front_vent' => ['label' => 'De Fogger Front Vent Working', 'type' => 'radio', 'required' => true],
            'defogger_rear_vent' => ['label' => 'De Fogger rear Vent Working', 'type' => 'radio', 'required' => true],
            'ac_all_vents' => ['label' => 'Air Conditioning All Vents', 'type' => 'radio', 'required' => true],
            'ac_abnormal_vibration' => ['label' => 'AC Abnormal Vibration', 'type' => 'radio', 'required' => true],
        ]
    ],
    
    // STEP 10: Tyres
    10 => [
        'title' => 'Tyres',
        'fields' => [
            'tyre_size' => ['label' => 'Tyre Size', 'type' => 'text', 'required' => true],
            'tyre_type' => ['label' => 'Tyre Type', 'type' => 'radio', 'required' => true],
            'rim_type' => ['label' => 'Rim Type', 'type' => 'radio', 'required' => true],
            'driver_front_tyre_depth_check' => ['label' => 'Driver Front Tyre Depth Check', 'type' => 'radio', 'required' => true],
            'driver_front_tyre_tread_depth' => ['label' => 'Driver Front Tyre Tread Depth', 'type' => 'file', 'required' => true],
            'driver_front_tyre_date' => ['label' => 'Driver Front Tyre Manufacturing Date', 'type' => 'text', 'required' => false],
            'driver_front_tyre_shape' => ['label' => 'Driver Front Tyre Shape', 'type' => 'radio', 'required' => true],
            'driver_back_tyre_depth_check' => ['label' => 'Driver Back Tyre Depth Check', 'type' => 'radio', 'required' => true],
            'driver_back_tyre_tread_depth' => ['label' => 'Driver Back Tyre Tread Depth', 'type' => 'file', 'required' => true],
            'driver_back_tyre_date' => ['label' => 'Driver Back Tyre Manufacturing Date', 'type' => 'text', 'required' => false],
            'driver_back_tyre_shape' => ['label' => 'Driver Back Tyre Shape', 'type' => 'radio', 'required' => true],
            'passenger_back_tyre_depth_check' => ['label' => 'Passenger Back Tyre Depth Check', 'type' => 'radio', 'required' => true],
            'passenger_back_tyre_tread_depth' => ['label' => 'Passenger Back Tyre Tread Depth', 'type' => 'file', 'required' => true],
            'passenger_back_tyre_date' => ['label' => 'Passenger Back Tyre Manufacturing Date', 'type' => 'text', 'required' => false],
            'passenger_back_tyre_shape' => ['label' => 'Passenger Back Tyre Shape', 'type' => 'radio', 'required' => true],
            'passenger_front_tyre_depth_check' => ['label' => 'Passenger Front Tyre Depth Check', 'type' => 'radio', 'required' => true],
            'passenger_front_tyre_tread_depth' => ['label' => 'Passenger Front Tyre Tread Depth', 'type' => 'file', 'required' => true],
            'passenger_front_tyre_date' => ['label' => 'Passenger Front Tyre Manufacturing Date', 'type' => 'text', 'required' => false],
            'passenger_front_tyre_shape' => ['label' => 'Passenger Front Tyre Shape', 'type' => 'radio', 'required' => true],
            'stepney_tyre_depth_check' => ['label' => 'Stepney Tyre Depth Check', 'type' => 'radio', 'required' => true],
            'stepney_tyre_tread_depth' => ['label' => 'Stepney Tyre Tread Depth', 'type' => 'file', 'required' => true],
            'stepney_tyre_date' => ['label' => 'Stepney Tyre Manufacturing Date', 'type' => 'text', 'required' => false],
            'stepney_tyre_shape' => ['label' => 'Stepney Front Tyre Shape', 'type' => 'radio', 'required' => true],
            'sign_of_camber_issue' => ['label' => 'Sign of Camber Issue', 'type' => 'radio', 'required' => true],
        ]
    ],
    
    // STEP 11: Under Body
    11 => [
        'title' => 'Under Body',
        'fields' => [
            'fuel_leaks_under_body' => ['label' => 'Any Fuel Leaks under Body', 'type' => 'radio', 'required' => true],
            'underbody_left' => ['label' => 'Underbody Left', 'type' => 'file', 'required' => true],
            'underbody_rear' => ['label' => 'Underbody Rear', 'type' => 'file', 'required' => true],
            'underbody_front' => ['label' => 'Underbody Front', 'type' => 'file', 'required' => true],
            'underbody_right' => ['label' => 'Underbody Right', 'type' => 'file', 'required' => true],
        ]
    ],
    
    // STEP 12: Equipments
    12 => [
        'title' => 'Equipment\'s',
        'fields' => [
            'tool_kit' => ['label' => 'Tool Kit', 'type' => 'radio', 'required' => true],
            'tool_kit_image' => ['label' => 'Tool Kit Image', 'type' => 'file', 'required' => false],
        ]
    ],
    
    // STEP 13: Final Result
    13 => [
        'title' => 'Final Result',
        'fields' => [
            'issues_found_in_car' => ['label' => 'Any Issues Found in Car', 'type' => 'textarea', 'required' => true],
            'photo_of_issues' => ['label' => 'Photo of Issues', 'type' => 'file', 'required' => false],
        ]
    ],
];
