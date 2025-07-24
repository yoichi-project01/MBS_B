<?php

try {
    require_once(__DIR__ . '/../component/autoloader.php');
    require_once(__DIR__ . '/../component/db.php');
    include(__DIR__ . '/../component/header.php');

    SessionManager::start();
} catch (Exception $e) {
    die('初期化エラー: ' . $e->getMessage());
}

$customerNo = $_GET['customer_no'] ?? '';
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

if (empty($customerNo)) {
    die('顧客番号が指定されていません。');
}

if (empty($storeName)) {
    $_SESSION['error_message'] = '店舗が選択されていません。';
    header('Location: /MBS_B/index.php');
    exit;
}

try {
    $pdo = db_connect();
    
    // 店舗名のバリデーション
    $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
    if (!in_array($storeName, $allowedStores)) {
        throw new Exception('無効な店舗名です');
    }
    
    // 顧客基本情報を取得
    $customerQuery = "
        SELECT 
            c.customer_no,
            c.customer_name,
            c.store_name,
            c.address,
            c.telephone_number,
            c.manager_name,
            c.registration_date,
            c.delivery_conditions,
            c.created_at,
            c.updated_at
        FROM customers c
        WHERE c.customer_no = :customer_no AND c.store_name = :store_name
    ";
    
    $customerStmt = $pdo->prepare($customerQuery);
    $customerStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $customerStmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
    $customerStmt->execute();
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('指定された顧客が見つかりません');
    }
    
    // 顧客統計情報を取得
    $statsQuery = "
        SELECT 
            COALESCE(cs.total_sales, 0) as total_sales,
            COALESCE(cs.delivery_count, 0) as delivery_count,
            COALESCE(cs.avg_lead_time, 0) as avg_lead_time,
            cs.last_order_date
        FROM customer_summary cs
        WHERE cs.customer_no = :customer_no AND cs.store_name = :store_name
    ";
    
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $statsStmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // 最近の注文履歴を取得
    $ordersQuery = "
        SELECT 
            o.order_no,
            o.registration_date,
            o.status,
            (SELECT SUM(oi.order_volume * oi.price) FROM order_items oi WHERE oi.order_no = o.order_no) as total_amount
        FROM orders o
        WHERE o.customer_no = :customer_no
        ORDER BY o.registration_date DESC
        LIMIT 10
    ";
    
    $ordersStmt = $pdo->prepare($ordersQuery);
    $ordersStmt->bindValue(':customer_no', $customerNo, PDO::PARAM_INT);
    $ordersStmt->execute();
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("データベースエラー: " . $e->getMessage());
    die('データベースエラーが発生しました');
} catch (Exception $e) {
    error_log("エラー: " . $e->getMessage());
    die($e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>顧客詳細 - <?php echo htmlspecialchars($customer['customer_name']); ?> - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($customer['customer_name']); ?>の詳細情報を表示します。">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/action_button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/status_badge.css">
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
                    
                    <!-- ページヘッダー -->
                    <div class="detail-header">
                        <h1 class="page-title">
                            <i class="fas fa-user"></i>
                            顧客詳細 - <?php echo htmlspecialchars($customer['customer_name']); ?>
                        </h1>
                        <a href="/MBS_B/order_list/?store=<?php echo urlencode($storeName); ?>" class="back-button">
                            <i class="fas fa-arrow-left"></i>
                            注文一覧に戻る
                        </a>
                    </div>

                    <!-- 顧客基本情報 -->
                    <div class="customer-info-box">
                        <h2 class="section-title">基本情報</h2>
                        <div class="order-meta">
                            <div class="meta-item">
                                <div class="meta-label">顧客番号</div>
                                <div class="meta-value"><?php echo htmlspecialchars($customer['customer_no']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">顧客名</div>
                                <div class="meta-value"><?php echo htmlspecialchars($customer['customer_name']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">店舗</div>
                                <div class="meta-value"><?php echo htmlspecialchars($customer['store_name']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">登録日</div>
                                <div class="meta-value"><?php echo htmlspecialchars($customer['registration_date'] ?? ''); ?></div>
                            </div>
                        </div>
                        
                        <div class="order-info-grid">
                            <div class="info-item">
                                <label>住所:</label>
                                <span><?php echo htmlspecialchars($customer['address'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <label>電話番号:</label>
                                <span><?php echo htmlspecialchars($customer['telephone_number'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <label>担当者:</label>
                                <span><?php echo htmlspecialchars($customer['manager_name'] ?? ''); ?></span>
                            </div>
                            <div class="info-item">
                                <label>配達条件:</label>
                                <span><?php echo htmlspecialchars($customer['delivery_conditions'] ?? ''); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- 統計情報 -->
                    <?php if ($stats): ?>
                    <div class="customer-info-box">
                        <h2 class="section-title">統計情報</h2>
                        <div class="order-meta">
                            <div class="meta-item">
                                <div class="meta-label">累計売上</div>
                                <div class="meta-value">¥<?php echo number_format($stats['total_sales']); ?></div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">配達回数</div>
                                <div class="meta-value"><?php echo $stats['delivery_count']; ?>回</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">平均リードタイム</div>
                                <div class="meta-value"><?php echo $stats['avg_lead_time']; ?>日</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-label">最終注文日</div>
                                <div class="meta-value"><?php echo htmlspecialchars($stats['last_order_date'] ?? '-'); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 注文履歴 -->
                    <div class="order-items-box">
                        <h2 class="section-title">注文履歴</h2>
                        <?php if (!empty($orders)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>注文番号</th>
                                    <th>注文日</th>
                                    <th>ステータス</th>
                                    <th>金額</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_no']); ?></td>
                                    <td><?php echo htmlspecialchars($order['registration_date']); ?></td>
                                    <td>
                                        <span class="status-<?php echo $order['status']; ?>">
                                            <?php 
                                            switch($order['status']) {
                                                case 'pending': echo '保留中'; break;
                                                case 'processing': echo '処理中'; break;
                                                case 'completed': echo '完了'; break;
                                                case 'cancelled': echo 'キャンセル'; break;
                                                default: echo $order['status']; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>¥<?php echo number_format($order['total_amount'] ?? 0); ?></td>
                                    <td>
                                        <a href="/MBS_B/order_list/detail.php?order_no=<?php echo $order['order_no']; ?>&store=<?php echo urlencode($storeName); ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> 詳細
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📝</div>
                            <div class="empty-message">注文履歴がありません</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    
    <script nonce="<?= SessionManager::get('csp_nonce') ?>">
    document.addEventListener('DOMContentLoaded', function() {
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('customer-detail-page-loaded');
        }
    });
    </script>
</body>
</html>