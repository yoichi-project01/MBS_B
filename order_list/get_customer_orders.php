<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');

header('Content-Type: application/json');

if (!SessionManager::isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$customerName = $_GET['customer_name'] ?? '';

if (empty($customerName)) {
    echo json_encode(['error' => 'Customer name is required']);
    exit;
}

try {
    // 顧客の注文情報を取得
    $stmt = $pdo->prepare("
        SELECT 
            o.order_no, 
            o.registration_date, 
            o.status,
            oi.item_name, 
            oi.order_volume, 
            oi.price
        FROM orders o
        JOIN customers c ON o.customer_no = c.customer_no
        JOIN order_items oi ON o.order_no = oi.order_no
        WHERE c.customer_name = :customer_name
        ORDER BY o.registration_date DESC, o.order_no DESC
    ");
    $stmt->bindParam(':customer_name', $customerName);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 注文を注文番号ごとにグループ化
    $groupedOrders = [];
    foreach ($orders as $order) {
        $orderNo = $order['order_no'];
        if (!isset($groupedOrders[$orderNo])) {
            $groupedOrders[$orderNo] = [
                'order_no' => $order['order_no'],
                'registration_date' => $order['registration_date'],
                'status' => $order['status'],
                'items' => []
            ];
        }
        $groupedOrders[$orderNo]['items'][] = [
            'item_name' => $order['item_name'],
            'order_volume' => $order['order_volume'],
            'price' => $order['price']
        ];
    }

    echo json_encode(['success' => true, 'orders' => array_values($groupedOrders)]);

} catch (PDOException $e) {
    error_log("Database error in get_customer_orders.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}

function translate_status($status) {
    switch ($status) {
        case 'pending': return '保留';
        case 'processing': return '処理中';
        case 'completed': return '完了';
        case 'cancelled': return 'キャンセル';
        default: return $status;
    }
}

?>