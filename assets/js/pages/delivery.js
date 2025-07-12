
/* ==================================
   Delivery Page Logic
   ================================== */

import { showModal, closeModal } from '../components/modal.js';
import { showErrorMessage } from '../components/notification.js';
import { validateInput } from '../components/validator.js';

class DeliverySystem {
    constructor() {
        this.selectedCustomer = '';
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupAccessibility();
    }

    bindEvents() {
        if (!document.querySelector('.delivery-container')) {
            return;
        }

        const rows = document.querySelectorAll('#deliveryTableBody tr');
        rows.forEach((row) => {
            row.addEventListener('click', (e) => {
                if (e.target.type !== 'checkbox') {
                    const customerName = row.cells[1].textContent;
                    this.showDeliveryDetail(customerName);
                }
            });
        });

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.searchDeliveries();
            }, 300));
        }

        const customerSearchInput = document.getElementById('customerSearchInput');
        if (customerSearchInput) {
            customerSearchInput.addEventListener('input', this.debounce(() => {
                this.searchCustomers();
            }, 300));
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideCustomerSelect();
                this.hideDeliveryDetail();
            }
        });

        const customerSelect = document.getElementById('customerSelect');
        if (customerSelect) {
            customerSelect.addEventListener('click', (e) => {
                if (e.target === customerSelect) {
                    this.hideCustomerSelect();
                }
            });
        }

        const deliveryDetail = document.getElementById('deliveryDetail');
        if (deliveryDetail) {
            deliveryDetail.addEventListener('click', (e) => {
                if (e.target === deliveryDetail) {
                    this.hideDeliveryDetail();
                }
            });
        }

        this.setupCheckboxHandlers();
    }

    setupAccessibility() {
        const customerModal = document.querySelector('.customer-modal');
        if (customerModal) {
            customerModal.setAttribute('role', 'dialog');
            customerModal.setAttribute('aria-modal', 'true');
            customerModal.setAttribute('aria-labelledby', 'customer-modal-title');
        }

        const deliveryModal = document.querySelector('.delivery-modal');
        if (deliveryModal) {
            deliveryModal.setAttribute('role', 'dialog');
            deliveryModal.setAttribute('aria-modal', 'true');
            deliveryModal.setAttribute('aria-labelledby', 'delivery-modal-title');
        }
    }

    setupCheckboxHandlers() {
        const detailTable = document.getElementById('deliveryDetailBody');
        if (detailTable) {
            detailTable.addEventListener('change', (e) => {
                if (e.target.type === 'checkbox') {
                    this.updateTotalAmount();
                }
            });
        }
    }

    showCustomerSelect() {
        showModal('customerSelect');
    }

    hideCustomerSelect() {
        closeModal('customerSelect');
        this.selectedCustomer = '';
        document.querySelectorAll('.customer-item').forEach(item => {
            item.classList.remove('selected');
            item.style.background = '';
        });
    }

    selectCustomer(customerName) {
        if (!validateInput(customerName, 'text', 100)) {
            showErrorMessage('無効な顧客名です。');
            return;
        }

        this.selectedCustomer = customerName;
    }

    showDeliveryDetail(customerName) {
        const detailModal = document.getElementById('deliveryDetail');
        if (!detailModal) return;

        const customerNameEl = document.getElementById('detailCustomerName');
        if(customerNameEl) customerNameEl.textContent = customerName;

        showModal('deliveryDetail');
        this.fetchDeliveryData(customerName);
    }

    hideDeliveryDetail() {
        closeModal('deliveryDetail');
    }

    fetchDeliveryData(customerName) {
        // In a real application, you would fetch this data from a server
        console.log(`Fetching data for ${customerName}`);
    }

    updateTotalAmount() {
        const detailTable = document.getElementById('deliveryDetailBody');
        if (!detailTable) return;

        let total = 0;
        const rows = detailTable.querySelectorAll('tr');
        rows.forEach(row => {
            const checkbox = row.querySelector('input[type="checkbox"]');
            if (checkbox && checkbox.checked) {
                const amountCell = row.cells[4];
                if (amountCell) {
                    total += parseFloat(amountCell.textContent.replace(/[^0-9.-]+/g,""));
                }
            }
        });

        const totalAmountEl = document.getElementById('totalAmount');
        if(totalAmountEl) totalAmountEl.textContent = `¥${total.toLocaleString()}`;
    }

    searchDeliveries() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;
        const filter = searchInput.value.toUpperCase();
        const tableBody = document.getElementById('deliveryTableBody');
        if (!tableBody) return;
        const tr = tableBody.getElementsByTagName('tr');

        for (let i = 0; i < tr.length; i++) {
            let td = tr[i].getElementsByTagName('td')[1]; // Customer Name column
            if (td) {
                let txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    searchCustomers() {
        const searchInput = document.getElementById('customerSearchInput');
        if (!searchInput) return;
        const filter = searchInput.value.toUpperCase();
        const customerList = document.getElementById('customerList');
        if (!customerList) return;
        const items = customerList.getElementsByClassName('customer-item');

        for (let i = 0; i < items.length; i++) {
            let txtValue = items[i].textContent || items[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                items[i].style.display = "";
            } else {
                items[i].style.display = "none";
            }
        }
    }

    debounce(func, wait) {
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
}

new DeliverySystem();
