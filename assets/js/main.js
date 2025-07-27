
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
    console.log('Current path:', path);

    if (path.endsWith('/index.php') && !path.includes('customer_information/') && !path.includes('menu.php') && !path.includes('statistics/') && !path.includes('delivery_list/')) {
        console.log('Loading top.js...');
        import('./pages/top.js');
    } else if (path.includes('menu.php')) {
        import('./pages/menu.js');
    } else if (path.includes('customer_information/index.php')) {
        console.log('Loading customer.js...');
        import('./pages/customer.js');
    } else if (path.includes('statistics/index.php')) {
        console.log('Loading statistics.js...');
        import('./pages/statistics.js');
    } else if (path.includes('delivery_list/index.php')) {
        console.log('Loading delivery.js...');
        import('./pages/delivery.js');
    }
});
