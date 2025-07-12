<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');
SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

$sampleDeliveries = [
    ['id' => 1, 'customer_name' => '木村 紗希', 'status' => 'completed', 'completed' => 3, 'total' => 3],
    ['id' => 2, 'customer_name' => '桜井株式会社', 'status' => 'partial', 'completed' => 2, 'total' => 3],
    ['id' => 3, 'customer_name' => 'カフェ ドルチェビータ', 'status' => 'completed', 'completed' => 1, 'total' => 1],
    ['id' => 4, 'customer_name' => '喫茶店 フレーバー', 'status' => 'completed', 'completed' => 5, 'total' => 5],
    ['id' => 5, 'customer_name' => '木下萌', 'status' => 'partial', 'completed' => 3, 'total' => 4],
    ['id' => 6, 'customer_name' => 'コーヒーハウス レインボー', 'status' => 'completed', 'completed' => 3, 'total' => 3],
];

$sampleCustomers = [
    '木村紗希',
    'カフェ ドルチェビータ',
    '喫茶店 フレーバー',
    '木下萌',
    '川上里奈',
    'コーヒーハウス レインボー',
    '桜井株式会社'
];
?>
<body class="with-header">
    <div class="delivery-container">
        <div class="delivery-list">
            <div class="search-section">
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="顧客名で検索..." id="searchInput">
                    <button class="btn" onclick="searchDeliveries()">
                        <i class="fas fa-search"></i> 検索
                    </button>
                    <button class="btn btn-new" onclick="showCustomerSelect()">
                        <i class="fas fa-plus"></i> 新規作成
                    </button>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th class="checkbox-col">選択</th>
                        <th>顧客名（企業名含む）</th>
                        <th>ステータス</th>
                    </tr>
                </thead>
                <tbody id="deliveryTableBody">
                    <?php foreach ($sampleDeliveries as $delivery): ?>
                    <tr data-customer="<?php echo htmlspecialchars($delivery['customer_name']); ?>">
                        <td class="checkbox-col">
                            <input type="checkbox" value="<?php echo $delivery['id']; ?>">
                        </td>
                        <td><?php echo htmlspecialchars($delivery['customer_name']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $delivery['status']; ?>">
                                <?php
                                    if ($delivery['status'] === 'completed') {
                                        echo "納品済み {$delivery['completed']}/{$delivery['total']}";
                                    } else {
                                        echo "納品未完了 {$delivery['completed']}/{$delivery['total']}";
                                    }
                                    ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <button class="pagination-btn" id="prevBtn">前</button>
                <span>1/4</span>
                <button class="pagination-btn" id="nextBtn">次</button>
            </div>
        </div>
    </div>

    <div class="customer-select" id="customerSelect">
        <div class="customer-modal">
            <button class="close-btn" onclick="hideCustomerSelect()">&times;</button>
            <h2>※顧客名を選択してください</h2>
            <input type="text" class="search-input" placeholder="検索..." style="margin-bottom: 1rem;"
                id="customerSearchInput">

            <div class="customer-list" id="customerList">
                <?php foreach ($sampleCustomers as $customer): ?>
                <div class="customer-item" onclick="selectCustomer('<?php echo htmlspecialchars($customer); ?>')">
                    <?php echo htmlspecialchars($customer); ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="action-buttons">
                <button class="btn" onclick="confirmCustomerSelection()">
                    <i class="fas fa-check"></i> 顧客選択決定
                </button>
            </div>
        </div>
    </div>

    <div class="delivery-detail" id="deliveryDetail">
        <div class="delivery-modal">
            <button class="close-btn" onclick="hideDeliveryDetail()">&times;</button>

            <div class="delivery-header">
                <h2><i class="fas fa-file-invoice"></i> 納品書 No. <span id="deliveryNo">1</span></h2>
                <div class="delivery-info">
                    <div class="info-item">
                        <span class="info-label">登録日:</span>
                        <span id="registrationDate">2022/11/25</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">顧客名（企業名含む）:</span>
                        <span id="customerName">木村 紗希</span>
                    </div>
                </div>
            </div>

            <table class="detail-table">
                <thead>
                    <tr>
                        <th class="checkbox-col">選択</th>
                        <th>品名</th>
                        <th>数量</th>
                        <th>単価</th>
                        <th>金額</th>
                    </tr>
                </thead>
                <tbody id="deliveryDetailBody">
                    </tbody>
            </table>

            <div class="total-section">
                <table style="width: 100%; margin-bottom: 1rem;">
                    <tr>
                        <td>税率</td>
                        <td>消費税額等</td>
                        <td>合計金額</td>
                    </tr>
                    <tr>
                        <td style="border-bottom: 1px solid #ddd; padding: 0.5rem;"></td>
                        <td style="border-bottom: 1px solid #ddd; padding: 0.5rem;"></td>
                        <td style="border-bottom: 1px solid #ddd; padding: 0.5rem;" class="total-amount">¥0</td>
                    </tr>
                </table>
            </div>

            <div class="action-buttons">
                <button class="btn" onclick="saveDelivery()">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button class="btn" onclick="printDelivery()">
                    <i class="fas fa-print"></i> 印刷
                </button>
            </div>
        </div>
    </div>
</body>