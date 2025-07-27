<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
require_once(__DIR__ . '/../component/search_section.php');
require_once(__DIR__ . '/../component/data_table.php');
require_once(__DIR__ . '/../component/pagination.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

// 店舗が選択されていない場合のエラーハンドリング
if (empty($storeName)) {
    $_SESSION['error_message'] = '店舗が選択されていません。店舗選択画面からアクセスしてください。';
    header('Location: /MBS_B/index.php');
    exit;
}

// データベースから納品書データを取得
$deliveries = [];
$error_message = '';

try {
    $pdo = db_connect();
    
    // 検索条件の取得
    $search_customer = $_GET['search_customer'] ?? '';
    $sort_column = $_GET['sort'] ?? 'delivery_record';
    $sort_order = ($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
    $page = max(1, intval($_GET['page'] ?? 1));
    $items_per_page = 10;
    $offset = ($page - 1) * $items_per_page;
    
    // 基本SQL
    $base_sql = "
        SELECT 
            d.delivery_no,
            d.delivery_record,
            d.total_amount,
            c.customer_name,
            c.customer_no,
            COUNT(di.delivery_item_no) as total_items,
            SUM(di.delivery_volume) as total_volume,
            d.created_at,
            d.updated_at
        FROM deliveries d
        INNER JOIN delivery_items di ON d.delivery_no = di.delivery_no
        INNER JOIN order_items oi ON di.order_item_no = oi.order_item_no
        INNER JOIN orders o ON oi.order_no = o.order_no
        INNER JOIN customers c ON o.customer_no = c.customer_no
        WHERE c.store_name = :store_name
    ";
    
    $params = [':store_name' => $storeName];
    
    // 検索条件の追加
    if (!empty($search_customer)) {
        $base_sql .= " AND c.customer_name LIKE :search_customer";
        $params[':search_customer'] = '%' . $search_customer . '%';
    }
    
    $base_sql .= " GROUP BY d.delivery_no, d.delivery_record, d.total_amount, c.customer_name, c.customer_no, d.created_at, d.updated_at";
    
    // 並び替え
    $valid_columns = ['delivery_record', 'customer_name', 'total_amount', 'delivery_no'];
    if (!in_array($sort_column, $valid_columns)) {
        $sort_column = 'delivery_record';
    }
    
    $base_sql .= " ORDER BY " . $sort_column . " " . $sort_order;
    
    // 件数取得
    $count_sql = "SELECT COUNT(DISTINCT d.delivery_no) as total FROM deliveries d
                  INNER JOIN delivery_items di ON d.delivery_no = di.delivery_no
                  INNER JOIN order_items oi ON di.order_item_no = oi.order_item_no
                  INNER JOIN orders o ON oi.order_no = o.order_no
                  INNER JOIN customers c ON o.customer_no = c.customer_no
                  WHERE c.store_name = :store_name";
    
    if (!empty($search_customer)) {
        $count_sql .= " AND c.customer_name LIKE :search_customer";
    }
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $items_per_page);
    
    // データ取得
    $data_sql = $base_sql . " LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($data_sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $raw_deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // データの整形
    foreach ($raw_deliveries as $row) {
        $deliveries[] = [
            'id' => $row['delivery_no'],
            'delivery_no' => $row['delivery_no'],
            'customer_name' => $row['customer_name'],
            'customer_no' => $row['customer_no'],
            'delivery_date' => $row['delivery_record'],
            'total_amount' => $row['total_amount'],
            'total_items' => $row['total_items'],
            'total_volume' => $row['total_volume'],
            'status' => 'completed', // 納品済みとして扱う
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
} catch (PDOException $e) {
    $error_message = "データベースエラー: " . $e->getMessage();
    error_log("Delivery list error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書一覧 - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($storeName); ?>の納品書一覧を表示します。納品書の検索、並び替え、詳細確認が可能です。">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/action_button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/status_badge.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table_actions.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/order.css">
    
    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="with-header delivery-list-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="delivery-list-container">
                    
                    <?php
                    // エラーメッセージの表示
                    if (!empty($error_message)) {
                        echo '<div class="alert alert-error">' . htmlspecialchars($error_message) . '</div>';
                    }
                    
                    // 検索セクションの設定
                    renderSearchSection([
                        'storeName' => $storeName,
                        'pageType' => 'delivery',
                        'icon' => 'fas fa-truck',
                        'title' => '納品書一覧',
                        'totalCount' => isset($total_count) ? $total_count : count($deliveries),
                        'itemName' => '納品書',
                        'searchValue' => $search_customer ?? '',
                        'createUrl' => "create.php?store=" . urlencode($storeName),
                        'createButtonText' => '新規納品書作成'
                    ]);
                    ?>

                    <?php
                    // テーブルの設定
                    renderDataTable([
                        'storeName' => $storeName,
                        'pageType' => 'delivery',
                        'columns' => getDeliveryColumns(),
                        'data' => $deliveries,
                        'sortParams' => [
                            'column' => $sort_column ?? 'delivery_record',
                            'order' => $sort_order ?? 'DESC',
                            'search' => $search_customer ?? ''
                        ],
                        'emptyMessage' => '該当する納品書はありません。',
                        'mobileMode' => 'customer-only'
                    ]);
                    ?>

                    <?php
                    // ページネーションの設定
                    renderPagination([
                        'storeName' => $storeName,
                        'currentPage' => $page ?? 1,
                        'totalPages' => $total_pages ?? 1,
                        'searchValue' => $search_customer ?? '',
                        'sortColumn' => $sort_column ?? 'delivery_record',
                        'sortOrder' => $sort_order ?? 'DESC',
                        'totalItems' => $total_count ?? count($deliveries),
                        'itemsPerPage' => $items_per_page ?? 10
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    <script nonce="<?= SessionManager::get('csp_nonce') ?>">
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Delivery list page DOMContentLoaded');
            document.querySelectorAll('.view-customer-detail-btn').forEach(button => {
                console.log('Found detail button:', button);
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default link behavior
                    const customerNo = this.dataset.customerNo;
                    const storeName = this.dataset.store;
                    console.log('Detail button clicked. customerNo:', customerNo, 'storeName:', storeName);
                    if (customerNo && storeName) {
                        window.location.href = `/MBS_B/statistics/customer_detail.php?customer_no=${encodeURIComponent(customerNo)}&store=${encodeURIComponent(storeName)}`;
                    } else {
                        console.error('Customer number or store name not found on detail button.');
                    }
                });
            });
        });
    </script>
</body>

</html>