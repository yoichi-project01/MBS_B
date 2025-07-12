<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

// 店舗が選択されていない場合のエラーハンドリング
if (empty($storeName)) {
    $_SESSION['error_message'] = '店舗が選択されていません。店舗選択画面からアクセスしてください。';
    header('Location: /MBS_B/index.php');
    exit;
}

// デバッグモードの設定
$debugMode = ($_ENV['ENVIRONMENT'] ?? 'development') !== 'production';

// 統計データの初期化
$totalCustomers = 0;
$monthlySales = 0;
$previousMonthSales = 0;
$salesTrend = 0;
$totalDeliveries = 0;
$avgLeadTime = 0;
$customerList = [];
$errorMessage = '';

try {
    // データベース接続の確認
    if (!checkDatabaseHealth($pdo)) {
        throw new Exception('データベース接続に問題があります。');
    }

    // 1. 総顧客数の取得
    $totalCustomersStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customers 
        WHERE store_name = :storeName
    ");
    $totalCustomersStmt->bindParam(':storeName', $storeName, PDO::PARAM_STR);
    $totalCustomersStmt->execute();
    $totalCustomers = (int)$totalCustomersStmt->fetchColumn();

    if ($totalCustomers === 0) {
        $errorMessage = '指定された店舗の顧客データが見つかりません。';
    } else {
        // 2. ダッシュボード基本メトリクスの取得
        $dashboardQuery = "
            SELECT 
                COALESCE(SUM(s.sales_by_customer), 0) as total_sales, 
                COALESCE(SUM(s.delivery_amount), 0) as total_deliveries, 
                COALESCE(AVG(s.lead_time), 0) as avg_lead_time 
            FROM customers c
            LEFT JOIN statistics_information s ON c.customer_no = s.customer_no
            WHERE c.store_name = :storeName
        ";
        $dashboardMetricsStmt = $pdo->prepare($dashboardQuery);
        $dashboardMetricsStmt->bindParam(':storeName', $storeName, PDO::PARAM_STR);
        $dashboardMetricsStmt->execute();
        $dashboardMetrics = $dashboardMetricsStmt->fetch(PDO::FETCH_ASSOC);

        $totalDeliveries = (int)($dashboardMetrics['total_deliveries'] ?? 0);
        $avgLeadTime = (float)($dashboardMetrics['avg_lead_time'] ?? 0);

        // 3. 今月の売上データ
        $currentMonthQuery = "
            SELECT COALESCE(SUM(di.amount), 0) as monthly_sales
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
        $currentMonthSalesStmt->bindParam(':storeName', $storeName, PDO::PARAM_STR);
        $currentMonthSalesStmt->execute();
        $monthlySales = (float)$currentMonthSalesStmt->fetchColumn();

        // 4. 先月の売上データ（トレンド計算用）
        $previousMonthQuery = "
            SELECT COALESCE(SUM(di.amount), 0) as previous_monthly_sales
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
        $previousMonthSalesStmt->bindParam(':storeName', $storeName, PDO::PARAM_STR);
        $previousMonthSalesStmt->execute();
        $previousMonthSales = (float)$previousMonthSalesStmt->fetchColumn();

        // 売上トレンドの計算
        if ($previousMonthSales > 0) {
            $salesTrend = (($monthlySales - $previousMonthSales) / $previousMonthSales) * 100;
        } elseif ($monthlySales > 0) {
            $salesTrend = 100; // 先月がゼロで今月に売上がある場合
        } else {
            $salesTrend = 0;
        }

        // 5. 顧客リストの取得（詳細統計情報付き）
        $customerListQuery = "
            SELECT 
                c.customer_no,
                c.customer_name,
                c.store_name,
                c.address,
                c.telephone_number,
                c.registration_date,
                COALESCE(s.sales_by_customer, 0) as total_sales,
                COALESCE(s.delivery_amount, 0) as delivery_count,
                COALESCE(s.lead_time, 0) as avg_lead_time,
                s.last_order_date,
                c.created_at,
                c.updated_at,
                -- 効率性の計算
                CASE 
                    WHEN s.delivery_amount > 0 THEN s.sales_by_customer / s.delivery_amount
                    ELSE 0
                END as efficiency_score,
                -- 顧客ランクの計算
                CASE 
                    WHEN s.sales_by_customer > 500000 THEN 'VIP'
                    WHEN s.sales_by_customer > 100000 THEN 'Premium'
                    ELSE 'Regular'
                END as customer_rank
            FROM customers c
            LEFT JOIN statistics_information s ON c.customer_no = s.customer_no
            WHERE c.store_name = :storeName
            ORDER BY s.sales_by_customer DESC, c.customer_name ASC
        ";

        $customersStmt = $pdo->prepare($customerListQuery);
        $customersStmt->bindParam(':storeName', $storeName, PDO::PARAM_STR);
        $customersStmt->execute();
        $customerList = $customersStmt->fetchAll(PDO::FETCH_ASSOC);

        // データの後処理
        foreach ($customerList as &$customer) {
            // NULL値の処理
            $customer['total_sales'] = (float)($customer['total_sales'] ?? 0);
            $customer['delivery_count'] = (int)($customer['delivery_count'] ?? 0);
            $customer['avg_lead_time'] = (float)($customer['avg_lead_time'] ?? 0);
            $customer['efficiency_score'] = (float)($customer['efficiency_score'] ?? 0);

            // 日付フォーマットの調整
            if ($customer['last_order_date']) {
                $customer['last_order_date_formatted'] = date('Y年m月d日', strtotime($customer['last_order_date']));
            } else {
                $customer['last_order_date_formatted'] = '取引実績なし';
            }

            if ($customer['registration_date']) {
                $customer['registration_date_formatted'] = date('Y年m月d日', strtotime($customer['registration_date']));
            }
        }
        unset($customer); // 参照を解除
    }
} catch (PDOException $e) {
    $errorMessage = 'データベースエラーが発生しました。';
    if ($debugMode) {
        $errorMessage .= ' 詳細: ' . $e->getMessage();
    }
    error_log("Statistics page database error: " . $e->getMessage());
} catch (Exception $e) {
    $errorMessage = 'システムエラーが発生しました。';
    if ($debugMode) {
        $errorMessage .= ' 詳細: ' . $e->getMessage();
    }
    error_log("Statistics page error: " . $e->getMessage());
}

// エラーハンドリング：データが取得できない場合
if (!empty($errorMessage)) {
    // エラー表示用のダミーデータ
    $totalCustomers = 0;
    $monthlySales = 0;
    $totalDeliveries = 0;
    $avgLeadTime = 0;
    $customerList = [];
}

// ヘルパー関数群
function format_yen($amount)
{
    if (!is_numeric($amount) || $amount <= 0) {
        return '¥0';
    }

    if ($amount >= 1000000) {
        return '¥' . number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000) {
        return '¥' . number_format($amount / 1000, 0) . 'K';
    }
    return '¥' . number_format($amount);
}

function format_yen_full($amount)
{
    if (!is_numeric($amount)) {
        return '¥0';
    }
    return '¥' . number_format($amount);
}

function format_days($days)
{
    if (!is_numeric($days) || $days <= 0) {
        return '0日';
    }
    return number_format($days, 1) . '日';
}

function format_percentage($value, $decimals = 1)
{
    if (!is_numeric($value)) {
        return '0%';
    }
    return number_format($value, $decimals) . '%';
}

function get_efficiency_class($efficiency)
{
    if ($efficiency > 5000) return 'high';
    if ($efficiency > 2000) return 'medium';
    return 'low';
}

function get_rank_class($index)
{
    if ($index < 3) return 'top-rank';
    if ($index < 10) return 'high-rank';
    return 'normal-rank';
}

function get_leadtime_status($leadtime)
{
    if ($leadtime <= 2) return 'excellent';
    if ($leadtime <= 4) return 'good';
    return 'needs-improvement';
}

// セキュリティ関連の設定
$csrfToken = CSRFProtection::getToken();

// メタデータの設定
$pageTitle = "統計情報 - {$storeName}";
$pageDescription = "{$storeName}の売上統計、顧客分析、配達実績などの詳細な統計情報を表示します。";
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">

    <!-- CSP Meta Tag -->
    <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;">

    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/statistics.css">

    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <!-- Preload key resources -->
    <link rel="preload" href="/MBS_B/assets/js/main.js" as="script">
    <link rel="preload" href="/MBS_B/assets/js/pages/statistics.js" as="script">
</head>

<body class="with-header statistics-page">
    <!-- エラーメッセージの表示 -->
    <?php if (!empty($errorMessage)): ?>
    <div class="error-banner" role="alert" aria-live="assertive">
        <div class="error-content">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($errorMessage); ?></span>
            <button class="error-close" onclick="this.parentElement.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics content starts here -->
    <?php if (empty($errorMessage) && !empty($customerList)): ?>

    <!-- ダッシュボードタブ -->
    <?php include 'dashboard_content.php'; ?>

    <!-- 顧客一覧タブ -->
    <?php include 'customer_list_content.php'; ?>

    <!-- 全顧客タブ -->
    <?php include 'all_customers_content.php'; ?>

    <?php else: ?>
    <!-- エラー状態またはデータなしの場合 -->
    <div class="empty-state-main">
        <div class="empty-icon">
            <i class="fas fa-chart-bar"></i>
        </div>
        <h2>データが利用できません</h2>
        <p>
            <?php if (empty($customerList)): ?>
            現在、この店舗にはデータが登録されていません。<br>
            顧客情報を登録してから再度アクセスしてください。
            <?php else: ?>
            データの読み込み中にエラーが発生しました。<br>
            しばらく時間をおいてから再度お試しください。
            <?php endif; ?>
        </p>
        <div class="empty-actions">
            <a href="/MBS_B/customer_information/index.php?store=<?php echo urlencode($storeName); ?>"
                class="btn btn-primary">
                <i class="fas fa-plus"></i>
                顧客情報を登録
            </a>
            <button onclick="location.reload()" class="btn btn-secondary">
                <i class="fas fa-refresh"></i>
                ページを再読み込み
            </button>
        </div>
    </div>
    <?php endif; ?>

    </div>
    </main>
    </div>

    <!-- 詳細モーダル -->
    <div id="detailModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="detailTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">顧客詳細</h2>
                <button class="close-modal" onclick="closeModal('detailModal')" aria-label="モーダルを閉じる">
                    &times;
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="loading-placeholder">
                    <div class="loading-spinner"></div>
                    <span>読み込み中...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- エクスポート用の隠しフォーム -->
    <form id="exportForm" method="POST" action="export.php" style="display: none;">
        <?php echo CSRFProtection::getTokenField(); ?>
        <input type="hidden" name="store" value="<?php echo htmlspecialchars($storeName); ?>">
        <input type="hidden" name="export_type" value="statistics">
        <input type="hidden" name="selected_customers" id="selectedCustomers">
        <input type="hidden" name="export_format" id="exportFormat" value="csv">
    </form>

    <!-- 印刷用スタイル -->
    <style media="print">
    .site-header,
    .top-nav,
    .modal,
    .action-buttons,
    .pagination-controls,
    .bulk-actions,
    .quick-actions {
        display: none !important;
    }

    .content-scroll-area {
        padding: 0 !important;
    }

    .data-table {
        font-size: 12px;
    }

    .data-table th,
    .data-table td {
        padding: 8px 4px;
    }

    body {
        background: white !important;
    }

    .customer-card,
    .metric-card {
        break-inside: avoid;
    }
    </style>

    <!-- JavaScript Data -->
    <script>
    // グローバルデータの設定
    window.statisticsData = {
        storeName: <?php echo json_encode($storeName); ?>,
        totalCustomers: <?php echo (int)$totalCustomers; ?>,
        monthlySales: <?php echo (float)$monthlySales; ?>,
        totalDeliveries: <?php echo (int)$totalDeliveries; ?>,
        avgLeadTime: <?php echo (float)$avgLeadTime; ?>,
        salesTrend: <?php echo (float)$salesTrend; ?>,
        customerList: <?php echo json_encode($customerList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        csrfToken: <?php echo json_encode($csrfToken); ?>,
        debugMode: <?php echo json_encode($debugMode); ?>
    };

    // 下位互換性のため
    window.customerData = window.statisticsData.customerList;

    // エラーハンドリング
    window.addEventListener('error', function(e) {
        if (window.statisticsData.debugMode) {
            console.error('Statistics page error:', e.error);
        }
    });

    // パフォーマンス測定
    if (window.performance && window.performance.mark) {
        window.performance.mark('statistics-data-loaded');
    }
    </script>

    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>

    <!-- 個別の機能モジュール -->
    <script type="module">
    // 統計ページ固有の初期化
    import('/MBS_B/assets/js/pages/statistics.js').then(module => {
        // 統計ページが正常に読み込まれた後の処理
        if (window.performance && window.performance.mark) {
            window.performance.mark('statistics-js-loaded');
        }
    }).catch(error => {
        console.error('Failed to load statistics module:', error);

        // フォールバック処理
        if (typeof window.showErrorMessage === 'function') {
            window.showErrorMessage('統計ページの初期化に失敗しました。ページを再読み込みしてください。');
        }
    });
    </script>

    <!-- 分析・トラッキング（本番環境のみ） -->
    <?php if (($_ENV['ENVIRONMENT'] ?? 'development') === 'production'): ?>
    <script>
    // Google Analytics や他の分析ツールをここに追加
    // ユーザーのプライバシーを尊重し、必要最小限のデータのみ収集
    </script>
    <?php endif; ?>

</body>

</html>

<style>
/* エラーバナーのスタイル */
.error-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    padding: 15px 20px;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.error-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.error-close {
    margin-left: auto;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.error-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* 空の状態のスタイル */
.empty-state-main {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    border-radius: 20px;
    margin: 40px auto;
    max-width: 600px;
    box-shadow: 0 12px 40px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.empty-state-main .empty-icon {
    font-size: 80px;
    color: var(--sub-green);
    margin-bottom: 30px;
    opacity: 0.6;
}

.empty-state-main h2 {
    font-size: 32px;
    color: var(--main-green);
    margin-bottom: 15px;
    font-weight: 800;
}

.empty-state-main p {
    color: var(--sub-green);
    margin-bottom: 30px;
    line-height: 1.6;
    font-size: 16px;
}

.empty-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn.btn-primary {
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    box-shadow: 0 4px 12px rgba(126, 217, 87, 0.3);
}

.btn.btn-secondary {
    background: rgba(126, 217, 87, 0.1);
    color: var(--main-green);
    border: 1px solid rgba(126, 217, 87, 0.3);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(126, 217, 87, 0.4);
}

/* パフォーマンス最適化 */
.customer-overview-card,
.metric-card,
.top-customer-card {
    will-change: transform;
}

/* ローディングスピナーのスタイル */
.loading-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px;
    color: var(--sub-green);
}

.loading-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid rgba(126, 217, 87, 0.3);
    border-top: 3px solid var(--accent-green);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* レスポンシブ調整 */
@media (max-width: 768px) {
    .error-banner {
        position: relative;
        margin-bottom: 20px;
    }

    .empty-state-main {
        margin: 20px 10px;
        padding: 40px 20px;
    }

    .empty-state-main .empty-icon {
        font-size: 60px;
    }

    .empty-state-main h2 {
        font-size: 24px;
    }

    .empty-actions {
        flex-direction: column;
        align-items: center;
    }

    .btn {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
}

/* アクセシビリティ改善 */
@media (prefers-reduced-motion: reduce) {
    .loading-spinner {
        animation: none;
    }

    .metric-card,
    .customer-overview-card,
    .btn {
        transition: none;
    }
}

/* 高コントラストモード対応 */
@media (prefers-contrast: high) {
    .error-banner {
        border: 3px solid white;
    }

    .empty-state-main {
        border: 2px solid var(--main-green);
    }

    .btn {
        border: 2px solid currentColor;
    }
}
</style>