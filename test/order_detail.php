<?php
session_start();

require_once 'db_connect.php';

$message = $_SESSION['message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// セッションメッセージは一度表示したらクリア
unset($_SESSION['message']);
unset($_SESSION['error_message']);

$order_no = $_GET['order_no'] ?? null;

if (!$order_no) {
    $_SESSION['error_message'] = "表示する注文書が指定されていません。";
    header('Location: order_history.php');
    exit();
}

$order = null;
$order_items = [];
$total_amount = 0; // 合計金額を初期化

try {
    // 注文書詳細の取得
    $sql_order = "SELECT o.order_no, o.customer_no, c.customer_name, o.registration_date
                  FROM orders o
                  JOIN customers c ON o.customer_no = c.customer_no
                  WHERE o.order_no = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$order_no]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        $_SESSION['error_message'] = "指定された注文書が見つかりません。";
        header('Location: order_history.php');
        exit();
    }

    // 注文書アイテムの取得
    $sql_items = "SELECT oi.item_no, oi.product_id, p.product_name, oi.quantity, oi.price_at_order
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_no = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$order_no]);
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 合計金額の計算
    foreach ($order_items as $item) {
        $total_amount += $item['quantity'] * $item['price_at_order'];
    }

} catch (PDOException $e) {
    error_log("データベースエラー (order_detail.php): " . $e->getMessage());
    $_SESSION['error_message'] = "注文書詳細の読み込み中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
    header('Location: order_history.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>注文書詳細 - No.<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0; padding: 0;
            background-color: #f4f4f4;
            display: flex; justify-content: center; align-items: flex-start;
            min-height: 100vh;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 800px;
            margin-top: 20px;
            overflow: hidden;
        }
        .header {
            background-color: #28a745; /* Green */
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.2em;
        }
        .nav-buttons {
            display: flex;
        }
        .nav-buttons button {
            background-color: #218838;
            color: white;
            border: none;
            padding: 10px 15px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .nav-buttons button:hover {
            background-color: #1e7e34;
        }
        .content {
            padding: 20px;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .detail-item {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .detail-item label {
            font-weight: bold;
            width: 120px; /* ラベルの幅を固定 */
            flex-shrink: 0;
        }
        .detail-item span {
            flex-grow: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #555;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tfoot td {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .action-buttons {
            margin-top: 30px;
            text-align: center;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .action-buttons button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .edit-button {
            background-color: #007bff; /* Blue */
            color: white;
        }
        .edit-button:hover {
            background-color: #0056b3;
        }
        .delete-button {
            background-color: #dc3545; /* Red */
            color: white;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
        .back-button {
            padding: 10px 20px;
            background-color: #6c757d; /* Gray */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>緑橋書店 受注管理システム</h1>
            <div class="nav-buttons">
                <button onclick="location.href='#'">顧客情報</button>
                <button onclick="location.href='#'">統計情報</button>
                <button onclick="location.href='order_history.php'">注文書</button>
                <button onclick="location.href='#'">納品書</button>
            </div>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="message error"><?= htmlspecialchars($error_message, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <div class="section-title">注文書詳細 - No.<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?></div>

            <div class="order-details">
                <div class="detail-item">
                    <label>顧客名:</label>
                    <span><?= htmlspecialchars($order['customer_name'], ENT_QUOTES) ?></span>
                </div>
                <div class="detail-item">
                    <label>登録日:</label>
                    <span><?= htmlspecialchars($order['registration_date'], ENT_QUOTES) ?></span>
                </div>
            </div>

            <div class="order-items-section">
                <h3>注文アイテム</h3>
                <?php if (empty($order_items)): ?>
                    <p>この注文書にはアイテムが登録されていません。</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>アイテムNo</th>
                                <th>商品名</th>
                                <th>数量</th>
                                <th>単価</th>
                                <th>小計</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_no'], ENT_QUOTES) ?></td>
                                    <td><?= htmlspecialchars($item['product_name'], ENT_QUOTES) ?></td>
                                    <td><?= htmlspecialchars($item['quantity'], ENT_QUOTES) ?></td>
                                    <td><?= htmlspecialchars(number_format($item['price_at_order']), ENT_QUOTES) ?></td>
                                    <td><?= htmlspecialchars(number_format($item['quantity'] * $item['price_at_order']), ENT_QUOTES) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align: right;"><strong>合計金額:</strong></td>
                                <td><strong><?= htmlspecialchars(number_format($total_amount), ENT_QUOTES) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <button class="edit-button" onclick="location.href='order_edit.php?order_no=<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>'">編集</button>
                <button class="delete-button" onclick="confirmDelete(<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>)">削除</button>
            </div>

            <div style="margin-top: 30px; text-align: center;">
                <button class="back-button" onclick="location.href='order_history.php'">注文履歴に戻る</button>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(orderNo) {
            if (confirm('注文No ' + orderNo + ' を本当に削除してもよろしいですか？この操作は元に戻せません。')) {
                window.location.href = 'order_delete.php?order_no=' + orderNo;
            }
        }
    </script>
</body>
</html>