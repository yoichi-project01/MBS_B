<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
require_once(__DIR__ . '/../component/search_section.php');
require_once(__DIR__ . '/../component/data_table.php');
require_once(__DIR__ . '/../component/pagination.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();

$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

// ページネーションの設定
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // 1ページあたりの表示件数
$offset = ($page - 1) * $limit;

// 検索条件
$search_customer = $_GET['search_customer'] ?? '';

// ソート条件
$sort_column = $_GET['sort'] ?? 'registration_date';
$sort_order = $_GET['order'] ?? 'DESC';

// パラメータのバリデーション
$page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$allowed_sort_columns = ['order_no', 'customer_name', 'registration_date', 'total_amount', 'status'];
if (!in_array($sort_column, $allowed_sort_columns)) {
    $sort_column = 'registration_date';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

try {
    // 基本クエリ
    $sql = "
        SELECT 
            o.order_no, 
            c.customer_name, 
            o.registration_date, 
            o.status,
            (SELECT SUM(oi.order_volume * oi.price) FROM order_items oi WHERE oi.order_no = o.order_no) as total_amount
        FROM orders o
        JOIN customers c ON o.customer_no = c.customer_no
    ";

    // 条件句
    $where_clauses = [];
    $params = [];

    if (!empty($storeName)) {
        $where_clauses[] = "c.store_name = :store_name";
        $params[':store_name'] = $storeName;
    }

    if (!empty($search_customer)) {
        $where_clauses[] = "c.customer_name LIKE :search_customer";
        $params[':search_customer'] = '%' . $search_customer . '%';
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // 総件数を取得
    $count_sql = str_replace("SELECT ... FROM", "SELECT COUNT(*) FROM", $sql);
    $total_stmt = $pdo->prepare($sql);
    $total_stmt->execute($params);
    $total_orders = $total_stmt->rowCount();
    $total_pages = ceil($total_orders / $limit);

    // ソートとページネーションを追加
    $sql .= " ORDER BY $sort_column $sort_order LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}

// ステータスの日本語変換
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

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文書一覧 - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($storeName); ?>の注文書一覧を表示します。注文の検索、並び替え、詳細確認が可能です。">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/order.css">
    
    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="with-header order-list-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="order-list-container">

                    <?php
                    // 検索セクションの設定
                    renderSearchSection([
                        'storeName' => $storeName,
                        'pageType' => 'order',
                        'icon' => 'fas fa-file-alt',
                        'title' => '注文書一覧',
                        'totalCount' => $total_orders,
                        'itemName' => '注文',
                        'searchValue' => $search_customer,
                        'createUrl' => "create.php?store=" . urlencode($storeName),
                        'createButtonText' => '新規注文書作成'
                    ]);
                    ?>

                    <?php
                    // テーブルの設定
                    renderDataTable([
                        'storeName' => $storeName,
                        'pageType' => 'order',
                        'columns' => getOrderColumns(),
                        'data' => $orders,
                        'sortParams' => [
                            'column' => $sort_column,
                            'order' => $sort_order,
                            'search' => $search_customer
                        ],
                        'emptyMessage' => '該当する注文はありません。'
                    ]);
                    ?>

                    <?php
                    // ページネーションの設定
                    renderPagination([
                        'storeName' => $storeName,
                        'currentPage' => $page,
                        'totalPages' => $total_pages,
                        'searchValue' => $search_customer,
                        'sortColumn' => $sort_column,
                        'sortOrder' => $sort_order
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 顧客注文詳細モーダル -->
    <div id="customerOrdersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="customerOrdersTitle"></h2>
                <button class="close-modal" onclick="closeModal('customerOrdersModal')">&times;</button>
            </div>
            <div class="modal-body" id="customerOrdersContent">
                <!-- 注文内容がここに表示されます -->
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    <script src="/MBS_B/assets/js/components/modal.js"></script>
    
    <script>
    // 注文ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        // 顧客名クリックイベント
        document.querySelectorAll('.customer-name-clickable').forEach(element => {
            element.addEventListener('click', function() {
                const customerName = this.dataset.customerName;
                loadCustomerOrders(customerName);
            });
        });
        
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('order-page-loaded');
        }
    });
    
    // 顧客注文詳細をロードする関数
    function loadCustomerOrders(customerName) {
        const modal = document.getElementById('customerOrdersModal');
        const title = document.getElementById('customerOrdersTitle');
        const content = document.getElementById('customerOrdersContent');
        
        title.textContent = customerName + ' の注文履歴';
        content.innerHTML = '<div class="loading-placeholder"><div class="loading-spinner"></div><span>読み込み中...</span></div>';
        
        modal.style.display = 'block';
        
        // Ajax呼び出し（実装に応じて調整）
        fetch(`get_customer_orders.php?customer_name=${encodeURIComponent(customerName)}&store=<?= htmlspecialchars($storeName) ?>`)
            .then(response => response.text())
            .then(data => {
                content.innerHTML = data;
            })
            .catch(error => {
                content.innerHTML = '<p class="error">データの読み込みに失敗しました。</p>';
                console.error('Error:', error);
            });
    }
    
    // モーダルを閉じる関数
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // モーダル外クリックで閉じる
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
    </script>
</body>
</html>
