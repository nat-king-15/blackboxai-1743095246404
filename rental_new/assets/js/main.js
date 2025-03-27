/**
 * House Rental Management System
 * Main JavaScript File
 */

// Flash message handling
function showAlert(message, type = 'success', timeout = 3000) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show custom-alert`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Add to document
    document.body.appendChild(alertDiv);
    
    // Remove after timeout
    setTimeout(() => {
        if (alertDiv) {
            $(alertDiv).fadeOut('slow', function() {
                $(this).remove();
            });
        }
    }, timeout);
    
    // Allow manual close
    alertDiv.querySelector('button.close').addEventListener('click', function() {
        $(alertDiv).fadeOut('slow', function() {
            $(this).remove();
        });
    });
}

// Form validation
function validateForm(formId, options = {}) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    
    // Required fields validation
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Create error message if not exists
            if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = 'This field is required';
                field.parentNode.insertBefore(errorMsg, field.nextElementSibling);
            }
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Email validation
    const emailFields = form.querySelectorAll('input[type="email"]');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    emailFields.forEach(field => {
        if (field.value.trim() && !emailPattern.test(field.value.trim())) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Create error message if not exists
            if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('invalid-feedback')) {
                const errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = 'Please enter a valid email address';
                field.parentNode.insertBefore(errorMsg, field.nextElementSibling);
            }
        }
    });
    
    return isValid;
}

// AJAX form submission
function submitFormAjax(formId, url, successCallback, errorCallback) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm(formId)) return;
        
        const formData = new FormData(form);
        
        // Show loading
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>';
        document.body.appendChild(loadingOverlay);
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Remove loading overlay
            document.body.removeChild(loadingOverlay);
            
            if (data.status === 'success') {
                if (typeof successCallback === 'function') {
                    successCallback(data);
                } else {
                    showAlert(data.message || 'Operation completed successfully', 'success');
                    
                    // Reset form if not specified otherwise
                    if (data.resetForm !== false) {
                        form.reset();
                    }
                    
                    // Redirect if specified
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                }
            } else {
                if (typeof errorCallback === 'function') {
                    errorCallback(data);
                } else {
                    showAlert(data.message || 'An error occurred', 'danger');
                }
            }
        })
        .catch(error => {
            // Remove loading overlay
            document.body.removeChild(loadingOverlay);
            
            console.error('Error:', error);
            
            if (typeof errorCallback === 'function') {
                errorCallback({status: 'error', message: 'An unexpected error occurred'});
            } else {
                showAlert('An unexpected error occurred. Please try again.', 'danger');
            }
        });
    });
}

// Confirmation dialog
function confirmAction(message, callback) {
    Swal.fire({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}

// Document ready
$(document).ready(function() {
    // Toggle sidebar in admin panel
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('collapsed');
        $('.content-wrapper').toggleClass('expanded');
    });
    
    // Handle invalid form input styling on keyup
    $(document).on('keyup', '.is-invalid', function() {
        if ($(this).val().trim()) {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Image preview for file uploads
    $(document).on('change', '.custom-file-input', function() {
        const file = this.files[0];
        const fileType = file.type;
        const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        
        if ($.inArray(fileType, validImageTypes) < 0) {
            showAlert('Please select a valid image file (JPEG, PNG, GIF)', 'warning');
            this.value = '';
            return;
        }
        
        const fileNameElement = $(this).siblings('.custom-file-label');
        fileNameElement.text(file.name);
        
        // If preview container exists
        const previewContainer = $(this).closest('.form-group').find('.image-preview');
        if (previewContainer.length > 0) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.html(`<img src="${e.target.result}" class="img-fluid rounded" alt="Preview">`);
            }
            reader.readAsDataURL(file);
        }
    });
}); 