<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();

$delivery_no = $_GET['delivery_no'] ?? '';
$storeName = $_GET['store'] ?? '';

if (empty($delivery_no)) {
    die("納品書番号が指定されていません。");
}

// 納品書番号からIDを抽出 (D0001 -> 1)
$delivery_id = intval(str_replace('D', '', $delivery_no));

// サンプルデータから該当する納品書を取得
$sampleDeliveries = [
    ['id' => 1, 'customer_name' => '木村 紗希', 'status' => 'completed', 'completed' => 3, 'total' => 3],
    ['id' => 2, 'customer_name' => '桜井株式会社', 'status' => 'partial', 'completed' => 2, 'total' => 3],
    ['id' => 3, 'customer_name' => 'カフェ ドルチェビータ', 'status' => 'completed', 'completed' => 1, 'total' => 1],
    ['id' => 4, 'customer_name' => '喫茶店 フレーバー', 'status' => 'completed', 'completed' => 5, 'total' => 5],
    ['id' => 5, 'customer_name' => '木下萌', 'status' => 'partial', 'completed' => 3, 'total' => 4],
    ['id' => 6, 'customer_name' => 'コーヒーハウス レインボー', 'status' => 'completed', 'completed' => 3, 'total' => 3],
];

$delivery = null;
foreach ($sampleDeliveries as $item) {
    if ($item['id'] == $delivery_id) {
        $delivery = $item;
        break;
    }
}

if (!$delivery) {
    die("指定された納品書が見つかりません。");
}

// サンプル納品商品データ
$sampleItems = [
    ['product_name' => 'コーヒー豆A', 'quantity' => 10, 'unit_price' => 1500, 'subtotal' => 15000, 'delivered' => true],
    ['product_name' => 'コーヒー豆B', 'quantity' => 5, 'unit_price' => 2000, 'subtotal' => 10000, 'delivered' => true],
    ['product_name' => 'エスプレッソ豆', 'quantity' => 3, 'unit_price' => 2500, 'subtotal' => 7500, 'delivered' => $delivery['status'] === 'completed'],
];

$total_amount = array_sum(array_column($sampleItems, 'subtotal'));

// ステータス変換
function translate_delivery_status($status) {
    $status_map = [
        'pending' => ['label' => '未納品', 'class' => 'is-pending'],
        'partial' => ['label' => '一部納品', 'class' => 'is-processing'],
        'completed' => ['label' => '納品完了', 'class' => 'is-completed']
    ];
    return $status_map[$status] ?? ['label' => $status, 'class' => ''];
}
$status_info = translate_delivery_status($delivery['status']);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書詳細 <?= htmlspecialchars($delivery_no) ?> - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="納品書番号 <?= htmlspecialchars($delivery_no) ?> の詳細情報を表示します。顧客情報、納品商品、合計金額などを確認できます。">
    
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
    
</head>
<body class="with-header order-detail-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="order-detail-container">
                    <div class="detail-header">
                        <h1 class="page-title">納品書詳細</h1>
                        <a href="index.php?store=<?= htmlspecialchars($storeName) ?>" class="back-button"><i class="fas fa-arrow-left"></i> 一覧へ戻る</a>
                    </div>

                    <div class="order-meta">
                        <div class="meta-item">
                            <span class="meta-label">納品書番号:</span>
                            <span class="meta-value"><?= htmlspecialchars($delivery_no) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">納品日:</span>
                            <span class="meta-value"><?= date('Y-m-d') ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">ステータス:</span>
                            <span class="meta-value status-badge <?= $status_info['class'] ?>"><?= $status_info['label'] ?></span>
                        </div>
                    </div>

                    <div class="customer-info-box">
                        <h2 class="section-title">顧客情報</h2>
                        <p><strong>顧客名:</strong> <?= htmlspecialchars($delivery['customer_name']) ?></p>
                        <p><strong>住所:</strong> 東京都渋谷区サンプル町1-2-3</p>
                        <p><strong>電話番号:</strong> 03-1234-5678</p>
                    </div>

                    <div class="order-items-box">
                        <h2 class="section-title">納品商品</h2>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>商品名</th>
                                    <th>単価</th>
                                    <th>数量</th>
                                    <th>小計</th>
                                    <th>納品状況</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sampleItems as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td>¥<?= number_format($item['unit_price']) ?></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td>¥<?= number_format($item['subtotal']) ?></td>
                                        <td>
                                            <?php if ($item['delivered']): ?>
                                                <span class="delivery-status-badge delivery-status-completed">納品済</span>
                                            <?php else: ?>
                                                <span class="delivery-status-badge delivery-status-pending">未納品</span>
                                            <?php endif; ?>
                                        </td>
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

                    <div class="delivery-progress-box">
                        <h2 class="section-title">納品進捗</h2>
                        <div class="progress-info">
                            <span class="progress-text">
                                <?= $delivery['completed'] ?>/<?= $delivery['total'] ?> 商品納品完了
                                (<?= round(($delivery['completed'] / $delivery['total']) * 100) ?>%)
                            </span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= round(($delivery['completed'] / $delivery['total']) * 100) ?>%"></div>
                            </div>
                        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        if (window.performance && window.performance.mark) {
            window.performance.mark('delivery-detail-page-loaded');
        }
        
        const printBtn = document.querySelector('.print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function() {
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