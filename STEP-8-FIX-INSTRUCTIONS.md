# Step 8 Draft System - Fix Instructions

## Problem Summary

✅ **ROOT CAUSE IDENTIFIED**: Step 11 contains duplicate Electrical and Interior fields from Step 8, causing the draft system to fail for Step 8.

### Evidence:
- All 38 Step 8 fields have duplicates (2-8 instances each instead of 2)
- Duplicates are located in Step 11 (lines ~1316-2132)
- This prevents the draft system from saving Step 8 data correctly

## Quick Fix (Recommended)

### Option 1: Manual Edit in IDE

1. **Backup first!**
   ```powershell
   Copy-Item index.php index.php.backup
   ```

2. **Open index.php and find line 1316** (search for "Driver - Front Indicator" in Step 11)

3. **Delete everything from line ~1316 to line ~2132** (the duplicate Electrical content)
   - Start: `<div class="form-group">` with "Driver - Front Indicator"
   - End: `</div>` after "check_all_buttons" textarea (just before "<!-- STEP 13")

4. **Add the correct Warning Lights content** (copy from Step 13, lines 2143-2259)
   - Copy all fields from Step 13: ABS Sensor, Airbag Sensor, Battery Problem, etc.
   - Paste after "Power Steering Problem" field in Step 11

5. **Delete entire Step 13** (lines ~2136-2262)

6. **Delete entire Step 14** (lines ~2263-2377)

7. **Save and test**

### Option 2: Automated Fix (I can do this)

I can read the entire file, remove duplicates programmatically, and save a clean version. Would you like me to do this?

## Verification

After fix, run these commands to verify no duplicates:

```powershell
# Each should return the correct count
(Select-String -Path "index.php" -Pattern 'name="cruise_control"').Matches.Count  # Should be 4
(Select-String -Path "index.php" -Pattern 'name="back_camera"').Matches.Count  # Should be 3
(Select-String -Path "index.php" -Pattern 'name="driver_headlight_highbeam"').Matches.Count  # Should be 2
(Select-String -Path "index.php" -Pattern 'name="central_lock_working"').Matches.Count  # Should be 2
```

## Expected Result

After fix:
- Step 8 fields appear only once (in Step 8)
- Step 11 has complete Warning Lights content (10 fields)
- No duplicate Steps 13 or 14
- Draft system will save all Step 8 fields correctly

## Test Plan

1. Open the form in browser
2. Navigate to Step 8
3. Fill all 38 fields
4. Click "Save Draft"
5. Check draft JSON file - all Step 8 fields should be present
6. Reload page and "Load Draft" - all fields should restore

## Alternative: Let Me Fix It

If you want me to fix it automatically, I can:
1. Read the entire index.php
2. Remove duplicate sections programmatically
3. Save as index-fixed.php for you to review
4. You can then rename it to index.php

**Would you like me to proceed with the automated fix?**

Just say "Yes, fix it automatically" and I'll do it safely.
