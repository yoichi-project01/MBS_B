<?php
// 出力バッファリングを開始
ob_start();

// エラー出力を無効化
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once(__DIR__ . '/../component/autoloader.php');
    require_once(__DIR__ . '/../component/db.php');

    SessionManager::start();

    // 出力バッファをクリア
    ob_clean();

    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// CSRFトークンの検証を一時的に無効化（デバッグ用）
/*
if (!CSRFProtection::validateToken()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}
*/

$customerNo = $_POST['customer_no'] ?? $_GET['customer_no'] ?? '';
$storeName = $_POST['store'] ?? $_GET['store'] ?? '';

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'customer_info' => null,
        'order_history' => [],
        'delivery_history' => [],
        'statistics' => null
    ]
];

try {
    $pdo = db_connect();
    
    // パラメータのバリデーション
    if (empty($customerNo) || !is_numeric($customerNo)) {
        throw new Exception('顧客番号が無効です');
    }
    
    if (empty($storeName)) {
        throw new Exception('店舗名が指定されていません');
    }
    
    $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
    if (!in_array($storeName, $allowedStores)) {
        throw new Exception('無効な店舗名です');
    }
    
    // 顧客基本情報の取得
    $customerQuery = "
        SELECT 
            c.customer_no,
            c.customer_name,
            c.manager_name,
            c.store_name,
            c.address,
            c.telephone_number,
            c.delivery_conditions,
            c.registration_date,
            c.remarks,
            c.created_at,
            c.updated_at
        FROM customers c
        WHERE c.customer_no = :customer_no
    ";
    
    $customerStmt = $pdo->prepare($customerQuery);
    $customerStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $customerStmt->execute();
    $customerInfo = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customerInfo) {
        throw new Exception('指定された顧客が見つかりません');
    }
    
    // 注文履歴の取得
    $orderQuery = "
        SELECT 
            o.order_no,
            o.registration_date,
            o.status,
            o.created_at,
            GROUP_CONCAT(
                CONCAT(oi.books, ' (', oi.order_volume, '冊 × ¥', FORMAT(oi.price, 0), ')')
                SEPARATOR ', '
            ) as order_items,
            SUM(oi.order_volume * oi.price) as total_amount,
            COUNT(oi.order_item_no) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_no = oi.order_no
        WHERE o.customer_no = :customer_no
        GROUP BY o.order_no
        ORDER BY o.registration_date DESC, o.order_no DESC
        LIMIT 20
    ";
    
    $orderStmt = $pdo->prepare($orderQuery);
    $orderStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $orderStmt->execute();
    $orderHistory = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 配達履歴の取得
    $deliveryQuery = "
        SELECT 
            d.delivery_no,
            d.delivery_record,
            d.total_amount,
            d.created_at,
            GROUP_CONCAT(
                CONCAT(oi.books, ' (', di.delivery_volume, '冊)')
                SEPARATOR ', '
            ) as delivered_items,
            COUNT(di.delivery_item_no) as item_count
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        JOIN order_items oi ON di.order_item_no = oi.order_item_no
        JOIN orders o ON oi.order_no = o.order_no
        WHERE o.customer_no = :customer_no
        GROUP BY d.delivery_no
        ORDER BY d.delivery_record DESC, d.delivery_no DESC
        LIMIT 20
    ";
    
    $deliveryStmt = $pdo->prepare($deliveryQuery);
    $deliveryStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $deliveryStmt->execute();
    $deliveryHistory = $deliveryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 統計情報の取得（簡略化）
    $statsQuery = "
        SELECT 
            s.sales_by_customer,
            s.lead_time,
            s.delivery_amount,
            s.last_order_date,
            s.created_at,
            s.updated_at
        FROM statistics_information s
        WHERE s.customer_no = :customer_no
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $statsStmt->execute();
    $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // 追加の統計情報を別途取得
    $totalOrdersQuery = "SELECT COUNT(*) as total_orders FROM orders WHERE customer_no = :customer_no";
    $totalOrdersStmt = $pdo->prepare($totalOrdersQuery);
    $totalOrdersStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $totalOrdersStmt->execute();
    $orderStats = $totalOrdersStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statistics && $orderStats) {
        $statistics['total_orders'] = $orderStats['total_orders'];
        $statistics['orders_this_month'] = 0; // 暫定値
    }
    
    // データの整形
    $formattedOrderHistory = [];
    foreach ($orderHistory as $order) {
        $formattedOrderHistory[] = [
            'order_no' => (int)$order['order_no'],
            'registration_date' => $order['registration_date'],
            'status' => $order['status'],
            'status_label' => translateStatus($order['status']),
            'order_items' => $order['order_items'],
            'total_amount' => (float)$order['total_amount'],
            'item_count' => (int)$order['item_count'],
            'created_at' => $order['created_at']
        ];
    }
    
    $formattedDeliveryHistory = [];
    foreach ($deliveryHistory as $delivery) {
        $formattedDeliveryHistory[] = [
            'delivery_no' => (int)$delivery['delivery_no'],
            'delivery_record' => $delivery['delivery_record'],
            'total_amount' => (float)$delivery['total_amount'],
            'delivered_items' => $delivery['delivered_items'],
            'item_count' => (int)$delivery['item_count'],
            'created_at' => $delivery['created_at']
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'データを正常に取得しました';
    $response['data']['customer_info'] = $customerInfo;
    $response['data']['order_history'] = $formattedOrderHistory;
    $response['data']['delivery_history'] = $formattedDeliveryHistory;
    $response['data']['statistics'] = $statistics ? [
        'sales_by_customer' => (float)$statistics['sales_by_customer'],
        'lead_time' => (float)$statistics['lead_time'],
        'delivery_amount' => (int)$statistics['delivery_amount'],
        'last_order_date' => $statistics['last_order_date'],
        'total_orders' => (int)$statistics['total_orders'],
        'orders_this_month' => (int)$statistics['orders_this_month']
    ] : null;
    
} catch (PDOException $e) {
    error_log("Database error in get_customer_detail.php: " . $e->getMessage());
    $response['message'] = 'データベースエラーが発生しました';
    http_response_code(500);
} catch (Exception $e) {
    error_log("General error in get_customer_detail.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

// ステータス翻訳関数
function translateStatus($status) {
    switch ($status) {
        case 'pending': return '保留';
        case 'processing': return '処理中';
        case 'completed': return '完了';
        case 'cancelled': return 'キャンセル';
        default: return $status;
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    // 予期しないエラーをキャッチ
    ob_clean();
    error_log("Critical error in get_customer_detail.php: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'システムエラーが発生しました'
    ], JSON_UNESCAPED_UNICODE);
} finally {
    ob_end_flush();
}
?>