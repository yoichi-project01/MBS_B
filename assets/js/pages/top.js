
/* ==================================
   Top Page (index.html) Logic
   ================================== */

import { showErrorMessage } from '../components/notification.js';
import { validateInput, sanitizeInput } from '../components/validator.js';

function selectedStore(storeName) {
    if (!storeName || typeof storeName !== 'string') {
        showErrorMessage('無効な店舗名です。');
        return;
    }

    const sanitizedStoreName = sanitizeInput(storeName.trim());

    if (!validateInput(sanitizedStoreName, 'text', 50)) {
        showErrorMessage('無効な文字が含まれています。');
        return;
    }

    const allowedStores = ['緑橋本店', '今里店', '深江橋店'];
    if (!allowedStores.includes(sanitizedStoreName)) {
        showErrorMessage('許可されていない店舗名です。');
        return;
    }

    // ページ遷移
    window.location.href = '/MBS_B/menu.php?store=' + encodeURIComponent(sanitizedStoreName);
}

function initializeTopPage() {
    const storeButtons = document.querySelectorAll('.menu-button[data-store]');
    storeButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const storeName = this.getAttribute('data-store');
            selectedStore(storeName);
        });
    });
}

initializeTopPage();
