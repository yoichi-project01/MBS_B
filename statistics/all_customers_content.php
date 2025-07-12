<!-- 全顧客タブ -->
<div id="all-customers" class="tab-content">
    <div class="all-customers-header">
        <h3>全顧客一覧</h3>
        <div class="controls-section">
            <div class="search-container">
                <input type="text" id="allCustomerSearchInput" placeholder="顧客名で検索..."
                    class="search-input">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div class="sort-controls">
                <label for="sortBy">並び替え:</label>
                <select id="sortBy" class="sort-select">
                    <option value="name-asc">顧客名（昇順）</option>
                    <option value="name-desc">顧客名（降順）</option>
                    <option value="sales-desc" selected>売上（降順）</option>
                    <option value="sales-asc">売上（昇順）</option>
                    <option value="deliveries-desc">配達回数（降順）</option>
                    <option value="deliveries-asc">配達回数（昇順）</option>
                    <option value="leadtime-asc">リードタイム（昇順）</option>
                    <option value="leadtime-desc">リードタイム（降順）</option>
                </select>
            </div>
        </div>
    </div>

    <div class="all-customers-table-container">
        <table class="data-table" id="allCustomersTable">
            <thead>
                <tr>
                    <th>顧客名</th>
                    <th>売上</th>
                    <th>リードタイム</th>
                    <th>配達回数</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody id="allCustomersTableBody">
                <?php foreach ($customerList as $customer) : ?>
                <tr data-customer-name="<?php echo htmlspecialchars($customer['customer_name']); ?>">
                    <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                    <td class="text-right" data-value="<?php echo $customer['total_sales']; ?>">
                        <?php echo format_yen($customer['total_sales']); ?>
                    </td>
                    <td class="text-center" data-value="<?php echo $customer['avg_lead_time']; ?>">
                        <?php echo format_days($customer['avg_lead_time']); ?>
                    </td>
                    <td class="text-center" data-value="<?php echo $customer['delivery_count']; ?>">
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
</div>