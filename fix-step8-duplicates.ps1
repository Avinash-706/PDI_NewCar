# PowerShell Script to Fix Step 8 Duplicate Issues
# This script removes duplicate Electrical and Interior content from Step 11

Write-Host "=== Step 8 Draft System Fix Script ===" -ForegroundColor Cyan
Write-Host ""

# Backup the file first
Write-Host "1. Creating backup..." -ForegroundColor Yellow
Copy-Item "index.php" "index.php.backup-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
Write-Host "   Backup created successfully!" -ForegroundColor Green
Write-Host ""

# Read the file
Write-Host "2. Reading index.php..." -ForegroundColor Yellow
$content = Get-Content "index.php" -Raw
Write-Host "   File size: $($content.Length) characters" -ForegroundColor Green
Write-Host ""

# Define the patterns to find and remove
Write-Host "3. Identifying duplicate sections..." -ForegroundColor Yellow

# Pattern 1: Find Step 11 start
$step11Start = '            <!-- STEP 11: WARNING LIGHTS -->'

# Pattern 2: Find where duplicate Electrical content starts (after Power Steering Problem)
$duplicateStart = @'
                <div class="form-group">
                    <label>Driver - Front Indicator <span class="required">*</span></label>
                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="driver_front_indicator" value="Working" required> Working
'@

# Pattern 3: Find where duplicate ends (check_all_buttons textarea)
$duplicateEnd = @'
                <div class="form-group">
                    <label>Check All Buttons</label>
                    <small>Mention if anything is not working</small>
                    <textarea name="check_all_buttons" rows="3" placeholder="Mention if anything is not working"></textarea>
                </div>
            </div>

            <!-- STEP 13: WARNING LIGHTS -->
'@

# Check if patterns exist
if ($content -match [regex]::Escape($duplicateStart)) {
    Write-Host "   ✓ Found duplicate Electrical content in Step 11" -ForegroundColor Green
} else {
    Write-Host "   ✗ Could not find duplicate pattern" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "4. Removing duplicate content..." -ForegroundColor Yellow

# Strategy: Replace Step 11's content with correct Warning Lights from Step 13
# Then remove Step 13 and Step 14

# First, extract the correct Warning Lights content from Step 13
$step13Pattern = @'
            <!-- STEP 13: WARNING LIGHTS -->
            <div class="form-step" data-step="13">
                <h2>⊙ Step 13: Warning Lights</h2>
                
                <div class="form-group">
                    <label>Check Engine <span class="required">*</span></label>
'@

if ($content -match [regex]::Escape($step13Pattern)) {
    Write-Host "   ✓ Found Step 13 (correct Warning Lights)" -ForegroundColor Green
    
    # Extract Step 13 content
    $step13Start = $content.IndexOf('            <!-- STEP 13: WARNING LIGHTS -->')
    $step14Start = $content.IndexOf('                        <!-- STEP 14: AIR CONDITIONING -->')
    
    if ($step13Start -gt 0 -and $step14Start -gt $step13Start) {
        $step13Content = $content.Substring($step13Start, $step14Start - $step13Start).Trim()
        
        # Modify to be Step 11
        $step11Content = $step13Content -replace 'data-step="13"', 'data-step="11"'
        $step11Content = $step11Content -replace '⊙ Step 13: Warning Lights', '⊙ Warning Lights'
        $step11Content = $step11Content -replace '<!-- STEP 13:', '<!-- STEP 11:'
        
        Write-Host "   ✓ Extracted and modified Warning Lights content" -ForegroundColor Green
    } else {
        Write-Host "   ✗ Could not extract Step 13 content" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "   ✗ Could not find Step 13" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "5. Applying fixes..." -ForegroundColor Yellow

# Find Step 11 boundaries
$step11StartIdx = $content.IndexOf('            <!-- STEP 11: WARNING LIGHTS -->')
$step13StartIdx = $content.IndexOf('            <!-- STEP 13: WARNING LIGHTS -->')

if ($step11StartIdx -lt 0 -or $step13StartIdx -lt 0) {
    Write-Host "   ✗ Could not find step boundaries" -ForegroundColor Red
    exit 1
}

# Replace Step 11 with correct content
$beforeStep11 = $content.Substring(0, $step11StartIdx)
$afterStep13 = $content.Substring($step14Start)

# Combine: before + new Step 11 + after (skipping old Step 11, 13, 14)
$newContent = $beforeStep11 + $step11Content + "`r`n`r`n" + $afterStep13

Write-Host "   ✓ Replaced Step 11 with correct content" -ForegroundColor Green
Write-Host "   ✓ Removed duplicate Step 13" -ForegroundColor Green
Write-Host "   ✓ Removed duplicate Step 14" -ForegroundColor Green

Write-Host ""
Write-Host "6. Saving fixed file..." -ForegroundColor Yellow
$newContent | Set-Content "index.php" -NoNewline
Write-Host "   ✓ File saved successfully!" -ForegroundColor Green

Write-Host ""
Write-Host "7. Verifying fixes..." -ForegroundColor Yellow

# Count some key fields
$cruiseCount = ([regex]::Matches($newContent, 'name="cruise_control"')).Count
$backCameraCount = ([regex]::Matches($newContent, 'name="back_camera"')).Count
$highbeamCount = ([regex]::Matches($newContent, 'name="driver_headlight_highbeam"')).Count

Write-Host "   cruise_control: $cruiseCount instances (should be 4)" -ForegroundColor $(if ($cruiseCount -eq 4) { 'Green' } else { 'Red' })
Write-Host "   back_camera: $backCameraCount instances (should be 3)" -ForegroundColor $(if ($backCameraCount -eq 3) { 'Green' } else { 'Red' })
Write-Host "   driver_headlight_highbeam: $highbeamCount instances (should be 2)" -ForegroundColor $(if ($highbeamCount -eq 2) { 'Green' } else { 'Red' })

Write-Host ""
if ($cruiseCount -eq 4 -and $backCameraCount -eq 3 -and $highbeamCount -eq 2) {
    Write-Host "=== FIX COMPLETED SUCCESSFULLY! ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Test the form in your browser"
    Write-Host "2. Fill Steps 7, 8, 9"
    Write-Host "3. Save draft and verify all fields are saved"
    Write-Host "4. If there are issues, restore from backup: index.php.backup-*"
} else {
    Write-Host "=== WARNING: Verification failed ===" -ForegroundColor Red
    Write-Host "Some field counts are incorrect. Please review manually."
    Write-Host "Backup is available: index.php.backup-*"
}

Write-Host ""
