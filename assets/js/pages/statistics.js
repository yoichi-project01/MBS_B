/* ==================================
   Statistics Page Logic
   ================================== */

import { showModal, closeModal } from '../components/modal.js';
import { showErrorMessage } from '../components/notification.js';
import { validateInput, sanitizeInput } from '../components/validator.js';

// showDetails 関数をグローバルスコープに公開
window.showDetails = showStatisticsDetails;

function initializeStatisticsPage() {
    if (window.location.pathname.includes('/statistics/')) {
        document.body.classList.add('statistics-page');
    }

    setupEventListeners();
    setupAccessibility();
    initializeTabNavigation();
    initializeSidebarToggle();
    initializeViewToggle();
    setupModalHandlers();
    enhanceAnimations();

    // 初期ロード時およびタブ切り替え時のイベントリスナー再初期化
    reinitializeTabContentListeners();
    window.addEventListener('tabSwitched', reinitializeTabContentListeners);
}

function reinitializeTabContentListeners() {
    setupSortButtons();
    initializeSearch();
    // 詳細ボタンのイベントリスナーを再設定
    document.querySelectorAll('.table-action-btn, .card-btn').forEach(button => {
        // 既存のイベントリスナーを削除してから追加することで重複を防ぐ
        button.removeEventListener('click', handleDetailButtonClick);
        button.addEventListener('click', handleDetailButtonClick);
    });
}

function setupEventListeners() {
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modal = document.getElementById('detailModal');
            if (modal && modal.style.display === 'block') {
                closeModal('detailModal');
            }
        }
    });

    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearchInput, 300));
    }

    window.addEventListener('resize', debounce(handleResize, 250));

    // 初期ロード時の詳細ボタンイベントリスナー設定
    document.querySelectorAll('.table-action-btn, .card-btn').forEach(button => {
        button.addEventListener('click', handleDetailButtonClick);
    });
}

function handleDetailButtonClick(event) {
    const customerName = event.currentTarget.dataset.customerName;
    showStatisticsDetails(customerName);
}

function handleResize() {
    if (window.innerWidth > 768) {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.overlay');
        if (sidebar && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            if (overlay) overlay.remove();
        }
    }
}

function initializeTabNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const mainTitle = document.getElementById('main-title');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            if (link.href && link.href.startsWith('http') && !link.dataset.tab) {
                return;
            }

            e.preventDefault();

            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            if (sidebar && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                if (overlay) overlay.remove();
            }

            const tab = link.dataset.tab;
            if (tab) {
                switchTab(tab, link, navLinks, tabContents, mainTitle);
            }
        });
    });

    const initialActiveLink = document.querySelector('.nav-link.active');
    if (initialActiveLink && initialActiveLink.dataset.tab) {
        const initialTab = initialActiveLink.dataset.tab;
        switchTab(initialTab, initialActiveLink, navLinks, tabContents, mainTitle);
    }
}

function switchTab(targetTab, activeLink, navLinks, tabContents, mainTitle) {
    navLinks.forEach(link => {
        link.classList.remove('active');
        link.setAttribute('aria-current', 'false');
    });

    tabContents.forEach(content => {
        content.classList.remove('active');
        content.setAttribute('aria-hidden', 'true');
    });

    activeLink.classList.add('active');
    activeLink.setAttribute('aria-current', 'page');

    const targetContent = document.getElementById(targetTab);
    if (targetContent) {
        targetContent.classList.add('active');
        targetContent.setAttribute('aria-hidden', 'false');
        animateTabSwitch(targetContent);
    }

    const titleSpan = activeLink.querySelector('span');
    if (titleSpan && mainTitle) {
        mainTitle.textContent = titleSpan.textContent;
    }

    window.dispatchEvent(new CustomEvent('tabSwitched', {
        detail: { 
            tab: targetTab, 
            title: titleSpan ? titleSpan.textContent : targetTab 
        }
    }));
}

function animateTabSwitch(targetContent) {
    targetContent.style.opacity = '0';
    targetContent.style.transform = 'translateX(20px)';

    requestAnimationFrame(() => {
        targetContent.style.transition = 'all 0.3s ease';
        targetContent.style.opacity = '1';
        targetContent.style.transform = 'translateX(0)';

        setTimeout(() => {
            targetContent.style.transition = '';
        }, 300);
    });
}

function setupSortButtons() {
    const sortButtons = document.querySelectorAll('.sort-btn, [data-sort]');
    sortButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            const column = this.getAttribute('data-column') || this.getAttribute('data-sort');
            const currentOrder = this.classList.contains('sort-asc') ? 'desc' : 'asc';

            if (column) {
                sortTable(column, currentOrder, this);
            }
        });

        const column = button.getAttribute('data-column') || button.getAttribute('data-sort');
        if (column && !button.getAttribute('data-tooltip')) {
            const columnNames = {
                'customer_name': '顧客名',
                'name': '顧客名',
                'sales_by_customer': '売上',
                'sales': '売上',
                'lead_time': 'リードタイム',
                'leadtime': 'リードタイム',
                'delivery_amount': '配達回数',
                'deliveries': '配達回数'
            };

            if (columnNames[column]) {
                button.setAttribute('data-tooltip', `${columnNames[column]}でソート`);
                button.classList.add('tooltip');
            }
        }
    });

    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const sortType = this.dataset.sort;
            const isAscending = this.classList.contains('sort-asc');

            sortableHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });

            const newOrder = isAscending ? 'desc' : 'asc';
            this.classList.add(`sort-${newOrder}`);

            sortTableByHeader(sortType, newOrder, this);
        });
    });
}

function sortTableByHeader(sortType, order, header) {
    const table = header.closest('table');
    const tbody = table.querySelector('tbody');

    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        let aValue, bValue;
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);

        aValue = a.cells[columnIndex]?.textContent.trim() || '';
        bValue = b.cells[columnIndex]?.textContent.trim() || '';

        if (sortType === 'sales' || sortType === 'deliveries') {
            aValue = parseFloat(aValue.replace(/[,¥]/g, '')) || 0;
            bValue = parseFloat(bValue.replace(/[,¥]/g, '')) || 0;
        } else if (sortType === 'leadtime') {
            aValue = parseLeadTimeToSeconds(aValue);
            bValue = parseLeadTimeToSeconds(bValue);
        } else {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }

        if (order === 'asc') {
            return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
        } else {
            return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
        }
    });

    animateTableSort(tbody, rows);
    announceSort(sortType, order);
}

function showStatisticsDetails(customerName) {
    const modal = document.getElementById('detailModal');
    const title = document.getElementById('detailTitle');
    const content = document.getElementById('detailContent');

    if (!modal || !title || !content) {
        createDetailModal();
        return showStatisticsDetails(customerName);
    }

    title.textContent = `${customerName} の詳細情報`;

    const customerInfo = window.customerData.find(customer => 
        customer.customer_name === customerName
    );

    const detailHtml = `
        <div class="customer-detail-info">
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> 基本情報</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>顧客名:</label>
                        <span>${sanitizeInput(customerName)}</span>
                    </div>
                    <div class="detail-item">
                        <label>顧客ID:</label>
                        <span>${customerInfo ? customerInfo.customer_no : 'N/A'}</span>
                    </div>
                    <div class="detail-item">
                        <label>登録日:</label>
                        <span>データ取得中...</span>
                    </div>
                    <div class="detail-item">
                        <label>ステータス:</label>
                        <span class="badge success">アクティブ</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-chart-line"></i> 売上統計</h4>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">${customerInfo ? `¥${customerInfo.sales_by_customer.toLocaleString()}` : 'N/A'}</div>
                        <div class="stat-label">総売上</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${customerInfo ? customerInfo.delivery_amount : 'N/A'}</div>
                        <div class="stat-label">配達回数</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${customerInfo ? customerInfo.lead_time : 'N/A'}</div>
                        <div class="stat-label">平均リードタイム</div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-history"></i> 取引履歴</h4>
                <p class="loading-text">取引履歴を読み込み中...</p>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-sticky-note"></i> 備考・特記事項</h4>
                <p>特記事項はありません。</p>
            </div>
        </div>
    `;

    content.innerHTML = detailHtml;
    showModal('detailModal');

    setTimeout(() => {
        loadCustomerDetailData(customerName);
    }, 500);
}

function createDetailModal() {
    const modalHtml = `
        <div id="detailModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="detailTitle">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="detailTitle">顧客詳細</h2>
                    <button class="close-modal" onclick="closeModal('detailModal')" aria-label="モーダルを閉じる">
                        &times;
                    </button>
                </div>
                <div class="modal-body" id="detailContent">
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function loadCustomerDetailData(customerName) {
    setTimeout(() => {
        const registrationDate = document.querySelector('.detail-item:nth-child(3) span');
        const historySection = document.querySelector('.detail-section:nth-child(3) p');

        if (registrationDate) {
            registrationDate.textContent = '2023-01-15';
        }

        if (historySection) {
            historySection.innerHTML = `
                <div class="history-timeline">
                    <div class="history-item">
                        <div class="history-date">2024-12-15</div>
                        <div class="history-content">商品購入: ¥25,000</div>
                    </div>
                    <div class="history-item">
                        <div class="history-date">2024-11-28</div>
                        <div class="history-content">商品購入: ¥18,500</div>
                    </div>
                    <div class="history-item">
                        <div class="history-date">2024-10-10</div>
                        <div class="history-content">初回購入: ¥12,000</div>
                    </div>
                </div>
            `;
        }
    }, 1000);
}

function setupAccessibility() {
    const tables = document.querySelectorAll('.enhanced-statistics-table, .statistics-table, .data-table');
    tables.forEach(function(table) {
        table.setAttribute('aria-label', '顧客統計情報テーブル');
        table.setAttribute('role', 'table');
    });

    document.querySelectorAll('.sort-btn, [data-sort]').forEach(function(button) {
        const column = button.getAttribute('data-column') || button.getAttribute('data-sort');
        const columnNames = {
            'customer_name': '顧客名',
            'name': '顧客名',
            'sales_by_customer': '売上',
            'sales': '売上',
            'lead_time': 'リードタイム',
            'leadtime': 'リードタイム',
            'delivery_amount': '配達回数',
            'deliveries': '配達回数'
        };

        if (column && columnNames[column]) {
            button.setAttribute('aria-label', `${columnNames[column]}でソート`);
        }
    });
}

function sortTable(column, order, activeButton) {
    const tbody = document.getElementById('customerTableBody') ||
                 document.querySelector('.enhanced-statistics-table tbody') ||
                 document.querySelector('.statistics-table tbody') ||
                 document.querySelector('.data-table tbody');

    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        let aValue, bValue;
        const columnIndex = Array.from(header.parentNode.children).indexOf(header);

        aValue = a.cells[columnIndex]?.textContent.trim() || '';
        bValue = b.cells[columnIndex]?.textContent.trim() || '';

        if (sortType === 'sales' || sortType === 'deliveries') {
            aValue = parseFloat(aValue.replace(/[,¥]/g, '')) || 0;
            bValue = parseFloat(bValue.replace(/[,¥]/g, '')) || 0;
        } else if (sortType === 'leadtime') {
            aValue = parseLeadTimeToSeconds(aValue);
            bValue = parseLeadTimeToSeconds(bValue);
        } else {
            aValue = aValue.toLowerCase();
            bValue = bValue.toLowerCase();
        }

        if (order === 'asc') {
            return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
        } else {
            return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
        }
    });

    updateSortButtonState(activeButton, order);
    animateTableSort(tbody, rows);
    announceSort(sortType, order);
}

function getColumnIndex(column) {
    const columnMappings = {
        'customer_name': 0,
        'name': 0,
        'sales_by_customer': 1,
        'sales': 1,
        'lead_time': 2,
        'leadtime': 2,
        'delivery_amount': 3,
        'deliveries': 3
    };

    return columnMappings[column] || 0;
}

function parseLeadTimeToSeconds(timeStr) {
    let totalSeconds = 0;
    const patterns = [
        { regex: /(\d+)日/, multiplier: 86400 },
        { regex: /(\d+)時間/, multiplier: 3600 },
        { regex: /(\d+)分/, multiplier: 60 },
        { regex: /(\d+)秒/, multiplier: 1 }
    ];

    patterns.forEach(function(pattern) {
        const match = timeStr.match(pattern.regex);
        if (match) {
            totalSeconds += parseInt(match[1], 10) * pattern.multiplier;
        }
    });

    return totalSeconds;
}

function updateSortButtonState(activeButton, order) {
    document.querySelectorAll('.sort-btn, [data-sort]').forEach(function(btn) {
        btn.classList.remove('active', 'sort-asc', 'sort-desc');
        btn.setAttribute('aria-pressed', 'false');
    });

    if (activeButton) {
        activeButton.classList.add('active', order === 'asc' ? 'sort-asc' : 'sort-desc');
        activeButton.setAttribute('aria-pressed', 'true');
    }
}

function animateTableSort(tbody, sortedRows) {
    tbody.style.opacity = '0.6';
    tbody.style.transform = 'translateY(10px)';

    setTimeout(function() {
        tbody.innerHTML = '';
        sortedRows.forEach(function(row) {
            tbody.appendChild(row);
        });

        tbody.style.transition = 'all 0.3s ease';
        tbody.style.opacity = '1';
        tbody.style.transform = 'translateY(0)';

        setTimeout(() => {
            tbody.style.transition = '';
        }, 300);
    }, 150);
}

function announceSort(column, order) {
    const columnNames = {
        'customer_name': '顧客名',
        'name': '顧客名',
        'sales_by_customer': '売上',
        'sales': '売上',
        'lead_time': 'リードタイム',
        'leadtime': 'リードタイム',
        'delivery_amount': '配達回数',
        'deliveries': '配達回数'
    };
    const orderText = order === 'asc' ? '昇順' : '降順';
    const message = `${columnNames[column] || column}を${orderText}でソートしました`;

    announceToScreenReader(message);
}

function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.className = 'sr-only';
    announcement.textContent = message;

    document.body.appendChild(announcement);

    setTimeout(function() {
        if (announcement.parentNode) {
            document.body.removeChild(announcement);
        }
    }, 1000);
}

function handleSearchInput(event) {
    const searchTerm = event.target.value.toLowerCase().trim();

    if (!validateInput(searchTerm, 'text', 100)) {
        event.target.value = '';
        showErrorMessage('無効な文字が含まれています。');
        return;
    }

    const tbody = document.getElementById('customerTableBody') ||
                 document.querySelector('.enhanced-statistics-table tbody') ||
                 document.querySelector('.statistics-table tbody') ||
                 document.querySelector('.data-table tbody');

    if (!tbody) return;

    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;

    rows.forEach(function(row) {
        const customerNameCell = row.querySelector('[data-column="customer_name"]') || 
                               row.cells[0];
        if (!customerNameCell) return;

        const customerName = customerNameCell.textContent.toLowerCase();
        const isVisible = searchTerm === '' || customerName.includes(searchTerm);

        if (isVisible) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const message = searchTerm ? `${visibleCount}件の顧客が見つかりました` : '全ての顧客を表示しています';
    announceToScreenReader(message);
}

function initializeSidebarToggle() {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (!menuToggle || !sidebar) return;

    const toggleSidebar = () => {
        sidebar.classList.toggle('active');
        if (sidebar.classList.contains('active')) {
            const overlay = document.createElement('div');
            overlay.classList.add('overlay');
            document.body.appendChild(overlay);
            overlay.addEventListener('click', toggleSidebar);
        } else {
            const overlay = document.querySelector('.overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    };

    menuToggle.addEventListener('click', toggleSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            toggleSidebar();
        }
    });
}

function initializeViewToggle() {
    const viewBtns = document.querySelectorAll('.view-btn');
    const tableView = document.querySelector('.table-view-container');
    const cardView = document.querySelector('.card-view-container');

    if (viewBtns.length === 0) return;

    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const viewType = btn.dataset.view;

            if (viewType === 'table') {
                if (tableView) tableView.style.display = 'block';
                if (cardView) cardView.style.display = 'none';
            } else if (viewType === 'card') {
                if (tableView) tableView.style.display = 'none';
                if (cardView) cardView.style.display = 'grid';
            }

            animateViewSwitch(viewType);
        });
    });
}

function animateViewSwitch(viewType) {
    const container = viewType === 'table' ? 
        document.querySelector('.table-view-container') : 
        document.querySelector('.card-view-container');

    if (!container) return;

    container.style.opacity = '0';
    container.style.transform = 'translateY(20px)';

    setTimeout(() => {
        container.style.transition = 'all 0.3s ease';
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';

        setTimeout(() => {
            container.style.transition = '';
        }, 300);
    }, 50);
}

function initializeSearch() {
    const customerSearchInput = document.getElementById('customerSearchInput');

    if (!customerSearchInput) return;

    customerSearchInput.addEventListener('keyup', () => {
        const filter = customerSearchInput.value.toLowerCase().trim();

        if (!validateInput(filter, 'text', 100)) {
            customerSearchInput.value = '';
            showErrorMessage('無効な文字が含まれています。');
            return;
        }

        const tableRows = document.querySelectorAll('.data-table tbody tr');
        let tableVisibleCount = 0;

        tableRows.forEach(row => {
            const nameCell = row.cells[0];
            if (nameCell) {
                const name = nameCell.textContent.toLowerCase();
                const isVisible = name.includes(filter);
                row.style.display = isVisible ? '' : 'none';
                if (isVisible) tableVisibleCount++;
            }
        });

        const customerCards = document.querySelectorAll('.card-view-container .customer-card');
        let cardVisibleCount = 0;

        customerCards.forEach(card => {
            const nameElement = card.querySelector('.customer-name');
            if (nameElement) {
                const name = nameElement.textContent.toLowerCase();
                const isVisible = name.includes(filter);
                card.style.display = isVisible ? 'flex' : 'none';
                if (isVisible) cardVisibleCount++;
            }
        });

        const totalVisible = Math.max(tableVisibleCount, cardVisibleCount);
        const message = filter ? 
            `${totalVisible}件の顧客が見つかりました` : 
            '全ての顧客を表示しています';
        announceToScreenReader(message);
    });
}

function setupModalHandlers() {
    window.addEventListener('click', function(event) {
        const detailModal = document.getElementById('detailModal');
        if (event.target === detailModal) {
            closeModal('detailModal');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="block"]');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

function enhanceAnimations() {
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach((card, index) => {
        card.addEventListener('mouseenter', () => {
            if (!window.matchMedia('(hover: none)').matches) {
                card.style.transform = 'translateY(-5px)';
            }
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });

        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    const topCustomerCards = document.querySelectorAll('.top-customer-card');
    topCustomerCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');

        card.addEventListener('mouseenter', () => {
            if (!window.matchMedia('(hover: none)').matches) {
                card.style.transform = 'translateY(-2px)';
            }
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });
}

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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

initializeStatisticsPage();