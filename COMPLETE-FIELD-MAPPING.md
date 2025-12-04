# Complete Field Mapping - Steps 1-5

## Step 1: Car Inspection

### Fields (11 total):
| Field Name | Type | Required | Notes |
|------------|------|----------|-------|
| `booking_id` | text | ✅ Yes | |
| `expert_name` | text | ✅ Yes | NEW - was `expert_id` |
| `expert_city` | select | ✅ Yes | NEW |
| `customer_name` | text | ✅ Yes | Now required |
| `customer_phone` | tel | ❌ No | |
| `inspection_date` | date | ✅ Yes | Now required |
| `inspection_time` | time | ✅ Yes | Now required |
| `inspection_address` | textarea | ✅ Yes | Now required |
| `obd_scanning` | radio | ✅ Yes | Now required |
| `car` | text | ❌ No | |
| `lead_owner` | text | ❌ No | |
| `pending_amount` | number | ❌ No | |

### Changes from Original:
- ❌ REMOVED: `expert_id`
- ✅ ADDED: `expert_name` (required)
- ✅ ADDED: `expert_city` (required, dropdown)
- ⚠️ CHANGED: `customer_name` - now required
- ⚠️ CHANGED: `inspection_date` - now required
- ⚠️ CHANGED: `inspection_time` - now required
- ⚠️ CHANGED: `inspection_address` - now required
- ⚠️ CHANGED: `obd_scanning` - now required

---

## Step 2: Payment Taking

### Fields (4 total):
| Field Name | Type | Required | Notes |
|------------|------|----------|-------|
| `payment_taken` | radio | ✅ Yes | Yes/No |
| `payment_type` | radio | ⚠️ Conditional | Required if payment_taken=Yes |
| `payment_proof` | file | ⚠️ Conditional | Required if payment_type=Online |
| `amount_paid` | number | ⚠️ Conditional | Required if payment_taken=Yes |

### Dynamic Behavior:
- If `payment_taken` = "No": No other fields required
- If `payment_taken` = "Yes": `payment_type` and `amount_paid` required
- If `payment_type` = "Online": `payment_proof` also required
- If `payment_type` = "Cash": Only `amount_paid` required

### Changes from Original:
- ✅ NEW STEP - Payment moved from Step 23 to Step 2
- ✅ ADDED: `payment_type` (Online/Cash)
- ✅ ADDED: `payment_proof` (image upload for online)
- ✅ ADDED: `amount_paid` (number field)

---

## Step 3: Expert Details

### Fields (2 total):
| Field Name | Type | Required | Notes |
|------------|------|----------|-------|
| `inspection_delayed` | radio | ✅ Yes | Yes/No |
| `car_photo` | file | ✅ Yes | Photo with car number plate |

### Changes from Original:
- ❌ REMOVED: `latitude` (location fields)
- ❌ REMOVED: `longitude` (location fields)
- ❌ REMOVED: `location_address` (location fields)
- ❌ REMOVED: `expert_date` (auto-filled fields)
- ❌ REMOVED: `expert_time` (auto-filled fields)
- ⚠️ SIMPLIFIED: Only 2 fields remain

---

## Step 4: Car Images

### Fields (5 total - ALL NEW):
| Field Name | Type | Required | Notes |
|------------|------|----------|-------|
| `car_image_front` | file | ✅ Yes | Front view |
| `car_image_back` | file | ✅ Yes | Back view |
| `car_image_driver_side` | file | ✅ Yes | Driver side view |
| `car_image_passenger_side` | file | ✅ Yes | Passenger side view |
| `car_image_dashboard` | file | ✅ Yes | Dashboard view |

### Changes from Original:
- ✅ COMPLETELY NEW STEP
- ✅ 5 new image upload fields

---

## Step 5: Car Details

### Fields (14 total):
| Field Name | Type | Required | Notes |
|------------|------|----------|-------|
| `car_company` | text | ✅ Yes | |
| `car_variant` | text | ✅ Yes | |
| `car_registered_state` | text | ✅ Yes | |
| `car_registered_city` | text | ❌ No | |
| `fuel_type` | radio | ✅ Yes | CHANGED from checkbox |
| `engine_capacity` | text | ✅ Yes | CHANGED from number |
| `transmission` | radio | ✅ Yes | |
| `car_colour` | text | ✅ Yes | |
| `car_km_reading` | text | ✅ Yes | CHANGED from number |
| `car_km_photo` | file | ✅ Yes | |
| `car_keys_available` | number | ✅ Yes | |
| `chassis_number` | text | ✅ Yes | |
| `engine_number` | text | ✅ Yes | |
| `chassis_plate_photo` | file | ✅ Yes | |

### Changes from Original:
- ❌ REMOVED: `car_registration_number`
- ❌ REMOVED: `car_registration_year`
- ⚠️ CHANGED: `fuel_type` - from checkbox[] to radio (single selection)
- ⚠️ CHANGED: `engine_capacity` - from number to text
- ⚠️ CHANGED: `car_km_reading` - from number to text

---

## Summary of All Changes

### New Fields (Total: 9)
1. `expert_name` (Step 1)
2. `expert_city` (Step 1)
3. `payment_taken` (Step 2)
4. `payment_type` (Step 2)
5. `payment_proof` (Step 2)
6. `amount_paid` (Step 2)
7. `car_image_front` (Step 4)
8. `car_image_back` (Step 4)
9. `car_image_driver_side` (Step 4)
10. `car_image_passenger_side` (Step 4)
11. `car_image_dashboard` (Step 4)

### Removed Fields (Total: 9)
1. `expert_id` (Step 1)
2. `latitude` (Step 3)
3. `longitude` (Step 3)
4. `location_address` (Step 3)
5. `expert_date` (Step 3)
6. `expert_time` (Step 3)
7. `car_registration_number` (Step 5)
8. `car_registration_year` (Step 5)

### Changed Fields (Total: 7)
1. `customer_name` - now required
2. `inspection_date` - now required
3. `inspection_time` - now required
4. `inspection_address` - now required
5. `obd_scanning` - now required
6. `fuel_type` - checkbox[] → radio (single value)
7. `engine_capacity` - number → text
8. `car_km_reading` - number → text

---

## Data Type Changes for Backend

### Fuel Type
```php
// OLD: Array of strings
$fuel_type = ['Petrol', 'CNG'];  // Multiple selections

// NEW: Single string
$fuel_type = 'Petrol';  // Single selection
```

### Engine Capacity
```php
// OLD: Integer/Float
$engine_capacity = 1500;

// NEW: String
$engine_capacity = '1500';
```

### Car KM Reading
```php
// OLD: Integer
$car_km_reading = 45000;

// NEW: String
$car_km_reading = '45000';
```

---

## PDF Generation Updates Needed

### Step 1 Section:
```php
// ADD these fields:
$html .= generateField('Assigned Expert Name', $data['expert_name'] ?? '', true);
$html .= generateField('Inspection Expert City', $data['expert_city'] ?? '', true);

// REMOVE this field:
// $html .= generateField('Expert ID', $data['expert_id'] ?? '', false);

// UPDATE these to required:
$html .= generateField('Customer Name', $data['customer_name'] ?? '', true);
$html .= generateField('Date', $data['inspection_date'] ?? '', true);
$html .= generateField('Time', $data['inspection_time'] ?? '', true);
$html .= generateField('Inspection Address', $data['inspection_address'] ?? '', true);
$html .= generateField('OBD Scanning', $data['obd_scanning'] ?? '', true);
```

### Step 2 Section (NEW):
```php
$html .= generateStepHeader(2, 'Payment Taking');
$html .= generateField('Payment Taken', $data['payment_taken'] ?? '', true);

if (($data['payment_taken'] ?? '') === 'Yes') {
    $html .= generateField('Payment Type', $data['payment_type'] ?? '', true);
    $html .= generateField('Amount Paid', $data['amount_paid'] ?? '', true);
    
    if (($data['payment_type'] ?? '') === 'Online') {
        $images = [];
        $images[] = generateImage('Payment Proof', $data['payment_proof_path'] ?? '', true);
        $html .= generateImageGrid($images);
    }
}
```

### Step 3 Section:
```php
// REMOVE location fields:
// $html .= generateField('Latitude', $data['latitude'] ?? '', true);
// $html .= generateField('Longitude', $data['longitude'] ?? '', true);
// $html .= generateField('Full Location Address', $data['location_address'] ?? '', true);
// $html .= generateField('Date', $data['expert_date'] ?? '', false);
// $html .= generateField('Time', $data['expert_time'] ?? '', false);

// KEEP only:
$html .= generateField('Inspection 45 Minutes Delayed?', $data['inspection_delayed'] ?? '', true);
$images = [];
$images[] = generateImage('Your photo with car number plate', $data['car_photo_path'] ?? '', true);
$html .= generateImageGrid($images);
```

### Step 4 Section (NEW):
```php
$html .= generateStepHeader(4, 'Car Images');
$images = [];
$images[] = generateImage('Front', $data['car_image_front_path'] ?? '', true);
$images[] = generateImage('Back', $data['car_image_back_path'] ?? '', true);
$images[] = generateImage('Driver Side', $data['car_image_driver_side_path'] ?? '', true);
$images[] = generateImage('Passenger Side', $data['car_image_passenger_side_path'] ?? '', true);
$images[] = generateImage('Front Dashboard', $data['car_image_dashboard_path'] ?? '', true);
$html .= generateImageGrid($images);
```

### Step 5 Section:
```php
// REMOVE these fields:
// $html .= generateField('Car Registration Number', $data['car_registration_number'] ?? '', true);
// $html .= generateField('Car Registration Year (YYYY)', $data['car_registration_year'] ?? '', true);

// UPDATE fuel_type handling (now single value, not array):
$html .= generateField('Fuel Type', $data['fuel_type'] ?? '', true);  // No formatArray()

// All other fields remain the same
```

---

## Draft System Updates Needed

### save-draft.php
- ✅ Already handles all field types dynamically
- ✅ Already handles file uploads
- ⚠️ Ensure `fuel_type` is saved as single value (not array)

### load-draft.php
- ✅ Already handles all field types dynamically
- ✅ Already handles file paths
- ⚠️ Ensure `fuel_type` is loaded as single value

---

## Form Validation Updates

### JavaScript (script.js)
- ✅ Already updated - no Step 2 location validation
- ✅ Already updated - no Step 4 year validation
- ⚠️ May need to add Step 2 payment validation

### Suggested Payment Validation:
```javascript
// Special validation for Step 2 (Payment)
if (step === 2) {
    const paymentTaken = document.querySelector('input[name="payment_taken"]:checked');
    if (paymentTaken && paymentTaken.value === 'Yes') {
        const paymentType = document.querySelector('input[name="payment_type"]:checked');
        if (!paymentType) {
            alert('Please select payment type');
            return false;
        }
        
        const amountPaid = document.querySelector('input[name="amount_paid"]').value;
        if (!amountPaid) {
            alert('Please enter amount paid');
            return false;
        }
        
        if (paymentType.value === 'Online') {
            const paymentProof = document.querySelector('input[name="payment_proof"]');
            if (!paymentProof.files || paymentProof.files.length === 0) {
                if (!paymentProof.dataset.savedFile) {
                    alert('Please upload payment proof for online payment');
                    return false;
                }
            }
        }
    }
}
```

---

## Testing Checklist

### Step 1:
- [ ] All 11 fields display correctly
- [ ] Expert City dropdown works
- [ ] Required validation works
- [ ] Draft save includes all fields
- [ ] PDF shows all fields

### Step 2:
- [ ] Payment Yes/No toggle works
- [ ] Payment Type toggle works
- [ ] QR code displays for Online
- [ ] Payment Proof upload works
- [ ] Amount Paid validation works
- [ ] Draft save includes payment data
- [ ] PDF shows payment section

### Step 3:
- [ ] Only 2 fields display
- [ ] No location fields
- [ ] Car photo upload works
- [ ] Draft save works
- [ ] PDF shows correct fields

### Step 4:
- [ ] All 5 image uploads display
- [ ] All uploads work
- [ ] Draft save includes all images
- [ ] PDF shows all 5 images

### Step 5:
- [ ] All 14 fields display
- [ ] Fuel Type is radio (single selection)
- [ ] Engine Capacity accepts text
- [ ] Car KM Reading accepts text
- [ ] Draft save works
- [ ] PDF shows all fields correctly
- [ ] Fuel type shows as single value (not array)
