/* ==================================
   Statistics Page Logic (改善版)
   ================================== */

import { showModal, closeModal } from '../components/modal.js';
import { showErrorMessage } from '../components/notification.js';
import { validateInput, sanitizeInput } from '../components/validator.js';

// showDetails 関数をグローバルスコープに公開
window.showDetails = showStatisticsDetails;

class StatisticsManager {
    constructor() {
        this.currentTab = 'dashboard';
        this.currentSort = 'sales';
        this.currentPage = 1;
        this.itemsPerPage = 12;
        this.customerData = []; // 初期化時は空
        this.filteredData = []; // 初期化時は空
        this.searchTerm = '';

        this.dashboardMetrics = {
            totalCustomers: 0,
            monthlySales: 0,
            previousMonthSales: 0,
            salesTrend: 0,
            totalDeliveries: 0,
            avgLeadTime: 0
        };

        this.init();
    }

    async init() {
        this.setupPageLayout();
        this.setupEventListeners();
        this.setupAccessibility();
        this.initializeTabNavigation();
        this.initializeSearch();
        this.initializeSort();
        this.initializePagination();
        this.enhanceAnimations();
        this.setupModals();
        this.initializeDashboardFeatures();

        // データを非同期で取得
        await this.fetchDashboardData();

        // 初期データの表示
        this.updateDisplayedData();
        this.updateMetricCards();
    }

    setupPageLayout() {
        if (window.location.pathname.includes('/statistics/')) {
            document.body.classList.add('statistics-page');
        }
    }

    async fetchDashboardData() {
        try {
            const storeName = window.statisticsData.storeName;
            const response = await fetch(`/MBS_B/statistics/get_dashboard_data.php?store=${encodeURIComponent(storeName)}`);
            const data = await response.json();

            if (data.success) {
                this.dashboardMetrics.totalCustomers = data.data.totalCustomers;
                this.dashboardMetrics.monthlySales = data.data.monthlySales;
                this.dashboardMetrics.previousMonthSales = data.data.previousMonthSales;
                this.dashboardMetrics.salesTrend = data.data.salesTrend;
                this.dashboardMetrics.totalDeliveries = data.data.totalDeliveries;
                this.dashboardMetrics.avgLeadTime = data.data.avgLeadTime;
                this.customerData = data.data.customerList;
                this.filteredData = [...this.customerData];
            } else {
                showErrorMessage(data.message || 'ダッシュボードデータの取得に失敗しました。');
            }
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            showErrorMessage('ダッシュボードデータの取得中にエラーが発生しました。');
        }
    }

    

    
        const navLinks = document.querySelectorAll('.nav-link[data-tab]');
        const tabContents = document.querySelectorAll('.tab-content');

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const tab = link.dataset.tab;
                if (tab) {
                    this.switchTab(tab, link, navLinks, tabContents);
                }
            });
        });

        // 初期タブの設定
        const initialActiveLink = document.querySelector('.nav-link.active[data-tab]');
        if (initialActiveLink) {
            const initialTab = initialActiveLink.dataset.tab;
            this.switchTab(initialTab, initialActiveLink, navLinks, tabContents);
        }
    }

    switchTab(targetTab, activeLink, navLinks, tabContents) {
        // 既存のアクティブ状態をクリア
        navLinks.forEach(link => {
            link.classList.remove('active');
            link.setAttribute('aria-current', 'false');
        });

        tabContents.forEach(content => {
            content.classList.remove('active');
            content.setAttribute('aria-hidden', 'true');
        });

        // 新しいタブをアクティブに
        activeLink.classList.add('active');
        activeLink.setAttribute('aria-current', 'page');

        const targetContent = document.getElementById(targetTab);
        if (targetContent) {
            targetContent.classList.add('active');
            targetContent.setAttribute('aria-hidden', 'false');
            this.animateTabSwitch(targetContent);
        }

        this.currentTab = targetTab;

        // タブ変更後の処理
        setTimeout(() => {
            this.onTabChanged(targetTab);
        }, 300);

        // カスタムイベントの発火
        window.dispatchEvent(new CustomEvent('tabSwitched', {
            detail: { tab: targetTab, title: activeLink.textContent.trim() }
        }));
    }

    animateTabSwitch(targetContent) {
        targetContent.style.opacity = '0';
        targetContent.style.transform = 'translateX(20px)';

        requestAnimationFrame(() => {
            targetContent.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            targetContent.style.opacity = '1';
            targetContent.style.transform = 'translateX(0)';

            setTimeout(() => {
                targetContent.style.transition = '';
            }, 400);
        });
    }

    onTabChanged(tab) {
        switch (tab) {
            case 'dashboard':
                this.refreshDashboard();
                break;
            case 'customers':
                this.refreshCustomerList();
                break;
            case 'all-customers':
                this.refreshAllCustomers();
                break;
        }

        // イベントリスナーを再バインド
        this.bindDetailButtons();
    }

    initializeSearch() {
        const searchInput = document.getElementById('customerSearchInput');

        if (searchInput) {
            searchInput.addEventListener('input', this.debounce((e) => {
                this.handleSearch(e.target.value);
            }, 300));
        }
    }

    handleSearch(searchTerm) {
        if (!validateInput(searchTerm, 'text', 100)) {
            showErrorMessage('検索語に無効な文字が含まれています。');
            return;
        }

        this.searchTerm = searchTerm.toLowerCase().trim();
        this.filterData();
        this.updateDisplayedData();
        this.announceSearchResults();
    }

    filterData() {
        this.filteredData = this.customerData.filter(customer => {
            const searchTerm = this.searchTerm.toLowerCase();
            return customer.customer_name.toLowerCase().includes(searchTerm) ||
                   customer.customer_no.toLowerCase().includes(searchTerm);
        });
    }

    initializeSort() {
        // テーブルヘッダーのソート
        document.querySelectorAll('th[data-sort], .sortable').forEach(header => {
            header.addEventListener('click', () => {
                const sortType = header.dataset.sort;
                if (sortType) {
                    this.handleTableSort(sortType, header);
                }
            });
        });

        // 全顧客ページのソート
        const allCustomerSort = document.getElementById('sortBy');
        if (allCustomerSort) {
            allCustomerSort.addEventListener('change', (e) => {
                this.handleAllCustomerSort(e.target.value);
            });
        }

        
    }

    handleTableSort(sortType, header) {
        const isAscending = header.classList.contains('sort-asc');
        const newOrder = isAscending ? 'desc' : 'asc';

        // すべてのヘッダーからソートクラスを削除
        document.querySelectorAll('th[data-sort], .sortable').forEach(h => {
            h.classList.remove('sort-asc', 'sort-desc');
        });

        // 現在のヘッダーにソートクラスを追加
        header.classList.add(`sort-${newOrder}`);

        this.sortData(sortType, newOrder);
        this.updateDisplayedData();
        this.animateTableUpdate();
        this.announceSort(sortType, newOrder);
    }

    handleAllCustomerSort(sortValue) {
        const [field, order] = sortValue.split('-');
        this.sortData(field, order);
        this.updateDisplayedData();
        this.animateTableUpdate();
    }

    sortData(field, order) {
        this.filteredData.sort((a, b) => {
            let aValue, bValue;

            switch (field) {
                case 'name':
                    aValue = a.customer_name.toLowerCase();
                    bValue = b.customer_name.toLowerCase();
                    break;
                case 'sales':
                    aValue = parseFloat(a.total_sales) || 0;
                    bValue = parseFloat(b.total_sales) || 0;
                    break;
                case 'deliveries':
                    aValue = parseInt(a.delivery_count) || 0;
                    bValue = parseInt(b.delivery_count) || 0;
                    break;
                case 'leadtime':
                case 'efficiency':
                    aValue = parseFloat(a.avg_lead_time) || 0;
                    bValue = parseFloat(b.avg_lead_time) || 0;
                    break;
                default:
                    return 0;
            }

            if (order === 'asc') {
                return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
            } else {
                return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
            }
        });
    }

    

    animateViewSwitch(viewType) {
        const container = viewType === 'table' ?
            document.querySelector('.table-view-container') :
            document.querySelector('.card-view-container');

        if (!container) return;

        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';

        setTimeout(() => {
            container.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';

            setTimeout(() => {
                container.style.transition = '';
            }, 400);
        }, 50);
    }

    initializePagination() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.updateDisplayedData();
                    this.updatePaginationControls();
                }
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
                if (this.currentPage < totalPages) {
                    this.currentPage++;
                    this.updateDisplayedData();
                    this.updatePaginationControls();
                }
            });
        }
    }

    updatePaginationControls() {
        const totalPages = Math.ceil(this.filteredData.length / this.itemsPerPage);
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageIndicator = document.querySelector('.page-indicator');
        const paginationInfo = document.querySelector('.pagination-info');

        if (prevBtn) {
            prevBtn.disabled = this.currentPage <= 1;
        }

        if (nextBtn) {
            nextBtn.disabled = this.currentPage >= totalPages;
        }

        if (pageIndicator) {
            pageIndicator.textContent = `${this.currentPage} / ${totalPages}`;
        }

        if (paginationInfo) {
            const start = (this.currentPage - 1) * this.itemsPerPage + 1;
            const end = Math.min(this.currentPage * this.itemsPerPage, this.filteredData.length);
            paginationInfo.textContent = `${start}-${end} of ${this.filteredData.length} customers`;
        }
    }

    updateDisplayedData() {
        const start = (this.currentPage - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        const currentData = this.filteredData.slice(start, end);

        this.updateTableContent(currentData);
        this.updateDashboardContent(currentData);
        this.updatePaginationControls();
        this.bindDetailButtons(); // イベントリスナーを再バインド
    }

    updateTableContent(data) {
        const tableBody = document.querySelector('#customerTable tbody');
        if (!tableBody) return;

        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">該当する顧客が見つかりません。</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(customer => `
               <tr>
                   <td>${customer.customer_no}</td>
                   <td>${this.highlightSearchTerm(customer.customer_name)}</td>
                   <td class="text-right">${this.formatYen(customer.total_sales)}</td>
                   <td class="text-center">${customer.delivery_count.toLocaleString()}</td>
                   <td class="text-center">${this.formatDays(customer.avg_lead_time)}</td>
                   <td class="text-center">
                       <button class="table-action-btn" data-customer-name="${customer.customer_name}">
                           <i class="fas fa-eye"></i> 詳細
                       </button>
                   </td>
               </tr>
           `).join('');
    }

    

    updateDashboardContent(data) {
        const grid = document.getElementById('customerOverviewGrid');
        if (!grid || this.currentTab !== 'dashboard') return;

        // ダッシュボードの顧客リストは、常にthis.customerData全体からソート・フィルタリングなしで表示
        const dashboardCustomerList = [...this.customerData];

        grid.innerHTML = dashboardCustomerList.map((customer, index) => {
            const globalIndex = this.customerData.indexOf(customer) + 1;
            const target = 600000; // 目標売上
            const achievement = Math.min(100, (customer.total_sales / target) * 100);

            return `
                   <div class="customer-overview-card" data-customer-name="${customer.customer_name}">
                       <div class="card-header">
                           <div class="customer-rank">${globalIndex}</div>
                           <div class="customer-info">
                               <div class="customer-name">${this.highlightSearchTerm(customer.customer_name)}</div>
                               <div class="customer-id">ID: ${customer.customer_no}</div>
                           </div>
                           <div class="card-actions">
                               <button class="quick-action-btn" data-customer-name="${customer.customer_name}">
                                   <i class="fas fa-eye"></i>
                               </button>
                           </div>
                       </div>
                       
                       <div class="card-metrics">
                           <div class="metric-item">
                               <div class="metric-icon"><i class="fas fa-yen-sign"></i></div>
                               <div class="metric-data">
                                   <span class="metric-value">${this.formatYen(customer.total_sales)}</span>
                                   <span class="metric-label">売上</span>
                               </div>
                           </div>
                           <div class="metric-item">
                               <div class="metric-icon"><i class="fas fa-truck"></i></div>
                               <div class="metric-data">
                                   <span class="metric-value">${customer.delivery_count.toLocaleString()}</span>
                                   <span class="metric-label">配達回数</span>
                               </div>
                           </div>
                           <div class="metric-item">
                               <div class="metric-icon"><i class="fas fa-clock"></i></div>
                               <div class="metric-data">
                                   <span class="metric-value">${this.formatDays(customer.avg_lead_time)}</span>
                                   <span class="metric-label">リードタイム</span>
                               </div>
                           </div>
                       </div>
   
                       <div class="card-footer">
                           <div class="progress-section">
                               <div class="progress-label">売上目標達成率</div>
                               <div class="progress-bar">
                                   <div class="progress-fill" style="width: ${achievement}%"></div>
                               </div>
                               <span class="progress-percentage">${achievement.toFixed(1)}%</span>
                           </div>
                       </div>
                   </div>
               `;
        }).join('');

        // ダッシュボードの顧客数表示を更新
        const dashboardCustomerCountElement = document.querySelector('#dashboardCustomerCount');
        if (dashboardCustomerCountElement) {
            dashboardCustomerCountElement.innerHTML = `<i class="fas fa-users"></i> 表示中: ${dashboardCustomerList.length}人`;
        }

        // パフォーマンス指標の更新
        this.updatePerformanceMetrics();
    }

    sortDashboardCustomers(sortType) {
        switch (sortType) {
            case 'sales':
                this.sortData('sales', 'desc');
                break;
            case 'deliveries':
                this.sortData('deliveries', 'desc');
                break;
            case 'efficiency':
                this.sortData('leadtime', 'asc');
                break;
            case 'name':
                this.sortData('name', 'asc');
                break;
        }
        this.updateDisplayedData();
    }

    initializeDashboardFeatures() {
        // メトリックカードのホバーエフェクト
        document.querySelectorAll('.metric-card').forEach((card, index) => {
            card.addEventListener('mouseenter', () => {
                if (!window.matchMedia('(hover: none)').matches) {
                    card.style.transform = 'translateY(-8px) scale(1.02)';
                }
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });

            // スタガードアニメーション
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // 顧客カードのアニメーション
        setTimeout(() => {
            this.animateCustomerCards();
        }, 500);
    }

    animateCustomerCards() {
        const cards = document.querySelectorAll('.customer-overview-card, .top-customer-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';

                // ホバーエフェクト
                card.addEventListener('mouseenter', () => {
                    if (!window.matchMedia('(hover: none)').matches) {
                        card.style.transform = 'translateY(-4px)';
                    }
                });

                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            }, index * 50);
        });
    }

    updateMetricCards() {
        // メトリックカードの値にアニメーションを追加
        document.querySelector('#totalCustomersValue').textContent = this.dashboardMetrics.totalCustomers.toLocaleString();
        document.querySelector('#monthlySalesValue').textContent = this.formatYen(this.dashboardMetrics.monthlySales);
        document.querySelector('#totalDeliveriesValue').textContent = this.dashboardMetrics.totalDeliveries.toLocaleString();
        document.querySelector('#avgLeadTimeValue').textContent = this.formatDays(this.dashboardMetrics.avgLeadTime);

        // 売上トレンドの更新
        const salesTrendElement = document.querySelector('#salesTrendValue');
        if (salesTrendElement) {
            salesTrendElement.parentElement.classList.remove('positive', 'negative');
            if (this.dashboardMetrics.salesTrend >= 0) {
                salesTrendElement.parentElement.classList.add('positive');
                salesTrendElement.innerHTML = `${Math.abs(this.dashboardMetrics.salesTrend).toFixed(1)}% 前月比`;
                salesTrendElement.previousElementSibling.className = 'fas fa-arrow-up';
            } else {
                salesTrendElement.parentElement.classList.add('negative');
                salesTrendElement.innerHTML = `${Math.abs(this.dashboardMetrics.salesTrend).toFixed(1)}% 前月比`;
                salesTrendElement.previousElementSibling.className = 'fas fa-arrow-down';
            }
        }

        document.querySelectorAll('.metric-value').forEach(element => {
            const finalValue = element.textContent;
            const isNumeric = /[\d,.]/.test(finalValue);

            if (isNumeric) {
                this.animateValue(element, finalValue);
            }
        });
    }

    animateValue(element, finalValue) {
        const numericValue = parseFloat(finalValue.replace(/[^\d.]/g, ''));
        if (isNaN(numericValue)) return;

        const duration = 1000;
        const startTime = Date.now();
        const startValue = 0;

        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easeProgress = this.easeOutCubic(progress);

            const currentValue = startValue + (numericValue * easeProgress);

            if (finalValue.includes('¥')) {
                element.textContent = this.formatYen(currentValue);
            } else if (finalValue.includes('日')) {
                element.textContent = this.formatDays(currentValue);
            } else {
                element.textContent = Math.round(currentValue).toLocaleString();
            }

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.textContent = finalValue;
            }
        };

        animate();
    }

    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    showStatisticsDetails(customerName) {
        if (!validateInput(customerName, 'text', 255)) {
            showErrorMessage('無効な顧客名です。');
            return;
        }

        const modal = document.getElementById('detailModal');
        if (!modal) {
            this.createDetailModal();
            this.showStatisticsDetails(customerName);
            return;
        }

        const title = document.getElementById('detailTitle');
        const content = document.getElementById('detailContent');

        if (!title || !content) return;

        title.textContent = `${customerName} の詳細情報`;

        const customerInfo = this.customerData.find(customer =>
            customer.customer_name === customerName
        );

        if (!customerInfo) {
            content.innerHTML = '<p>顧客情報が見つかりません。</p>';
            showModal('detailModal');
            return;
        }

        const detailHtml = this.generateDetailModalContent(customerName, customerInfo);
        content.innerHTML = detailHtml;

        showModal('detailModal');

        // 詳細データの遅延読み込み
        setTimeout(() => {
            this.loadCustomerDetailData(customerName);
        }, 500);
    }

    generateDetailModalContent(customerName, customerInfo) {
        return `
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
                               <span>${customerInfo.customer_no}</span>
                           </div>
                           <div class="detail-item">
                               <label>登録日:</label>
                               <span class="loading-text">読み込み中...</span>
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
                               <div class="stat-icon"><i class="fas fa-yen-sign"></i></div>
                               <div class="stat-content">
                                   <div class="stat-value">${this.formatYen(customerInfo.total_sales)}</div>
                                   <div class="stat-label">総売上</div>
                               </div>
                           </div>
                           <div class="stat-card">
                               <div class="stat-icon"><i class="fas fa-truck"></i></div>
                               <div class="stat-content">
                                   <div class="stat-value">${customerInfo.delivery_count}</div>
                                   <div class="stat-label">配達回数</div>
                               </div>
                           </div>
                           <div class="stat-card">
                               <div class="stat-icon"><i class="fas fa-clock"></i></div>
                               <div class="stat-content">
                                   <div class="stat-value">${this.formatDays(customerInfo.avg_lead_time)}</div>
                                   <div class="stat-label">平均リードタイム</div>
                               </div>
                           </div>
                           <div class="stat-card">
                               <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                               <div class="stat-content">
                                   <div class="stat-value">98.5%</div>
                                   <div class="stat-label">満足度</div>
                               </div>
                           </div>
                       </div>
                   </div>
                   
                   <div class="detail-section">
                       <h4><i class="fas fa-history"></i> 取引履歴</h4>
                       <div class="loading-placeholder">
                           <div class="loading-spinner"></div>
                           <span>取引履歴を読み込み中...</span>
                       </div>
                   </div>
                   
                   <div class="detail-section">
                       <h4><i class="fas fa-chart-bar"></i> パフォーマンス分析</h4>
                       <div class="performance-analysis">
                           <div class="analysis-item">
                               <label>売上ランキング:</label>
                               <span>${this.getCustomerRank(customerInfo, 'total_sales')}位 / ${this.customerData.length}人</span>
                           </div>
                           <div class="analysis-item">
                               <label>効率性ランキング:</label>
                               <span>${this.getCustomerRank(customerInfo, 'avg_lead_time', true)}位 / ${this.customerData.length}人</span>
                           </div>
                           <div class="analysis-item">
                               <label>配達回数ランキング:</label>
                               <span>${this.getCustomerRank(customerInfo, 'delivery_count')}位 / ${this.customerData.length}人</span>
                           </div>
                       </div>
                   </div>
                   
                   <div class="detail-section">
                       <h4><i class="fas fa-sticky-note"></i> 備考・特記事項</h4>
                       <div class="remarks-content">
                           <p>特記事項はありません。</p>
                           <div class="add-note-section">
                               <button class="btn-add-note">
                                   <i class="fas fa-plus"></i>
                                   備考を追加
                               </button>
                           </div>
                       </div>
                   </div>
               </div>
   
               <style>
               .detail-grid {
                   display: grid;
                   grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                   gap: 15px;
                   margin-top: 15px;
               }
   
               .detail-item {
                   display: flex;
                   justify-content: space-between;
                   align-items: center;
                   padding: 12px;
                   background: rgba(126, 217, 87, 0.05);
                   border-radius: 8px;
                   border: 1px solid rgba(126, 217, 87, 0.1);
               }
   
               .detail-item label {
                   font-weight: 600;
                   color: var(--sub-green);
               }
   
               .detail-item span {
                   color: var(--main-green);
                   font-weight: 600;
               }
   
               .badge {
                   padding: 4px 12px;
                   border-radius: 6px;
                   font-size: 12px;
                   font-weight: 700;
                   text-transform: uppercase;
                   letter-spacing: 0.5px;
               }
   
               .badge.success {
                   background: #dcfce7;
                   color: #166534;
               }
   
               .stats-grid {
                   display: grid;
                   grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                   gap: 15px;
                   margin-top: 15px;
               }
   
               .stat-card {
                   display: flex;
                   align-items: center;
                   gap: 15px;
                   padding: 20px;
                   background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
                   border-radius: 12px;
                   border: 1px solid rgba(126, 217, 87, 0.2);
                   transition: all 0.3s ease;
               }
   
               .stat-card:hover {
                   transform: translateY(-2px);
                   box-shadow: 0 8px 20px rgba(47, 93, 63, 0.1);
               }
   
               .stat-icon {
                   width: 50px;
                   height: 50px;
                   background: linear-gradient(135deg, var(--accent-green), var(--main-green));
                   color: white;
                   border-radius: 50%;
                   display: flex;
                   align-items: center;
                   justify-content: center;
                   font-size: 20px;
                   flex-shrink: 0;
               }
   
               .stat-content {
                   flex: 1;
               }
   
               .stat-value {
                   font-size: 24px;
                   font-weight: 800;
                   color: var(--main-green);
                   margin-bottom: 4px;
               }
   
               .stat-label {
                   font-size: 14px;
                   color: var(--sub-green);
                   font-weight: 600;
               }
   
               .loading-placeholder {
                   display: flex;
                   align-items: center;
                   justify-content: center;
                   gap: 12px;
                   padding: 40px;
                   color: var(--sub-green);
               }
   
               .loading-spinner {
                   width: 20px;
                   height: 20px;
                   border: 3px solid rgba(126, 217, 87, 0.3);
                   border-top: 3px solid var(--accent-green);
                   border-radius: 50%;
                   animation: spin 1s linear infinite;
               }
   
               .performance-analysis {
                   display: flex;
                   flex-direction: column;
                   gap: 12px;
                   margin-top: 15px;
               }
   
               .analysis-item {
                   display: flex;
                   justify-content: space-between;
                   align-items: center;
                   padding: 12px;
                   background: rgba(126, 217, 87, 0.05);
                   border-radius: 8px;
                   border-left: 4px solid var(--accent-green);
               }
   
               .analysis-item label {
                   font-weight: 600;
                   color: var(--main-green);
               }
   
               .analysis-item span {
                   font-weight: 700;
                   color: var(--sub-green);
               }
   
               .remarks-content {
                   margin-top: 15px;
               }
   
               .add-note-section {
                   margin-top: 15px;
                   text-align: center;
               }
   
               .btn-add-note {
                   padding: 10px 20px;
                   background: linear-gradient(135deg, var(--accent-green), var(--main-green));
                   color: white;
                   border: none;
                   border-radius: 8px;
                   cursor: pointer;
                   font-weight: 600;
                   display: inline-flex;
                   align-items: center;
                   gap: 8px;
                   transition: all 0.3s ease;
               }
   
               .btn-add-note:hover {
                   transform: translateY(-2px);
                   box-shadow: 0 6px 16px rgba(126, 217, 87, 0.3);
               }
   
               @keyframes spin {
                   0% { transform: rotate(0deg); }
                   100% { transform: rotate(360deg); }
               }
               </style>
           `;
    }

    getCustomerRank(customer, field, ascending = false) {
        const sortedData = [...this.customerData].sort((a, b) => {
            const aValue = parseFloat(a[field]) || 0;
            const bValue = parseFloat(b[field]) || 0;
            return ascending ? aValue - bValue : bValue - aValue;
        });

        return sortedData.findIndex(c => c.customer_no === customer.customer_no) + 1;
    }

    loadCustomerDetailData(customerName) {
        setTimeout(() => {
            // 登録日の更新
            const registrationDate = document.querySelector('.detail-item:nth-child(3) .loading-text');
            if (registrationDate) {
                registrationDate.textContent = '2023-01-15';
                registrationDate.classList.remove('loading-text');
            }

            // 取引履歴の更新
            const historySection = document.querySelector('.loading-placeholder');
            if (historySection) {
                historySection.innerHTML = `
                       <div class="history-timeline">
                           <div class="history-item">
                               <div class="history-date">2024-12-15</div>
                               <div class="history-content">
                                   <strong>商品購入</strong>
                                   <span class="history-amount">¥25,000</span>
                               </div>
                           </div>
                           <div class="history-item">
                               <div class="history-date">2024-11-28</div>
                               <div class="history-content">
                                   <strong>商品購入</strong>
                                   <span class="history-amount">¥18,500</span>
                               </div>
                           </div>
                           <div class="history-item">
                               <div class="history-date">2024-10-10</div>
                               <div class="history-content">
                                   <strong>初回購入</strong>
                                   <span class="history-amount">¥12,000</span>
                               </div>
                           </div>
                       </div>
   
                       <style>
                       .history-timeline {
                           display: flex;
                           flex-direction: column;
                           gap: 15px;
                       }
   
                       .history-item {
                           display: flex;
                           align-items: center;
                           gap: 20px;
                           padding: 15px;
                           background: rgba(126, 217, 87, 0.05);
                           border-radius: 10px;
                           border-left: 4px solid var(--accent-green);
                       }
   
                       .history-date {
                           font-weight: 600;
                           color: var(--sub-green);
                           min-width: 100px;
                           font-size: 14px;
                       }
   
                       .history-content {
                           flex: 1;
                           display: flex;
                           justify-content: space-between;
                           align-items: center;
                       }
   
                       .history-content strong {
                           color: var(--main-green);
                       }
   
                       .history-amount {
                           font-weight: 700;
                           color: var(--accent-green);
                           font-size: 16px;
                       }
                       </style>
                   `;
            }
        }, 1000);
    }

    createDetailModal() {
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

    setupModals() {
        // モーダル外クリックで閉じる
        document.addEventListener('click', (event) => {
            const modal = document.querySelector('.modal[style*="block"]');
            if (modal && event.target === modal) {
                closeModal(modal.id);
            }
        });
    }

    setupAccessibility() {
        // テーブルのアクセシビリティ
        document.querySelectorAll('.data-table').forEach(table => {
            table.setAttribute('aria-label', '顧客統計情報テーブル');
            table.setAttribute('role', 'table');
        });

        // ソートボタンのアクセシビリティ
        document.querySelectorAll('[data-sort], .sortable').forEach(button => {
            const column = button.getAttribute('data-sort');
            if (column) {
                button.setAttribute('aria-label', `${column}でソート`);
                button.setAttribute('role', 'button');
                button.setAttribute('tabindex', '0');
            }
        });
    }

    enhanceAnimations() {
        // プログレスバーのアニメーション
        document.querySelectorAll('.progress-fill').forEach((fill, index) => {
            const width = fill.style.width;
            fill.style.width = '0%';

            setTimeout(() => {
                fill.style.transition = 'width 1s ease-out';
                fill.style.width = width;
            }, index * 100);
        });

        // カウンターアニメーション
        this.updateMetricCards();
    }

    async refreshDashboard() {
        await this.fetchDashboardData();
        this.updateDisplayedData();
        this.updateMetricCards();
        this.animateCustomerCards();
    }

    refreshCustomerList() {
        this.updateDisplayedData();
        this.bindDetailButtons();
    }

    refreshAllCustomers() {
        this.updateDisplayedData();
        this.bindDetailButtons();
    }

    animateTableUpdate() {
        const tableBody = document.querySelector('.data-table tbody, #customerTableBody, #allCustomersTableBody');
        if (!tableBody) return;

        tableBody.style.opacity = '0.6';
        tableBody.style.transform = 'translateY(10px)';

        setTimeout(() => {
            tableBody.style.transition = 'all 0.3s ease';
            tableBody.style.opacity = '1';
            tableBody.style.transform = 'translateY(0)';

            setTimeout(() => {
                tableBody.style.transition = '';
            }, 300);
        }, 150);
    }

    closeAllModals() {
        const openModal = document.querySelector('.modal[style*="block"]');
        if (openModal) {
            closeModal(openModal.id);
        }
    }

    handleResize() {
        // レスポンシブ対応の処理
        if (window.innerWidth <= 768) {
            // モバイル表示の調整
            this.adjustMobileView();
        } else {
            // デスクトップ表示の調整
            this.adjustDesktopView();
        }
    }

    adjustMobileView() {
        // モバイル表示時の調整
        const dashboardGrid = document.querySelector('.dashboard-grid');
        if (dashboardGrid) {
            dashboardGrid.style.gridTemplateColumns = '1fr';
        }
    }

    adjustDesktopView() {
        // デスクトップ表示時の調整
        const dashboardGrid = document.querySelector('.dashboard-grid');
        if (dashboardGrid) {
            dashboardGrid.style.gridTemplateColumns = 'repeat(auto-fit, minmax(280px, 1fr))';
        }
    }

    announceSearchResults() {
        const count = this.filteredData.length;
        const message = this.searchTerm ?
            `${count}件の顧客が見つかりました` :
            '全ての顧客を表示しています';
        this.announceToScreenReader(message);
    }

    announceSort(column, order) {
        const columnNames = {
            'name': '顧客名',
            'sales': '売上',
            'leadtime': 'リードタイム',
            'deliveries': '配達回数'
        };
        const orderText = order === 'asc' ? '昇順' : '降順';
        const message = `${columnNames[column] || column}を${orderText}でソートしました`;
        this.announceToScreenReader(message);
    }

    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;

        document.body.appendChild(announcement);

        setTimeout(() => {
            if (announcement.parentNode) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }

    highlightSearchTerm(text) {
        if (!this.searchTerm) return text;

        const regex = new RegExp(`(${this.escapeRegExp(this.searchTerm)})`, 'gi');
        return text.replace(regex, '<mark class="search-highlight">$1</mark>');
    }

    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    formatYen(amount) {
        if (!amount) return '¥0';

        if (amount >= 1000000) {
            return '¥' + (amount / 1000000).toFixed(2) + 'M';
        } else if (amount >= 1000) {
            return '¥' + (amount / 1000).toFixed(1) + 'K';
        }
        return '¥' + amount.toLocaleString();
    }

    formatDays(days) {
        if (!days) return '0日';
        return parseFloat(days).toFixed(2) + '日';
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

// StatisticsDetails関数を独立させて下位互換性を保つ
function showStatisticsDetails(customerName) {
    if (window.statisticsManager) {
        window.statisticsManager.showStatisticsDetails(customerName);
    }
}

// 初期化
function initializeStatisticsPage() {
    if (window.location.pathname.includes('/statistics/')) {
        window.statisticsManager = new StatisticsManager();

        // グローバル関数として公開
        window.showDetails = showStatisticsDetails;
    }
}

// DOMContentLoaded時に初期化
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeStatisticsPage);
} else {
    initializeStatisticsPage();
}

// モジュールとしてエクスポート
export { StatisticsManager, showStatisticsDetails };
export default initializeStatisticsPage;