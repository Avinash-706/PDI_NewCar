# Step 8 Draft System Fix Plan

## Problem Identified ✅

**ROOT CAUSE**: Step 11 contains duplicate Electrical and Interior fields (from Step 8), causing the draft system to fail.

### Evidence:
- Step 8 fields appear correctly at lines 508-1004
- Same Step 8 fields appear AGAIN in Step 11 at lines ~1335-2132
- This creates duplicate field names, breaking the draft system

### Duplicate Count:
- `driver_headlight_highbeam`: 4 instances (should be 2)
- `cruise_control`: 8 instances (should be 4)
- `back_camera`: 6 instances (should be 3)
- And 35+ more duplicates

## Current Structure:

```
Step 7: OBD Scan (lines 472-506) ✅ CORRECT
Step 8: Electrical and Interior (lines 507-1004) ✅ CORRECT  
Step 9: Air Conditioning (lines 1005-1103) ✅ CORRECT
Step 10: Engine Before Test Drive (lines 1104-1282) ✅ CORRECT
Step 11: WARNING LIGHTS (lines 1283-2135) ❌ CONTAINS DUPLICATES
  - Lines 1283-1334: Correct Warning Lights content ✅
  - Lines 1335-2132: DUPLICATE Electrical & Interior ❌ REMOVE THIS
  - Lines 2133-2135: End of step
Step 13: WARNING LIGHTS (lines 2136-2262) ❌ DUPLICATE
Step 14: AIR CONDITIONING (lines 2263-2377) ❌ DUPLICATE
Step 15-26: Correct ✅
```

## Fix Required:

### Action 1: Remove Duplicate Content from Step 11
Remove lines ~1335-2132 (the duplicate Electrical and Interior content)

### Action 2: Remove Duplicate Step 13
Remove entire Step 13 (lines 2136-2262)

### Action 3: Remove Duplicate Step 14  
Remove entire Step 14 (lines 2263-2377)

### Action 4: Renumber Steps
- Current Step 15 → Step 12
- Current Step 16 → Step 13
- And so on...

## Expected Result:

After fix:
```
Step 7: OBD Scan ✅
Step 8: Electrical and Interior ✅
Step 9: Air Conditioning ✅
Step 10: Engine Before Test Drive ✅
Step 11: Warning Lights ✅ (cleaned)
Step 12: Tyres ✅ (renumbered from 15)
Step 13: Transmission & Clutch Pedal ✅ (renumbered from 16)
...
Step 23: Other Images ✅ (renumbered from 26)
```

## Verification Steps:

After fix, run these commands to verify:

```powershell
# Should return 2 for each field
(Select-String -Path "index.php" -Pattern 'name="cruise_control"').Matches.Count
(Select-String -Path "index.php" -Pattern 'name="back_camera"').Matches.Count
(Select-String -Path "index.php" -Pattern 'name="driver_headlight_highbeam"').Matches.Count
```

## Implementation:

I will now execute the fix automatically.
