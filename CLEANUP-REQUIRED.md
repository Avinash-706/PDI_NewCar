# Cleanup Required for index.php

## Issue
The index.php file has duplicate steps from previous session changes. The file currently has:
- Step 7: OBD Scan ✅ (CORRECT)
- Step 8: Electrical and Interior ✅ (CORRECT)
- Step 9: Air Conditioning ✅ (CORRECT)
- Step 10: Engine (Before Test Drive) ✅ (CORRECT)
- Step 11: WARNING LIGHTS (has old OBD Scan content) ❌ (WRONG CONTENT)
- Step 12: ELECTRICAL AND INTERIOR (duplicate) ❌ (DUPLICATE)
- Step 13: WARNING LIGHTS (duplicate) ❌ (DUPLICATE)
- Step 14: AIR CONDITIONING (duplicate) ❌ (DUPLICATE)
- Steps 15-26: Correct

## Required Actions

### Option 1: Manual Cleanup (Recommended)
1. Delete the entire Step 11 section (lines ~1284-1317) - the one with "Any Fault Codes Present?"
2. Delete the entire Step 12 section (lines ~1319-2168) - the duplicate Electrical and Interior
3. Renumber Step 13 to Step 11 (Warning Lights)
4. Delete Step 14 (duplicate Air Conditioning around line 2297)
5. Renumber all subsequent steps (current 15→12, 16→13, etc.)

### Option 2: Restore from Backup
If you have a backup before the previous session changes, restore it and reapply only the Steps 7, 8, 9 changes.

## Correct Final Structure Should Be:
1. Car Inspection
2. Payment Taking
3. Expert Details
4. Car Images
5. Car Details
6. Exterior Body
7. OBD Scan (NEW)
8. Electrical and Interior (NEW)
9. Air Conditioning (NEW)
10. Engine (Before Test Drive)
11. Warning Lights
12. Tyres
13. Transmission & Clutch Pedal
14. Axle
15. Engine (After Test Drive)
16. Brakes
17. Suspension
18. Brakes & Steering (Test Drive)
19. Underbody
20. Equipments
21. Final Car Result
22. Car Images From All Directions
23. Other Images

Total: 23 steps (not 26)

## Files That Need Updating After Cleanup:
1. **script.js** - Update `totalSteps` variable to 23
2. **form-schema.php** - Remove duplicate step definitions
3. **generate-pdf.php** - Remove duplicate step PDF generation

## Current Status:
✅ Steps 7, 8, 9 have been successfully updated with new content
❌ Duplicate steps from previous session need to be removed
❌ Step numbering needs to be corrected

## Recommendation:
The cleanest approach is to:
1. Keep Steps 1-10 as they are now (with new 7, 8, 9)
2. Remove the duplicate Steps 11-14
3. Renumber the remaining steps correctly
4. Update totalSteps in script.js to the correct number

Would you like me to attempt an automated cleanup, or would you prefer to manually review and clean up the duplicates?
