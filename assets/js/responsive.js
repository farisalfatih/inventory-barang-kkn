// Responsive JavaScript untuk Inventaris KKN

document.addEventListener('DOMContentLoaded', function() {
    // Initialize responsive features
    initResponsiveFeatures();
    initTableResponsive();
    initMobileNavigation();
    initFormValidation();
    initTooltips();
    initLoadingStates();
});

// Responsive Features Initialization
function initResponsiveFeatures() {
    // Add responsive classes based on screen size
    function updateResponsiveClasses() {
        const width = window.innerWidth;
        const body = document.body;
        
        // Remove existing responsive classes
        body.classList.remove('mobile', 'tablet', 'desktop');
        
        // Add appropriate class
        if (width < 768) {
            body.classList.add('mobile');
        } else if (width < 992) {
            body.classList.add('tablet');
        } else {
            body.classList.add('desktop');
        }
    }
    
    // Initial call
    updateResponsiveClasses();
    
    // Update on resize
    window.addEventListener('resize', debounce(updateResponsiveClasses, 250));
}

// Table Responsive Enhancement
function initTableResponsive() {
    const tables = document.querySelectorAll('.table-responsive table');
    
    tables.forEach(table => {
        // Add mobile-friendly table wrapper
        if (!table.closest('.mobile-table-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mobile-table-wrapper';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        // Hide less important columns on mobile
        const headers = table.querySelectorAll('thead th');
        const rows = table.querySelectorAll('tbody tr');
        
        // Mark columns for mobile hiding
        headers.forEach((header, index) => {
            const headerText = header.textContent.toLowerCase();
            
            // Hide these columns on mobile
            const mobileHideColumns = ['keterangan', 'alamat', 'kontak', 'dokumen', 'penanggung jawab'];
            
            if (mobileHideColumns.some(col => headerText.includes(col))) {
                header.classList.add('d-none-mobile');
                
                rows.forEach(row => {
                    const cell = row.children[index];
                    if (cell) {
                        cell.classList.add('d-none-mobile');
                    }
                });
            }
        });
        
        // Add mobile card view for very small screens
        if (window.innerWidth < 576) {
            convertTableToCards(table);
        }
    });
}

// Convert table to card view on very small screens
function convertTableToCards(table) {
    if (table.classList.contains('card-converted')) return;
    
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = table.querySelectorAll('tbody tr');
    
    const cardContainer = document.createElement('div');
    cardContainer.className = 'mobile-card-view d-block d-sm-none';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const card = document.createElement('div');
        card.className = 'card mb-3';
        
        let cardContent = '<div class="card-body">';
        
        cells.forEach((cell, index) => {
            if (headers[index] && cell.textContent.trim() !== '') {
                cardContent += `
                    <div class="row mb-2">
                        <div class="col-5 fw-bold">${headers[index]}:</div>
                        <div class="col-7">${cell.innerHTML}</div>
                    </div>
                `;
            }
        });
        
        cardContent += '</div>';
        card.innerHTML = cardContent;
        cardContainer.appendChild(card);
    });
    
    table.parentNode.insertBefore(cardContainer, table);
    table.classList.add('d-none', 'd-sm-table', 'card-converted');
}

// Mobile Navigation Enhancement
function initMobileNavigation() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    // Add mobile menu toggle if not exists
    let toggleButton = navbar.querySelector('.navbar-toggler');
    if (!toggleButton) {
        toggleButton = document.createElement('button');
        toggleButton.className = 'navbar-toggler';
        toggleButton.type = 'button';
        toggleButton.setAttribute('data-bs-toggle', 'collapse');
        toggleButton.setAttribute('data-bs-target', '#navbarNav');
        toggleButton.innerHTML = '<span class="navbar-toggler-icon"></span>';
        
        const brand = navbar.querySelector('.navbar-brand');
        brand.parentNode.insertBefore(toggleButton, brand.nextSibling);
    }
    
    // Make navbar collapsible on mobile
    const navLinks = navbar.querySelector('.navbar-nav');
    if (navLinks && !navLinks.closest('.navbar-collapse')) {
        const collapse = document.createElement('div');
        collapse.className = 'collapse navbar-collapse';
        collapse.id = 'navbarNav';
        
        navLinks.parentNode.insertBefore(collapse, navLinks);
        collapse.appendChild(navLinks);
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        const navbarCollapse = document.querySelector('.navbar-collapse');
        const toggleButton = document.querySelector('.navbar-toggler');
        
        if (navbarCollapse && navbarCollapse.classList.contains('show')) {
            if (!navbar.contains(e.target)) {
                toggleButton.click();
            }
        }
    });
}

// Form Validation Enhancement
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Add real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });
        
        // Enhanced form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showAlert('Mohon periksa kembali form yang Anda isi.', 'danger');
            } else {
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                }
            }
        });
    });
}

// Field validation function
function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');
    let isValid = true;
    let message = '';
    
    // Remove existing validation classes
    field.classList.remove('is-valid', 'is-invalid');
    
    // Required field validation
    if (isRequired && !value) {
        isValid = false;
        message = 'Field ini wajib diisi.';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Format email tidak valid.';
        }
    }
    
    // Number validation
    if (field.type === 'number' && value) {
        if (isNaN(value) || parseFloat(value) < 0) {
            isValid = false;
            message = 'Masukkan angka yang valid.';
        }
    }
    
    // Date validation
    if (field.type === 'date' && value) {
        const date = new Date(value);
        if (isNaN(date.getTime())) {
            isValid = false;
            message = 'Format tanggal tidak valid.';
        }
    }
    
    // Apply validation result
    if (isValid) {
        field.classList.add('is-valid');
    } else {
        field.classList.add('is-invalid');
        showFieldError(field, message);
    }
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

// Initialize tooltips
function initTooltips() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Add custom tooltips for truncated text
    const truncatedElements = document.querySelectorAll('.detail-text, .text-truncate-mobile');
    
    truncatedElements.forEach(element => {
        if (element.scrollWidth > element.clientWidth) {
            element.setAttribute('title', element.textContent);
            element.style.cursor = 'help';
        }
    });
}

// Loading states
function initLoadingStates() {
    // Add loading state to buttons with data-loading attribute
    const loadingButtons = document.querySelectorAll('[data-loading]');
    
    loadingButtons.forEach(button => {
        button.addEventListener('click', function() {
            if (!this.disabled) {
                this.classList.add('loading');
                this.disabled = true;
                
                // Auto-remove loading state after 5 seconds
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 5000);
            }
        });
    });
}

// Utility Functions

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Show alert function
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert.auto-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show auto-alert`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at top of main content
    const container = document.querySelector('.container, .container-fluid');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Format currency for Indonesian Rupiah
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Format date for Indonesian locale
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}

// Smooth scroll to element
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Lazy load images
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Export functions for global use
window.ResponsiveUtils = {
    showAlert,
    formatRupiah,
    formatDate,
    scrollToElement,
    isInViewport,
    validateField,
    debounce
};

// Initialize lazy loading if supported
if ('IntersectionObserver' in window) {
    initLazyLoading();
}

// Handle orientation change on mobile devices
window.addEventListener('orientationchange', function() {
    setTimeout(() => {
        // Recalculate responsive features after orientation change
        initTableResponsive();
        
        // Trigger resize event
        window.dispatchEvent(new Event('resize'));
    }, 100);
});

// Performance optimization: Passive event listeners
const passiveEvents = ['scroll', 'touchstart', 'touchmove', 'wheel'];
passiveEvents.forEach(eventName => {
    document.addEventListener(eventName, function() {}, { passive: true });
});

