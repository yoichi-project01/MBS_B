<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');

SessionManager::start();

header('Content-Type: application/json');

$storeName = $_GET['store'] ?? '';
$debugMode = ($_ENV['ENVIRONMENT'] ?? 'development') !== 'production';

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'totalCustomers' => 0,
        'monthlySales' => 0,
        'previousMonthSales' => 0,
        'salesTrend' => 0,
        'totalDeliveries' => 0,
        'avgLeadTime' => 0,
        'customerList' => []
    ]
];

if (empty($storeName)) {
    $response['message'] = '店舗名が指定されていません。';
    echo json_encode($response);
    exit;
}

try {
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
    $response['data']['totalCustomers'] = (int)$totalCustomersStmt->fetchColumn();

    if ($response['data']['totalCustomers'] === 0) {
        $response['message'] = '指定された店舗の顧客データが見つかりません。';
        echo json_encode($response);
        exit;
    }

    // 2. ダッシュボード基本メトリクスの取得 (customer_summaryビューを使用)
    $dashboardQuery = "
        SELECT 
            COALESCE(SUM(total_sales), 0) as total_sales, 
            COALESCE(SUM(delivery_count), 0) as total_deliveries, 
            COALESCE(AVG(avg_lead_time), 0) as avg_lead_time 
        FROM customer_summary
        WHERE store_name = :storeName
    ";
    $dashboardMetricsStmt = $pdo->prepare($dashboardQuery);
    $dashboardMetricsStmt->bindParam(':storeName', $storeName, PDO::PARAM_STR);
    $dashboardMetricsStmt->execute();
    $dashboardMetrics = $dashboardMetricsStmt->fetch(PDO::FETCH_ASSOC);

    $response['data']['totalDeliveries'] = (int)($dashboardMetrics['total_deliveries'] ?? 0);
    $response['data']['avgLeadTime'] = (float)($dashboardMetrics['avg_lead_time'] ?? 0);

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
    $response['data']['monthlySales'] = (float)$currentMonthSalesStmt->fetchColumn();

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
    $response['data']['previousMonthSales'] = (float)$previousMonthSalesStmt->fetchColumn();

    // 売上トレンドの計算
    if ($response['data']['previousMonthSales'] > 0) {
        $response['data']['salesTrend'] = (($response['data']['monthlySales'] - $response['data']['previousMonthSales']) / $response['data']['previousMonthSales']) * 100;
    } elseif ($response['data']['monthlySales'] > 0) {
        $response['data']['salesTrend'] = 100; // 先月がゼロで今月に売上がある場合
    } else {
        $response['data']['salesTrend'] = 0;
    }

    // 5. 顧客リストの取得（詳細統計情報付き）(customer_summaryビューを使用)
    $customerListQuery = "
        SELECT 
            *,
            -- 効率性の計算
            CASE 
                WHEN delivery_count > 0 THEN total_sales / delivery_count
                ELSE 0
            END as efficiency_score,
            -- 顧客ランクの計算
            CASE 
                WHEN total_sales > 500000 THEN 'VIP'
                WHEN total_sales > 100000 THEN 'Premium'
                ELSE 'Regular'
            END as customer_rank
        FROM customer_summary
        WHERE store_name = :storeName
        ORDER BY total_sales DESC, customer_name ASC
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

    $response['data']['customerList'] = $customerList;
    $response['success'] = true;

} catch (PDOException $e) {
    $response['message'] = 'データベースエラーが発生しました。';
    if ($debugMode) {
        $response['message'] .= ' 詳細: ' . $e->getMessage();
    }
    error_log("Statistics API database error: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'システムエラーが発生しました。';
    if ($debugMode) {
        $response['message'] .= ' 詳細: ' . $e->getMessage();
    }
    error_log("Statistics API error: " . $e->getMessage());
}

echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>