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
<body class="with-header delivery-page">
    <div class="delivery-container">
        <h1 class="page-title"><i class="fas fa-truck"></i> 納品書管理</h1>

        <div class="delivery-actions-bar">
            <div class="search-box">
                <input type="text" class="search-input" placeholder="顧客名で検索..." id="searchInput">
                <button class="btn btn-icon" data-action="searchDeliveries">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <button class="btn btn-primary btn-icon" data-action="showCustomerSelect">
                <i class="fas fa-plus"></i> 新規納品書
            </button>
        </div>

        <div class="delivery-list-section card">
            <table class="table delivery-table">
                <thead>
                    <tr>
                        <th class="checkbox-col">選択</th>
                        <th>顧客名</th>
                        <th>ステータス</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="deliveryTableBody">
                    <?php foreach ($sampleDeliveries as $delivery): ?>
                    <tr data-customer-name="<?php echo htmlspecialchars($delivery['customer_name']); ?>">
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
                        <td>
                            <button class="btn btn-sm btn-outline" data-action="showDeliveryDetail" data-customer-name="<?php echo htmlspecialchars($delivery['customer_name']); ?>">
                                <i class="fas fa-eye"></i> 詳細
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination-controls">
                <button class="pagination-btn" id="prevBtn"><i class="fas fa-chevron-left"></i> 前へ</button>
                <span class="page-info">1/4</span>
                <button class="pagination-btn" id="nextBtn">次へ <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <div class="modal-overlay customer-select" id="customerSelect">
        <div class="modal-content customer-modal">
            <button class="close-modal" data-action="hideCustomerSelect">&times;</button>
            <h2><i class="fas fa-user-plus"></i> 顧客名を選択してください</h2>
            <input type="text" class="search-input" placeholder="検索..." id="customerSearchInput">

            <div class="customer-list" id="customerList">
                <?php foreach ($sampleCustomers as $customer): ?>
                <div class="customer-item" data-customer-name="<?php echo htmlspecialchars($customer); ?>">
                    <?php echo htmlspecialchars($customer); ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="modal-actions">
                <button class="btn btn-primary" data-action="confirmCustomerSelection">
                    <i class="fas fa-check"></i> 顧客選択決定
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay delivery-detail" id="deliveryDetail">
        <div class="modal-content delivery-modal">
            <button class="close-modal" data-action="hideDeliveryDetail">&times;</button>

            <div class="delivery-header">
                <h2><i class="fas fa-file-invoice"></i> 納品書 No. <span id="deliveryNo">1</span></h2>
                <div class="delivery-info-grid">
                    <div class="info-item">
                        <span class="info-label">登録日:</span>
                        <span id="registrationDate">2022/11/25</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">顧客名:</span>
                        <span id="detailCustomerName">木村 紗希</span>
                    </div>
                </div>
            </div>

            <div class="detail-table-container">
                <table class="table detail-table">
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
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" checked></td>
                            <td>週間BCN　10月号</td>
                            <td>1</td>
                            <td class="text-right">¥1,100</td>
                            <td class="text-right">¥1,210</td>
                        </tr>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" checked></td>
                            <td>日経コンピューター　10月号</td>
                            <td>2</td>
                            <td class="text-right">¥1,000</td>
                            <td class="text-right">¥2,200</td>
                        </tr>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox"></td>
                            <td>週間マガジン　10月号</td>
                            <td>1</td>
                            <td class="text-right">¥800</td>
                            <td class="text-right">¥880</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-section card">
                <div class="total-row">
                    <span class="total-label">合計金額:</span>
                    <span class="total-amount" id="totalAmount">¥4,290</span>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary" data-action="saveDelivery">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button class="btn btn-primary" data-action="printDelivery">
                    <i class="fas fa-print"></i> 印刷
                </button>
            </div>
        </div>
    </div>
</body>