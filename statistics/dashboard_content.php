<!-- ダッシュボードタブ -->
<div id="dashboard" class="tab-content active">
    <div class="dashboard-grid">
        <div class="metric-card">
            <div class="card-icon" style="background-color: #e7f3ff; color: #4a90e2;">
                <i class="fas fa-users"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">総顧客数</h3>
                <p class="metric-value"><?php echo number_format($totalCustomers); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="card-icon" style="background-color: #e5f9f0; color: #2f855a;">
                <i class="fas fa-yen-sign"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">月間売上 (推定)</h3>
                <p class="metric-value"><?php echo format_yen($monthlySales); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="card-icon" style="background-color: #fff4e6; color: #d66a00;">
                <i class="fas fa-truck"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">総配達回数</h3>
                <p class="metric-value"><?php echo number_format($totalDeliveries); ?></p>
            </div>
        </div>
        <div class="metric-card">
            <div class="card-icon" style="background-color: #f0e8ff; color: #6b46c1;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="card-info">
                <h3 class="card-title">平均リードタイム</h3>
                <p class="metric-value"><?php echo format_days($avgLeadTime); ?></p>
            </div>
        </div>
    </div>
    <div class="top-customers-section">
        <h4>全顧客一覧（売上順）</h4>
        <div class="customers-count">
            <span class="count-info">表示中: <?php echo count($customerList); ?>人</span>
        </div>
        <div class="top-customers-grid">
            <?php
            foreach ($customerList as $index => $customer) :
            ?>
            <div class="top-customer-card">
                <div class="customer-rank"><?php echo $index + 1; ?></div>
                <div class="customer-info">
                    <div class="customer-name">
                        <?php echo htmlspecialchars($customer['customer_name']); ?></div>
                    <div class="customer-stats">
                        <span class="stat-item">
                            <i class="fas fa-yen-sign"></i>
                            <?php echo format_yen($customer['total_sales']); ?>
                        </span>
                        <span class="stat-item">
                            <i class="fas fa-truck"></i>
                            <?php echo number_format($customer['delivery_count']); ?>回
                        </span>
                        <span class="stat-item">
                            <i class="fas fa-clock"></i>
                            <?php echo format_days($customer['avg_lead_time']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>