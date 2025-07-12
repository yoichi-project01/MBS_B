
/* ==================================
   Delivery Page Logic
   ================================== */

import { showModal, closeModal } from '../components/modal.js';
import { showErrorMessage, showInfoMessage } from '../components/notification.js';
import { validateInput } from '../components/validator.js';

class DeliverySystem {
    constructor() {
        this.selectedCustomer = '';
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupAccessibility();
        this.updateTotalAmount(); // 初期ロード時に合計金額を計算
    }

    bindEvents() {
        // 納品書ページでない場合は初期化をスキップ
        if (!document.querySelector('.delivery-container')) {
            return;
        }

        // イベントデリゲーションを使用してボタンクリックを処理
        document.addEventListener('click', (e) => {
            const target = e.target.closest('[data-action]');
            if (target) {
                const action = target.dataset.action;
                switch (action) {
                    case 'searchDeliveries':
                        this.searchDeliveries();
                        break;
                    case 'showCustomerSelect':
                        this.showCustomerSelect();
                        break;
                    case 'hideCustomerSelect':
                        this.hideCustomerSelect();
                        break;
                    case 'confirmCustomerSelection':
                        this.confirmCustomerSelection();
                        break;
                    case 'hideDeliveryDetail':
                        this.hideDeliveryDetail();
                        break;
                    case 'saveDelivery':
                        this.saveDelivery();
                        break;
                    case 'printDelivery':
                        this.printDelivery();
                        break;
                    case 'showDeliveryDetail':
                        const customerName = target.dataset.customerName;
                        this.showDeliveryDetail(customerName);
                        break;
                }
            }

            const customerItem = e.target.closest('.customer-item');
            if (customerItem) {
                const customerName = customerItem.dataset.customerName;
                this.selectCustomer(customerName);
            }
        });

        // テーブル行クリックイベント (詳細表示用)
        const deliveryTableBody = document.getElementById('deliveryTableBody');
        if (deliveryTableBody) {
            deliveryTableBody.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                if (row && !e.target.closest('input[type="checkbox"]')) {
                    const customerName = row.dataset.customerName;
                    this.showDeliveryDetail(customerName);
                }
            });
        }

        // 検索機能 (debounce)
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

        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideCustomerSelect();
                this.hideDeliveryDetail();
            }
        });

        // チェックボックスの動的更新
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
        const detailTableBody = document.getElementById('deliveryDetailBody');
        if (detailTableBody) {
            detailTableBody.addEventListener('change', (e) => {
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
        document.querySelectorAll('.customer-item').forEach(item => {
            item.classList.remove('selected');
            item.style.background = '';
        });
        const selectedItem = document.querySelector(`.customer-item[data-customer-name="${customerName}"]`);
        if (selectedItem) {
            selectedItem.classList.add('selected');
            selectedItem.style.background = 'var(--light-green)';
        }
    }

    confirmCustomerSelection() {
        if (!this.selectedCustomer) {
            showErrorMessage('顧客が選択されていません。');
            return;
        }
        showInfoMessage('顧客選択', `${this.selectedCustomer} が選択されました。`);
        this.hideCustomerSelect();
        // ここで選択された顧客名を使って納品書作成画面に遷移するなどの処理を追加
        // 例: window.location.href = `create_delivery.php?customer=${encodeURIComponent(this.selectedCustomer)}`;
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
        // 実際のプロジェクトではここでAPIコールを行う
        console.log(`Fetching data for ${customerName}`);
        // サンプルデータを表示
        const sampleItems = [
            { name: '週間BCN　10月号', quantity: 1, unitPrice: 1100, amount: 1210, checked: true },
            { name: '日経コンピューター　10月号', quantity: 2, unitPrice: 1000, amount: 2200, checked: true },
            { name: '週間マガジン　10月号', quantity: 1, unitPrice: 800, amount: 880, checked: false },
        ];
        const deliveryDetailBody = document.getElementById('deliveryDetailBody');
        if (deliveryDetailBody) {
            deliveryDetailBody.innerHTML = '';
            sampleItems.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="checkbox-col"><input type="checkbox" ${item.checked ? 'checked' : ''}></td>
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td class="text-right">¥${item.unitPrice.toLocaleString()}</td>
                    <td class="text-right">¥${item.amount.toLocaleString()}</td>
                `;
                deliveryDetailBody.appendChild(row);
            });
            this.updateTotalAmount();
        }
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

    saveDelivery() {
        showInfoMessage('保存', '納品書が保存されました。');
        this.hideDeliveryDetail();
    }

    printDelivery() {
        showInfoMessage('印刷', '納品書を印刷します。');
        // window.print(); // 実際の印刷処理
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
