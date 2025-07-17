/**
 * 顧客統計情報管理クラス
 */
class CustomerStatistics {
    constructor() {
        this.storeName = window.statisticsData?.storeName || '';
        this.csrfToken = window.statisticsData?.csrfToken || '';
        this.currentPage = 1;
        this.itemsPerPage = 20;
        this.currentSort = 'customer_name';
        this.currentOrder = 'ASC';
        this.searchTerm = '';
        this.isLoading = false;
        
        this.initializeElements();
        this.bindEvents();
        this.loadCustomerData();
    }

    /**
     * DOM要素の初期化
     */
    initializeElements() {
        this.elements = {
            customerTable: document.getElementById('customerTable'),
            searchInput: document.getElementById('customerSearchInput'),
            loadingSpinner: this.createLoadingSpinner(),
            pagination: this.createPaginationContainer(),
            summary: this.createSummaryContainer(),
            detailModal: document.getElementById('detailModal')
        };
        
        // 要素をDOMに追加
        const container = document.querySelector('.customer-list-container');
        if (container) {
            container.insertBefore(this.elements.summary, container.firstChild);
            container.appendChild(this.elements.pagination);
        }
    }

    /**
     * イベントリスナーの設定
     */
    bindEvents() {
        // 検索機能
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', 
                this.debounce(() => this.handleSearch(), 300)
            );
        }

        // テーブルヘッダーのソート
        if (this.elements.customerTable) {
            this.elements.customerTable.addEventListener('click', (e) => {
                if (e.target.classList.contains('sortable')) {
                    this.handleSort(e.target);
                }
            });
        }

        // 詳細モーダルの設定
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('view-detail-btn')) {
                const customerNo = e.target.dataset.customerNo;
                this.showCustomerDetail(customerNo);
            }
        });

        // モーダルを閉じる
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal') || e.target.classList.contains('close-modal')) {
                this.closeModal();
            }
        });

        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.elements.detailModal) {
                this.closeModal();
            }
        });
    }

    /**
     * 顧客データの読み込み
     */
    async loadCustomerData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();

        try {
            const formData = new FormData();
            formData.append('store', this.storeName);
            formData.append('page', this.currentPage);
            formData.append('limit', this.itemsPerPage);
            formData.append('sort', this.currentSort);
            formData.append('order', this.currentOrder);
            formData.append('search', this.searchTerm);
            
            // CSRFトークンがある場合のみ追加
            if (this.csrfToken) {
                formData.append('csrf_token', this.csrfToken);
            }

            const response = await fetch('/MBS_B/statistics/get_customer_statistics_debug.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const responseText = await response.text();
            
            // レスポンスの詳細をログに出力
            console.log('=== Response Details ===');
            console.log('Status:', response.status);
            console.log('Status Text:', response.statusText);
            console.log('Headers:', response.headers);
            console.log('Response Text (first 1000 chars):', responseText.substring(0, 1000));
            console.log('Response Text (full):', responseText);
            console.log('========================');
            
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON Parse Error:', jsonError);
                console.error('Response text length:', responseText.length);
                console.error('Response text starts with:', responseText.substring(0, 100));
                console.error('Response text ends with:', responseText.substring(responseText.length - 100));
                
                // HTMLエラーページかどうか確認
                if (responseText.includes('<html>') || responseText.includes('<!DOCTYPE')) {
                    throw new Error('サーバーからHTMLエラーページが返されました');
                } else if (responseText.includes('Fatal error') || responseText.includes('Parse error')) {
                    throw new Error('PHPエラーが発生しました');
                } else {
                    throw new Error('サーバーからの応答が不正です: ' + responseText.substring(0, 100));
                }
            }
            
            if (data.success) {
                this.renderCustomerTable(data.data.customers);
                this.renderSummary(data.data.summary);
                this.renderPagination(data.data.pagination);
            } else {
                throw new Error(data.message || 'データの取得に失敗しました');
            }
        } catch (error) {
            console.error('Error loading customer data:', error);
            this.showError('顧客データの読み込みに失敗しました: ' + error.message);
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }

    /**
     * 顧客テーブルの描画
     */
    renderCustomerTable(customers) {
        const tbody = this.elements.customerTable.querySelector('tbody');
        if (!tbody) return;

        if (customers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="empty-state">
                            <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 16px;"></i>
                            <p>該当する顧客が見つかりません</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = customers.map(customer => `
            <tr>
                <td>${this.escapeHtml(customer.customer_no)}</td>
                <td>${this.escapeHtml(customer.customer_name)}</td>
                <td>¥${this.formatNumber(customer.total_sales)}</td>
                <td>${customer.delivery_count}回</td>
                <td>${customer.avg_lead_time}日</td>
                <td>
                    <button class="btn btn-sm btn-primary view-detail-btn" 
                            data-customer-no="${customer.customer_no}">
                        <i class="fas fa-eye"></i> 詳細
                    </button>
                </td>
            </tr>
        `).join('');
    }

    /**
     * 統計サマリーの描画
     */
    renderSummary(summary) {
        this.elements.summary.innerHTML = `
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-content">
                        <h3>総顧客数</h3>
                        <p class="summary-value">${this.formatNumber(summary.total_customers)}</p>
                        <small>アクティブ: ${summary.active_customers}</small>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-yen-sign"></i>
                    </div>
                    <div class="summary-content">
                        <h3>総売上</h3>
                        <p class="summary-value">¥${this.formatNumber(summary.total_sales)}</p>
                        <small>店舗全体</small>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="summary-content">
                        <h3>平均リードタイム</h3>
                        <p class="summary-value">${summary.avg_lead_time.toFixed(1)}日</p>
                        <small>配達までの平均日数</small>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="summary-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="summary-content">
                        <h3>総配達回数</h3>
                        <p class="summary-value">${this.formatNumber(summary.total_deliveries)}</p>
                        <small>全期間</small>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * ページネーションの描画
     */
    renderPagination(pagination) {
        if (pagination.total_pages <= 1) {
            this.elements.pagination.innerHTML = '';
            return;
        }

        const { current_page, total_pages, total_records } = pagination;
        
        let paginationHTML = `
            <div class="pagination-info">
                ${total_records}件中 ${((current_page - 1) * this.itemsPerPage) + 1} - 
                ${Math.min(current_page * this.itemsPerPage, total_records)} 件を表示
            </div>
            <div class="pagination-controls">
        `;

        // 前へボタン
        if (current_page > 1) {
            paginationHTML += `
                <button class="btn btn-sm btn-secondary" onclick="customerStats.goToPage(${current_page - 1})">
                    <i class="fas fa-chevron-left"></i> 前へ
                </button>
            `;
        }

        // ページ番号
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === current_page ? 'btn-primary' : 'btn-secondary';
            paginationHTML += `
                <button class="btn btn-sm ${activeClass}" onclick="customerStats.goToPage(${i})">
                    ${i}
                </button>
            `;
        }

        // 次へボタン
        if (current_page < total_pages) {
            paginationHTML += `
                <button class="btn btn-sm btn-secondary" onclick="customerStats.goToPage(${current_page + 1})">
                    次へ <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }

        paginationHTML += '</div>';
        this.elements.pagination.innerHTML = paginationHTML;
    }

    /**
     * 顧客詳細モーダルの表示
     */
    async showCustomerDetail(customerNo) {
        try {
            const formData = new FormData();
            formData.append('customer_no', customerNo);
            formData.append('store', this.storeName);
            
            if (this.csrfToken) {
                formData.append('csrf_token', this.csrfToken);
            }

            const response = await fetch('/MBS_B/statistics/get_customer_detail.php', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON Parse Error:', jsonError);
                console.error('Response text:', responseText);
                throw new Error('サーバーからの応答が不正です');
            }

            if (data.success) {
                this.renderCustomerDetailModal(data.data);
                this.elements.detailModal.classList.add('show');
                this.elements.detailModal.setAttribute('aria-hidden', 'false');
            } else {
                throw new Error(data.message || '顧客詳細の取得に失敗しました');
            }
        } catch (error) {
            console.error('Error loading customer detail:', error);
            this.showError('顧客詳細の読み込みに失敗しました: ' + error.message);
        }
    }

    /**
     * 顧客詳細モーダルの内容を描画
     */
    renderCustomerDetailModal(data) {
        const { customer_info, order_history, delivery_history, statistics } = data;
        
        const modalBody = this.elements.detailModal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="customer-detail-content">
                <div class="customer-basic-info">
                    <h3>基本情報</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>顧客番号:</label>
                            <span>${customer_info.customer_no}</span>
                        </div>
                        <div class="info-item">
                            <label>顧客名:</label>
                            <span>${this.escapeHtml(customer_info.customer_name)}</span>
                        </div>
                        <div class="info-item">
                            <label>管理者:</label>
                            <span>${this.escapeHtml(customer_info.manager_name || '-')}</span>
                        </div>
                        <div class="info-item">
                            <label>住所:</label>
                            <span>${this.escapeHtml(customer_info.address)}</span>
                        </div>
                        <div class="info-item">
                            <label>電話番号:</label>
                            <span>${this.escapeHtml(customer_info.telephone_number)}</span>
                        </div>
                        <div class="info-item">
                            <label>配達条件:</label>
                            <span>${this.escapeHtml(customer_info.delivery_conditions || '-')}</span>
                        </div>
                        <div class="info-item">
                            <label>登録日:</label>
                            <span>${customer_info.registration_date}</span>
                        </div>
                    </div>
                </div>

                ${statistics ? `
                    <div class="customer-statistics">
                        <h3>統計情報</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <label>累計売上:</label>
                                <span>¥${this.formatNumber(statistics.sales_by_customer)}</span>
                            </div>
                            <div class="stat-item">
                                <label>平均リードタイム:</label>
                                <span>${statistics.lead_time}日</span>
                            </div>
                            <div class="stat-item">
                                <label>配達回数:</label>
                                <span>${statistics.delivery_amount}回</span>
                            </div>
                            <div class="stat-item">
                                <label>最終注文日:</label>
                                <span>${statistics.last_order_date || '-'}</span>
                            </div>
                            <div class="stat-item">
                                <label>総注文数:</label>
                                <span>${statistics.total_orders}件</span>
                            </div>
                            <div class="stat-item">
                                <label>今月の注文:</label>
                                <span>${statistics.orders_this_month}件</span>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <div class="customer-history">
                    <h3>注文履歴</h3>
                    <div class="history-table-container">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>注文番号</th>
                                    <th>注文日</th>
                                    <th>ステータス</th>
                                    <th>金額</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order_history.map(order => `
                                    <tr>
                                        <td>${order.order_no}</td>
                                        <td>${order.registration_date}</td>
                                        <td><span class="status-badge status-${order.status}">${order.status_label}</span></td>
                                        <td>¥${this.formatNumber(order.total_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * ページ移動
     */
    goToPage(page) {
        this.currentPage = page;
        this.loadCustomerData();
    }

    /**
     * 検索処理
     */
    handleSearch() {
        this.searchTerm = this.elements.searchInput.value.trim();
        this.currentPage = 1;
        this.loadCustomerData();
    }

    /**
     * ソート処理
     */
    handleSort(element) {
        const sortColumn = element.dataset.sort;
        
        if (this.currentSort === sortColumn) {
            this.currentOrder = this.currentOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            this.currentSort = sortColumn;
            this.currentOrder = 'ASC';
        }

        // ソート表示の更新
        this.updateSortIndicators();
        this.loadCustomerData();
    }

    /**
     * ソート表示の更新
     */
    updateSortIndicators() {
        const headers = this.elements.customerTable.querySelectorAll('th.sortable');
        headers.forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
            if (header.dataset.sort === this.currentSort) {
                header.classList.add(this.currentOrder === 'ASC' ? 'sort-asc' : 'sort-desc');
            }
        });
    }

    /**
     * モーダルを閉じる
     */
    closeModal() {
        this.elements.detailModal.classList.remove('show');
        this.elements.detailModal.setAttribute('aria-hidden', 'true');
    }

    /**
     * ローディング表示
     */
    showLoading() {
        this.elements.loadingSpinner.style.display = 'block';
    }

    /**
     * ローディング非表示
     */
    hideLoading() {
        this.elements.loadingSpinner.style.display = 'none';
    }

    /**
     * エラー表示
     */
    showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'エラー',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            alert(message);
        }
    }

    /**
     * ヘルパー関数
     */
    escapeHtml(text) {
        // null、undefined、数値などを安全に文字列に変換
        if (text === null || text === undefined) {
            return '';
        }
        
        // 文字列に変換
        const textStr = String(text);
        
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return textStr.replace(/[&<>"']/g, m => map[m]);
    }

    formatNumber(number) {
        // null、undefined、または数値以外の値を安全に処理
        if (number === null || number === undefined || isNaN(number)) {
            return '0';
        }
        return new Intl.NumberFormat('ja-JP').format(Number(number));
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

    /**
     * DOM要素作成ヘルパー
     */
    createLoadingSpinner() {
        const spinner = document.createElement('div');
        spinner.className = 'loading-overlay';
        spinner.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>データを読み込んでいます...</p>
            </div>
        `;
        spinner.style.display = 'none';
        document.body.appendChild(spinner);
        return spinner;
    }

    createPaginationContainer() {
        const container = document.createElement('div');
        container.className = 'pagination-container';
        return container;
    }

    createSummaryContainer() {
        const container = document.createElement('div');
        container.className = 'customer-summary-container';
        return container;
    }
}

// グローバルインスタンス
let customerStats;

// DOMContentLoaded時の初期化
document.addEventListener('DOMContentLoaded', function() {
    customerStats = new CustomerStatistics();
});

// モジュールとしてエクスポート（必要に応じて）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CustomerStatistics;
}