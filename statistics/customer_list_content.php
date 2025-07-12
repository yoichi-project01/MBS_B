<!-- 改善されたダッシュボードタブ -->
<div id="dashboard" class="tab-content active">
    <!-- メトリックカードセクション -->
    <div class="dashboard-grid">
        <div class="metric-card">
            <div class="card-icon"
                style="background: linear-gradient(135deg, #e7f3ff 0%, #cce7ff 100%); color: #4a90e2;">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">総顧客数</h3>
                <p class="metric-value"><?php echo number_format($totalCustomers); ?></p>
                <div class="metric-subtitle">
                    <i class="fas fa-info-circle"></i>
                    <span>アクティブ顧客</span>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="card-icon"
                style="background: linear-gradient(135deg, #e5f9f0 0%, #ccf2dc 100%); color: #2f855a;">
                <i class="fas fa-yen-sign"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">今月の売上</h3>
                <p class="metric-value"><?php echo format_yen($monthlySales); ?></p>
                <div class="metric-trend <?php echo $salesTrend >= 0 ? 'positive' : 'negative'; ?>">
                    <i class="fas fa-<?php echo $salesTrend >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                    <span><?php echo abs(round($salesTrend, 1)); ?>% 前月比</span>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="card-icon"
                style="background: linear-gradient(135deg, #fff4e6 0%, #ffe6cc 100%); color: #d66a00;">
                <i class="fas fa-truck"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">総配達回数</h3>
                <p class="metric-value"><?php echo number_format($totalDeliveries); ?></p>
                <div class="metric-subtitle">
                    <i class="fas fa-calendar-alt"></i>
                    <span>累計実績</span>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="card-icon"
                style="background: linear-gradient(135deg, #f0e8ff 0%, #e6ccff 100%); color: #6b46c1;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">平均リードタイム</h3>
                <p class="metric-value"><?php echo format_days($avgLeadTime); ?></p>
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
                <div class="performance-list">
                    <?php
                    $topSalesCustomers = array_slice($customerList, 0, 5);
                    foreach ($topSalesCustomers as $index => $customer):
                    ?>
                    <div class="performance-item">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <span class="name"><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                        <span class="value"><?php echo format_yen($customer['total_sales']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="performance-card">
                <div class="performance-header">
                    <h5>配達回数トップ5</h5>
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="performance-list">
                    <?php
                    $topDeliveryCustomers = $customerList;
                    usort($topDeliveryCustomers, function ($a, $b) {
                        return $b['delivery_count'] - $a['delivery_count'];
                    });
                    $topDeliveryCustomers = array_slice($topDeliveryCustomers, 0, 5);
                    foreach ($topDeliveryCustomers as $index => $customer):
                    ?>
                    <div class="performance-item">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <span class="name"><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                        <span class="value"><?php echo number_format($customer['delivery_count']); ?>回</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="performance-card">
                <div class="performance-header">
                    <h5>効率性ランキング</h5>
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="performance-list">
                    <?php
                    $efficientCustomers = $customerList;
                    usort($efficientCustomers, function ($a, $b) {
                        return $a['avg_lead_time'] - $b['avg_lead_time'];
                    });
                    $efficientCustomers = array_slice($efficientCustomers, 0, 5);
                    foreach ($efficientCustomers as $index => $customer):
                    ?>
                    <div class="performance-item">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <span class="name"><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                        <span class="value"><?php echo format_days($customer['avg_lead_time']); ?></span>
                    </div>
                    <?php endforeach; ?>
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
                    <span class="count-info">
                        <i class="fas fa-users"></i>
                        表示中: <?php echo count($customerList); ?>人
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
            <?php foreach ($customerList as $index => $customer): ?>
            <div class="customer-overview-card"
                data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                <div class="card-header">
                    <div class="customer-rank"><?php echo $index + 1; ?></div>
                    <div class="customer-info">
                        <div class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                        <div class="customer-id">ID: <?php echo htmlspecialchars($customer['customer_no']); ?></div>
                    </div>
                    <div class="card-actions">
                        <button class="quick-action-btn"
                            onclick="window.showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="card-metrics">
                    <div class="metric-item">
                        <div class="metric-icon">
                            <i class="fas fa-yen-sign"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?php echo format_yen($customer['total_sales']); ?></span>
                            <span class="metric-label">売上</span>
                        </div>
                    </div>

                    <div class="metric-item">
                        <div class="metric-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?php echo number_format($customer['delivery_count']); ?></span>
                            <span class="metric-label">配達回数</span>
                        </div>
                    </div>

                    <div class="metric-item">
                        <div class="metric-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="metric-data">
                            <span class="metric-value"><?php echo format_days($customer['avg_lead_time']); ?></span>
                            <span class="metric-label">リードタイム</span>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="progress-section">
                        <div class="progress-label">売上目標達成率</div>
                        <div class="progress-bar">
                            <?php
                                $target = 600000; // 目標売上
                                $achievement = min(100, ($customer['total_sales'] / $target) * 100);
                                ?>
                            <div class="progress-fill" style="width: <?php echo $achievement; ?>%"></div>
                        </div>
                        <span class="progress-percentage"><?php echo round($achievement, 1); ?>%</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ページネーション -->
        <div class="pagination-section">
            <div class="pagination-info">
                <span>1-<?php echo min(12, count($customerList)); ?> of <?php echo count($customerList); ?>
                    customers</span>
            </div>
            <div class="pagination-controls">
                <button class="pagination-btn" id="prevPage" disabled>
                    <i class="fas fa-chevron-left"></i>
                    前へ
                </button>
                <span class="page-indicator">1 / 1</span>
                <button class="pagination-btn" id="nextPage" disabled>
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

<style>
/* ダッシュボード専用の追加スタイル */
.metric-subtitle {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--sub-green);
    margin-top: 8px;
    opacity: 0.8;
}

.metric-trend {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    margin-top: 8px;
}

.metric-trend.positive {
    color: #059669;
}

.metric-trend.negative {
    color: #dc2626;
}

.performance-section {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 8px 32px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.section-title {
    font-size: 24px;
    font-weight: 800;
    color: var(--main-green);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.performance-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid rgba(126, 217, 87, 0.2);
    box-shadow: 0 4px 16px rgba(47, 93, 63, 0.05);
}

.performance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid rgba(126, 217, 87, 0.1);
}

.performance-header h5 {
    font-size: 16px;
    font-weight: 700;
    color: var(--main-green);
    margin: 0;
}

.performance-header i {
    color: var(--accent-green);
    font-size: 18px;
}

.performance-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.performance-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
}

.performance-item .rank {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.performance-item .name {
    flex: 1;
    font-weight: 600;
    color: var(--main-green);
    font-size: 14px;
}

.performance-item .value {
    font-weight: 700;
    color: var(--sub-green);
    font-size: 14px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.sort-select-mini {
    padding: 8px 12px;
    border: 2px solid rgba(126, 217, 87, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    color: var(--main-green);
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
}

.customer-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.customer-overview-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 20px;
    border: 1px solid rgba(126, 217, 87, 0.2);
    box-shadow: 0 6px 24px rgba(47, 93, 63, 0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.customer-overview-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent-green), var(--main-green));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.customer-overview-card:hover::before {
    transform: scaleX(1);
}

.customer-overview-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(47, 93, 63, 0.12);
    border-color: var(--accent-green);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.card-header .customer-rank {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, var(--main-green), var(--accent-green));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    flex-shrink: 0;
}

.card-header .customer-info {
    flex: 1;
}

.card-header .customer-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--main-green);
    margin-bottom: 4px;
}

.card-header .customer-id {
    font-size: 11px;
    color: var(--sub-green);
    background: rgba(126, 217, 87, 0.1);
    padding: 2px 8px;
    border-radius: 6px;
    display: inline-block;
}

.quick-action-btn {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(126, 217, 87, 0.3);
}

.quick-action-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(126, 217, 87, 0.4);
}

.card-metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.metric-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: rgba(126, 217, 87, 0.05);
    border-radius: 10px;
    border: 1px solid rgba(126, 217, 87, 0.1);
}

.metric-icon {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    flex-shrink: 0;
}

.metric-data {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.metric-data .metric-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--main-green);
    line-height: 1.2;
}

.metric-data .metric-label {
    font-size: 10px;
    color: var(--sub-green);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress-section {
    padding-top: 12px;
    border-top: 1px solid rgba(126, 217, 87, 0.1);
}

.progress-label {
    font-size: 12px;
    color: var(--sub-green);
    margin-bottom: 6px;
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: rgba(126, 217, 87, 0.1);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent-green), var(--main-green));
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-percentage {
    font-size: 11px;
    color: var(--main-green);
    font-weight: 700;
}

.pagination-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: rgba(126, 217, 87, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(126, 217, 87, 0.1);
}

.pagination-info {
    font-size: 14px;
    color: var(--sub-green);
    font-weight: 600;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.pagination-btn {
    padding: 8px 16px;
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #ccc;
}

.pagination-btn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(126, 217, 87, 0.3);
}

.page-indicator {
    font-size: 14px;
    color: var(--main-green);
    font-weight: 600;
}

.additional-stats {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 8px 32px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
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

.stat-content h5 {
    font-size: 14px;
    color: var(--sub-green);
    margin: 0 0 5px 0;
    font-weight: 600;
}

.stat-number {
    font-size: 24px;
    font-weight: 800;
    color: var(--main-green);
    margin: 0 0 5px 0;
}

.stat-change {
    font-size: 12px;
    color: var(--sub-green);
    font-weight: 600;
}

.stat-change.positive {
    color: #059669;
}

.stat-change.negative {
    color: #dc2626;
}

@media (max-width: 768px) {
    .customer-overview-grid {
        grid-template-columns: 1fr;
    }

    .card-metrics {
        grid-template-columns: 1fr;
    }

    .performance-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .pagination-section {
        flex-direction: column;
        gap: 15px;
    }

    .section-header {
        flex-direction: column;
        align-items: stretch;
    }

    .header-actions {
        justify-content: space-between;
    }
}
</style>