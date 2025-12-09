// Multi-step form navigation
let currentStep = 1;
const totalSteps = 13;

// Progressive upload tracking
let uploadedFiles = {};
let draftId = localStorage.getItem('draftId') || null;

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    // Setup progressive upload
    setupProgressiveUpload();
    // Set current dat  e and time for Step 2
    updateDateTime();
    
    // Load draft if exists
    loadDraft();
    
    // Event listeners
    document.getElementById('nextBtn').addEventListener('click', nextStep);
    document.getElementById('prevBtn').addEventListener('click', prevStep);
    document.getElementById('saveDraftBtn').addEventListener('click', saveDraft);
    document.getElementById('discardDraftBtn').addEventListener('click', discardDraft);
    
    // Fetch location button (only if it exists)
    const fetchLocationBtn = document.getElementById('fetchLocation');
    if (fetchLocationBtn) {
        fetchLocationBtn.addEventListener('click', fetchLocation);
    }
    
    // Submit button - both form submit and direct click
    document.getElementById('inspectionForm').addEventListener('submit', submitForm);
    document.getElementById('submitBtn').addEventListener('click', function(e) {
        e.preventDefault();
        submitForm(e);
    });
    
    // File upload preview (removed - Step 3 deleted)
    
    // Setup camera capture for all file inputs
    setupCameraCapture();
    
    // Setup all image previews
    setupImagePreviews();
    
    // Setup OK checkbox logic
    setupOkCheckboxLogic();
    
    // Setup payment toggle logic
    setupPaymentToggle();
    
    // Setup tool kit conditional logic
    setupToolKitConditional();
    
    // Setup conditional image fields for Engine step
    setupEngineConditionalImages();
    
    // Setup conditional OBD Scan Photo
    setupOBDScanPhotoConditional();
    
    // Setup conditional Fuel Leaks Image
    setupFuelLeaksImageConditional();
    
    // Setup conditional Sun Roof Image
    setupSunRoofImageConditional();
    
    // Auto-save on input change
    const formInputs = document.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('change', autoSave);
    });
    
    updateProgress();
});

function nextStep() {
    if (validateStep(currentStep)) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function showStep(step) {
    // Hide all steps
    const steps = document.querySelectorAll('.form-step');
    steps.forEach(s => s.classList.remove('active'));
    
    // Show current step
    const currentStepElement = document.querySelector(`[data-step="${step}"]`);
    if (currentStepElement) {
        currentStepElement.classList.add('active');
    }
    
    // Update buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.style.display = step === 1 ? 'none' : 'block';
    nextBtn.style.display = step === totalSteps ? 'none' : 'block';
    submitBtn.style.display = step === totalSteps ? 'block' : 'none';
    
    // Ensure submit button is enabled and clickable on final step
    if (step === totalSteps) {
        submitBtn.disabled = false;
        submitBtn.style.pointerEvents = 'auto';
        submitBtn.style.opacity = '1';
        console.log('Submit button should now be visible and clickable');
    }
    
    updateProgress();
}

function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
    document.getElementById('progressText').textContent = `Step ${currentStep} of ${totalSteps}`;
}

function validateStep(step) {
    const currentStepElement = document.querySelector(`[data-step="${step}"]`);
    
    // Special validation for Step 6 (Exterior Body with 52 fields)
    if (step === 6) {
        // Find all checkbox groups with data-ok-group attribute
        const checkboxGroups = currentStepElement.querySelectorAll('[data-ok-group]');
        
        for (let group of checkboxGroups) {
            const groupName = group.getAttribute('data-ok-group');
            const checkboxes = group.querySelectorAll('input[type="checkbox"]');
            const isAnyChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            if (!isAnyChecked) {
                const label = group.querySelector('label');
                const labelText = label ? label.textContent.replace('*', '').trim() : groupName;
                alert('Please select at least one option for: ' + labelText);
                
                // Scroll to the field
                group.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }
            
            // Check if image is required (non-OK option selected)
            const okCheckbox = group.querySelector('[data-ok-checkbox]');
            const isOkChecked = okCheckbox && okCheckbox.checked;
            
            if (!isOkChecked) {
                // Check if any non-OK checkbox is checked
                const hasNonOkChecked = Array.from(checkboxes).some(cb => 
                    cb !== okCheckbox && cb.checked
                );
                
                if (hasNonOkChecked) {
                    // Image is required
                    const imageContainer = document.getElementById(groupName + '_image_container');
                    if (imageContainer && imageContainer.style.display !== 'none') {
                        const imageInput = imageContainer.querySelector('input[type="file"]');
                        if (imageInput) {
                            const hasSavedFile = imageInput.dataset.savedFile;
                            const hasNewFile = imageInput.files && imageInput.files.length > 0;
                            
                            if (!hasSavedFile && !hasNewFile) {
                                const label = group.querySelector('label');
                                const labelText = label ? label.textContent.replace('*', '').trim() : groupName;
                                alert('Please upload an image for: ' + labelText);
                                
                                // Scroll to the field
                                imageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                return false;
                            }
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    // Standard validation for other steps
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    for (let field of requiredFields) {
        // Skip file inputs that are not visible or already have files
        if (field.type === 'file') {
            // Check if there's a saved file OR a newly uploaded file
            const hasSavedFile = field.dataset.savedFile;
            const hasNewFile = field.files && field.files.length > 0;
            
            // Debug logging for Step 5
            if (step === 5) {
                console.log('Validating file input:', field.name, {
                    hasSavedFile: hasSavedFile,
                    hasNewFile: hasNewFile,
                    filesLength: field.files ? field.files.length : 0
                });
            }
            
            if (!hasSavedFile && !hasNewFile) {
                field.focus();
                alert('Please upload all required images for: ' + field.name);
                return false;
            }
            
            // Only validate new uploads
            if (hasNewFile) {
                // Validate file size (15MB max)
                if (field.files[0].size > 15728640) {
                    alert('File size must be less than 15MB');
                    return false;
                }
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(field.files[0].type)) {
                    alert('Only JPG and PNG images are allowed');
                    return false;
                }
            }
        } else if (field.type === 'checkbox' || field.type === 'radio') {
            // Check if at least one checkbox/radio in the group is selected
            const groupName = field.name;
            const group = currentStepElement.querySelectorAll(`[name="${groupName}"]`);
            const isChecked = Array.from(group).some(input => input.checked);
            if (!isChecked) {
                field.focus();
                alert('Please select at least one option for: ' + field.closest('.form-group').querySelector('label').textContent);
                return false;
            }
        } else if (!field.value) {
            field.focus();
            alert('Please fill in all required fields');
            return false;
        }
    }
    
    return true;
}

function updateDateTime() {
    const now = new Date();
    
    // Format date as DD-MM-YYYY
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const dateStr = `${day}-${month}-${year}`;
    
    // Format time as HH:MM AM/PM
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    const timeStr = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
    
    // Update fields only if they exist (for backward compatibility)
    const expertDateField = document.getElementById('expertDate');
    const expertTimeField = document.getElementById('expertTime');
    
    if (expertDateField) {
        expertDateField.value = dateStr;
    }
    if (expertTimeField) {
        expertTimeField.value = timeStr;
    }
}

function fetchLocation() {
    const errorDiv = document.getElementById('locationError');
    const locationBtn = document.getElementById('fetchLocation');
    
    // Check if elements exist (they may not exist in new form structure)
    if (!errorDiv || !locationBtn) {
        console.log('Location elements not found - feature may have been removed');
        return;
    }
    
    errorDiv.textContent = '';
    errorDiv.style.display = 'none';
    
    // Check if geolocation is supported
    if (!navigator.geolocation) {
        alert("Your device does not support Geolocation.");
        return;
    }
    
    // Show loading state
    locationBtn.textContent = '⏳';
    locationBtn.disabled = true;
    
    // Request location with improved settings
    navigator.geolocation.getCurrentPosition(
        // SUCCESS callback
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            console.log('Location obtained:', lat, lon, 'Accuracy:', accuracy);
            
            // Auto-fill latitude and longitude
            document.getElementById('latitude').value = lat.toFixed(6);
            document.getElementById('longitude').value = lon.toFixed(6);
            
            // Show success in button
            locationBtn.textContent = '✓';
            locationBtn.style.background = '#4CAF50';
            
            // Fetch address via reverse geocoding with timeout
            const geocodeTimeout = setTimeout(() => {
                // If geocoding takes too long, just use coordinates
                document.getElementById('locationAddress').value = `Lat: ${lat.toFixed(6)}, Lon: ${lon.toFixed(6)}`;
                locationBtn.textContent = '📍';
                locationBtn.style.background = '';
                locationBtn.disabled = false;
            }, 8000);
            
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`, {
                headers: { 'User-Agent': 'CarInspectionApp/1.0' }
            })
            .then(response => {
                clearTimeout(geocodeTimeout);
                return response.json();
            })
            .then(data => {
                const address = data.display_name || `Lat: ${lat.toFixed(6)}, Lon: ${lon.toFixed(6)}`;
                document.getElementById('locationAddress').value = address;
                console.log('Address resolved:', address);
                
                locationBtn.textContent = '✓';
                locationBtn.style.background = '#4CAF50';
                setTimeout(() => {
                    locationBtn.textContent = '📍';
                    locationBtn.style.background = '';
                    locationBtn.disabled = false;
                }, 2000);
            })
            .catch(error => {
                clearTimeout(geocodeTimeout);
                console.error('Geocoding error:', error);
                // Fallback to coordinates
                document.getElementById('locationAddress').value = `Lat: ${lat.toFixed(6)}, Lon: ${lon.toFixed(6)}`;
                locationBtn.textContent = '📍';
                locationBtn.style.background = '';
                locationBtn.disabled = false;
            });
        },
        // ERROR callback
        function(error) {
            locationBtn.textContent = '📍';
            locationBtn.disabled = false;
            
            console.error('Geolocation error:', error.code, error.message);
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorDiv.innerHTML = '<strong>⚠️ Location Permission Denied</strong><br>' +
                                        '📱 <strong>Mobile:</strong> Tap the lock/info icon in address bar → Permissions → Location → Allow<br>' +
                                        '💻 <strong>Desktop:</strong> Click the lock icon → Site settings → Location → Allow';
                    errorDiv.style.display = 'block';
                    errorDiv.style.background = '#fff3cd';
                    errorDiv.style.padding = '10px';
                    errorDiv.style.borderRadius = '4px';
                    errorDiv.style.color = '#856404';
                    errorDiv.style.fontSize = '13px';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorDiv.innerHTML = '<strong>⚠️ Location Unavailable</strong><br>Please ensure GPS/Location Services are enabled on your device.';
                    errorDiv.style.display = 'block';
                    errorDiv.style.background = '#fff3cd';
                    errorDiv.style.padding = '10px';
                    errorDiv.style.borderRadius = '4px';
                    errorDiv.style.color = '#856404';
                    break;
                case error.TIMEOUT:
                    errorDiv.innerHTML = '<strong>⚠️ Location Timeout</strong><br>GPS is taking too long. Please try again or check if Location Services are enabled.';
                    errorDiv.style.display = 'block';
                    errorDiv.style.background = '#fff3cd';
                    errorDiv.style.padding = '10px';
                    errorDiv.style.borderRadius = '4px';
                    errorDiv.style.color = '#856404';
                    break;
                default:
                    errorDiv.innerHTML = '<strong>⚠️ Location Error</strong><br>Unable to get location. Please try again.';
                    errorDiv.style.display = 'block';
                    errorDiv.style.background = '#ffebee';
                    errorDiv.style.padding = '10px';
                    errorDiv.style.borderRadius = '4px';
                    errorDiv.style.color = '#c62828';
            }
        },
        // OPTIONS - Optimized for reliability
        {
            enableHighAccuracy: false,  // Changed to false for faster response
            timeout: 30000,              // Increased to 30 seconds
            maximumAge: 60000            // Allow cached position up to 1 minute
        }
    );
}



function showLocationError(message, errorDiv) {
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    errorDiv.style.background = '#ffebee';
    errorDiv.style.color = '#c62828';
    errorDiv.style.padding = '15px';
    errorDiv.style.borderRadius = '4px';
    errorDiv.style.marginTop = '10px';
    errorDiv.style.whiteSpace = 'pre-line';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.border = '1px solid #ef5350';
}

function showLocationSuccess(message, errorDiv) {
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    errorDiv.style.background = '#e8f5e9';
    errorDiv.style.color = '#2e7d32';
    errorDiv.style.padding = '10px';
    errorDiv.style.borderRadius = '4px';
    errorDiv.style.marginTop = '10px';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.border = '1px solid #66bb6a';
    
    // Auto-hide success message after 3 seconds
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 3000);
}

function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('carPhotoPreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
        };
        reader.readAsDataURL(file);
    }
}

function setupImagePreviews() {
    // Get all file inputs
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const previewId = input.id + 'Preview';
            const preview = document.getElementById(previewId);
            
            if (file && preview) {
                // Check file size
                if (file.size > 15728640) {
                    alert('File size must be less than 15MB');
                    input.value = '';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG and PNG images are allowed');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="replace-image-btn" onclick="replaceImage('${input.name}')">Replace Image</button>
                    `;
                };
                reader.readAsDataURL(file);
                
                // Remove saved file marker since user is uploading new file
                delete input.dataset.savedFile;
                
                // Make field required again if it was marked as having saved file
                if (input.hasAttribute('data-was-required')) {
                    input.setAttribute('required', 'required');
                }
            }
        });
    });
}

function setupOkCheckboxLogic() {
    // Find all checkbox groups with OK logic
    const checkboxGroups = document.querySelectorAll('[data-ok-group]');
    
    checkboxGroups.forEach(group => {
        const checkboxes = group.querySelectorAll('input[type="checkbox"]');
        const okCheckbox = group.querySelector('[data-ok-checkbox]');
        
        if (!okCheckbox) return;
        
        // Get the group name from data attribute
        const groupName = group.getAttribute('data-ok-group');
        const imageContainer = document.getElementById(groupName + '_image_container');
        
        // Function to update image container visibility
        function updateImageVisibility() {
            if (!imageContainer) return;
            
            // Check if OK is checked
            const isOkChecked = okCheckbox.checked;
            
            // Check if any non-OK checkbox is checked
            let hasNonOkChecked = false;
            checkboxes.forEach(cb => {
                if (cb !== okCheckbox && cb.checked) {
                    hasNonOkChecked = true;
                }
            });
            
            // Show image container only if non-OK options are checked and OK is not checked
            if (hasNonOkChecked && !isOkChecked) {
                imageContainer.style.display = 'block';
                // Make image required
                const imageInput = imageContainer.querySelector('input[type="file"]');
                if (imageInput && !imageInput.dataset.savedFile) {
                    imageInput.setAttribute('required', 'required');
                }
            } else {
                imageContainer.style.display = 'none';
                // Remove required from image
                const imageInput = imageContainer.querySelector('input[type="file"]');
                if (imageInput) {
                    imageInput.removeAttribute('required');
                }
            }
        }
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this === okCheckbox) {
                    // If OK is checked, uncheck all others
                    if (this.checked) {
                        checkboxes.forEach(cb => {
                            if (cb !== okCheckbox) {
                                cb.checked = false;
                            }
                        });
                    }
                } else {
                    // If any other checkbox is checked, uncheck OK
                    if (this.checked) {
                        okCheckbox.checked = false;
                    }
                }
                
                // Update image visibility after checkbox change
                updateImageVisibility();
            });
        });
        
        // Initial check on page load
        updateImageVisibility();
    });
}

function setupPaymentToggle() {
    const paymentYes = document.getElementById('payment_yes');
    const paymentNo = document.getElementById('payment_no');
    const paymentDetailsSection = document.getElementById('payment_details_section');
    const paymentOnline = document.getElementById('payment_online');
    const paymentCash = document.getElementById('payment_cash');
    const onlinePaymentSection = document.getElementById('online_payment_section');
    const paymentProof = document.getElementById('paymentProof');
    const paymentTypeInputs = document.querySelectorAll('input[name="payment_type"]');
    
    if (!paymentYes || !paymentNo || !paymentDetailsSection) return;
    
    // Function to toggle payment details
    function togglePaymentDetails() {
        if (paymentYes.checked) {
            paymentDetailsSection.style.display = 'block';
            // Make payment type required when Yes is selected
            paymentTypeInputs.forEach(input => input.setAttribute('required', 'required'));
        } else {
            paymentDetailsSection.style.display = 'none';
            // Remove required when No is selected
            paymentTypeInputs.forEach(input => input.removeAttribute('required'));
            if (paymentProof) {
                paymentProof.removeAttribute('required');
            }
            // Hide online payment section
            if (onlinePaymentSection) {
                onlinePaymentSection.style.display = 'none';
            }
        }
    }
    
    // Function to toggle online payment section
    function toggleOnlinePayment() {
        if (paymentOnline && paymentOnline.checked) {
            if (onlinePaymentSection) {
                onlinePaymentSection.style.display = 'block';
            }
            // Make payment proof required for online payment
            if (paymentProof) {
                paymentProof.setAttribute('required', 'required');
            }
        } else {
            if (onlinePaymentSection) {
                onlinePaymentSection.style.display = 'none';
            }
            // Remove required for cash payment
            if (paymentProof) {
                paymentProof.removeAttribute('required');
            }
        }
    }
    
    // Add event listeners
    paymentYes.addEventListener('change', togglePaymentDetails);
    paymentNo.addEventListener('change', togglePaymentDetails);
    
    if (paymentOnline) {
        paymentOnline.addEventListener('change', toggleOnlinePayment);
    }
    if (paymentCash) {
        paymentCash.addEventListener('change', toggleOnlinePayment);
    }
    
    // Check initial state (for draft loading)
    togglePaymentDetails();
    toggleOnlinePayment();
}

function saveDraft() {
    console.log('Saving draft...');
    const form = document.getElementById('inspectionForm');
    
    // Get the current draft ID (only if it exists and is not empty)
    const currentDraftId = localStorage.getItem('draftId') || draftId || null;
    
    // Collect uploaded files from both global variable and localStorage
    let allUploadedFiles = {};
    
    // First, get from localStorage
    const storedFiles = localStorage.getItem('uploadedFiles');
    if (storedFiles) {
        try {
            allUploadedFiles = JSON.parse(storedFiles);
        } catch (e) {
            console.error('Error parsing uploadedFiles from localStorage:', e);
        }
    }
    
    // Then merge with global uploadedFiles (in case of new uploads)
    if (uploadedFiles && typeof uploadedFiles === 'object') {
        allUploadedFiles = { ...allUploadedFiles, ...uploadedFiles };
    }
    
    console.log('Uploaded files to save:', allUploadedFiles);
    
    // Collect all form data as JSON for better structure
    const draftData = {
        current_step: currentStep,
        form_data: {},
        uploaded_files: allUploadedFiles
    };
    
    // Only include draft_id if it exists and is not empty
    if (currentDraftId && currentDraftId.trim() !== '') {
        draftData.draft_id = currentDraftId;
    }
    
    // Collect all form fields with proper handling for arrays
    const inputs = form.querySelectorAll('input:not([type="file"]), select, textarea');
    const fieldGroups = {};
    
    inputs.forEach(input => {
        if (!input.name) return;
        
        if (input.type === 'checkbox') {
            // Handle checkbox arrays
            if (!fieldGroups[input.name]) {
                fieldGroups[input.name] = [];
            }
            if (input.checked) {
                fieldGroups[input.name].push(input.value);
            }
        } else if (input.type === 'radio') {
            // Handle radio buttons
            if (input.checked) {
                draftData.form_data[input.name] = input.value;
            }
        } else if (input.tagName === 'SELECT' && input.multiple) {
            // Handle multi-select
            const selected = Array.from(input.selectedOptions).map(opt => opt.value);
            draftData.form_data[input.name] = selected;
        } else {
            // Handle regular inputs - Convert text inputs to uppercase
            let value = input.value;
            if ((input.type === 'text' || input.tagName === 'TEXTAREA') && !input.readOnly) {
                value = value.toUpperCase();
            }
            draftData.form_data[input.name] = value;
        }
    });
    
    // Add checkbox arrays to form_data
    for (const fieldName in fieldGroups) {
        if (fieldGroups[fieldName].length > 0) {
            draftData.form_data[fieldName] = fieldGroups[fieldName];
        }
    }
    
    console.log('Draft data to save:', draftData);
    
    // Show loading indicator
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const originalText = saveDraftBtn.textContent;
    saveDraftBtn.textContent = 'Saving...';
    saveDraftBtn.disabled = true;
    
    fetch('save-draft.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(draftData)
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            throw new Error('Server returned ' + response.status + ': ' + response.statusText);
        }
        
        // Get the response text first to see what we're getting
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON. Response was:', text);
                throw new Error('Invalid JSON response from server. Check console for details.');
            }
        });
    })
    .then(data => {
        saveDraftBtn.textContent = originalText;
        saveDraftBtn.disabled = false;
        
        if (data.success) {
            // Save draft ID
            draftId = data.draft_id;
            localStorage.setItem('draftId', data.draft_id);
            
            // Update uploaded files from server response
            if (data.draft_data && data.draft_data.uploaded_files) {
                uploadedFiles = data.draft_data.uploaded_files;
                localStorage.setItem('uploadedFiles', JSON.stringify(data.draft_data.uploaded_files));
                localStorage.setItem('savedFiles', JSON.stringify(data.draft_data.uploaded_files));
                console.log('Updated uploadedFiles:', uploadedFiles);
            }
            
            alert('Draft saved successfully! ' + (data.files_saved || 0) + ' images saved.');
            console.log('Draft saved:', data);
        } else {
            alert('Error saving draft: ' + data.message);
            console.error('Draft save error:', data);
        }
    })
    .catch(error => {
        saveDraftBtn.textContent = originalText;
        saveDraftBtn.disabled = false;
        alert('Error saving draft: ' + error.message);
        console.error('Draft save error:', error);
    });
}

function loadDraft() {
    console.log('Loading draft...');
    
    // Check if draft was just discarded
    if (sessionStorage.getItem('draftDiscarded') === 'true') {
        console.log('Draft was discarded, skipping load');
        sessionStorage.removeItem('draftDiscarded');
        // Clear any remaining localStorage items
        localStorage.removeItem('draftId');
        localStorage.removeItem('savedFiles');
        localStorage.removeItem('uploadedFiles');
        return;
    }
    
    const storedDraftId = localStorage.getItem('draftId');
    
    if (!storedDraftId) {
        console.log('No draft ID found');
        // Try to load from localStorage as fallback
        const storedFiles = localStorage.getItem('uploadedFiles');
        if (storedFiles) {
            try {
                uploadedFiles = JSON.parse(storedFiles);
                console.log('Loaded uploadedFiles from localStorage:', uploadedFiles);
            } catch (e) {
                console.error('Error parsing uploadedFiles:', e);
            }
        }
        return;
    }
    
    // Update global draftId
    draftId = storedDraftId;
    
    // Fetch draft from server
    fetch('load-draft.php?draft_id=' + storedDraftId)
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Draft not found on server, clear local storage
            console.log('Draft not found on server, clearing local storage');
            localStorage.removeItem('draftId');
            localStorage.removeItem('savedFiles');
            localStorage.removeItem('uploadedFiles');
            return;
        }
        
        if (data.success && data.draft_data) {
            console.log('Draft loaded:', data);
            const draftData = data.draft_data;
            
            // Restore current step
            if (draftData.current_step) {
                currentStep = parseInt(draftData.current_step);
            }
            
            // Restore form fields
            if (draftData.form_data) {
                for (let key in draftData.form_data) {
                    const value = draftData.form_data[key];
                    const fields = document.querySelectorAll(`[name="${key}"]`);
                    
                    if (fields.length === 0) continue;
                    
                    const firstField = fields[0];
                    
                    if (firstField.type === 'checkbox') {
                        // Handle checkbox arrays
                        const values = Array.isArray(value) ? value : [value];
                        fields.forEach(field => {
                            field.checked = values.includes(field.value);
                        });
                    } else if (firstField.type === 'radio') {
                        // Handle radio buttons
                        fields.forEach(field => {
                            field.checked = field.value === value;
                        });
                    } else if (firstField.tagName === 'SELECT' && firstField.multiple) {
                        // Handle multi-select
                        const values = Array.isArray(value) ? value : [value];
                        Array.from(firstField.options).forEach(option => {
                            option.selected = values.includes(option.value);
                        });
                    } else {
                        // Handle regular inputs - Convert text to uppercase
                        let finalValue = value;
                        if ((firstField.type === 'text' || firstField.tagName === 'TEXTAREA') && !firstField.readOnly) {
                            finalValue = String(value).toUpperCase();
                        }
                        firstField.value = finalValue;
                    }
                }
            }
            
            // Restore uploaded images
            if (draftData.uploaded_files && Object.keys(draftData.uploaded_files).length > 0) {
                console.log('Restoring uploaded files:', draftData.uploaded_files);
                
                // Store in both uploadedFiles and localStorage
                uploadedFiles = draftData.uploaded_files;
                localStorage.setItem('uploadedFiles', JSON.stringify(draftData.uploaded_files));
                localStorage.setItem('savedFiles', JSON.stringify(draftData.uploaded_files));
                
                let loadedCount = 0;
                let failedCount = 0;
                
                for (let fieldName in draftData.uploaded_files) {
                    const filePath = draftData.uploaded_files[fieldName];
                    const fileInput = document.querySelector(`[name="${fieldName}"]`);
                    
                    if (fileInput && fileInput.type === 'file') {
                        const previewId = fileInput.id + 'Preview';
                        const preview = document.getElementById(previewId);
                        
                        if (preview) {
                            // Verify image exists before showing preview
                            const img = new Image();
                            img.onload = function() {
                                // Image loaded successfully
                                preview.innerHTML = `
                                    <img src="${filePath}" alt="Saved image">
                                    <button type="button" class="replace-image-btn" onclick="replaceImage('${fieldName}')">Replace Image</button>
                                    <span class="upload-success">✅ Uploaded</span>
                                `;
                                
                                // Mark field as having saved file
                                fileInput.dataset.savedFile = filePath;
                                fileInput.removeAttribute('required');
                                loadedCount++;
                                console.log('✅ Loaded image:', fieldName, filePath);
                            };
                            img.onerror = function() {
                                // Image failed to load
                                console.error('❌ Failed to load image:', fieldName, filePath);
                                preview.innerHTML = `
                                    <div class="upload-error">❌ Image not found. Please upload again.</div>
                                `;
                                
                                // Remove from uploadedFiles
                                delete uploadedFiles[fieldName];
                                localStorage.setItem('uploadedFiles', JSON.stringify(uploadedFiles));
                                
                                // Make field required again
                                fileInput.setAttribute('required', 'required');
                                delete fileInput.dataset.savedFile;
                                failedCount++;
                            };
                            img.src = filePath;
                        }
                    }
                }
                
                // Show summary after a short delay to let images load
                setTimeout(() => {
                    console.log(`Restored ${loadedCount} images, ${failedCount} failed`);
                    if (failedCount > 0) {
                        alert(`Draft loaded! ${loadedCount} images restored.\n${failedCount} images could not be found and need to be re-uploaded.`);
                    }
                }, 1000);
            }
            
            showStep(currentStep);
            
            // Trigger payment toggle check after draft loads
            setTimeout(() => {
                const paymentYes = document.getElementById('payment_yes');
                const paymentNo = document.getElementById('payment_no');
                const paymentDetailsSection = document.getElementById('payment_details_section');
                
                if (paymentYes && paymentYes.checked && paymentDetailsSection) {
                    paymentDetailsSection.style.display = 'block';
                    
                    // Also check payment type and show online section if needed
                    const paymentOnline = document.getElementById('payment_online');
                    const onlinePaymentSection = document.getElementById('online_payment_section');
                    const paymentProof = document.getElementById('paymentProof');
                    
                    if (paymentOnline && paymentOnline.checked && onlinePaymentSection) {
                        onlinePaymentSection.style.display = 'block';
                        if (paymentProof && !paymentProof.dataset.savedFile) {
                            paymentProof.setAttribute('required', 'required');
                        }
                    }
                } else if (paymentNo && paymentNo.checked && paymentDetailsSection) {
                    paymentDetailsSection.style.display = 'none';
                }
                
                // Trigger conditional image fields for Engine step
                // This ensures image containers show/hide correctly based on saved radio values
                if (typeof setupEngineConditionalImages === 'function') {
                    setupEngineConditionalImages();
                }
                
                // Trigger conditional OBD Scan Photo
                if (typeof setupOBDScanPhotoConditional === 'function') {
                    setupOBDScanPhotoConditional();
                }
                
                // Trigger conditional Fuel Leaks Image
                if (typeof setupFuelLeaksImageConditional === 'function') {
                    setupFuelLeaksImageConditional();
                }
                
                // Trigger conditional Sun Roof Image
                if (typeof setupSunRoofImageConditional === 'function') {
                    setupSunRoofImageConditional();
                }
                
                // Trigger OK checkbox logic for Step 6 (Exterior Body)
                // This ensures image containers show/hide correctly based on saved checkbox values
                const checkboxGroups = document.querySelectorAll('[data-ok-group]');
                checkboxGroups.forEach(group => {
                    const groupName = group.getAttribute('data-ok-group');
                    const imageContainer = document.getElementById(groupName + '_image_container');
                    const okCheckbox = group.querySelector('[data-ok-checkbox]');
                    const checkboxes = group.querySelectorAll('input[type="checkbox"]');
                    
                    if (!imageContainer || !okCheckbox) return;
                    
                    // Check if OK is checked
                    const isOkChecked = okCheckbox.checked;
                    
                    // Check if any non-OK checkbox is checked
                    let hasNonOkChecked = false;
                    checkboxes.forEach(cb => {
                        if (cb !== okCheckbox && cb.checked) {
                            hasNonOkChecked = true;
                        }
                    });
                    
                    // Show image container only if non-OK options are checked and OK is not checked
                    if (hasNonOkChecked && !isOkChecked) {
                        imageContainer.style.display = 'block';
                        const imageInput = imageContainer.querySelector('input[type="file"]');
                        if (imageInput && !imageInput.dataset.savedFile) {
                            imageInput.setAttribute('required', 'required');
                        }
                    } else {
                        imageContainer.style.display = 'none';
                        const imageInput = imageContainer.querySelector('input[type="file"]');
                        if (imageInput) {
                            imageInput.removeAttribute('required');
                        }
                    }
                });
                
                // Trigger Tool Kit conditional logic for Step 12
                // This ensures tool kit image field shows/hides correctly based on saved value
                const toolKitRadios = document.querySelectorAll('input[name="tool_kit"]');
                const toolKitImageGroup = document.getElementById('toolKitImageGroup');
                const toolKitImageInput = document.getElementById('toolKitImage');
                
                if (toolKitRadios.length && toolKitImageGroup) {
                    const selectedValue = document.querySelector('input[name="tool_kit"]:checked')?.value;
                    
                    if (selectedValue === 'Present') {
                        toolKitImageGroup.style.display = 'block';
                        if (toolKitImageInput && !toolKitImageInput.dataset.savedFile) {
                            toolKitImageInput.setAttribute('required', 'required');
                        }
                    } else {
                        toolKitImageGroup.style.display = 'none';
                        if (toolKitImageInput) {
                            toolKitImageInput.removeAttribute('required');
                        }
                    }
                }
            }, 100);
            
            alert('Draft loaded successfully!');
        } else {
            console.log('No draft data or load failed:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading draft:', error);
    });
}

function replaceImage(fieldName) {
    const fileInput = document.querySelector(`[name="${fieldName}"]`);
    if (fileInput) {
        fileInput.click();
    }
}

function discardDraft() {
    if (confirm('Are you sure you want to discard the draft? All saved data and images will be deleted.')) {
        const draftId = localStorage.getItem('draftId');
        
        // Show loading
        const discardBtn = document.getElementById('discardDraftBtn');
        if (discardBtn) {
            discardBtn.textContent = 'Discarding...';
            discardBtn.disabled = true;
        }
        
        // Delete draft from server (use new endpoint)
        if (draftId) {
            fetch('drafts/discard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'draft_id=' + encodeURIComponent(draftId)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Draft discarded:', data);
                
                // Complete cleanup
                completeDiscardCleanup();
            })
            .catch(error => {
                console.error('Error discarding draft:', error);
                
                // Still do local cleanup even if server fails
                completeDiscardCleanup();
            });
        } else {
            // No draft ID, just clean up locally
            completeDiscardCleanup();
        }
    }
}

function completeDiscardCleanup() {
    // Set a flag to prevent draft loading after discard
    sessionStorage.setItem('draftDiscarded', 'true');
    
    // Clear ALL localStorage items
    localStorage.removeItem('draftId');
    localStorage.removeItem('savedFiles');
    localStorage.removeItem('inspectionDraft');
    localStorage.removeItem('uploadedFiles');
    localStorage.clear(); // Nuclear option - clear everything
    
    // Clear sessionStorage except the discard flag
    const discardFlag = sessionStorage.getItem('draftDiscarded');
    sessionStorage.clear();
    sessionStorage.setItem('draftDiscarded', discardFlag);
    
    // Clear global variables
    if (typeof uploadedFiles !== 'undefined') {
        uploadedFiles = {};
    }
    if (typeof draftId !== 'undefined') {
        draftId = null;
    }
    
    // Clear all form inputs
    const form = document.getElementById('inspectionForm');
    if (form) {
        form.reset();
    }
    
    // Clear all file input previews
    const allPreviews = document.querySelectorAll('[id$="Preview"]');
    allPreviews.forEach(preview => {
        preview.innerHTML = '';
    });
    
    // Clear all image previews
    const allImages = document.querySelectorAll('.image-container img');
    allImages.forEach(img => {
        img.remove();
    });
    
    // Remove all "Replace Image" buttons
    const replaceButtons = document.querySelectorAll('.replace-image-btn');
    replaceButtons.forEach(btn => {
        btn.remove();
    });
    
    // Remove all upload success indicators
    const successIndicators = document.querySelectorAll('.upload-success');
    successIndicators.forEach(indicator => {
        indicator.remove();
    });
    
    // Reset all file inputs to required if they were required
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        // Remove saved file marker
        delete input.dataset.savedFile;
        
        // Restore required attribute if it was originally required
        if (input.hasAttribute('data-originally-required')) {
            input.setAttribute('required', 'required');
        }
        
        // Clear the file input value (this is tricky, need to replace the element)
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
    });
    
    // Reset to step 1
    currentStep = 1;
    showStep(1);
    updateProgress();
    
    // Show success message
    alert('Draft discarded successfully. All data and images have been deleted.');
    
    // Reload page to ensure clean state
    setTimeout(() => {
        location.reload();
    }, 500);
}

function autoSave() {
    // Auto-save to localStorage every 30 seconds
    clearTimeout(window.autoSaveTimer);
    window.autoSaveTimer = setTimeout(saveDraft, 30000);
}

function submitForm(event) {
    console.log('Submit function called!');
    event.preventDefault();
    
    console.log('Current step:', currentStep, 'Total steps:', totalSteps);
    
    if (!validateStep(currentStep)) {
        console.log('Validation failed for step', currentStep);
        return;
    }
    
    console.log('Validation passed, submitting form...');
    
    // Show loading overlay
    document.getElementById('loadingOverlay').classList.add('active');
    
    const formData = new FormData(document.getElementById('inspectionForm'));
    
    // Add draft_id for cleanup after successful submission
    const draftId = localStorage.getItem('draftId');
    if (draftId) {
        formData.append('draft_id', draftId);
    }
    
    // Add progressively uploaded file paths
    for (const fieldName in uploadedFiles) {
        formData.append('existing_' + fieldName, uploadedFiles[fieldName]);
    }
    
    // Also check old savedFiles for backward compatibility
    const savedFiles = JSON.parse(localStorage.getItem('savedFiles') || '{}');
    for (const fieldName in savedFiles) {
        if (!uploadedFiles[fieldName]) {
            const fileInput = document.querySelector(`[name="${fieldName}"]`);
            // Only add existing file if no new file was uploaded
            if (fileInput && fileInput.type === 'file' && (!fileInput.files || fileInput.files.length === 0)) {
                formData.append('existing_' + fieldName, savedFiles[fieldName]);
            }
        }
    }
    
    console.log('Sending data to submit.php...');
    
    // Show progress message
    const loadingText = document.querySelector('#loadingOverlay p');
    if (loadingText) {
        loadingText.textContent = 'Processing images and generating PDF... This may take 2-3 minutes.';
    }
    
    fetch('submit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status);
        
        // Check if response is OK
        if (!response.ok) {
            throw new Error('Server returned ' + response.status + ': ' + response.statusText);
        }
        
        // Get response text first to handle errors
        return response.text().then(text => {
            console.log('Raw response:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON. Response was:', text);
                throw new Error('Invalid JSON response from server. Check console for details.');
            }
        });
    })
    .then(data => {
        console.log('Response data:', data);
        document.getElementById('loadingOverlay').classList.remove('active');
        
        if (data.success) {
            console.log('Submission successful!');
            
            // Delete draft from server if exists
            const draftId = localStorage.getItem('draftId');
            if (draftId) {
                fetch('delete-draft.php?draft_id=' + draftId, {
                    method: 'DELETE'
                }).catch(err => console.log('Draft cleanup error:', err));
            }
            
            // Clear all draft data
            localStorage.removeItem('inspectionDraft');
            localStorage.removeItem('draftId');
            localStorage.removeItem('savedFiles');
            
            // Show success message
            document.getElementById('successMessage').classList.add('active');
        } else {
            console.error('Submission failed:', data.message);
            alert('Error: ' + (data.message || 'Submission failed'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        document.getElementById('loadingOverlay').classList.remove('active');
        alert('Error submitting form: ' + error.message);
    });
}


/**
 * Progressive Upload Functions
 * Upload images immediately when selected
 */
function setupProgressiveUpload() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadImageImmediately(input.name, file, input.id);
            }
        });
    });
    
    // Load previously uploaded files
    loadUploadedFiles();
}

function uploadImageImmediately(fieldName, file, inputId) {
    const previewId = inputId + 'Preview';
    const preview = document.getElementById(previewId);
    
    if (!preview) return;
    
    // Show uploading status
    preview.innerHTML = '<div class="uploading">📤 Uploading...</div>';
    
    // Create form data
    const formData = new FormData();
    formData.append('image', file);
    formData.append('field_name', fieldName);
    formData.append('current_step', currentStep || 1);
    
    if (draftId) {
        formData.append('draft_id', draftId);
    }
    
    // Upload via AJAX
    fetch('upload-image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store draft ID
            if (data.draft_id) {
                draftId = data.draft_id;
                localStorage.setItem('draftId', draftId);
            }
            
            // Store file path
            uploadedFiles[fieldName] = data.file_path;
            localStorage.setItem('uploadedFiles', JSON.stringify(uploadedFiles));
            
            // CRITICAL FIX: Mark the input as having a saved file
            const fileInput = document.querySelector(`[name="${fieldName}"]`);
            if (fileInput) {
                fileInput.dataset.savedFile = data.file_path;
                fileInput.removeAttribute('required');
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="replace-image-btn" onclick="replaceImage('${fieldName}')">Replace Image</button>
                    <span class="upload-success">✅ Uploaded</span>
                `;
            };
            reader.readAsDataURL(file);
            
            console.log('Image uploaded:', fieldName, data.file_path);
        } else {
            preview.innerHTML = `<div class="upload-error">❌ Upload failed: ${data.message}</div>`;
            console.error('Upload failed:', data.message);
        }
    })
    .catch(error => {
        preview.innerHTML = `<div class="upload-error">❌ Upload error: ${error.message}</div>`;
        console.error('Upload error:', error);
    });
}

function loadUploadedFiles() {
    // Don't load from localStorage if we have a draft ID
    // The loadDraft() function will handle loading images from the server
    const draftId = localStorage.getItem('draftId');
    if (draftId) {
        console.log('Draft ID exists, skipping localStorage image load (will load from server)');
        return;
    }
    
    const savedFiles = localStorage.getItem('uploadedFiles');
    
    if (savedFiles) {
        try {
            uploadedFiles = JSON.parse(savedFiles);
            
            // Show previews for uploaded files
            for (let fieldName in uploadedFiles) {
                const filePath = uploadedFiles[fieldName];
                const fileInput = document.querySelector(`[name="${fieldName}"]`);
                
                if (fileInput && fileInput.type === 'file') {
                    const previewId = fileInput.id + 'Preview';
                    const preview = document.getElementById(previewId);
                    
                    if (preview) {
                        // Verify image exists by trying to load it
                        const img = new Image();
                        img.onload = function() {
                            preview.innerHTML = `
                                <img src="${filePath}" alt="Saved image">
                                <button type="button" class="replace-image-btn" onclick="replaceImage('${fieldName}')">Replace Image</button>
                                <span class="upload-success">✅ Saved</span>
                            `;
                            
                            // Mark as not required since file exists
                            fileInput.removeAttribute('required');
                            fileInput.dataset.savedFile = filePath;
                        };
                        img.onerror = function() {
                            console.warn('Image not found:', filePath);
                            // Remove from uploadedFiles if image doesn't exist
                            delete uploadedFiles[fieldName];
                            localStorage.setItem('uploadedFiles', JSON.stringify(uploadedFiles));
                        };
                        img.src = filePath;
                    }
                }
            }
            
            console.log('Loaded', Object.keys(uploadedFiles).length, 'uploaded images');
        } catch (e) {
            console.error('Error loading uploaded files:', e);
        }
    }
}

// Add CSS for upload status
const uploadStyle = document.createElement('style');
uploadStyle.textContent = `
    .uploading {
        padding: 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 5px;
        text-align: center;
        animation: pulse 1s infinite;
    }
    
    .upload-success {
        display: inline-block;
        margin-top: 5px;
        padding: 5px 10px;
        background: #d4edda;
        color: #155724;
        border-radius: 3px;
        font-size: 12px;
    }
    
    .upload-error {
        padding: 10px;
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 5px;
        color: #721c24;
        text-align: center;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
`;
document.head.appendChild(uploadStyle);


// Setup camera capture for all file inputs
function setupCameraCapture() {
    // Find all file inputs that accept images
    const fileInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    fileInputs.forEach(fileInput => {
        const fileUploadDiv = fileInput.closest('.file-upload');
        if (!fileUploadDiv) return;
        
        const fileLabel = fileUploadDiv.querySelector('.file-label');
        if (!fileLabel) return;
        
        // Check if camera button already exists
        if (fileUploadDiv.querySelector('.camera-btn-wrapper')) return;
        
        // Create camera input
        const cameraInput = document.createElement('input');
        cameraInput.type = 'file';
        cameraInput.accept = 'image/*';
        cameraInput.capture = 'environment';
        cameraInput.style.display = 'none';
        cameraInput.id = fileInput.id + 'Camera';
        
        // Create camera button label
        const cameraLabel = document.createElement('label');
        cameraLabel.htmlFor = cameraInput.id;
        cameraLabel.className = 'file-label';
        cameraLabel.style.flex = '1';
        cameraLabel.style.background = '#848383ff';
        cameraLabel.innerHTML = '<span class="camera-icon">📷</span><span class="file-text">Take Photo</span>';
        
        // Create wrapper for both buttons
        const wrapper = document.createElement('div');
        wrapper.className = 'camera-btn-wrapper';
        wrapper.style.display = 'flex';
        wrapper.style.gap = '10px';
        
        // Hide original input
        fileInput.style.display = 'none';
        
        // Update original label
        fileLabel.style.flex = '1';
        fileLabel.querySelector('.camera-icon').textContent = '📁';
        
        // Insert camera input and reorganize
        fileUploadDiv.insertBefore(cameraInput, fileLabel);
        wrapper.appendChild(fileLabel);
        wrapper.appendChild(cameraLabel);
        fileUploadDiv.insertBefore(wrapper, fileUploadDiv.querySelector('.file-preview'));
        
        // Handle camera input change
        cameraInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
}

// Show permission denied popup with instructions
function showPermissionDeniedPopup() {
    const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
    const isAndroid = /Android/i.test(navigator.userAgent);
    const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
    
    const modal = document.createElement('div');
    modal.id = 'locationPermissionModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn 0.3s;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        padding: 30px;
        border-radius: 16px;
        max-width: 450px;
        width: 100%;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        animation: slideUp 0.3s;
    `;
    
    let instructions = '';
    if (isAndroid) {
        instructions = `
            <div style="background: #e8f5e9; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #4CAF50;">
                <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">📱 Android Instructions:</h3>
                <ol style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                    <li>Tap the <strong>🔒 lock icon</strong> in the address bar</li>
                    <li>Tap <strong>"Permissions"</strong> or <strong>"Site settings"</strong></li>
                    <li>Find <strong>"Location"</strong></li>
                    <li>Change to <strong>"Allow"</strong></li>
                    <li>Refresh this page</li>
                    <li>Tap <strong>"Get Location"</strong> again</li>
                </ol>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <strong style="color: #e65100;">Alternative:</strong>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 14px;">
                    Settings → Apps → Browser → Permissions → Location → Allow
                </p>
            </div>
        `;
    } else if (isIOS) {
        instructions = `
            <div style="background: #e3f2fd; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #2196F3;">
                <h3 style="margin: 0 0 15px 0; color: #1565c0; font-size: 16px;">🍎 iOS Instructions:</h3>
                <ol style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                    <li>Tap <strong>"AA"</strong> in the address bar</li>
                    <li>Tap <strong>"Website Settings"</strong></li>
                    <li>Find <strong>"Location"</strong></li>
                    <li>Change to <strong>"Allow"</strong></li>
                    <li>Refresh this page</li>
                    <li>Tap <strong>"Get Location"</strong> again</li>
                </ol>
            </div>
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-top: 15px;">
                <strong style="color: #e65100;">Alternative:</strong>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 14px;">
                    Settings → Privacy → Location Services → Safari → While Using the App
                </p>
            </div>
        `;
    } else {
        instructions = `
            <div style="background: #e8f5e9; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #4CAF50;">
                <h3 style="margin: 0 0 15px 0; color: #2e7d32; font-size: 16px;">💻 Desktop Instructions:</h3>
                <ol style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                    <li>Click the <strong>🔒 lock icon</strong> in the address bar</li>
                    <li>Find <strong>"Location"</strong> permission</li>
                    <li>Change to <strong>"Allow"</strong></li>
                    <li>Refresh this page</li>
                    <li>Click <strong>"Get Location"</strong> again</li>
                </ol>
            </div>
        `;
    }
    
    content.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 48px; margin-bottom: 10px;">🚫</div>
            <h2 style="margin: 0; color: #d32f2f; font-size: 22px;">Location Permission Denied</h2>
        </div>
        <p style="margin: 0 0 10px 0; color: #666; text-align: center; line-height: 1.6;">
            You need to allow location access for this feature to work.
        </p>
        ${instructions}
        <button id="closePermissionModal" style="
            width: 100%;
            padding: 16px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        " onmouseover="this.style.background='#1976D2'" onmouseout="this.style.background='#2196F3'">
            Got It, I'll Enable Location
        </button>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    document.getElementById('closePermissionModal').addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}

// Show location unavailable popup
function showLocationUnavailablePopup() {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        padding: 30px;
        border-radius: 16px;
        max-width: 450px;
        width: 100%;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    `;
    
    content.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 48px; margin-bottom: 10px;">📡</div>
            <h2 style="margin: 0; color: #ff9800; font-size: 22px;">Location Unavailable</h2>
        </div>
        <p style="margin: 0 0 20px 0; color: #666; text-align: center; line-height: 1.6;">
            Unable to determine your location. Please check the following:
        </p>
        <div style="background: #fff3e0; padding: 20px; border-radius: 12px; border-left: 4px solid #ff9800;">
            <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                <li>Make sure <strong>Location Services</strong> are turned ON</li>
                <li>Check if you're in <strong>Airplane Mode</strong> (turn it OFF)</li>
                <li>Move to an area with <strong>better GPS signal</strong></li>
                <li>Try going <strong>outdoors</strong> for better accuracy</li>
                <li>Restart your device if the problem persists</li>
            </ul>
        </div>
        <button id="closeUnavailableModal" style="
            width: 100%;
            padding: 16px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        ">Try Again</button>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    document.getElementById('closeUnavailableModal').addEventListener('click', function() {
        document.body.removeChild(modal);
        fetchLocation();
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}

// Show location timeout popup
function showLocationTimeoutPopup() {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        padding: 30px;
        border-radius: 16px;
        max-width: 450px;
        width: 100%;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    `;
    
    content.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 48px; margin-bottom: 10px;">⏱️</div>
            <h2 style="margin: 0; color: #ff5722; font-size: 22px;">Location Request Timed Out</h2>
        </div>
        <p style="margin: 0 0 20px 0; color: #666; text-align: center; line-height: 1.6;">
            It's taking too long to get your location. Please check:
        </p>
        <div style="background: #ffebee; padding: 20px; border-radius: 12px; border-left: 4px solid #ff5722;">
            <ul style="margin: 0; padding-left: 20px; color: #555; line-height: 1.8;">
                <li>Check your <strong>internet connection</strong></li>
                <li>Make sure <strong>GPS is enabled</strong></li>
                <li>Move to an <strong>open area</strong> for better signal</li>
                <li>Wait a moment and try again</li>
            </ul>
        </div>
        <button id="closeTimeoutModal" style="
            width: 100%;
            padding: 16px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        ">Try Again</button>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    document.getElementById('closeTimeoutModal').addEventListener('click', function() {
        document.body.removeChild(modal);
        fetchLocation();
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}

// Add CSS animations
if (!document.getElementById('locationModalStyles')) {
    const style = document.createElement('style');
    style.id = 'locationModalStyles';
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
}


// Setup Tool Kit conditional display
function setupToolKitConditional() {
    const toolKitRadios = document.querySelectorAll('input[name="tool_kit"]');
    const toolKitImageGroup = document.getElementById('toolKitImageGroup');
    const toolKitImageInput = document.getElementById('toolKitImage');
    
    if (!toolKitRadios.length || !toolKitImageGroup) return;
    
    function toggleToolKitImage() {
        const selectedValue = document.querySelector('input[name="tool_kit"]:checked')?.value;
        
        if (selectedValue === 'Present') {
            toolKitImageGroup.style.display = 'block';
            if (toolKitImageInput) {
                toolKitImageInput.setAttribute('required', 'required');
            }
        } else {
            toolKitImageGroup.style.display = 'none';
            if (toolKitImageInput) {
                toolKitImageInput.removeAttribute('required');
                toolKitImageInput.value = ''; // Clear the file input
            }
        }
    }
    
    // Add event listeners
    toolKitRadios.forEach(radio => {
        radio.addEventListener('change', toggleToolKitImage);
    });
    
    // Initial check
    toggleToolKitImage();
}

// Setup conditional image fields for Engine step fields
function setupEngineConditionalImages() {
    const conditionalFields = [
        { name: 'engine_noise', containerId: 'engine_noise_image_container', inputId: 'engineNoiseImage' },
        { name: 'engine_oil', containerId: 'engine_oil_image_container', inputId: 'engineOilImage' },
        { name: 'engine_oil_leakage', containerId: 'engine_oil_leakage_image_container', inputId: 'engineOilLeakageImage' },
        { name: 'back_compression', containerId: 'back_compression_image_container', inputId: 'backCompressionImage' },
        { name: 'coolant', containerId: 'coolant_image_container', inputId: 'coolantImage' },
        { name: 'brake_oil', containerId: 'brake_oil_image_container', inputId: 'brakeOilImage' }
    ];
    
    conditionalFields.forEach(field => {
        const radios = document.querySelectorAll(`input[name="${field.name}"]`);
        const container = document.getElementById(field.containerId);
        const imageInput = document.getElementById(field.inputId);
        
        if (!radios.length || !container) return;
        
        function toggleImageField() {
            const notOkRadio = Array.from(radios).find(r => r.value === 'Not Ok' && r.checked);
            
            if (notOkRadio) {
                container.style.display = 'block';
                if (imageInput && !imageInput.dataset.savedFile) {
                    imageInput.setAttribute('required', 'required');
                }
            } else {
                container.style.display = 'none';
                if (imageInput) {
                    imageInput.removeAttribute('required');
                }
            }
        }
        
        radios.forEach(radio => {
            radio.addEventListener('change', toggleImageField);
        });
        
        // Initial check
        toggleImageField();
    });
}


// Setup conditional OBD Scan Photo field
function setupOBDScanPhotoConditional() {
    const faultCodeRadios = document.querySelectorAll('input[name="fault_code_present"]');
    const obdPhotoContainer = document.getElementById('obd_scan_photo_container');
    const obdPhotoInput = document.getElementById('obdScanPhoto');
    
    if (!faultCodeRadios.length || !obdPhotoContainer) return;
    
    function toggleOBDPhoto() {
        const notCheckedRadio = Array.from(faultCodeRadios).find(r => r.value === 'Not Checked' && r.checked);
        
        if (notCheckedRadio) {
            // Hide and remove required if "Not Checked" is selected
            obdPhotoContainer.style.display = 'none';
            if (obdPhotoInput) {
                obdPhotoInput.removeAttribute('required');
            }
        } else {
            // Show and make required for any other selection
            const anySelected = Array.from(faultCodeRadios).some(r => r.checked);
            if (anySelected) {
                obdPhotoContainer.style.display = 'block';
                if (obdPhotoInput && !obdPhotoInput.dataset.savedFile) {
                    obdPhotoInput.setAttribute('required', 'required');
                }
            }
        }
    }
    
    faultCodeRadios.forEach(radio => {
        radio.addEventListener('change', toggleOBDPhoto);
    });
    
    // Initial check
    toggleOBDPhoto();
}


// Setup conditional Fuel Leaks Image field
function setupFuelLeaksImageConditional() {
    const fuelLeaksRadios = document.querySelectorAll('input[name="fuel_leaks_under_body"]');
    const fuelLeaksImageContainer = document.getElementById('fuel_leaks_image_container');
    const fuelLeaksImageInput = document.getElementById('fuelLeaksImage');
    
    if (!fuelLeaksRadios.length || !fuelLeaksImageContainer) return;
    
    function toggleFuelLeaksImage() {
        const presentRadio = Array.from(fuelLeaksRadios).find(r => r.value === 'Present' && r.checked);
        
        if (presentRadio) {
            // Show and make required if "Present" is selected
            fuelLeaksImageContainer.style.display = 'block';
            if (fuelLeaksImageInput && !fuelLeaksImageInput.dataset.savedFile) {
                fuelLeaksImageInput.setAttribute('required', 'required');
            }
        } else {
            // Hide and remove required for any other selection
            fuelLeaksImageContainer.style.display = 'none';
            if (fuelLeaksImageInput) {
                fuelLeaksImageInput.removeAttribute('required');
            }
        }
    }
    
    fuelLeaksRadios.forEach(radio => {
        radio.addEventListener('change', toggleFuelLeaksImage);
    });
    
    // Initial check
    toggleFuelLeaksImage();
}

// Setup conditional Sun Roof Image field
function setupSunRoofImageConditional() {
    const sunRoofRadios = document.querySelectorAll('input[name="sun_roof"]');
    const sunRoofImageContainer = document.getElementById('sun_roof_image_container');
    const sunRoofImageInput = document.getElementById('sunRoofImage');
    
    if (!sunRoofRadios.length || !sunRoofImageContainer) return;
    
    function toggleSunRoofImage() {
        const selectedRadio = Array.from(sunRoofRadios).find(r => r.checked);
        
        if (selectedRadio && (selectedRadio.value === 'Working' || selectedRadio.value === 'Not Working')) {
            // Show and make required if "Working" or "Not Working" is selected
            sunRoofImageContainer.style.display = 'block';
            if (sunRoofImageInput && !sunRoofImageInput.dataset.savedFile) {
                sunRoofImageInput.setAttribute('required', 'required');
            }
        } else {
            // Hide and remove required if "Not Available" is selected
            sunRoofImageContainer.style.display = 'none';
            if (sunRoofImageInput) {
                sunRoofImageInput.removeAttribute('required');
            }
        }
    }
    
    sunRoofRadios.forEach(radio => {
        radio.addEventListener('change', toggleSunRoofImage);
    });
    
    // Initial check
    toggleSunRoofImage();
}
