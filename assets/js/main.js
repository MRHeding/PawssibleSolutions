/**
 * Pet Veterinary Appointment System Main JavaScript
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize components and event listeners
    initializeFormValidation();
    initializeAppointmentTimePicker();
    setupMobileMenuToggle();
    setupNotificationDismissal();
    initializeTooltips();
});

/**
 * Form validation for various forms
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let hasError = false;
            const requiredFields = form.querySelectorAll('[required]');
            
            // Clear previous error messages
            const errorMessages = form.querySelectorAll('.error-message');
            errorMessages.forEach(el => el.remove());
            
            // Check each required field
            requiredFields.forEach(field => {
                field.classList.remove('border-red-500');
                
                if (!field.value.trim()) {
                    hasError = true;
                    field.classList.add('border-red-500');
                    
                    const errorMessage = document.createElement('p');
                    errorMessage.className = 'text-red-500 text-xs mt-1 error-message';
                    errorMessage.innerText = 'This field is required';
                    field.parentNode.appendChild(errorMessage);
                }
                
                // Email validation
                if (field.type === 'email' && field.value.trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test(field.value.trim())) {
                        hasError = true;
                        field.classList.add('border-red-500');
                        
                        const errorMessage = document.createElement('p');
                        errorMessage.className = 'text-red-500 text-xs mt-1 error-message';
                        errorMessage.innerText = 'Please enter a valid email address';
                        field.parentNode.appendChild(errorMessage);
                    }
                }
            });
            
            // Password matching validation if applicable
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value && confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    hasError = true;
                    confirmPassword.classList.add('border-red-500');
                    
                    const errorMessage = document.createElement('p');
                    errorMessage.className = 'text-red-500 text-xs mt-1 error-message';
                    errorMessage.innerText = 'Passwords do not match';
                    confirmPassword.parentNode.appendChild(errorMessage);
                }
            }
            
            // Prevent form submission if validation fails
            if (hasError) {
                event.preventDefault();
            }
        });
    });
}

/**
 * Initialize appointment time picker functionality
 */
function initializeAppointmentTimePicker() {
    const dateInput = document.querySelector('#appointment_date');
    const timeSelect = document.querySelector('#appointment_time');
    
    if (dateInput && timeSelect) {
        dateInput.addEventListener('change', function() {
            // In a real application, this would query the backend for available times on the selected date
            console.log('Date selected:', dateInput.value);
            
            // For demonstration purposes, let's simulate some time slots being unavailable on certain days
            const selectedDate = new Date(dateInput.value);
            const dayOfWeek = selectedDate.getDay(); // 0 = Sunday, 1 = Monday, etc.
            
            // Example: On weekends, only morning appointments are available
            Array.from(timeSelect.options).forEach(option => {
                const timeValue = option.value;
                
                // Skip the placeholder option
                if (!timeValue) return;
                
                // Parse time (e.g., "14:30:00" -> 14.5)
                const hour = parseInt(timeValue.split(':')[0]);
                const minute = parseInt(timeValue.split(':')[1]) / 60;
                const timeDecimal = hour + minute;
                
                // Example rule: weekends only have morning appointments
                if ((dayOfWeek === 0 || dayOfWeek === 6) && timeDecimal >= 12) {
                    option.disabled = true;
                    option.classList.add('text-gray-400');
                } else {
                    option.disabled = false;
                    option.classList.remove('text-gray-400');
                }
            });
        });
    }
}

/**
 * Set up mobile menu toggle functionality
 */
function setupMobileMenuToggle() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
}

/**
 * Set up notification dismissal functionality
 */
function setupNotificationDismissal() {
    const notificationCloseButtons = document.querySelectorAll('.notification-close');
    
    notificationCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const notification = this.closest('.notification');
            if (notification) {
                notification.classList.add('opacity-0');
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300); // Match this with CSS transition duration
            }
        });
    });
    
    // Auto-dismiss success messages after 5 seconds
    const successMessages = document.querySelectorAll('.bg-green-100');
    successMessages.forEach(message => {
        setTimeout(() => {
            message.classList.add('opacity-0');
            setTimeout(() => {
                message.style.display = 'none';
            }, 300);
        }, 5000);
    });
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            
            // Create tooltip element
            const tooltipElement = document.createElement('div');
            tooltipElement.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-800 rounded shadow-lg transition-opacity';
            tooltipElement.textContent = tooltipText;
            tooltipElement.style.bottom = 'calc(100% + 5px)';
            tooltipElement.style.left = '50%';
            tooltipElement.style.transform = 'translateX(-50%)';
            tooltipElement.style.opacity = '0';
            
            // Add tooltip to DOM
            this.style.position = 'relative';
            this.appendChild(tooltipElement);
            
            // Fade in
            setTimeout(() => {
                tooltipElement.style.opacity = '1';
            }, 10);
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipElement = this.querySelector('div');
            if (tooltipElement) {
                tooltipElement.style.opacity = '0';
                setTimeout(() => {
                    tooltipElement.remove();
                }, 300);
            }
        });
    });
}

/**
 * Pet weight chart functionality (for pet details page)
 */
function initializePetWeightChart() {
    const weightChartCanvas = document.getElementById('weightChart');
    if (!weightChartCanvas) return;
    
    // This would fetch data from the backend in a real application
    // For now, we'll use sample data
    const weightData = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Weight (kg)',
            data: [5.2, 5.3, 5.4, 5.3, 5.5, 5.6],
            backgroundColor: 'rgba(79, 209, 197, 0.2)',
            borderColor: 'rgba(79, 209, 197, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(79, 209, 197, 1)',
            tension: 0.4
        }]
    };
    
    // If Chart.js is loaded, create a chart
    if (typeof Chart !== 'undefined') {
        new Chart(weightChartCanvas, {
            type: 'line',
            data: weightData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
}

/**
 * Initialize any custom dropdowns
 */
function initializeCustomDropdowns() {
    const dropdownButtons = document.querySelectorAll('.custom-dropdown-button');
    
    dropdownButtons.forEach(button => {
        button.addEventListener('click', function() {
            const dropdown = this.nextElementSibling;
            
            // Close all other dropdowns
            document.querySelectorAll('.custom-dropdown-content').forEach(content => {
                if (content !== dropdown) {
                    content.classList.add('hidden');
                }
            });
            
            // Toggle current dropdown
            dropdown.classList.toggle('hidden');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.custom-dropdown-content').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }
    });
}

// Call any additional initialization functions that weren't included in the DOMContentLoaded event
document.addEventListener('DOMContentLoaded', function() {
    initializePetWeightChart();
    initializeCustomDropdowns();
    
    // Add data-validate attribute to forms that need validation
    document.querySelectorAll('form').forEach(form => {
        if (form.querySelector('[required]')) {
            form.setAttribute('data-validate', 'true');
        }
    });
});
