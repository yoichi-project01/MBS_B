<!-- 改善された顧客一覧タブ -->
<div id="customers" class="tab-content">
    <!-- 検索・フィルター・ビュー切り替えセクション -->
    <div class="customer-controls-section">
        <div class="controls-row">
            <div class="search-container">
                <input type="text" id="customerSearchInput" placeholder="顧客名で検索..." class="search-input"
                    autocomplete="off" aria-label="顧客名で検索">
                <i class="fas fa-search search-icon" aria-hidden="true"></i>
                <button class="search-clear-btn" id="clearSearch" style="display: none;" aria-label="検索をクリア">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="filter-controls">
                <div class="filter-group">
                    <label for="salesFilter" class="filter-label">売上フィルター:</label>
                    <select id="salesFilter" class="filter-select">
                        <option value="all">全て</option>
                        <option value="high">¥500K以上</option>
                        <option value="medium">¥100K-500K</option>
                        <option value="low">¥100K未満</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="deliveryFilter" class="filter-label">配達回数:</label>
                    <select id="deliveryFilter" class="filter-select">
                        <option value="all">全て</option>
                        <option value="frequent">50回以上</option>
                        <option value="regular">10-49回</option>
                        <option value="occasional">10回未満</option>
                    </select>
                </div>
            </div>

            <div class="view-toggle">
                <button class="view-btn active" data-view="table" aria-pressed="true">
                    <i class="fas fa-table"></i>
                    <span>テーブル</span>
                </button>
                <button class="view-btn" data-view="card" aria-pressed="false">
                    <i class="fas fa-id-card"></i>
                    <span>カード</span>
                </button>
            </div>
        </div>

        <div class="summary-row">
            <div class="results-summary">
                <span class="results-count" id="resultsCount">
                    <i class="fas fa-users"></i>
                    表示中: <strong><?php echo count($customerList); ?>人</strong>
                </span>
                <span class="total-sales" id="totalSalesDisplay">
                    <i class="fas fa-yen-sign"></i>
                    合計売上:
                    <strong><?php echo format_yen(array_sum(array_column($customerList, 'total_sales'))); ?></strong>
                </span>
            </div>

            <div class="quick-actions">
                <button class="quick-action-btn" id="exportData" title="データをエクスポート">
                    <i class="fas fa-download"></i>
                    <span>エクスポート</span>
                </button>
                <button class="quick-action-btn" id="printList" title="リストを印刷">
                    <i class="fas fa-print"></i>
                    <span>印刷</span>
                </button>
                <button class="quick-action-btn" id="refreshData" title="データを更新">
                    <i class="fas fa-sync-alt"></i>
                    <span>更新</span>
                </button>
            </div>
        </div>
    </div>

    <!-- テーブル表示 -->
    <div class="table-view-container" id="tableView">
        <div class="table-wrapper">
            <table class="data-table enhanced-table" role="table" aria-label="顧客一覧テーブル">
                <thead>
                    <tr role="row">
                        <th class="sortable checkbox-col" role="columnheader">
                            <input type="checkbox" id="selectAll" aria-label="全て選択">
                        </th>
                        <th class="sortable" data-sort="name" role="columnheader" aria-sort="none" tabindex="0">
                            <div class="header-content">
                                <span>顧客名</span>
                                <i class="fas fa-sort sort-icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th class="sortable" data-sort="sales" role="columnheader" aria-sort="none" tabindex="0">
                            <div class="header-content">
                                <span>売上</span>
                                <i class="fas fa-sort sort-icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th class="sortable" data-sort="leadtime" role="columnheader" aria-sort="none" tabindex="0">
                            <div class="header-content">
                                <span>リードタイム</span>
                                <i class="fas fa-sort sort-icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th class="sortable" data-sort="deliveries" role="columnheader" aria-sort="none" tabindex="0">
                            <div class="header-content">
                                <span>配達回数</span>
                                <i class="fas fa-sort sort-icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th class="sortable" data-sort="efficiency" role="columnheader" aria-sort="none" tabindex="0">
                            <div class="header-content">
                                <span>効率性</span>
                                <i class="fas fa-sort sort-icon" aria-hidden="true"></i>
                            </div>
                        </th>
                        <th role="columnheader">操作</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody" role="rowgroup">
                    <?php foreach ($customerList as $index => $customer) :
                        $efficiency = $customer['delivery_count'] > 0 ? $customer['total_sales'] / $customer['delivery_count'] : 0;
                        $efficiencyClass = $efficiency > 5000 ? 'high' : ($efficiency > 2000 ? 'medium' : 'low');
                    ?>
                    <tr role="row" data-customer-id="<?php echo $customer['customer_no']; ?>" class="customer-row">
                        <td class="checkbox-col">
                            <input type="checkbox" class="row-checkbox" value="<?php echo $customer['customer_no']; ?>"
                                aria-label="<?php echo htmlspecialchars($customer['customer_name']); ?>を選択">
                        </td>
                        <td data-sort-value="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                            class="customer-name-cell">
                            <div class="customer-info">
                                <div class="customer-name-primary">
                                    <?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                <div class="customer-meta">
                                    <span class="customer-id">ID: <?php echo $customer['customer_no']; ?></span>
                                    <span class="customer-rank">#<?php echo $index + 1; ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="text-right sales-cell" data-sort-value="<?php echo $customer['total_sales']; ?>">
                            <div class="sales-info">
                                <span class="sales-amount"><?php echo format_yen($customer['total_sales']); ?></span>
                                <div class="sales-bar">
                                    <?php
                                        $maxSales = max(array_column($customerList, 'total_sales'));
                                        $percentage = $maxSales > 0 ? ($customer['total_sales'] / $maxSales) * 100 : 0;
                                        ?>
                                    <div class="sales-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center leadtime-cell"
                            data-sort-value="<?php echo $customer['avg_lead_time']; ?>">
                            <div class="leadtime-info">
                                <span
                                    class="leadtime-value"><?php echo format_days($customer['avg_lead_time']); ?></span>
                                <div
                                    class="leadtime-indicator <?php echo $customer['avg_lead_time'] <= 2 ? 'excellent' : ($customer['avg_lead_time'] <= 4 ? 'good' : 'needs-improvement'); ?>">
                                    <i class="fas fa-circle"></i>
                                </div>
                            </div>
                        </td>
                        <td class="text-center delivery-cell"
                            data-sort-value="<?php echo $customer['delivery_count']; ?>">
                            <div class="delivery-info">
                                <span
                                    class="delivery-count"><?php echo number_format($customer['delivery_count']); ?></span>
                                <span class="delivery-label">回</span>
                            </div>
                        </td>
                        <td class="text-center efficiency-cell" data-sort-value="<?php echo $efficiency; ?>">
                            <div class="efficiency-info">
                                <span class="efficiency-score <?php echo $efficiencyClass; ?>">
                                    <?php echo format_yen($efficiency); ?>/回
                                </span>
                                <div class="efficiency-badge <?php echo $efficiencyClass; ?>">
                                    <?php
                                        switch ($efficiencyClass) {
                                            case 'high':
                                                echo '優秀';
                                                break;
                                            case 'medium':
                                                echo '良好';
                                                break;
                                            default:
                                                echo '改善';
                                                break;
                                        }
                                        ?>
                                </div>
                            </div>
                        </td>
                        <td class="actions-cell">
                            <div class="action-buttons">
                                <button class="table-action-btn primary"
                                    data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>"
                                    title="詳細を表示">
                                    <i class="fas fa-eye"></i>
                                    <span>詳細</span>
                                </button>
                                <button class="table-action-btn secondary"
                                    data-customer-id="<?php echo $customer['customer_no']; ?>" title="編集">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="table-action-btn tertiary"
                                    data-customer-id="<?php echo $customer['customer_no']; ?>" title="メニューを表示">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- テーブルフッター -->
        <div class="table-footer">
            <div class="selection-summary" id="selectionSummary" style="display: none;">
                <span class="selected-count">0件選択中</span>
                <div class="bulk-actions">
                    <button class="bulk-action-btn" id="bulkExport">
                        <i class="fas fa-download"></i>
                        選択項目をエクスポート
                    </button>
                    <button class="bulk-action-btn" id="bulkPrint">
                        <i class="fas fa-print"></i>
                        選択項目を印刷
                    </button>
                </div>
            </div>

            <div class="table-pagination">
                <div class="pagination-info">
                    <span>1-<?php echo min(20, count($customerList)); ?> of <?php echo count($customerList); ?>
                        件を表示</span>
                </div>
                <div class="pagination-controls">
                    <button class="pagination-btn" id="prevTablePage" disabled>
                        <i class="fas fa-chevron-left"></i>
                        前へ
                    </button>
                    <span class="page-numbers">
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <span class="page-ellipsis">...</span>
                    </span>
                    <button class="pagination-btn" id="nextTablePage">
                        次へ
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- カード表示 -->
    <div class="card-view-container" id="cardView" style="display: none;">
        <div class="card-grid">
            <?php foreach ($customerList as $index => $customer) :
                $efficiency = $customer['delivery_count'] > 0 ? $customer['total_sales'] / $customer['delivery_count'] : 0;
                $efficiencyClass = $efficiency > 5000 ? 'high' : ($efficiency > 2000 ? 'medium' : 'low');
                $target = 600000;
                $achievement = min(100, ($customer['total_sales'] / $target) * 100);
            ?>
            <div class="customer-card enhanced-card" data-customer-id="<?php echo $customer['customer_no']; ?>">
                <div class="card-header">
                    <div class="card-header-left">
                        <div class="customer-avatar">
                            <span class="avatar-text"><?php echo mb_substr($customer['customer_name'], 0, 1); ?></span>
                        </div>
                        <div class="customer-primary-info">
                            <h4 class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></h4>
                            <div class="customer-meta-info">
                                <span class="customer-id">ID: <?php echo $customer['customer_no']; ?></span>
                                <span class="customer-rank">#<?php echo $index + 1; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-header-right">
                        <div class="efficiency-badge-large <?php echo $efficiencyClass; ?>">
                            <?php
                                switch ($efficiencyClass) {
                                    case 'high':
                                        echo '⭐ 優秀';
                                        break;
                                    case 'medium':
                                        echo '👍 良好';
                                        break;
                                    default:
                                        echo '📈 改善';
                                        break;
                                }
                                ?>
                        </div>
                        <button class="card-menu-btn" title="メニューを表示">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                </div>

                <div class="card-stats-grid">
                    <div class="stat-item primary">
                        <div class="stat-icon">
                            <i class="fas fa-yen-sign"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo format_yen($customer['total_sales']); ?></div>
                            <div class="stat-label">総売上</div>
                            <div class="stat-progress">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $achievement; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo round($achievement, 1); ?>%</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-item secondary">
                        <div class="stat-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($customer['delivery_count']); ?></div>
                            <div class="stat-label">配達回数</div>
                        </div>
                    </div>

                    <div class="stat-item tertiary">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo format_days($customer['avg_lead_time']); ?></div>
                            <div class="stat-label">リードタイム</div>
                            <div
                                class="leadtime-indicator <?php echo $customer['avg_lead_time'] <= 2 ? 'excellent' : ($customer['avg_lead_time'] <= 4 ? 'good' : 'needs-improvement'); ?>">
                                <i class="fas fa-circle"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-item quaternary">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo format_yen($efficiency); ?></div>
                            <div class="stat-label">効率性/回</div>
                        </div>
                    </div>
                </div>

                <div class="card-actions">
                    <button class="card-action-btn primary"
                        data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                        <i class="fas fa-eye"></i>
                        詳細表示
                    </button>
                    <button class="card-action-btn secondary"
                        data-customer-id="<?php echo $customer['customer_no']; ?>">
                        <i class="fas fa-edit"></i>
                        編集
                    </button>
                    <button class="card-action-btn tertiary" data-customer-id="<?php echo $customer['customer_no']; ?>">
                        <i class="fas fa-chart-bar"></i>
                        分析
                    </button>
                </div>

                <div class="card-footer">
                    <div class="last-activity">
                        <i class="fas fa-calendar-alt"></i>
                        最終取引:
                        <?php echo $customer['last_order_date'] ? date('Y/m/d', strtotime($customer['last_order_date'])) : '未確認'; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- カードビューのページネーション -->
        <div class="card-pagination">
            <button class="pagination-btn" id="prevCardPage" disabled>
                <i class="fas fa-chevron-left"></i>
                前のページ
            </button>
            <div class="page-info">
                <span>ページ 1 / 3</span>
            </div>
            <button class="pagination-btn" id="nextCardPage">
                次のページ
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <!-- 空の状態 -->
    <div class="empty-state" id="emptyState" style="display: none;">
        <div class="empty-icon">
            <i class="fas fa-search"></i>
        </div>
        <h3>検索結果が見つかりません</h3>
        <p>検索条件を変更して再度お試しください。</p>
        <button class="btn btn-primary" id="clearFilters">
            <i class="fas fa-refresh"></i>
            フィルターをクリア
        </button>
    </div>
</div>

<style>
/* 顧客一覧タブ専用の追加スタイル */
.customer-controls-section {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 8px 32px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.controls-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.search-container {
    position: relative;
    flex: 1;
    max-width: 350px;
}

.search-clear-btn {
    position: absolute;
    right: 45px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--sub-green);
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.search-clear-btn:hover {
    background: rgba(126, 217, 87, 0.1);
    color: var(--main-green);
}

.filter-controls {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--main-green);
    white-space: nowrap;
}

.filter-select {
    padding: 8px 12px;
    border: 2px solid rgba(126, 217, 87, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.9);
    color: var(--main-green);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:focus {
    outline: none;
    border-color: var(--accent-green);
    box-shadow: 0 0 0 3px rgba(126, 217, 87, 0.1);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.results-summary {
    display: flex;
    gap: 25px;
    align-items: center;
    flex-wrap: wrap;
}

.results-count,
.total-sales {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: var(--sub-green);
}

.results-count i,
.total-sales i {
    color: var(--accent-green);
}

.quick-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.quick-action-btn {
    padding: 8px 15px;
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(126, 217, 87, 0.3);
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(126, 217, 87, 0.4);
}

.table-wrapper {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 249, 0.95) 100%);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.enhanced-table {
    width: 100%;
    border-collapse: collapse;
}

.enhanced-table th {
    background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
    color: white;
    padding: 18px 15px;
    font-weight: 700;
    font-size: 14px;
    border-bottom: 3px solid var(--accent-green);
    position: relative;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.sort-icon {
    opacity: 0.6;
    transition: all 0.3s ease;
}

.enhanced-table th.sortable:hover .sort-icon {
    opacity: 1;
    transform: scale(1.1);
}

.enhanced-table th.sort-asc .sort-icon::before {
    content: '\f0de';
    color: var(--accent-green);
}

.enhanced-table th.sort-desc .sort-icon::before {
    content: '\f0dd';
    color: var(--accent-green);
}

.checkbox-col {
    width: 60px;
    text-align: center;
}

.customer-row {
    transition: all 0.3s ease;
    border-bottom: 1px solid rgba(126, 217, 87, 0.1);
}

.customer-row:hover {
    background: linear-gradient(135deg, rgba(126, 217, 87, 0.05) 0%, rgba(248, 250, 249, 0.8) 100%);
    transform: translateX(4px);
}

.customer-row:nth-child(even) {
    background: rgba(248, 250, 249, 0.3);
}

.customer-name-cell {
    padding: 16px 15px;
}

.customer-info {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.customer-name-primary {
    font-size: 16px;
    font-weight: 700;
    color: var(--main-green);
    line-height: 1.3;
}

.customer-meta {
    display: flex;
    gap: 12px;
    align-items: center;
}

.customer-id {
    font-size: 11px;
    color: var(--sub-green);
    background: rgba(126, 217, 87, 0.1);
    padding: 2px 8px;
    border-radius: 6px;
    font-weight: 600;
}

.customer-rank {
    font-size: 11px;
    color: var(--accent-green);
    font-weight: 700;
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.sales-cell {
    padding: 16px 15px;
}

.sales-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}

.sales-amount {
    font-size: 16px;
    font-weight: 700;
    color: var(--main-green);
}

.sales-bar {
    width: 80px;
    height: 4px;
    background: rgba(126, 217, 87, 0.2);
    border-radius: 2px;
    overflow: hidden;
}

.sales-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent-green), var(--main-green));
    border-radius: 2px;
    transition: width 0.3s ease;
}

.leadtime-cell {
    padding: 16px 15px;
}

.leadtime-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.leadtime-value {
    font-weight: 700;
    color: var(--main-green);
}

.leadtime-indicator {
    font-size: 8px;
}

.leadtime-indicator.excellent {
    color: #059669;
}

.leadtime-indicator.good {
    color: #d97706;
}

.leadtime-indicator.needs-improvement {
    color: #dc2626;
}

.delivery-info {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 4px;
}

.delivery-count {
    font-size: 18px;
    font-weight: 700;
    color: var(--main-green);
}

.delivery-label {
    font-size: 12px;
    color: var(--sub-green);
}

.efficiency-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.efficiency-score {
    font-weight: 700;
    font-size: 14px;
}

.efficiency-score.high {
    color: #059669;
}

.efficiency-score.medium {
    color: #d97706;
}

.efficiency-score.low {
    color: #dc2626;
}

.efficiency-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.efficiency-badge.high {
    background: #dcfce7;
    color: #166534;
}

.efficiency-badge.medium {
    background: #fed7aa;
    color: #9a3412;
}

.efficiency-badge.low {
    background: #fecaca;
    color: #991b1b;
}

.actions-cell {
    padding: 16px 15px;
}

.action-buttons {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
}

.table-action-btn.primary {
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(126, 217, 87, 0.3);
}

.table-action-btn.secondary,
.table-action-btn.tertiary {
    background: rgba(126, 217, 87, 0.1);
    color: var(--main-green);
    padding: 8px;
    border-radius: 6px;
    border: 1px solid rgba(126, 217, 87, 0.3);
    cursor: pointer;
    transition: all 0.3s ease;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.table-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(126, 217, 87, 0.4);
}

.table-footer {
    background: rgba(248, 250, 249, 0.5);
    padding: 20px;
    border-top: 1px solid rgba(126, 217, 87, 0.1);
}

.selection-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 12px 20px;
    background: rgba(126, 217, 87, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.selected-count {
    font-weight: 600;
    color: var(--main-green);
}

.bulk-actions {
    display: flex;
    gap: 10px;
}

.bulk-action-btn {
    padding: 6px 12px;
    background: linear-gradient(135deg, var(--main-green), var(--accent-green));
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
}

.table-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    font-size: 14px;
    color: var(--sub-green);
    font-weight: 600;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-numbers {
    display: flex;
    gap: 5px;
    align-items: center;
}

.page-btn {
    width: 32px;
    height: 32px;
    border: 1px solid rgba(126, 217, 87, 0.3);
    background: white;
    color: var(--main-green);
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.page-btn.active {
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    border-color: var(--accent-green);
}

.page-btn:hover:not(.active) {
    background: rgba(126, 217, 87, 0.1);
}

.page-ellipsis {
    color: var(--sub-green);
    padding: 0 5px;
}

/* カード表示のスタイル */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.enhanced-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 249, 0.95) 100%);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 8px 32px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.enhanced-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--accent-green), var(--main-green));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.enhanced-card:hover::before {
    transform: scaleX(1);
}

.enhanced-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(47, 93, 63, 0.15);
    border-color: var(--accent-green);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.card-header-left {
    display: flex;
    gap: 15px;
    align-items: center;
    flex: 1;
}

.customer-avatar {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--main-green), var(--accent-green));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(47, 93, 63, 0.3);
}

.customer-primary-info {
    flex: 1;
}

.card-header .customer-name {
    font-size: 18px;
    font-weight: 700;
    color: var(--main-green);
    margin: 0 0 6px 0;
    line-height: 1.3;
}

.customer-meta-info {
    display: flex;
    gap: 10px;
    align-items: center;
}

.card-header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.efficiency-badge-large {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    white-space: nowrap;
}

.efficiency-badge-large.high {
    background: #dcfce7;
    color: #166534;
}

.efficiency-badge-large.medium {
    background: #fed7aa;
    color: #9a3412;
}

.efficiency-badge-large.low {
    background: #fecaca;
    color: #991b1b;
}

.card-menu-btn {
    width: 32px;
    height: 32px;
    background: rgba(126, 217, 87, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.3);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--main-green);
    transition: all 0.3s ease;
}

.card-menu-btn:hover {
    background: rgba(126, 217, 87, 0.2);
    transform: scale(1.1);
}

.card-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    border-radius: 12px;
    border: 1px solid rgba(126, 217, 87, 0.1);
    transition: all 0.3s ease;
}

.stat-item.primary {
    background: linear-gradient(135deg, rgba(126, 217, 87, 0.1) 0%, rgba(248, 250, 249, 0.5) 100%);
    grid-column: 1 / -1;
}

.stat-item.secondary {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.1);
}

.stat-item.tertiary {
    background: rgba(245, 158, 11, 0.05);
    border-color: rgba(245, 158, 11, 0.1);
}

.stat-item.quaternary {
    background: rgba(139, 69, 19, 0.05);
    border-color: rgba(139, 69, 19, 0.1);
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(47, 93, 63, 0.1);
}

.stat-item .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.stat-item.primary .stat-icon {
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
}

.stat-item.secondary .stat-icon {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-item.tertiary .stat-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-item.quaternary .stat-icon {
    background: linear-gradient(135deg, #8b4513, #a0522d);
    color: white;
}

.stat-item .stat-content {
    flex: 1;
}

.stat-item .stat-value {
    font-size: 18px;
    font-weight: 800;
    color: var(--main-green);
    margin-bottom: 4px;
    line-height: 1.2;
}

.stat-item.primary .stat-value {
    font-size: 24px;
}

.stat-item .stat-label {
    font-size: 12px;
    color: var(--sub-green);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-progress {
    display: flex;
    align-items: center;
    gap: 8px;
}

.progress-bar {
    flex: 1;
    height: 6px;
    background: rgba(126, 217, 87, 0.2);
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--accent-green), var(--main-green));
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 11px;
    font-weight: 700;
    color: var(--main-green);
}

.card-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.card-action-btn {
    flex: 1;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.card-action-btn.primary {
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    box-shadow: 0 4px 12px rgba(126, 217, 87, 0.3);
}

.card-action-btn.secondary {
    background: rgba(126, 217, 87, 0.1);
    color: var(--main-green);
    border: 1px solid rgba(126, 217, 87, 0.3);
}

.card-action-btn.tertiary {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.card-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.card-footer {
    padding-top: 15px;
    border-top: 1px solid rgba(126, 217, 87, 0.1);
}

.last-activity {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--sub-green);
    font-weight: 600;
}

.last-activity i {
    color: var(--accent-green);
}

.card-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: rgba(126, 217, 87, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(126, 217, 87, 0.1);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    border-radius: 16px;
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.empty-icon {
    font-size: 64px;
    color: var(--sub-green);
    margin-bottom: 20px;
    opacity: 0.6;
}

.empty-state h3 {
    font-size: 24px;
    color: var(--main-green);
    margin-bottom: 10px;
}

.empty-state p {
    color: var(--sub-green);
    margin-bottom: 20px;
}

/* レスポンシブデザイン */
@media (max-width: 768px) {
    .controls-row {
        flex-direction: column;
        align-items: stretch;
    }

    .search-container {
        max-width: none;
    }

    .filter-controls {
        justify-content: space-between;
    }

    .summary-row {
        flex-direction: column;
        align-items: stretch;
    }

    .quick-actions {
        justify-content: center;
    }

    .card-grid {
        grid-template-columns: 1fr;
    }

    .card-stats-grid {
        grid-template-columns: 1fr;
    }

    .card-actions {
        flex-direction: column;
    }

    .table-pagination {
        flex-direction: column;
        gap: 15px;
    }

    .enhanced-table {
        font-size: 12px;
    }

    .enhanced-table th,
    .enhanced-table td {
        padding: 10px 8px;
    }

    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }
}
</style>