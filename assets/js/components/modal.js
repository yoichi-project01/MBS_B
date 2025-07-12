
/* ==================================
   Modal Component
   ================================== */

export function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');

    modal.focus();

    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';

    requestAnimationFrame(() => {
        modal.style.transition = 'all 0.3s ease';
        modal.style.opacity = '1';
        modal.style.transform = 'scale(1)';
    });
}

export function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.transition = 'all 0.3s ease';
    modal.style.opacity = '0';
    modal.style.transform = 'scale(0.9)';

    setTimeout(() => {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.style.transition = '';
        modal.style.transform = '';
        modal.style.opacity = '';
    }, 300);

    const triggerElement = document.querySelector(`[onclick*="${modalId}"]`) ||
                          document.querySelector('.table-action-btn:focus') ||
                          document.activeElement;

    if (triggerElement && typeof triggerElement.focus === 'function') {
        setTimeout(() => {
            triggerElement.focus();
        }, 350);
    }
}

export function initializeModalTriggers() {
    document.addEventListener('click', (event) => {
        const modal = document.querySelector('.modal[style*="block"]');
        if (modal && event.target === modal) {
            closeModal(modal.id);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}
