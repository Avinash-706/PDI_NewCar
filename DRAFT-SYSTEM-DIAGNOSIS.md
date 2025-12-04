# Draft System Diagnosis - Steps 7, 8, 9

## Current Situation

Looking at the draft file `draft_69316ce1746024.49125092.json`:
- **Current Step**: 7
- **Step 7 Data**: ✅ `fault_code_present: "Port Not Working"` is saved
- **Step 8 Data**: ❌ No fields saved
- **Step 9 Data**: ❌ No fields saved

## Analysis

### Why Steps 8 & 9 Are Not Saved

The draft system is working **correctly**. Here's why:

1. **User is on Step 7**: The draft shows `current_step: 7`, meaning the user hasn't progressed to Steps 8 and 9 yet.

2. **Radio Buttons Only Save When Selected**: The `saveDraft()` function in script.js only saves radio button values when they are checked:
   ```javascript
   if (input.type === 'radio') {
       if (input.checked) {  // Only saves if checked
           draftData.form_data[input.name] = input.value;
       }
   }
   ```

3. **This is Correct Behavior**: You don't want to save unselected radio buttons as that would create false data.

## Testing Steps

To verify the draft system is working for Steps 7, 8, 9:

### Test 1: Fill Step 7 Only
1. Fill Step 7 fields
2. Click "Save Draft"
3. **Expected**: Only Step 7 fields should be in draft ✅

### Test 2: Fill Steps 7, 8, 9
1. Fill Step 7 fields
2. Navigate to Step 8
3. Fill Step 8 fields (select radio buttons)
4. Click "Save Draft"
5. Navigate to Step 9
6. Fill Step 9 fields
7. Click "Save Draft"
8. **Expected**: All three steps' data should be in draft

### Test 3: Load Draft
1. Refresh page
2. Click "Load Draft"
3. **Expected**: All filled fields should restore correctly

## Potential Issues to Check

### Issue 1: Duplicate Field Names
If there are duplicate steps in the HTML, the same field name might appear multiple times, causing conflicts.

**Check**: Run this command to find duplicates:
```bash
Select-String -Path "index.php" -Pattern 'name="ac_turning_on"' | Measure-Object
```

**Expected**: Should be 2 (one for each radio option in Step 9)
**If More**: There are duplicate steps that need to be removed

### Issue 2: Form Not Capturing All Fields
The `saveDraft()` function queries all inputs:
```javascript
const inputs = form.querySelectorAll('input:not([type="file"]), select, textarea');
```

**Check**: Ensure all Step 8 and 9 fields are inside the form with `id="inspectionForm"`

### Issue 3: Field Names Don't Match Schema
**Check**: Verify field names in index.php match form-schema.php

## Step-by-Step Fields

### Step 7 Fields (2 fields)
- `fault_code_present` (radio) ✅
- `obd_scan_photo` (file) ✅

### Step 8 Fields (40 fields)
- `central_lock_working` (radio)
- `ignition_switch` (radio)
- `driver_front_indicator_elec` (radio)
- `passenger_front_indicator_elec` (radio)
- `driver_headlight_working` (radio)
- `passenger_headlight_working` (radio)
- `driver_headlight_highbeam` (radio)
- `passenger_headlight_highbeam` (radio)
- `front_number_plate_light` (radio)
- `driver_back_indicator_elec` (radio)
- `passenger_back_indicator_elec` (radio)
- `back_number_plate_light` (radio)
- `brake_light_driver` (radio)
- `brake_light_passenger` (radio)
- `driver_tail_light` (radio)
- `passenger_tail_light` (radio)
- `steering_wheel_condition` (radio)
- `steering_mountain_controls` (radio)
- `back_camera` (radio)
- `reverse_parking_sensor` (radio)
- `car_horn` (radio)
- `entertainment_system` (radio)
- `cruise_control` (radio)
- `interior_lights` (radio)
- `sun_roof` (radio)
- `bonnet_release_operation` (radio)
- `fuel_cap_release_operation` (radio)
- `adblue_level` (radio)
- `window_safety_lock` (radio)
- `driver_orvm_controls` (radio)
- `passenger_orvm_controls` (radio)
- `glove_box` (radio)
- `wiper` (radio)
- `rear_view_mirror` (radio)
- `dashboard_condition` (radio)
- `window_passenger_side` (radio)
- `seat_adjustment_passenger_rear` (radio)
- `check_all_buttons` (text)

### Step 9 Fields (9 fields)
- `ac_turning_on` (radio)
- `ac_cool_temperature` (text)
- `ac_hot_temperature` (text)
- `ac_video` (file)
- `ac_direction_mode` (radio)
- `defogger_front_vent` (radio)
- `defogger_rear_vent` (radio)
- `ac_all_vents` (radio)
- `ac_abnormal_vibration` (radio)

## Recommended Actions

1. **Test with Actual Data**: 
   - Fill out Steps 7, 8, and 9 completely
   - Save draft after each step
   - Check the draft JSON file to see if all fields are saved

2. **Check for Duplicates**:
   - Run the test file: `test-draft-steps-7-8-9.html`
   - Look for duplicate field names

3. **Enable Console Logging**:
   - Open browser console (F12)
   - Click "Save Draft"
   - Check the console log for "Draft data to save:"
   - Verify all fields are in the data object

4. **Check Network Tab**:
   - Open browser DevTools → Network tab
   - Click "Save Draft"
   - Check the request payload to see what's being sent
   - Check the response to see if save was successful

## Quick Fix If Issue Persists

If after filling Steps 8 and 9, the data still doesn't save, add this debug code to script.js before the fetch call:

```javascript
// Debug: Log all radio buttons
console.log('=== RADIO BUTTONS DEBUG ===');
const radios = form.querySelectorAll('input[type="radio"]');
radios.forEach(radio => {
    if (radio.checked) {
        console.log(`Checked: ${radio.name} = ${radio.value}`);
    }
});
console.log('=== END DEBUG ===');
```

This will show exactly which radio buttons are being detected as checked.

## Conclusion

Based on the current draft file, the system is working correctly - it's only saving Step 7 because that's the only step the user has filled. To verify Steps 8 and 9 work:

1. Navigate to Step 8
2. Fill all required fields
3. Save draft
4. Check if Step 8 fields appear in the draft JSON
5. Repeat for Step 9

If fields still don't save after being filled, then there's a bug that needs fixing.
