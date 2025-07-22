
/* ==================================
   Main JavaScript Entry Point
   ================================== */

import { HeaderManager } from './components/header.js';
import { initializeModalTriggers } from './components/modal.js';
import { initializeCustomerNameClick } from './components/customer-details.js';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize common components
    new HeaderManager();
    initializeModalTriggers();
    initializeCustomerNameClick();

    // Page-specific logic loader
    const path = window.location.pathname;

    if (path.endsWith('/') || path.endsWith('index.html')) {
        import('./pages/top.js');
    } else if (path.includes('menu.php')) {
        import('./pages/menu.js');
    } else if (path.includes('customer_information')) {
        import('./pages/customer.js');
    } else if (path.includes('statistics')) {
        import('./pages/statistics.js');
    } else if (path.includes('delivery_list')) {
        import('./pages/delivery.js');
    }
});
