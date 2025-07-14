<?php
// デバッグ用のget_customer_statistics.php
// 出力バッファリング開始
ob_start();

// エラー表示を有効にしてデバッグ
error_reporting(E_ALL);
ini_set('display_errors', 0);  // 画面表示は無効、ログは有効
ini_set('log_errors', 1);

// 出力バッファをクリア
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // ステップ1: 基本情報の取得
    $storeName = $_POST['store'] ?? $_GET['store'] ?? '';
    $searchTerm = $_POST['search'] ?? $_GET['search'] ?? '';
    $sortColumn = $_POST['sort'] ?? $_GET['sort'] ?? 'customer_name';
    $sortOrder = $_POST['order'] ?? $_GET['order'] ?? 'ASC';
    $page = max(1, (int)($_POST['page'] ?? $_GET['page'] ?? 1));
    $limit = min(50, max(10, (int)($_POST['limit'] ?? $_GET['limit'] ?? 20)));

    $response = [
        'success' => true,
        'message' => 'パラメータ取得成功',
        'data' => [
            'parameters' => [
                'store' => $storeName,
                'search' => $searchTerm,
                'sort' => $sortColumn,
                'order' => $sortOrder,
                'page' => $page,
                'limit' => $limit
            ],
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

    // ステップ2: コンポーネントの読み込みテスト
    try {
        require_once(__DIR__ . '/../component/autoloader.php');
        $response['data']['component_autoloader'] = '正常';
    } catch (Exception $e) {
        $response['data']['component_autoloader'] = 'エラー: ' . $e->getMessage();
    }

    try {
        require_once(__DIR__ . '/../component/db.php');
        $response['data']['component_db'] = '正常';
    } catch (Exception $e) {
        $response['data']['component_db'] = 'エラー: ' . $e->getMessage();
    }

    // ステップ3: セッション開始テスト
    try {
        SessionManager::start();
        $response['data']['session_manager'] = '正常';
    } catch (Exception $e) {
        $response['data']['session_manager'] = 'エラー: ' . $e->getMessage();
    }

    // ステップ4: データベース接続テスト
    if (empty($storeName)) {
        $response['data']['database_test'] = 'スキップ（店舗名なし）';
    } else {
        try {
            $pdo = db_connect();
            $response['data']['database_connection'] = '正常';
            
            // テーブルの存在確認
            $tables = [];
            $tableQuery = "SHOW TABLES";
            $stmt = $pdo->query($tableQuery);
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $response['data']['tables'] = $tables;
            
            // ビューの存在確認
            $viewQuery = "SELECT COUNT(*) as count FROM information_schema.views WHERE table_name = 'customer_summary'";
            $stmt = $pdo->query($viewQuery);
            $viewResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $response['data']['customer_summary_view'] = $viewResult['count'] > 0 ? '存在' : '不存在';
            
            // 簡単なクエリテスト
            $testQuery = "SELECT COUNT(*) as count FROM customers WHERE store_name = :store_name";
            $stmt = $pdo->prepare($testQuery);
            $stmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
            $stmt->execute();
            $testResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $response['data']['database_test'] = '正常';
            $response['data']['test_query_result'] = $testResult;
            
            // 店舗一覧の取得
            $storeQuery = "SELECT DISTINCT store_name FROM customers";
            $stmt = $pdo->query($storeQuery);
            $stores = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stores[] = $row['store_name'];
            }
            $response['data']['available_stores'] = $stores;
            
        } catch (Exception $e) {
            $response['data']['database_test'] = 'エラー: ' . $e->getMessage();
        }
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // エラー時も出力バッファをクリア
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    ob_end_flush();
}
?>