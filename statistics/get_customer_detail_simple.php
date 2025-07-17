<?php
// 簡単な顧客詳細APIテスト
header('Content-Type: application/json');

try {
    require_once(__DIR__ . '/../component/autoloader.php');
    require_once(__DIR__ . '/../component/db.php');

    // 認証チェック
    if (!SessionManager::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $customerNo = $_POST['customer_no'] ?? $_GET['customer_no'] ?? '';
    $storeName = $_POST['store'] ?? $_GET['store'] ?? '';

    $pdo = db_connect();
    
    $response = [
        'success' => false,
        'message' => '',
        'data' => []
    ];

    // 1. 顧客情報の基本チェック
    $customerQuery = "SELECT * FROM customers WHERE customer_no = ? LIMIT 1";
    $stmt = $pdo->prepare($customerQuery);
    $stmt->execute([$customerNo]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        $response['message'] = 'Customer not found';
        $response['data']['customer_no'] = $customerNo;
        $response['data']['customer_exists'] = false;
        
        // 顧客テーブルの総件数を確認
        $countQuery = "SELECT COUNT(*) as total FROM customers";
        $countStmt = $pdo->query($countQuery);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC);
        $response['data']['total_customers'] = $total['total'];
        
        // 最初の数件の顧客を取得
        $sampleQuery = "SELECT customer_no, customer_name FROM customers LIMIT 5";
        $sampleStmt = $pdo->query($sampleQuery);
        $response['data']['sample_customers'] = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $response['success'] = true;
        $response['message'] = 'Customer found';
        $response['data']['customer_info'] = $customer;
        $response['data']['customer_exists'] = true;
    }
    
    // 2. 注文テーブルの確認
    $orderQuery = "SELECT COUNT(*) as total FROM orders WHERE customer_no = ?";
    $orderStmt = $pdo->prepare($orderQuery);
    $orderStmt->execute([$customerNo]);
    $orderCount = $orderStmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['order_count'] = $orderCount['total'];
    
    // 3. 統計情報テーブルの確認
    $statsQuery = "SELECT COUNT(*) as total FROM statistics_information WHERE customer_no = ?";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute([$customerNo]);
    $statsCount = $statsStmt->fetch(PDO::FETCH_ASSOC);
    $response['data']['statistics_count'] = $statsCount['total'];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>