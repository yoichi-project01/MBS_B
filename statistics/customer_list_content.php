<!-- 顧客一覧タブ -->
<div id="customers" class="tab-content">
    <div class="customer-search-section">
        <div class="search-container">
            <input type="text" id="customerSearchInput" placeholder="顧客名で検索..." class="search-input">
            <i class="fas fa-search search-icon"></i>
        </div>
        <div class="view-toggle">
            <button class="view-btn active" data-view="table"><i class="fas fa-table"></i>
                テーブル表示</button>
            <button class="view-btn" data-view="card"><i class="fas fa-id-card"></i> カード表示</button>
        </div>
    </div>

    <div class="table-view-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="sortable" data-sort="name">顧客名 <i class="fas fa-sort"></i></th>
                    <th class="sortable" data-sort="sales">売上 <i class="fas fa-sort"></i></th>
                    <th class="sortable" data-sort="leadtime">リードタイム <i class="fas fa-sort"></i></th>
                    <th class="sortable" data-sort="deliveries">配達回数 <i class="fas fa-sort"></i></th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
                <?php foreach ($customerList as $customer) : ?>
                <tr>
                    <td data-sort-value="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                        <?php echo htmlspecialchars($customer['customer_name']); ?>
                    </td>
                    <td class="text-right" data-sort-value="<?php echo $customer['total_sales']; ?>">
                        <?php echo format_yen($customer['total_sales']); ?>
                    </td>
                    <td class="text-center" data-sort-value="<?php echo $customer['avg_lead_time']; ?>">
                        <?php echo format_days($customer['avg_lead_time']); ?>
                    </td>
                    <td class="text-center"
                        data-sort-value="<?php echo $customer['delivery_count']; ?>">
                        <?php echo number_format($customer['delivery_count']); ?>
                    </td>
                    <td class="text-center">
                        <button class="table-action-btn"
                                            data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                            <i class="fas fa-eye"></i> 詳細
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-view-container" style="display: none;">
        <?php foreach ($customerList as $customer) : ?>
        <div class="customer-card">
            <div class="card-main-info">
                <h4 class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?>
                </h4>
                <p class="customer-id">ID: <?php echo htmlspecialchars($customer['customer_no']); ?></p>
            </div>
            <div class="card-stats">
                <div class="stat">
                    <p class="stat-value"><?php echo format_yen($customer['total_sales']); ?></p>
                    <p class="stat-label">売上</p>
                </div>
                <div class="stat">
                    <p class="stat-value"><?php echo number_format($customer['delivery_count']); ?></p>
                    <p class="stat-label">配達回数</p>
                </div>
                <div class="stat">
                    <p class="stat-value"><?php echo format_days($customer['avg_lead_time']); ?></p>
                    <p class="stat-label">リードタイム</p>
                </div>
            </div>
            <div class="card-actions">
                <button class="card-btn"
                    onclick="window.showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                    <i class="fas fa-eye"></i> 詳細
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>