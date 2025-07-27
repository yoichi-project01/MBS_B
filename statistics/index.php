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

// 店舗が選択されていない場合のエラーハンドリング
if (empty($storeName)) {
    $_SESSION['error_message'] = '店舗が選択されていません。店舗選択画面からアクセスしてください。';
    header('Location: /MBS_B/index.php');
    exit;
}

// ページネーションの設定
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // 1ページあたりの表示件数
$offset = ($page - 1) * $limit;

// 検索条件
$search_customer = $_GET['search_customer'] ?? '';

// ソート条件
$sort_column = $_GET['sort'] ?? 'customer_name';
$sort_order = $_GET['order'] ?? 'ASC';

// パラメータのバリデーション
$page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$allowed_sort_columns = ['customer_no', 'customer_name', 'total_sales', 'delivery_count', 'avg_lead_time', 'last_order_date', 'registration_date'];
if (!in_array($sort_column, $allowed_sort_columns)) {
    $sort_column = 'customer_name';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'ASC';
}

try {
    $pdo = db_connect();
    
    // 店舗名のバリデーション
    $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
    if (!in_array($storeName, $allowedStores)) {
        throw new Exception('無効な店舗名です');
    }
    
    // 基本クエリ（customer_summaryビューを使用）
    $whereClause = "WHERE cs.store_name = :store_name";
    $params = ['store_name' => $storeName];
    
    // 検索条件の追加
    if (!empty($search_customer)) {
        $whereClause .= " AND (cs.customer_name LIKE :search OR cs.customer_no LIKE :search_no)";
        $params['search'] = '%' . $search_customer . '%';
        $params['search_no'] = '%' . $search_customer . '%';
    }
    
    // 総件数の取得
    $countQuery = "SELECT COUNT(*) as total FROM customer_summary cs $whereClause";
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total_customers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_customers / $limit);
    
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
            cs.updated_at
        FROM customer_summary cs
        $whereClause
        ORDER BY cs.$sort_column $sort_order
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
    if (!empty($search_customer)) {
        $stmt->bindValue(':search', '%' . $search_customer . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search_no', '%' . $search_customer . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("データベースエラー: " . $e->getMessage());
    // エラーの場合は空配列を設定
    $customers = [];
    $total_customers = 0;
    $total_pages = 1;
} catch (Exception $e) {
    error_log("エラー: " . $e->getMessage());
    $customers = [];
    $total_customers = 0;
    $total_pages = 1;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報一覧 - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($storeName); ?>の統計情報一覧を表示します。統計情報の検索、並び替え、詳細確認が可能です。">
    
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
                        'icon' => 'fas fa-chart-bar',
                        'title' => '統計情報一覧',
                        'totalCount' => $total_customers,
                        'itemName' => '顧客',
                        'searchValue' => $search_customer,
                        'createUrl' => null,
                        'createButtonText' => null
                    ]);
                    ?>

                    <?php
                    // テーブルの設定
                    renderDataTable([
                        'storeName' => $storeName,
                        'pageType' => 'order',
                        'columns' => getCustomerColumns(),
                        'data' => $customers,
                        'sortParams' => [
                            'column' => $sort_column,
                            'order' => $sort_order,
                            'search' => $search_customer
                        ],
                        'emptyMessage' => '該当する統計情報はありません。',
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
                        'totalItems' => $total_customers,
                        'itemsPerPage' => $limit
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 顧客詳細モーダル -->
    <div id="customerOrdersModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="customerOrdersTitle">顧客詳細</h2>
                <button class="close-modal" onclick="closeModal('customerOrdersModal')">&times;</button>
            </div>
            <div class="modal-body" id="customerOrdersContent">
                <!-- 顧客詳細がここに表示されます -->
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    <script src="/MBS_B/assets/js/components/modal.js"></script>
    <script src="/MBS_B/assets/js/pages/statistics-simple.js"></script>
    
    <script nonce="<?= SessionManager::get('csp_nonce') ?>">
    // 統計情報ページ固有の初期化
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
            const customerElements = document.querySelectorAll('.customer-name-statistics');
            
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
                        
                        // 行から顧客番号を取得（1列目のtd）
                        const row = this.closest('tr');
                        const customerNoCell = row.querySelector('td:first-child');
                        const customerNo = customerNoCell ? customerNoCell.textContent.trim() : '';
                        const storeName = <?= json_encode($storeName, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
                        
                        if (customerNo && storeName) {
                            // 顧客詳細ページに遷移
                            window.location.href = `/MBS_B/statistics/customer_detail.php?customer_no=${encodeURIComponent(customerNo)}&store=${encodeURIComponent(storeName)}`;
                        } else {
                            console.error('Customer number or store name not found');
                        }
                    });
                    
                    // ホバーエフェクト
                    newElement.addEventListener('mouseover', function() {
                        this.style.color = 'var(--accent-green)';
                        newElement.style.borderBottomColor = 'var(--accent-green)';
                        newElement.style.transform = 'translateY(-1px)';
                    });
                    
                    newElement.addEventListener('mouseout', function() {
                        this.style.color = 'var(--main-green)';
                        newElement.style.borderBottomColor = 'var(--main-green)';
                        newElement.style.transform = 'translateY(0)';
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
            window.performance.mark('statistics-page-loaded');
        }
    });
    
    
    </script>
</body>
</html>