<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();

$order_no = $_GET['order_no'] ?? 0;
$storeName = $_GET['store'] ?? '';

if (empty($order_no)) {
    die("注文番号が指定されていません。");
}

try {
    $pdo = db_connect();
    // 注文情報と顧客情報を取得
    $sql = "
        SELECT 
            o.order_no, 
            o.registration_date, 
            o.status,
            c.customer_name, 
            c.address,
            c.telephone_number,
            c.manager_name
        FROM orders o
        JOIN customers c ON o.customer_no = c.customer_no
        WHERE o.order_no = :order_no
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':order_no', $order_no, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("指定された注文が見つかりません。");
    }

    // 注文商品情報を取得
    $items_sql = "
        SELECT 
            books, 
            order_volume, 
            price, 
            (order_volume * price) as subtotal,
            abstract,
            order_remarks
        FROM order_items
        WHERE order_no = :order_no
        ORDER BY order_item_no
    ";
    $items_stmt = $pdo->prepare($items_sql);
    $items_stmt->bindValue(':order_no', $order_no, PDO::PARAM_INT);
    $items_stmt->execute();
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 合計金額の計算
    $total_amount = array_sum(array_column($order_items, 'subtotal'));

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}

// ステータスの日本語変換
function translate_status_detail($status) {
    $status_map = [
        'pending' => ['label' => '保留', 'class' => 'is-pending'],
        'processing' => ['label' => '処理中', 'class' => 'is-processing'],
        'completed' => ['label' => '完了', 'class' => 'is-completed'],
        'cancelled' => ['label' => 'キャンセル', 'class' => 'is-cancelled']
    ];
    return $status_map[$status] ?? ['label' => $status, 'class' => ''];
}
$status_info = translate_status_detail($order['status']);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文詳細 No.<?= htmlspecialchars($order['order_no']) ?> - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="注文番号 <?= htmlspecialchars($order['order_no']) ?> の詳細情報を表示します。顧客情報、注文商品、合計金額などを確認できます。">
    
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
<body class="with-header order-detail-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="order-detail-container">
                    <div class="detail-header">
                        <h1 class="page-title">注文詳細</h1>
                        <a href="index.php?store=<?= htmlspecialchars($storeName) ?>" class="back-button"><i class="fas fa-arrow-left"></i> 一覧へ戻る</a>
                    </div>

                    <div class="order-meta">
                        <div class="meta-item">
                            <span class="meta-label">注文番号:</span>
                            <span class="meta-value"><?= htmlspecialchars($order['order_no']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">注文日:</span>
                            <span class="meta-value"><?= htmlspecialchars($order['registration_date']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">ステータス:</span>
                            <span class="meta-value status-badge <?= $status_info['class'] ?>"><?= $status_info['label'] ?></span>
                        </div>
                    </div>

                    <div class="customer-info-box">
                        <h2 class="section-title">顧客情報</h2>
                        <p><strong>顧客名:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                        <p><strong>担当者:</strong> <?= htmlspecialchars($order['manager_name'] ?? '--') ?></p>
                        <p><strong>住所:</strong> <?= htmlspecialchars($order['address']) ?></p>
                        <p><strong>電話番号:</strong> <?= htmlspecialchars($order['telephone_number']) ?></p>
                    </div>

                    <div class="order-items-box">
                        <h2 class="section-title">注文商品</h2>
                        <table class="data-table">
                <thead>
                    <tr>
                        <th>書籍名</th>
                        <th>単価</th>
                        <th>数量</th>
                        <th>小計</th>
                        <th>備考</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($item['books']) ?><br>
                                <small class="item-abstract"><?= htmlspecialchars($item['abstract']) ?></small>
                            </td>
                            <td>¥<?= number_format($item['price']) ?></td>
                            <td><?= htmlspecialchars($item['order_volume']) ?></td>
                            <td>¥<?= number_format($item['subtotal']) ?></td>
                            <td><?= htmlspecialchars($item['order_remarks'] ?? '--') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="total-label">合計金額</td>
                        <td colspan="2" class="total-amount">¥<?= number_format($total_amount) ?></td>
                    </tr>
                </tfoot>
                        </table>
                    </div>

                    <div class="detail-actions">
                        <button onclick="window.print();" class="action-button print-btn"><i class="fas fa-print"></i> 印刷する</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    
    <script nonce="<?= SessionManager::get('csp_nonce') ?>">
    // 注文詳細ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('order-detail-page-loaded');
        }
        
        // 印刷ボタンのアニメーション
        const printBtn = document.querySelector('.print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
                // 印刷プレビュー表示前のアニメーション
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        }
    });
    </script>
</body>
</html>
