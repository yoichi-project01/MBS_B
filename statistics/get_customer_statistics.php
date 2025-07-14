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

    $storeName = $_POST['store'] ?? $_GET['store'] ?? '';
    $searchTerm = $_POST['search'] ?? $_GET['search'] ?? '';
    $sortColumn = $_POST['sort'] ?? $_GET['sort'] ?? 'customer_name';
    $sortOrder = $_POST['order'] ?? $_GET['order'] ?? 'ASC';
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    $limit = min(50, max(10, (int)($_POST['limit'] ?? $_GET['limit'] ?? 20)));

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'customers' => [],
        'summary' => [
            'total_customers' => 0,
            'total_sales' => 0,
            'avg_lead_time' => 0,
            'total_deliveries' => 0,
            'active_customers' => 0
        ],
        'pagination' => [
            'current_page' => $page,
            'total_pages' => 0,
            'total_records' => 0,
            'per_page' => $limit
        ]
    ]
];

    $pdo = db_connect();
    
    // 店舗名のバリデーション
    if (empty($storeName)) {
        throw new Exception('店舗名が指定されていません');
    }
    
    $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
    if (!in_array($storeName, $allowedStores)) {
        throw new Exception('無効な店舗名です');
    }
    
    // ソート列のバリデーション
    $allowedSortColumns = ['customer_no', 'customer_name', 'total_sales', 'delivery_count', 'avg_lead_time', 'last_order_date', 'registration_date'];
    if (!in_array($sortColumn, $allowedSortColumns)) {
        $sortColumn = 'customer_name';
    }
    
    // ソート順のバリデーション
    $sortOrder = strtoupper($sortOrder);
    if (!in_array($sortOrder, ['ASC', 'DESC'])) {
        $sortOrder = 'ASC';
    }
    
    // 基本クエリ（customer_summaryビューを使用）
    $whereClause = "WHERE cs.store_name = :store_name";
    $params = ['store_name' => $storeName];
    
    // 検索条件の追加
    if (!empty($searchTerm)) {
        $whereClause .= " AND (cs.customer_name LIKE :search OR cs.customer_no LIKE :search_no)";
        $params['search'] = '%' . $searchTerm . '%';
        $params['search_no'] = '%' . $searchTerm . '%';
    }
    
    // 総件数の取得
    $countQuery = "SELECT COUNT(*) as total FROM customer_summary cs $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // ページネーション計算
    $totalPages = ceil($totalRecords / $limit);
    $offset = ($page - 1) * $limit;
    
    // メインクエリ
    $query = "
        SELECT 
            cs.customer_no,
            cs.customer_name,
            cs.store_name,
            cs.address,
            cs.telephone_number,
            cs.registration_date,
            COALESCE(cs.total_sales, 0) as total_sales,
            COALESCE(cs.delivery_count, 0) as delivery_count,
            COALESCE(cs.avg_lead_time, 0) as avg_lead_time,
            cs.last_order_date,
            cs.created_at,
            cs.updated_at,
            -- 最近の注文状況を取得
            (SELECT COUNT(*) FROM orders o WHERE o.customer_no = cs.customer_no AND o.registration_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) as recent_orders,
            -- 最近の注文総額を取得
            (SELECT COALESCE(SUM(oi.order_volume * oi.price), 0) 
             FROM orders o 
             JOIN order_items oi ON o.order_no = oi.order_no 
             WHERE o.customer_no = cs.customer_no 
             AND o.registration_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)) as recent_sales
        FROM customer_summary cs
        $whereClause
        ORDER BY cs.$sortColumn $sortOrder
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
    if (!empty($searchTerm)) {
        $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search_no', '%' . $searchTerm . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 店舗別統計情報の取得
    $summaryQuery = "
        SELECT 
            COUNT(*) as total_customers,
            COALESCE(SUM(cs.total_sales), 0) as total_sales,
            COALESCE(AVG(cs.avg_lead_time), 0) as avg_lead_time,
            COALESCE(SUM(cs.delivery_count), 0) as total_deliveries,
            COUNT(CASE WHEN cs.last_order_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH) THEN 1 END) as active_customers
        FROM customer_summary cs
        WHERE cs.store_name = :store_name
    ";
    
    $summaryStmt = $pdo->prepare($summaryQuery);
    $summaryStmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
    $summaryStmt->execute();
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);
    
    // データの整形
    $formattedCustomers = [];
    foreach ($customers as $customer) {
        $formattedCustomers[] = [
            'customer_no' => (int)$customer['customer_no'],
            'customer_name' => $customer['customer_name'],
            'store_name' => $customer['store_name'],
            'address' => $customer['address'],
            'telephone_number' => $customer['telephone_number'],
            'registration_date' => $customer['registration_date'],
            'total_sales' => (float)$customer['total_sales'],
            'delivery_count' => (int)$customer['delivery_count'],
            'avg_lead_time' => (float)$customer['avg_lead_time'],
            'last_order_date' => $customer['last_order_date'],
            'recent_orders' => (int)$customer['recent_orders'],
            'recent_sales' => (float)$customer['recent_sales'],
            'status' => $customer['recent_orders'] > 0 ? 'active' : 'inactive',
            'created_at' => $customer['created_at'],
            'updated_at' => $customer['updated_at']
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'データを正常に取得しました';
    $response['data']['customers'] = $formattedCustomers;
    $response['data']['summary'] = [
        'total_customers' => (int)$summary['total_customers'],
        'total_sales' => (float)$summary['total_sales'],
        'avg_lead_time' => (float)$summary['avg_lead_time'],
        'total_deliveries' => (int)$summary['total_deliveries'],
        'active_customers' => (int)$summary['active_customers']
    ];
    $response['data']['pagination'] = [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_records' => (int)$totalRecords,
        'per_page' => $limit
    ];
    
} catch (PDOException $e) {
    error_log("Database error in get_customer_statistics.php: " . $e->getMessage());
    $response['message'] = 'データベースエラーが発生しました';
    http_response_code(500);
} catch (Exception $e) {
    error_log("General error in get_customer_statistics.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (Throwable $e) {
    // 予期しないエラーをキャッチ
    error_log("Critical error in get_customer_statistics.php: " . $e->getMessage());
    $response['message'] = 'システムエラーが発生しました';
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

ob_end_flush();
?>