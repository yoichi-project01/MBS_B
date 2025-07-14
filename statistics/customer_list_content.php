<!-- 顧客一覧 -->
<div class="customer-list-container">
    
    <div class="customer-search-section">
        <div class="search-container">
            <input type="text" id="customerSearchInput" class="search-input" placeholder="顧客名または顧客Noで検索...">
            <i class="fas fa-search search-icon"></i>
        </div>
    </div>

    <div class="table-view-container">
        <div class="table-container">
            <table class="data-table" id="customerTable">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="customer_no">顧客No</th>
                        <th class="sortable" data-sort="customer_name">顧客名</th>
                        <th class="sortable" data-sort="total_sales">総売上</th>
                        <th class="sortable" data-sort="delivery_count">配達回数</th>
                        <th class="sortable" data-sort="avg_lead_time">平均リードタイム</th>
                        <th>アクション</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- 顧客データはJavaScriptで挿入されます -->
                </tbody>
            </table>
        </div>
    </div>
</div>