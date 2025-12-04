# Draft System Issue - Root Cause and Fix

## Root Cause Identified ✅

The draft system is NOT saving Steps 8 and 9 data because there are **DUPLICATE STEPS** in index.php with the same field names.

### Evidence:
```
Field: ignition_switch
- Line 528, 531: Step 8 (correct)
- Line 1339, 1342: Step 12 (duplicate)

Field: ac_turning_on  
- Line 1013, 1016: Step 9 (correct)
- Line 2304, 2307: Step 14 (duplicate)
```

When the browser encounters duplicate field names, it only captures ONE value, causing data loss.

## The Problem

The file has duplicate steps from a previous session:
- **Step 7**: OBD Scan ✅ (CORRECT)
- **Step 8**: Electrical and Interior ✅ (CORRECT)
- **Step 9**: Air Conditioning ✅ (CORRECT)
- **Step 10**: Engine (Before Test Drive) ✅ (CORRECT)
- **Step 11**: Has wrong content (duplicate OBD/Electrical content) ❌
- **Step 12**: Duplicate Electrical and Interior ❌
- **Step 13**: Duplicate Warning Lights ❌
- **Step 14**: Duplicate Air Conditioning ❌

## The Fix

### Option 1: Manual Cleanup (Recommended)

1. **Backup the file first!**
   ```powershell
   Copy-Item index.php index.php.backup
   ```

2. **Remove duplicate Step 11** (around line 1283-1318)
   - Find: `<!-- STEP 11: WARNING LIGHTS -->` with OBD Scan content
   - Delete entire section until `<!-- STEP 12: ELECTRICAL AND INTERIOR -->`

3. **Remove duplicate Step 12** (around line 1319-2133)
   - Find: `<!-- STEP 12: ELECTRICAL AND INTERIOR -->`
   - Delete entire section until `<!-- STEP 13: WARNING LIGHTS -->`

4. **Renumber Step 13 to Step 11**
   - Find: `<div class="form-step" data-step="13">`
   - Change to: `<div class="form-step" data-step="11">`
   - Update comment: `<!-- STEP 11: WARNING LIGHTS -->`

5. **Remove duplicate Step 14** (around line 2297)
   - Find: `<!-- STEP 14: AIR CONDITIONING -->`
   - Delete entire section until next step

6. **Renumber all subsequent steps**
   - Current Step 15 → Step 12
   - Current Step 16 → Step 13
   - And so on...

### Option 2: Automated Script

I can create a PowerShell script to do this automatically, but manual is safer.

## After Cleanup

Once duplicates are removed:

1. **Update script.js**
   ```javascript
   const totalSteps = 23; // Update from 26
   ```

2. **Test the draft system**
   - Fill Steps 7, 8, 9
   - Save draft
   - Check draft JSON file
   - All fields should now be saved

3. **Verify no duplicates**
   ```powershell
   Select-String -Path "index.php" -Pattern 'name="ignition_switch"' | Measure-Object
   ```
   Should return: 2 (one for each radio option)

## Quick Test

After cleanup, run this to verify:
```powershell
# Should return 2 (one for each radio button option)
(Select-String -Path "index.php" -Pattern 'name="ac_turning_on"').Count

# Should return 2
(Select-String -Path "index.php" -Pattern 'name="ignition_switch"').Count

# Should return 2  
(Select-String -Path "index.php" -Pattern 'name="central_lock_working"').Count
```

## Why This Happened

From the context transfer, a previous session made changes that created duplicate steps. The new Steps 7, 8, 9 were added correctly, but old duplicate steps weren't removed.

## Immediate Workaround

Until cleanup is done, you can test Steps 7, 8, 9 by:
1. Commenting out the duplicate steps in HTML
2. Or using browser DevTools to delete duplicate form elements
3. Then test draft save/load

## Need Help?

If you want me to attempt automated cleanup, I can:
1. Read the entire index.php
2. Identify all duplicate sections
3. Create a clean version
4. Save as index-clean.php for you to review

Just let me know!
