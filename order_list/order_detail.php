<?php
include(__DIR__ . '/../component/header.php');
session_start();

// require_once 'db_connect.php'; // データベース接続はダミーデータ表示のためコメントアウト

$order_no = $_GET['order_no'] ?? null;
$message = $_SESSION['message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// セッションメッセージは一度表示したらクリア
unset($_SESSION['message']);
unset($_SESSION['error_message']);

// ダミーデータ
$dummy_order = null;
$dummy_order_items = [];

if ($order_no) {
    switch ($order_no) {
        case 'ORD0001':
            $dummy_order = [
                'order_no' => 'ORD0001',
                'registration_date' => '2022/11/24',
                'customer_name' => '木村 紗希',
                'remarks' => '初回注文。特別割引適用。',
            ];
            $dummy_order_items = [
                ['item_name' => '週間BCN 10月号', 'quantity' => 1, 'unit_price' => 1100, 'summary' => ''],
                ['item_name' => '日経コンピューター 10月号', 'quantity' => 2, 'unit_price' => 1000, 'summary' => ''],
                ['item_name' => '週間マガジン 10月号', 'quantity' => 1, 'unit_price' => 800, 'summary' => ''],
            ];
            break;
        case 'ORD0002':
            $dummy_order = [
                'order_no' => 'ORD0002',
                'registration_date' => '2022/11/20',
                'customer_name' => '株式会社ABC',
                'remarks' => '急ぎでの納品希望。',
            ];
            $dummy_order_items = [
                ['item_name' => 'PHPフレームワーク入門', 'quantity' => 3, 'unit_price' => 2500, 'summary' => ''],
                ['item_name' => 'データベース設計実践ガイド', 'quantity' => 1, 'unit_price' => 3200, 'summary' => ''],
            ];
            break;
        default:
            // --- ここから自動生成ダミーデータ ---
            if (preg_match('/^ORD(\\d{4})$/', $order_no, $m)) {
                $num = (int)$m[1];
                $dummy_order = [
                    'order_no' => $order_no,
                    'registration_date' => date('Y/m/d', strtotime('-' . $num . ' days')),
                    'customer_name' => ($num % 5 === 0) ? "テスト顧客 " . str_pad($num, 2, '0', STR_PAD_LEFT) : "ダミー顧客 " . str_pad($num, 2, '0', STR_PAD_LEFT),
                    'remarks' => '自動生成ダミー注文書です。',
                ];
                $dummy_order_items = array();
                $item_count = ($num % 3) + 1;
                for ($i = 1; $i <= $item_count; $i++) {
                    $dummy_order_items[] = array(
                        'item_name' => 'ダミー商品' . $i,
                        'quantity' => $i,
                        'unit_price' => (1000 + $i * 100 + $num),
                        'summary' => '自動生成品目'
                    );
                }
            } else {
                $error_message = "指定された注文書No (" . htmlspecialchars($order_no, ENT_QUOTES) . ") のデータは見つかりませんでした。";
            }
            // --- ここまで自動生成ダミーデータ ---
            break;
    }
} else {
    $error_message = "注文書が指定されていません。";
}

// データベース連携部分はコメントアウト
/*
try {
    if ($order_no) {
        // 注文書メインデータを取得
        $sql_order = "SELECT o.order_no, o.registration_date, c.customer_name, o.remarks FROM orders o JOIN customers c ON o.customer_no = c.customer_no WHERE o.order_no = ?";
        $stmt_order = $pdo->prepare($sql_order);
        $stmt_order->execute([$order_no]);
        $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            // 注文アイテムを取得
            $sql_items = "SELECT item_name, quantity, unit_price, summary FROM order_items WHERE order_no = ? ORDER BY item_id ASC";
            $stmt_items = $pdo->prepare($sql_items);
            $stmt_items->execute([$order_no]);
            $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error_message = "指定された注文書No (" . htmlspecialchars($order_no, ENT_QUOTES) . ") のデータは見つかりませんでした。";
        }
    } else {
        $error_message = "注文書が指定されていません。";
    }
} catch (PDOException $e) {
    error_log("データベースエラー (order_detail.php): " . $e->getMessage());
    $error_message = "注文書データの読み込み中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
}
*/
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>注文書詳細</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <main class="main-content" style="max-width: 800px;">
        <?php if (!empty($message)): ?>
            <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <div class="order-detail-header"
            style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 18px; gap: 16px;">
            <div class="order-no"
                style="font-size: 1.1em; font-weight: bold; color: var(--main-green); background: var(--bg-light); border-radius: 8px; padding: 8px 18px; box-shadow: 0 2px 8px rgba(47,93,63,0.06);">
                注文書No：<?= htmlspecialchars($order_no ?? '', ENT_QUOTES) ?>
            </div>
            <div class="menu" style="flex-direction: row; gap: 12px; margin: 0;">
                <button type="button" class="back-button" onclick="location.href='order_history.php'">注文書一覧へ戻る</button>
                <?php if ($dummy_order): ?>
                    <button type="button" class="delete-button" style="background: #ffc107; color: #333;"
                        onclick="if(confirm('この注文書を削除してもよろしいですか？')) { location.href='order_delete.php?order_no=<?= htmlspecialchars($order_no, ENT_QUOTES) ?>'; }">注文書削除</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($dummy_order): ?>
            <section class="order-info" style="margin-bottom: 24px;">
                <div style="display: flex; gap: 32px; flex-wrap: wrap;">
                    <div>
                        <strong>登録日:</strong>
                        <span><?= htmlspecialchars($dummy_order['registration_date'], ENT_QUOTES) ?></span>
                    </div>
                    <div>
                        <strong>顧客名:</strong>
                        <span><?= htmlspecialchars($dummy_order['customer_name'], ENT_QUOTES) ?></span>
                    </div>
                </div>
            </section>

            <div class="table-container">
                <table class="order-detail-table" id="orderDetailTable">
                    <thead>
                        <tr>
                            <th>品名</th>
                            <th>数量</th>
                            <th>単価</th>
                            <th>小計</th>
                            <th>摘要</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        foreach ($dummy_order_items as $item):
                            $subtotal = $item['quantity'] * $item['unit_price'];
                            $total += $subtotal;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name'], ENT_QUOTES) ?></td>
                                <td style="text-align:right;"><?= htmlspecialchars($item['quantity'], ENT_QUOTES) ?></td>
                                <td style="text-align:right;"><?= number_format($item['unit_price']) ?> 円</td>
                                <td style="text-align:right;"><?= number_format($subtotal) ?> 円</td>
                                <td><?= htmlspecialchars($item['summary'], ENT_QUOTES) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" style="text-align:right;">合計</th>
                            <th style="text-align:right;"><?= number_format($total) ?> 円</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="remarks-section" style="margin-top: 18px; width: 100%;">
                <label for="remarks" style="font-weight: bold; color: var(--main-green);">備考</label>
                <textarea id="remarks" name="remarks" readonly
                    style="width: 100%; min-height: 60px; border-radius: 8px; border: 1.5px solid #b5cbbb; padding: 10px; font-size: 1em; background: #f8faf9;"><?= htmlspecialchars($dummy_order['remarks'], ENT_QUOTES) ?></textarea>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666;">表示する注文書データがありません。</p>
        <?php endif; ?>
    </main>
</body>

</html>