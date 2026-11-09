// Smooth Navigation and Animation JavaScript
// Add smooth behavior to the entire system

// Initialize smooth navigation on page load
document.addEventListener('DOMContentLoaded', function() {
    initSmoothNavigation();
    initAnimations();
    initFormHandling();
    initAlertHandling();
    initKeyboardNavigation();
});

// Initialize smooth transitions for page navigation
function initSmoothNavigation() {
    // Add fade-in animation to main content
    const main = document.querySelector('main');
    if (main) {
        main.style.animation = 'fadeIn 0.6s ease-in';
    }

    // Smooth link navigation
    const links = document.querySelectorAll('a:not([href^="#"]):not([href^="javascript"])');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            // Don't add animation to external links or anchor links
            if (href && !href.startsWith('http') && !href.startsWith('mailto:')) {
                document.body.style.animation = 'fadeOut 0.4s ease-out forwards';
                // Let the browser complete the fade before navigating
                setTimeout(() => {
                    window.location.href = href;
                }, 400);
                e.preventDefault();
            }
        });
    });

    // Smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
}

// Initialize general animations
function initAnimations() {
    // Animate tables on load
    const tables = document.querySelectorAll('table');
    tables.forEach((table, index) => {
        table.style.animation = `slideUp 0.8s ease-out ${0.2 + index * 0.1}s both`;
    });

    // Animate cards/panels
    const cards = document.querySelectorAll('[class*="card"], [class*="panel"], [class*="box"]');
    cards.forEach((card, index) => {
        if (!card.style.animation) {
            card.style.animation = `slideUp 0.8s ease-out ${0.1 + index * 0.1}s both`;
        }
    });

    // Hover effects on table rows
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.transition = 'all 0.3s ease';
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
            this.style.transform = 'translateX(5px)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
    });

    // Animate buttons on hover
    const buttons = document.querySelectorAll('button, .btn');
    buttons.forEach(btn => {
        btn.style.transition = 'all 0.3s ease';
        btn.addEventListener('mouseenter', function() {
            if (!this.disabled) {
                this.style.transform = 'translateY(-2px)';
                if (this.classList.contains('btn-primary')) {
                    this.style.boxShadow = '0 8px 15px rgba(52, 152, 219, 0.3)';
                }
            }
        });
        btn.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

// Handle form submissions smoothly
function initFormHandling() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalHTML = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.style.opacity = '0.7';
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Restore button after timeout in case of errors
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerHTML = originalHTML;
                }, 3000);
            }
        });
    });

    // Input field animations
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.style.transition = 'all 0.3s ease';
        
        input.addEventListener('focus', function() {
            this.style.borderColor = '#3498db';
            this.style.boxShadow = '0 0 10px rgba(52, 152, 219, 0.2)';
            this.style.transform = 'translateX(2px)';
        });
        
        input.addEventListener('blur', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
            this.style.transform = '';
        });
    });
}

// Handle alert/message animations
function initAlertHandling() {
    const alerts = document.querySelectorAll('.alert, [class*="message"], [class*="notification"]');
    alerts.forEach(alert => {
        alert.style.animation = 'slideUp 0.5s ease-out';
        
        // Auto-hide alerts after 5 seconds
        const hideDelay = alert.dataset.hideDelay || 5000;
        if (hideDelay > 0) {
            setTimeout(() => {
                alert.style.animation = 'slideUp 0.5s ease-out reverse forwards';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            }, hideDelay);
        }
    });

    // Close button for alerts
    const closeButtons = document.querySelectorAll('.alert-close, [class*="close"]');
    closeButtons.forEach(btn => {
        btn.style.cursor = 'pointer';
        btn.style.transition = 'all 0.2s ease';
        
        btn.addEventListener('click', function() {
            const alert = this.closest('.alert, [class*="message"]');
            if (alert) {
                alert.style.animation = 'slideUp 0.4s ease-out reverse forwards';
                setTimeout(() => {
                    alert.remove();
                }, 400);
            }
        });
    });
}

// Keyboard navigation support
function initKeyboardNavigation() {
    // Focus visible outline for accessibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            document.body.classList.add('keyboard-nav');
        }
    });

    document.addEventListener('mousedown', function() {
        document.body.classList.remove('keyboard-nav');
    });

    // Escape key to close modals/dialogs
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('[class*="modal"]:not(.hidden)');
            modals.forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });

    // Enter key to submit forms when focused on inputs
    const inputs = document.querySelectorAll('input:not([type="checkbox"]):not([type="radio"])');
    inputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = this.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });
}

// CSS Animations - Add these styles dynamically if not already in stylesheet
function addSmoothStyles() {
    if (!document.getElementById('smooth-navigation-styles')) {
        const style = document.createElement('style');
        style.id = 'smooth-navigation-styles';
        style.innerHTML = `
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }

            @keyframes fadeOut {
                from {
                    opacity: 1;
                }
                to {
                    opacity: 0;
                }
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes slideInLeft {
                from {
                    opacity: 0;
                    transform: translateX(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes slideInRight {
                from {
                    opacity: 0;
                    transform: translateX(30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            @keyframes pulse {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
                100% {
                    transform: scale(1);
                }
            }

            html {
                scroll-behavior: smooth;
            }

            body {
                transition: all 0.3s ease;
            }

            body.keyboard-nav *:focus {
                outline: 3px solid #3498db;
                outline-offset: 2px;
            }

            * {
                box-sizing: border-box;
            }
        `;
        document.head.appendChild(style);
    }
}

// Add smooth styles on page load
addSmoothStyles();

// Export functions for external use if needed
window.smoothNavigation = {
    initSmoothNavigation,
    initAnimations,
    initFormHandling,
    initAlertHandling,
    initKeyboardNavigation,
    addSmoothStyles
};
