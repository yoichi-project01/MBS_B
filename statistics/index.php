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
    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="with-header statistics-tab-page">
    <!-- タブナビゲーション -->
    <div class="tab-navigation">
        <div class="tab-container">
            <button class="tab-btn active" data-tab="dashboard">
                <span class="tab-icon">📊</span>
                <span>ダッシュボード</span>
            </button>
            <button class="tab-btn" data-tab="customers">
                <span class="tab-icon">👥</span>
                <span>顧客一覧</span>
            </button>
            <button class="tab-btn" data-tab="charts">
                <span class="tab-icon">📈</span>
                <span>グラフ分析</span>
            </button>
            <button class="tab-btn" data-tab="export">
                <span class="tab-icon">📁</span>
                <span>データ出力</span>
            </button>
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="main-content">
        <!-- ダッシュボードタブ -->
        <div id="dashboard" class="tab-content active">
            <div class="dashboard-overview">
                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">👥</div>
                        <div class="metric-title">総顧客数</div>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalCustomers); ?></div>
                    <div class="metric-trend trend-up">
                        <span>↗</span>
                        <span>+12% 前月比</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">💰</div>
                        <div class="metric-title">月間売上 (推定)</div>
                    </div>
                    <div class="metric-value"><?php echo format_yen($monthlySales); ?></div>
                    <div class="metric-trend trend-up">
                        <span>↗</span>
                        <span>+8% 前月比</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">🚚</div>
                        <div class="metric-title">総配達回数</div>
                    </div>
                    <div class="metric-value"><?php echo number_format($totalDeliveries); ?></div>
                    <div class="metric-trend trend-down">
                        <span>↘</span>
                        <span>-3% 前月比</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">⏱️</div>
                        <div class="metric-title">平均リードタイム</div>
                    </div>
                    <div class="metric-value"><?php echo format_days($avgLeadTime); ?></div>
                    <div class="metric-trend trend-up">
                        <span>↗</span>
                        <span>改善中</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 顧客一覧タブ -->
        <div id="customers" class="tab-content">
            <div class="customer-search">
                <div class="search-header">
                    <h2 class="search-title">
                        <span>🔍</span>
                        顧客検索
                    </h2>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="table">
                            <span>📋</span>
                            テーブル
                        </button>
                        <button class="view-btn" data-view="card">
                            <span>📱</span>
                            カード
                        </button>
                    </div>
                </div>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" placeholder="顧客名で検索...">
                </div>
            </div>

            <!-- テーブルビュー -->
            <div class="table-view">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>顧客名 <button class="sort-btn" data-column="name">▲▼</button></th>
                            <th>売上（円） <button class="sort-btn" data-column="sales">▲▼</button></th>
                            <th>リードタイム <button class="sort-btn" data-column="leadtime">▲▼</button></th>
                            <th>配達回数 <button class="sort-btn" data-column="delivery">▲▼</button></th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerList as $customer) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['customer_name']); ?></td>
                                <td><?php echo format_yen($customer['total_sales']); ?></td>
                                <td><?php echo format_days($customer['avg_lead_time']); ?></td>
                                <td><?php echo number_format($customer['delivery_count']); ?></td>
                                <td>
                                    <button class="action-btn" onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">
                                        詳細
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- カードビュー -->
            <div class="card-view" style="display: none;">
                <?php foreach ($customerList as $customer) : ?>
                    <div class="customer-card">
                        <div class="card-header">
                            <div>
                                <div class="customer-name"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                                <div class="customer-id">ID: <?php echo htmlspecialchars($customer['customer_no']); ?></div>
                            </div>
                        </div>
                        <div class="card-stats">
                            <div class="card-stat">
                                <div class="card-stat-label">売上</div>
                                <div class="card-stat-value"><?php echo format_yen($customer['total_sales']); ?></div>
                            </div>
                            <div class="card-stat">
                                <div class="card-stat-label">配達回数</div>
                                <div class="card-stat-value"><?php echo number_format($customer['delivery_count']); ?></div>
                            </div>
                            <div class="card-stat">
                                <div class="card-stat-label">リードタイム</div>
                                <div class="card-stat-value"><?php echo format_days($customer['avg_lead_time']); ?></div>
                            </div>
                            <div class="card-stat">
                                <div class="card-stat-label">最終注文</div>
                                <div class="card-stat-value"><?php echo htmlspecialchars($customer['last_order_date'] ? (new DateTime($customer['last_order_date']))->format('Y-m-d') : 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="card-actions">
                            <button class="card-btn secondary" onclick="showDetails('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">詳細</button>
                            <button class="card-btn primary" onclick="showGraph('<?php echo htmlspecialchars(addslashes($customer['customer_name'])); ?>')">グラフ</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- グラフ分析タブ -->
        <div id="charts" class="tab-content">
            <div class="chart-selector">
                <h2 class="search-title">
                    <span>📊</span>
                    分析グラフを選択
                </h2>
                <div class="chart-options">
                    <div class="chart-option active" data-chart="sales">
                        <span class="chart-option-icon">💰</span>
                        <div class="chart-option-title">売上分析</div>
                    </div>
                    <div class="chart-option" data-chart="delivery">
                        <span class="chart-option-icon">🚚</span>
                        <div class="chart-option-title">配達実績</div>
                    </div>
                    <div class="chart-option" data-chart="leadtime">
                        <span class="chart-option-icon">⏱️</span>
                        <div class="chart-option-title">リードタイム</div>
                    </div>
                    <div class="chart-option" data-chart="trend">
                        <span class="chart-option-icon">📈</span>
                        <div class="chart-option-title">トレンド分析</div>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <div style="text-align: center;">
                    <span style="font-size: 48px; display: block; margin-bottom: 16px;">📊</span>
                    <h3 style="color: var(--main-green); margin-bottom: 8px;">グラフを選択してください</h3>
                    <p>上記のオプションから表示したいグラフを選択してください</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 詳細モーダル -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="detailTitle">顧客詳細情報</h2>
                <button class="close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div id="detailContent">
                <!-- 詳細情報がここに表示されます -->
            </div>
        </div>
    </div>

    <!-- グラフモーダル -->
    <div id="graphModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="graphTitle">売上推移グラフ</h2>
                <button class="close" onclick="closeModal('graphModal')">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="modalCanvas"></canvas>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>

</html>