<?php
// 最初にオートローダーを読み込み
require_once(__DIR__ . '/../component/autoloader.php');
// DB接続
require_once(__DIR__ . '/../component/db.php');

// その後にヘッダーを読み込み
include(__DIR__ . '/../component/header.php');

// セッション開始
SessionManager::start();

// 現在選択されている店舗名を取得
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

// 店舗名が設定されていない場合はエラーまたはデフォルト処理
if (empty($storeName)) {
    die("店舗が選択されていません。");
}

// 初期化
$totalCustomers = 0;
$monthlySales = 0;
$salesTrend = 0;
$totalDeliveries = 0;
$avgLeadTime = 0;
$customerList = [];

try {
    // 1. ダッシュボードのメトリクス
    $totalCustomersStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE store_name = :storeName");
    $totalCustomersStmt->bindParam(':storeName', $storeName);
    $totalCustomersStmt->execute();
    $totalCustomers = $totalCustomersStmt->fetchColumn();

    $dashboardQuery = "
        SELECT 
            SUM(total_sales) as total_sales, 
            SUM(delivery_count) as total_deliveries, 
            AVG(avg_lead_time) as avg_lead_time 
        FROM customer_summary
        WHERE store_name = :storeName
    ";
    $dashboardMetricsStmt = $pdo->prepare($dashboardQuery);
    $dashboardMetricsStmt->bindParam(':storeName', $storeName);
    $dashboardMetricsStmt->execute();
    $dashboardMetrics = $dashboardMetricsStmt->fetch(PDO::FETCH_ASSOC);

    $totalDeliveries = $dashboardMetrics['total_deliveries'] ?? 0;
    $avgLeadTime = $dashboardMetrics['avg_lead_time'] ?? 0;

    // 当月の売上
    $currentMonthQuery = "
        SELECT SUM(di.amount) 
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        JOIN order_items oi ON di.order_item_no = oi.order_item_no
        JOIN orders o ON oi.order_no = o.order_no
        JOIN customers c ON o.customer_no = c.customer_no
        WHERE YEAR(d.delivery_record) = YEAR(CURDATE()) 
          AND MONTH(d.delivery_record) = MONTH(CURDATE())
          AND c.store_name = :storeName
    ";
    $currentMonthSalesStmt = $pdo->prepare($currentMonthQuery);
    $currentMonthSalesStmt->bindParam(':storeName', $storeName);
    $currentMonthSalesStmt->execute();
    $monthlySales = $currentMonthSalesStmt->fetchColumn() ?? 0;

    // 前月の売上
    $previousMonthQuery = "
        SELECT SUM(di.amount) 
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        JOIN order_items oi ON di.order_item_no = oi.order_item_no
        JOIN orders o ON oi.order_no = o.order_no
        JOIN customers c ON o.customer_no = c.customer_no
        WHERE YEAR(d.delivery_record) = YEAR(CURDATE() - INTERVAL 1 MONTH) 
          AND MONTH(d.delivery_record) = MONTH(CURDATE() - INTERVAL 1 MONTH)
          AND c.store_name = :storeName
    ";
    $previousMonthSalesStmt = $pdo->prepare($previousMonthQuery);
    $previousMonthSalesStmt->bindParam(':storeName', $storeName);
    $previousMonthSalesStmt->execute();
    $previousMonthSales = $previousMonthSalesStmt->fetchColumn() ?? 0;

    // 前月比の計算
    if ($previousMonthSales > 0) {
        $salesTrend = (($monthlySales - $previousMonthSales) / $previousMonthSales) * 100;
    } elseif ($monthlySales > 0) {
        $salesTrend = 100;
    } else {
        $salesTrend = 0;
    }

    // 2. 顧客一覧データ (customer_summary ビューを使用)
    $customerListQuery = "SELECT * FROM customer_summary WHERE store_name = :storeName ORDER BY total_sales DESC";
    $customersStmt = $pdo->prepare($customerListQuery);
    $customersStmt->bindParam(':storeName', $storeName);
    $customersStmt->execute();
    $customerList = $customersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Statistics page database error: " . $e->getMessage());
    die("データベースへの接続中にエラーが発生しました。しばらくしてからもう一度お試しください。");
}

// 数値をフォーマットするヘルパー関数
function format_yen($amount)
{
    if ($amount >= 1000000) {
        return '¥' . number_format($amount / 1000000, 2) . 'M';
    } elseif ($amount >= 1000) {
        return '¥' . number_format($amount / 1000, 1) . 'K';
    }
    return '¥' . number_format($amount);
}

function format_days($days)
{
    return number_format($days, 2) . '日';
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報 - 受注管理システム</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="statistics-page">
    <div class="dashboard-container">
        <aside class="top-nav">
            <div class="top-nav-header">
                <i class="fas fa-book-open"></i>
                <h3>受注管理</h3>
            </div>
            <nav class="top-nav-links">
                <a href="#" class="nav-link active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>ダッシュボード</span>
                </a>
                <a href="#" class="nav-link" data-tab="customers">
                    <i class="fas fa-users"></i>
                    <span>顧客一覧</span>
                </a>
                <a href="#" class="nav-link" data-tab="all-customers">
                    <i class="fas fa-list"></i>
                    <span>全顧客</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div class="content-scroll-area">
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
                            // 全顧客を表示（制限なし）
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
                                            onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
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
                                    onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                                    <i class="fas fa-eye"></i> 詳細
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

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
                                            onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                                            <i class="fas fa-eye"></i> 詳細
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- 顧客詳細モーダル -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">顧客詳細</h2>
                <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- ここに詳細コンテンツが挿入されます -->
            </div>
        </div>
    </div>

    <script>
    // 顧客データを格納（JavaScriptで使用）
    const customerData = <?php echo json_encode($customerList); ?>;

    // タブ切り替え機能
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.nav-link');
        const tabContents = document.querySelectorAll('.tab-content');

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = this.getAttribute('data-tab');

                // すべてのタブコンテンツを非表示
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // すべてのナビリンクからactiveクラスを削除
                navLinks.forEach(nav => {
                    nav.classList.remove('active');
                });

                // 選択されたタブを表示
                document.getElementById(targetTab).classList.add('active');
                this.classList.add('active');
            });
        });

        // 検索機能（既存の顧客一覧タブ）
        const searchInput = document.getElementById('customerSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', filterCustomersTable);
        }

        // 並び替え機能（既存の顧客一覧タブ）
        const sortHeaders = document.querySelectorAll('.sortable');
        sortHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const sortType = this.getAttribute('data-sort');
                sortTable(sortType);
            });
        });

        // 検索機能（全顧客タブ）
        const allCustomerSearchInput = document.getElementById('allCustomerSearchInput');
        if (allCustomerSearchInput) {
            allCustomerSearchInput.addEventListener('input', filterAllCustomers);
        }

        // 並び替え機能（全顧客タブ）
        const sortSelect = document.getElementById('sortBy');
        if (sortSelect) {
            sortSelect.addEventListener('change', sortAllCustomers);
        }
    });

    // 検索・並び替え機能は元のコードと同じ
    function filterCustomersTable() {
        const searchTerm = document.getElementById('customerSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#customerTableBody tr');

        rows.forEach(row => {
            const customerName = row.children[0].textContent.toLowerCase();
            if (customerName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function sortTable(sortType) {
        const tbody = document.getElementById('customerTableBody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const currentSortHeader = document.querySelector(`[data-sort="${sortType}"]`);
        const isAscending = !currentSortHeader.classList.contains('desc');

        document.querySelectorAll('.sortable').forEach(header => {
            header.classList.remove('asc', 'desc');
        });

        currentSortHeader.classList.add(isAscending ? 'asc' : 'desc');

        rows.sort((a, b) => {
            let aValue, bValue;

            switch (sortType) {
                case 'name':
                    aValue = a.children[0].getAttribute('data-sort-value');
                    bValue = b.children[0].getAttribute('data-sort-value');
                    return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
                case 'sales':
                    aValue = parseFloat(a.children[1].getAttribute('data-sort-value'));
                    bValue = parseFloat(b.children[1].getAttribute('data-sort-value'));
                    return isAscending ? aValue - bValue : bValue - aValue;
                case 'leadtime':
                    aValue = parseFloat(a.children[2].getAttribute('data-sort-value'));
                    bValue = parseFloat(b.children[2].getAttribute('data-sort-value'));
                    return isAscending ? aValue - bValue : bValue - aValue;
                case 'deliveries':
                    aValue = parseInt(a.children[3].getAttribute('data-sort-value'));
                    bValue = parseInt(b.children[3].getAttribute('data-sort-value'));
                    return isAscending ? aValue - bValue : bValue - aValue;
                default:
                    return 0;
            }
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    function filterAllCustomers() {
        const searchTerm = document.getElementById('allCustomerSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#allCustomersTableBody tr');

        rows.forEach(row => {
            const customerName = row.getAttribute('data-customer-name').toLowerCase();
            if (customerName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function sortAllCustomers() {
        const sortBy = document.getElementById('sortBy').value;
        const tbody = document.getElementById('allCustomersTableBody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aValue, bValue;

            switch (sortBy) {
                case 'name-asc':
                    aValue = a.getAttribute('data-customer-name');
                    bValue = b.getAttribute('data-customer-name');
                    return aValue.localeCompare(bValue);
                case 'name-desc':
                    aValue = a.getAttribute('data-customer-name');
                    bValue = b.getAttribute('data-customer-name');
                    return bValue.localeCompare(aValue);
                case 'sales-asc':
                    aValue = parseFloat(a.children[1].getAttribute('data-value'));
                    bValue = parseFloat(b.children[1].getAttribute('data-value'));
                    return aValue - bValue;
                case 'sales-desc':
                    aValue = parseFloat(a.children[1].getAttribute('data-value'));
                    bValue = parseFloat(b.children[1].getAttribute('data-value'));
                    return bValue - aValue;
                case 'deliveries-asc':
                    aValue = parseInt(a.children[3].getAttribute('data-value'));
                    bValue = parseInt(b.children[3].getAttribute('data-value'));
                    return aValue - bValue;
                case 'deliveries-desc':
                    aValue = parseInt(a.children[3].getAttribute('data-value'));
                    bValue = parseInt(b.children[3].getAttribute('data-value'));
                    return bValue - aValue;
                case 'leadtime-asc':
                    aValue = parseFloat(a.children[2].getAttribute('data-value'));
                    bValue = parseFloat(b.children[2].getAttribute('data-value'));
                    return aValue - bValue;
                case 'leadtime-desc':
                    aValue = parseFloat(a.children[2].getAttribute('data-value'));
                    bValue = parseFloat(b.children[2].getAttribute('data-value'));
                    return bValue - aValue;
                default:
                    return 0;
            }
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    function showDetails(customerName) {
        console.log('詳細表示:', customerName);
        // 詳細表示のロジックを実装
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    </script>
    <script src="../script.js"></script>
    <script src="statistics.js"></script>
</body>

</html>