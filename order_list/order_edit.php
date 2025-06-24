<?php
include(__DIR__ . '/../component/header.php');
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
                $quantity = (int)$item['quantity'];
                $price_at_order = (float)$item['price_at_order'];
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

    $sql_items = "SELECT oi.item_no, oi.product_id, p.product_name, oi.quantity, oi.price_at_order
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.product_id
                  WHERE oi.order_no = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$order_no]);
    $order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $stmt_customers = $pdo->query("SELECT customer_no, customer_name FROM customers ORDER BY customer_name ASC");
    $customers = $stmt_customers->fetchAll(PDO::FETCH_ASSOC);

    $stmt_products = $pdo->query("SELECT product_id, product_name, price FROM products ORDER BY product_name ASC");
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("データベースエラー (order_edit.php): " . $e->getMessage());
    $_SESSION['error_message'] = "編集データの読み込み中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
    header('Location: order_history.php');
    exit();
}

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
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <main class="main-content" style="max-width:900px;">
        <?php if (!empty($message)): ?>
        <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
        <div class="message error"><?= htmlspecialchars($error_message, ENT_QUOTES) ?></div>
        <?php endif; ?>

        <h1 class="section-title">注文書編集 - No.<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?></h1>

        <form id="orderEditForm"
            action="order_edit.php?order_no=<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>" method="POST">
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
                <div class="table-container">
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
                                        <option value="<?= htmlspecialchars($product['product_id'], ENT_QUOTES) ?>"
                                            data-price="<?= htmlspecialchars($product['price'], ENT_QUOTES) ?>">
                                            <?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="quantity[]" value="1" min="1"
                                        oninput="updateSubtotal(this)" required></td>
                                <td><input type="text" name="price_at_order[]" value="" readonly></td>
                                <td><input type="text" name="subtotal[]" value="0" readonly></td>
                                <td><button type="button" class="remove-item-button"
                                        onclick="removeItem(this)">削除</button></td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <select name="product_id[]" onchange="updateItemPrice(this)" required>
                                        <option value="">商品を選択</option>
                                        <?php foreach ($products as $product): ?>
                                        <option value="<?= htmlspecialchars($product['product_id'], ENT_QUOTES) ?>"
                                            data-price="<?= htmlspecialchars($product['price'], ENT_QUOTES) ?>"
                                            <?= ($product['product_id'] == $item['product_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="quantity[]"
                                        value="<?= htmlspecialchars($item['quantity'], ENT_QUOTES) ?>" min="1"
                                        oninput="updateSubtotal(this)" required></td>
                                <td><input type="text" name="price_at_order[]"
                                        value="<?= htmlspecialchars($item['price_at_order'], ENT_QUOTES) ?>" readonly>
                                </td>
                                <td><input type="text" name="subtotal[]"
                                        value="<?= htmlspecialchars($item['quantity'] * $item['price_at_order'], ENT_QUOTES) ?>"
                                        readonly></td>
                                <td><button type="button" class="remove-item-button"
                                        onclick="removeItem(this)">削除</button></td>
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
                </div>
                <button type="button" class="add-item-button" onclick="addItem()">アイテム追加</button>
            </div>

            <input type="hidden" name="items_json" id="itemsJsonInput">

            <div class="menu" style="margin-top: 30px; flex-direction: row; gap: 15px;">
                <button type="submit" class="save-button">保存</button>
                <button type="button" class="cancel-button"
                    onclick="location.href='order_detail.php?order_no=<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>'">キャンセル</button>
            </div>
        </form>
    </main>
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
            priceAtOrderInput.value = '';
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
                <td><input type="text" name="price_at_order[]" value="" readonly></td>
                <td><input type="text" name="subtotal[]" value="0" readonly></td>
                <td><button type="button" class="remove-item-button" onclick="removeItem(this)">削除</button></td>
            `;
        const newSelect = newRow.querySelector('select[name="product_id[]"]');
        if (newSelect.value) {
            updateItemPrice(newSelect);
        }
        calculateTotal();
    }

    function removeItem(buttonElement) {
        const row = buttonElement.closest('tr');
        if (document.querySelectorAll('#orderItemsTableBody tr').length > 1) {
            row.remove();
        } else {
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

    document.getElementById('orderEditForm').addEventListener('submit', function(event) {
        const items = [];
        document.querySelectorAll('#orderItemsTableBody tr').forEach(row => {
            const productId = row.querySelector('select[name="product_id[]"]').value;
            const quantity = row.querySelector('input[name="quantity[]"]').value;
            const priceAtOrder = row.querySelector('input[name="price_at_order[]"]').value;

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

    window.onload = function() {
        calculateTotal();
        document.querySelectorAll('#orderItemsTableBody tr').forEach(row => {
            const selectElement = row.querySelector('select[name="product_id[]"]');
            updateItemPrice(selectElement);
        });
    };
    </script>
</body>

</html>