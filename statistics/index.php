<?php
// 最初にオートローダーを読み込み
require_once(__DIR__ . '/../component/autoloader.php');
// DB接続
require_once(__DIR__ . '/../component/db.php');

// その後にヘッダーを読み込み
include(__DIR__ . '/../component/header.php');

// セッション開始
SessionManager::start();

// 初期化
$totalCustomers = 0;
$monthlySales = 0;
$salesTrend = 0;
$totalDeliveries = 0;
$avgLeadTime = 0;
$customerList = [];

try {
    // 1. ダッシュボードのメトリクス
    // 総顧客数
    $totalCustomersStmt = $pdo->query("SELECT COUNT(*) FROM customers");
    $totalCustomers = $totalCustomersStmt->fetchColumn();

    // customer_summary ビューから基本統計を取得
    $dashboardQuery = "
        SELECT 
            SUM(total_sales) as total_sales, 
            SUM(delivery_count) as total_deliveries, 
            AVG(avg_lead_time) as avg_lead_time 
        FROM customer_summary
    ";
    $dashboardMetricsStmt = $pdo->query($dashboardQuery);
    $dashboardMetrics = $dashboardMetricsStmt->fetch(PDO::FETCH_ASSOC);

    $totalDeliveries = $dashboardMetrics['total_deliveries'] ?? 0;
    $avgLeadTime = $dashboardMetrics['avg_lead_time'] ?? 0;

    // 正確な月間売上と前月比を計算
    // 当月の売上
    $currentMonthQuery = "
        SELECT SUM(di.amount) 
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        WHERE YEAR(d.delivery_record) = YEAR(CURDATE()) 
          AND MONTH(d.delivery_record) = MONTH(CURDATE())
    ";
    $monthlySalesStmt = $pdo->query($currentMonthQuery);
    $monthlySales = $monthlySalesStmt->fetchColumn() ?? 0;

    // 前月の売上
    $previousMonthQuery = "
        SELECT SUM(di.amount) 
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        WHERE YEAR(d.delivery_record) = YEAR(CURDATE() - INTERVAL 1 MONTH) 
          AND MONTH(d.delivery_record) = MONTH(CURDATE() - INTERVAL 1 MONTH)
    ";
    $previousMonthSalesStmt = $pdo->query($previousMonthQuery);
    $previousMonthSales = $previousMonthSalesStmt->fetchColumn() ?? 0;

    // 前月比の計算
    if ($previousMonthSales > 0) {
        $salesTrend = (($monthlySales - $previousMonthSales) / $previousMonthSales) * 100;
    } elseif ($monthlySales > 0) {
        $salesTrend = 100; // 前月売上0で今月売上ありなら100%増
    } else {
        $salesTrend = 0;
    }

    // 2. 顧客一覧データ (customer_summary ビューを使用)
    $customerListQuery = "SELECT * FROM customer_summary ORDER BY total_sales DESC";
    $customersStmt = $pdo->query($customerListQuery);
    $customerList = $customersStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // 本番環境では、エラーメッセージをログに記録するなどの処理を推奨
    error_log("Statistics page database error: " . $e->getMessage());
    // ユーザーには汎用的なエラーメッセージを表示
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
    <link rel="stylesheet" href="statistics.css">
    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="statistics-page">
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-book-open"></i>
                <h3>受注管理</h3>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-link active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>ダッシュボード</span>
                </a>
                <a href="#" class="nav-link" data-tab="customers">
                    <i class="fas fa-users"></i>
                    <span>顧客一覧</span>
                </a>
                <a href="#" class="nav-link" data-tab="charts">
                    <i class="fas fa-chart-pie"></i>
                    <span>グラフ分析</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <button class="menu-toggle-btn" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="main-title">ダッシュボード</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="customerSearch" placeholder="顧客を検索...">
                    </div>
                    <button class="action-button" title="通知">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="action-button" title="設定">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </header>

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
                    <div class="chart-and-list-container">
                        <div class="main-chart-container">
                            <h4>売上トレンド</h4>
                            <canvas id="mainSalesChart"></canvas>
                        </div>
                        <div class="top-customers-container">
                            <h4>トップ顧客</h4>
                            <ul class="top-customers-list">
                                <?php
                                $topCustomers = array_slice($customerList, 0, 5);
                                foreach ($topCustomers as $customer) :
                                ?>
                                    <li>
                                        <span class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></span>
                                        <span class="customer-sales"><?php echo format_yen($customer['total_sales']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 顧客一覧タブ -->
                <div id="customers" class="tab-content">
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="table"><i class="fas fa-table"></i> テーブル表示</button>
                        <button class="view-btn" data-view="card"><i class="fas fa-id-card"></i> カード表示</button>
                    </div>

                    <div class="table-view-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th data-sort="name">顧客名 <i class="fas fa-sort"></i></th>
                                    <th data-sort="sales">売上 <i class="fas fa-sort"></i></th>
                                    <th data-sort="leadtime">リードタイム <i class="fas fa-sort"></i></th>
                                    <th data-sort="deliveries">配達回数 <i class="fas fa-sort"></i></th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerList as $customer) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                        <td class="text-right"><?php echo format_yen($customer['total_sales']); ?></td>
                                        <td class="text-center"><?php echo format_days($customer['avg_lead_time']); ?></td>
                                        <td class="text-center"><?php echo number_format($customer['delivery_count']); ?></td>
                                        <td class="text-center">
                                            <button class="table-action-btn" onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="table-action-btn" onclick="showGraph('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                                                <i class="fas fa-chart-line"></i>
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
                                    <h4 class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></h4>
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
                                    <button class="card-btn" onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">詳細</button>
                                    <button class="card-btn primary" onclick="showGraph('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">グラフ</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- グラフ分析タブ -->
                <div id="charts" class="tab-content">
                    <div class="chart-container-full">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- モーダル -->
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

    <div id="graphModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="graphTitle">売上推移</h2>
                <button class="close-modal" onclick="closeModal('graphModal')">&times;</button>
            </div>
            <div class="modal-body">
                <canvas id="modalChart"></canvas>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
    <script src="statistics.js"></script>
</body>

</html>