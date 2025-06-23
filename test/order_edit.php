<?php
session_start();
require_once 'db_connect.php';

$order_no = $_GET['order_no'] ?? null;

if (!$order_no) {
    $_SESSION['error_message'] = "編集する注文書が指定されていません。";
    header('Location: order_history.php');
    exit();
}

$order = null;
$order_items = [];
$products = [];
$customers = [];
$message = '';
$error_message = '';

// POSTリクエストの場合の処理（更新処理）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_no = $_POST['customer_no'] ?? null;
    $items_json = $_POST['items_json'] ?? null;

    if (!$customer_no || !$items_json) {
        $_SESSION['error_message'] = "顧客情報または注文アイテムが不足しています。";
        header('Location: order_edit.php?order_no=' . urlencode($order_no));
        exit();
    }

    $new_items = json_decode($items_json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error_message'] = "注文アイテムのデータ形式が不正です。";
        header('Location: order_edit.php?order_no=' . urlencode($order_no));
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 注文書の顧客情報を更新
        $update_order_sql = "UPDATE orders SET customer_no = ? WHERE order_no = ?";
        $stmt_update_order = $pdo->prepare($update_order_sql);
        $stmt_update_order->execute([$customer_no, $order_no]);

        // 既存の注文アイテムを全て削除
        $delete_items_sql = "DELETE FROM order_items WHERE order_no = ?";
        $stmt_delete_items = $pdo->prepare($delete_items_sql);
        $stmt_delete_items->execute([$order_no]);

        // 新しい注文アイテムを挿入
        $insert_item_sql = "INSERT INTO order_items (order_no, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)";
        $stmt_insert_item = $pdo->prepare($insert_item_sql);

        foreach ($new_items as $item) {
            if (isset($item['product_id']) && isset($item['quantity']) && isset($item['price_at_order'])) {
                // quantityとprice_at_orderが数値であることを確認
                $quantity = (int)$item['quantity'];
                $price_at_order = (float)$item['price_at_order'];

                // 数量が0以上のアイテムのみを登録
                if ($quantity > 0) {
                    $stmt_insert_item->execute([$order_no, $item['product_id'], $quantity, $price_at_order]);
                }
            }
        }

        $pdo->commit();
        $_SESSION['message'] = "注文書No " . htmlspecialchars($order_no, ENT_QUOTES) . " が正常に更新されました。";
        header('Location: order_detail.php?order_no=' . urlencode($order_no));
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("データベースエラー (order_edit.php): " . $e->getMessage());
        $_SESSION['error_message'] = "注文書の更新中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
        header('Location: order_edit.php?order_no=' . urlencode($order_no));
        exit();
    }
}


// GETリクエストまたはPOST後の再表示のためのデータ取得
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

    // 全顧客の取得 (顧客選択用)
    $stmt_customers = $pdo->query("SELECT customer_no, customer_name FROM customers ORDER BY customer_name ASC");
    $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

    // 全商品の取得 (商品選択用)
    $stmt_products = $pdo->query("SELECT product_id, product_name, price FROM products ORDER BY product_name ASC");
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("データベースエラー (order_edit.php): " . $e->getMessage());
    $_SESSION['error_message'] = "編集データの読み込み中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
    header('Location: order_history.php');
    exit();
}

// POST後のリダイレクトでセットされたメッセージがあれば取得
$message = $_SESSION['message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>注文書編集 - No.<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?></title>
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
            max-width: 900px;
            margin-top: 20px;
            overflow: hidden;
        }
        .header {
            background-color: #28a745;
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
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
        .add-item-button {
            background-color: #17a2b8; /* Cyan */
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        .add-item-button:hover {
            background-color: #138496;
        }
        .remove-item-button {
            background-color: #dc3545; /* Red */
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8em;
            transition: background-color 0.3s ease;
        }
        .remove-item-button:hover {
            background-color: #c82333;
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
        .save-button {
            background-color: #28a745; /* Green */
            color: white;
        }
        .save-button:hover {
            background-color: #218838;
        }
        .cancel-button {
            background-color: #6c757d; /* Gray */
            color: white;
        }
        .cancel-button:hover {
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

            <div class="section-title">注文書編集 - No.<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?></div>

            <form id="orderEditForm" action="order_edit.php?order_no=<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>" method="POST">
                <div class="form-group">
                    <label for="customer_no">顧客名:</label>
                    <select id="customer_no" name="customer_no" required>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['customer_no'], ENT_QUOTES) ?>"
                                <?= ($customer['customer_no'] == $order['customer_no']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['customer_name'], ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="order-items-section">
                    <h3>注文アイテム</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>商品名</th>
                                <th>数量</th>
                                <th>単価</th>
                                <th>小計</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsTableBody">
                            <?php if (empty($order_items)): ?>
                                <tr>
                                    <td>
                                        <select name="product_id[]" onchange="updateItemPrice(this)" required>
                                            <option value="">商品を選択</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?= htmlspecialchars($product['product_id'], ENT_QUOTES) ?>" data-price="<?= htmlspecialchars($product['price'], ENT_QUOTES) ?>">
                                                    <?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" name="quantity[]" value="1" min="1" oninput="updateSubtotal(this)" required></td>
                                    <td><input type="text" name="price_at_order[]" value="" readonly style="background-color: #e9ecef; color: #495057;"></td>
                                    <td><input type="text" name="subtotal[]" value="0" readonly style="background-color: #e9ecef; color: #495057;"></td>
                                    <td><button type="button" class="remove-item-button" onclick="removeItem(this)">削除</button></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <select name="product_id[]" onchange="updateItemPrice(this)" required>
                                                <option value="">商品を選択</option>
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= htmlspecialchars($product['product_id'], ENT_QUOTES) ?>" data-price="<?= htmlspecialchars($product['price'], ENT_QUOTES) ?>"
                                                        <?= ($product['product_id'] == $item['product_id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="number" name="quantity[]" value="<?= htmlspecialchars($item['quantity'], ENT_QUOTES) ?>" min="1" oninput="updateSubtotal(this)" required></td>
                                        <td><input type="text" name="price_at_order[]" value="<?= htmlspecialchars($item['price_at_order'], ENT_QUOTES) ?>" readonly style="background-color: #e9ecef; color: #495057;"></td>
                                        <td><input type="text" name="subtotal[]" value="<?= htmlspecialchars($item['quantity'] * $item['price_at_order'], ENT_QUOTES) ?>" readonly style="background-color: #e9ecef; color: #495057;"></td>
                                        <td><button type="button" class="remove-item-button" onclick="removeItem(this)">削除</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align: right;"><strong>合計金額:</strong></td>
                                <td id="totalAmountCell"><strong>0</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button type="button" class="add-item-button" onclick="addItem()">アイテム追加</button>
                </div>

                <input type="hidden" name="items_json" id="itemsJsonInput">

                <div class="action-buttons">
                    <button type="submit" class="save-button">保存</button>
                    <button type="button" class="cancel-button" onclick="location.href='order_detail.php?order_no=<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>'">キャンセル</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const productsData = <?= json_encode($products) ?>;
        const productsMap = new Map(productsData.map(p => [p.product_id.toString(), p.price]));

        function calculateTotal() {
            let total = 0;
            document.querySelectorAll('#orderItemsTableBody tr').forEach(row => {
                const subtotalInput = row.querySelector('input[name="subtotal[]"]');
                if (subtotalInput) {
                    total += parseFloat(subtotalInput.value || 0);
                }
            });
            document.getElementById('totalAmountCell').innerHTML = '<strong>' + total.toLocaleString() + '</strong>';
        }

        function updateSubtotal(quantityInput) {
            const row = quantityInput.closest('tr');
            const quantity = parseInt(quantityInput.value) || 0;
            const priceAtOrderInput = row.querySelector('input[name="price_at_order[]"]');
            const priceAtOrder = parseFloat(priceAtOrderInput.value) || 0;
            const subtotalInput = row.querySelector('input[name="subtotal[]"]');

            const subtotal = quantity * priceAtOrder;
            subtotalInput.value = subtotal;
            calculateTotal();
        }

        function updateItemPrice(selectElement) {
            const row = selectElement.closest('tr');
            const productId = selectElement.value;
            const priceAtOrderInput = row.querySelector('input[name="price_at_order[]"]');
            const quantityInput = row.querySelector('input[name="quantity[]"]');

            if (productsMap.has(productId)) {
                const price = productsMap.get(productId);
                priceAtOrderInput.value = price;
            } else {
                priceAtOrderInput.value = ''; // 商品が選択されていない場合
            }
            updateSubtotal(quantityInput);
        }

        function addItem() {
            const tableBody = document.getElementById('orderItemsTableBody');
            const newRow = tableBody.insertRow();
            newRow.innerHTML = `
                <td>
                    <select name="product_id[]" onchange="updateItemPrice(this)" required>
                        <option value="">商品を選択</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= htmlspecialchars($product['product_id'], ENT_QUOTES) ?>" data-price="<?= htmlspecialchars($product['price'], ENT_QUOTES) ?>">
                                <?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="quantity[]" value="1" min="1" oninput="updateSubtotal(this)" required></td>
                <td><input type="text" name="price_at_order[]" value="" readonly style="background-color: #e9ecef; color: #495057;"></td>
                <td><input type="text" name="subtotal[]" value="0" readonly style="background-color: #e9ecef; color: #495057;"></td>
                <td><button type="button" class="remove-item-button" onclick="removeItem(this)">削除</button></td>
            `;
            // 新しい行が追加されたら、最初の商品の単価を反映させる（もしデフォルトで選択されている商品があれば）
            const newSelect = newRow.querySelector('select[name="product_id[]"]');
            if (newSelect.value) {
                updateItemPrice(newSelect);
            }
            calculateTotal(); // 新しい行を追加したので合計を再計算
        }

        function removeItem(buttonElement) {
            const row = buttonElement.closest('tr');
            if (document.querySelectorAll('#orderItemsTableBody tr').length > 1) { // 最後の行が削除されないようにする
                 row.remove();
            } else {
                // 最後の行を削除する場合は、内容をクリアするだけにするか、新規作成時に戻るなどの考慮が必要
                // ここでは、最後の行はクリアする
                const selectElement = row.querySelector('select[name="product_id[]"]');
                const quantityInput = row.querySelector('input[name="quantity[]"]');
                const priceAtOrderInput = row.querySelector('input[name="price_at_order[]"]');
                const subtotalInput = row.querySelector('input[name="subtotal[]"]');

                if (selectElement) selectElement.value = "";
                if (quantityInput) quantityInput.value = "1";
                if (priceAtOrderInput) priceAtOrderInput.value = "";
                if (subtotalInput) subtotalInput.value = "0";
            }
            calculateTotal();
        }

        // フォーム送信前にアイテムデータをJSONに変換して hidden input にセット
        document.getElementById('orderEditForm').addEventListener('submit', function(event) {
            const items = [];
            document.querySelectorAll('#orderItemsTableBody tr').forEach(row => {
                const productId = row.querySelector('select[name="product_id[]"]').value;
                const quantity = row.querySelector('input[name="quantity[]"]').value;
                const priceAtOrder = row.querySelector('input[name="price_at_order[]"]').value;

                // product_idが選択されており、数量が有効なもののみを収集
                if (productId && parseInt(quantity) > 0) {
                    items.push({
                        product_id: productId,
                        quantity: parseInt(quantity),
                        price_at_order: parseFloat(priceAtOrder)
                    });
                }
            });
            document.getElementById('itemsJsonInput').value = JSON.stringify(items);
        });

        // ページロード時に合計金額を計算
        window.onload = function() {
            calculateTotal();
            // 初期選択されている商品の価格を反映させる
            document.querySelectorAll('#orderItemsTableBody tr').forEach(row => {
                const selectElement = row.querySelector('select[name="product_id[]"]');
                updateItemPrice(selectElement);
            });
        };
    </script>
</body>
</html>