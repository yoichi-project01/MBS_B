<!-- ダッシュボードタブ -->
<div id="dashboard" class="tab-content active">
    <div class="dashboard-grid">
        <div class="metric-card">
            <div class="card-icon" style="background-color: #e7f3ff; color: #4a90e2;">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">総顧客数</h3>
                <p class="metric-value" id="totalCustomersValue">0</p>
                <div class="metric-subtitle">
                    <i class="fas fa-info-circle"></i>
                    <span>アクティブ顧客</span>
                </div>
            </div>
        </div>
        <div class="metric-card">
            <div class="card-icon" style="background-color: #e5f9f0; color: #2f855a;">
                <i class="fas fa-yen-sign"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">月間売上 (推定)</h3>
                <p class="metric-value" id="monthlySalesValue">¥0</p>
                <div class="metric-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    <span id="salesTrendValue">0% 前月比</span>
                </div>
            </div>
        </div>
        <div class="metric-card">
            <div class="card-icon" style="background-color: #fff4e6; color: #d66a00;">
                <i class="fas fa-truck"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">総配達回数</h3>
                <p class="metric-value" id="totalDeliveriesValue">0</p>
                <div class="metric-subtitle">
                    <i class="fas fa-calendar-alt"></i>
                    <span>累計実績</span>
                </div>
            </div>
        </div>
        <div class="metric-card">
            <div class="card-icon" style="background-color: #f0e8ff; color: #6b46c1;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">平均リードタイム</h3>
                <p class="metric-value" id="avgLeadTimeValue">0日</p>
                <div class="metric-subtitle">
                    <i class="fas fa-chart-line"></i>
                    <span>注文から配達まで</span>
                </div>
            </div>
        </div>
    </div>

    <!-- パフォーマンス指標セクション -->
    <div class="performance-section">
        <h4 class="section-title">
            <i class="fas fa-chart-bar"></i>
            パフォーマンス指標
        </h4>
        <div class="performance-grid">
            <div class="performance-card">
                <div class="performance-header">
                    <h5>売上トップ5</h5>
                    <i class="fas fa-crown"></i>
                </div>
                <div class="performance-list" id="topSalesCustomersList">
                    <!-- データはJavaScriptで挿入されます -->
                </div>
            </div>

            <div class="performance-card">
                <div class="performance-header">
                    <h5>配達回数トップ5</h5>
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="performance-list" id="topDeliveryCustomersList">
                    <!-- データはJavaScriptで挿入されます -->
                </div>
            </div>

            <div class="performance-card">
                <div class="performance-header">
                    <h5>効率性ランキング</h5>
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="performance-list" id="efficientCustomersList">
                    <!-- データはJavaScriptで挿入されます -->
                </div>
            </div>
        </div>
    </div>

    <!-- 全顧客概要セクション -->
    <div class="top-customers-section">
        <div class="section-header">
            <h4>全顧客概要</h4>
            <div class="header-actions">
                <div class="customers-count">
                    <span class="count-info" id="dashboardCustomerCount">
                        <i class="fas fa-users"></i>
                        表示中: 0人
                    </span>
                </div>
                <div class="sort-options">
                    <select id="dashboardSort" class="sort-select-mini">
                        <option value="sales">売上順</option>
                        <option value="deliveries">配達回数順</option>
                        <option value="efficiency">効率順</option>
                        <option value="name">名前順</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="customer-overview-grid" id="customerOverviewGrid">
            <!-- データはJavaScriptで挿入されます -->
        </div>

        <!-- ページネーション -->
        <div class="pagination-section">
            <div class="pagination-info">
                <span id="dashboardPaginationInfo">0-0 of 0 customers</span>
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" id="dashboardPrevPage" disabled>
                    <i class="fas fa-chevron-left"></i>
                    前へ
                </button>
                <span class="page-indicator" id="dashboardPageIndicator">1 / 1</span>
                <button class="pagination-btn" id="dashboardNextPage" disabled>
                    次へ
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- 追加の統計情報 -->
    <div class="additional-stats">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <h5>今週の新規顧客</h5>
                    <p class="stat-number">0</p>
                    <span class="stat-change">前週比 0%</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-content">
                    <h5>顧客満足度</h5>
                    <p class="stat-number">98.5%</p>
                    <span class="stat-change positive">+2.1%</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-content">
                    <h5>リピート率</h5>
                    <p class="stat-number">87.3%</p>
                    <span class="stat-change positive">+5.2%</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h5>平均評価</h5>
                    <p class="stat-number">4.8/5</p>
                    <span class="stat-change positive">+0.3</span>
                </div>
            </div>
        </div>
    </div>
</div>