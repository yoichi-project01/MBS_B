<?php


try {
    require_once(__DIR__ . '/../component/autoloader.php');
    require_once(__DIR__ . '/../component/db.php');
    require_once(__DIR__ . '/../component/search_section.php');
    require_once(__DIR__ . '/../component/data_table.php');
    require_once(__DIR__ . '/../component/pagination.php');
    include(__DIR__ . '/../component/header.php');

    SessionManager::start();
} catch (Exception $e) {
    die('初期化エラー: ' . $e->getMessage());
}

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
    $pdo = db_connect();
    // 基本クエリ
    $sql = "
        SELECT 
            o.order_no, 
            c.customer_no,
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

    // 総件数を取得（専用クエリを作成）
    $count_sql = "SELECT COUNT(*) as total FROM orders o 
                  LEFT JOIN customers c ON o.customer_no = c.customer_no";
    if (!empty($where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $total_stmt = $pdo->prepare($count_sql);
    $total_stmt->execute($params);
    $total_result = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_orders = $total_result['total'];
    $total_pages = ceil($total_orders / $limit);

    // ソートとページネーションを追加（検証済みパラメータを使用）
    $sql .= " ORDER BY " . $sort_column . " " . $sort_order . " LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("データベースエラー: " . $e->getMessage());
    // エラーの場合は空配列を設定
    $orders = [];
    $total_orders = 0;
    $total_pages = 1;
}

// translate_status関数はdata_table.phpに移動しました
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
    <link rel="stylesheet" href="/MBS_B/assets/css/components/action_button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/status_badge.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table_actions.css">
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
                        'emptyMessage' => '該当する注文はありません。',
                        'mobileMode' => 'customer-only'
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
                        'sortOrder' => $sort_order,
                        'totalItems' => $total_orders,
                        'itemsPerPage' => $limit
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
    
    <script nonce="<?= SessionManager::get('csp_nonce') ?>">
    // 注文ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        let currentIsMobile = null;
        
        // スマホ時のみ顧客名クリックイベントを有効化
        function setupCustomerClickEvents() {
            const isMobile = window.innerWidth <= 768;
            
            // 状態が変わった時のみ処理
            if (currentIsMobile === isMobile) {
                return;
            }
            currentIsMobile = isMobile;
            
            // 全ての既存イベントを削除してから再設定
            const customerElements = document.querySelectorAll('.customer-name-clickable');
            
            customerElements.forEach(element => {
                // 既存の要素をクローンして置き換え（全イベントリスナーを削除）
                const newElement = element.cloneNode(true);
                element.parentNode.replaceChild(newElement, element);
                
                if (isMobile) {
                    // スマホ時：クリック可能にする
                    newElement.style.cursor = 'pointer';
                    newElement.style.color = 'var(--main-green)';
                    newElement.style.fontWeight = '600';
                    newElement.style.borderBottom = '1px dotted var(--main-green)';
                    newElement.style.transition = 'all 0.3s ease';
                    
                    // クリックイベント
                    newElement.addEventListener('click', function(e) {
                        e.preventDefault();
                        const orderNo = this.dataset.order;
                        const storeName = this.dataset.store;
                        
                        if (orderNo && storeName) {
                            // 注文書詳細ページに遷移
                            window.location.href = `/MBS_B/order_list/detail.php?order_no=${encodeURIComponent(orderNo)}&store=${encodeURIComponent(storeName)}`;
                        } else {
                            console.error('Order number or store name not found');
                        }
                    });
                    
                    // ホバーエフェクト
                    newElement.addEventListener('mouseover', function() {
                        this.style.color = 'var(--accent-green)';
                        this.style.borderBottomColor = 'var(--accent-green)';
                        this.style.transform = 'translateY(-1px)';
                    });
                    
                    newElement.addEventListener('mouseout', function() {
                        this.style.color = 'var(--main-green)';
                        this.style.borderBottomColor = 'var(--main-green)';
                        this.style.transform = 'translateY(0)';
                    });
                } else {
                    // デスクトップ時：通常の表示
                    newElement.style.cursor = 'default';
                    newElement.style.color = 'var(--font-color)';
                    newElement.style.fontWeight = 'normal';
                    newElement.style.borderBottom = 'none';
                    newElement.style.transform = 'translateY(0)';
                    newElement.style.transition = 'none';
                    
                    // クリックを無効化
                    newElement.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    });
                }
            });
        }
        
        // 初回実行
        setupCustomerClickEvents();
        
        // リサイズ時に再実行（デバウンス付き）
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(setupCustomerClickEvents, 100);
        });
        
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('order-page-loaded');
        }
    });
    
    
    </script>
</body>
</html>
