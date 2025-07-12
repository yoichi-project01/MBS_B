
/* ==================================
   Menu Page (menu.php) Logic
   ================================== */

function initializeMenuPage() {
    const menuButtons = document.querySelectorAll('.menu-button[data-path]');
    menuButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const path = this.getAttribute('data-path');
            const urlParams = new URLSearchParams(window.location.search);
            const store = urlParams.get('store');

            if (path && store) {
                const fullPath = `/MBS_B/${path}?store=${encodeURIComponent(store)}`;
                window.location.href = fullPath;
            }
        });
    });

    enhanceMenuButtons();
}

function enhanceMenuButtons() {
    const menuButtons = document.querySelectorAll('.menu-button');

    menuButtons.forEach(function(button, index) {
        button.addEventListener('mouseenter', function() {
            if (!window.matchMedia('(hover: none)').matches) {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            }
        });

        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });

        button.addEventListener('click', function(e) {
            createRippleEffect(this, e);
        });

        button.style.animationDelay = (index * 0.1) + 's';
    });
}

function createRippleEffect(element, event) {
    const ripple = document.createElement('div');
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;

    ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(126, 217, 87, 0.4);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s ease-out;
        pointer-events: none;
        z-index: 1;
    `;

    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);

    setTimeout(function() {
        if (ripple.parentNode) {
            ripple.remove();
        }
    }, 600);
}

initializeMenuPage();
