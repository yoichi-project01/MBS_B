<?php
session_start();
require_once 'db_connect.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_name = $_SESSION['temp_order_customer_name'] ?? '未選択';
$customer_no = $_SESSION['temp_order_customer_no'] ?? null;

// $registration_date = date('Y-m-d'); // registration_date を使用しない場合はこの行は削除またはコメントアウトのまま

$order_items = $_SESSION['temp_order_items'] ?? [];

$message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "不正なリクエストです。";
        if (isset($_POST['update_items_js']) || (isset($_POST['action']) && $_POST['action'] === 'save_order')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'CSRFトークンが無効です。']);
            exit;
        }
    }

    if (isset($_POST['update_items_js'])) {
        $updated_items_raw = json_decode($_POST['items_json'], true);
        $sanitized_items = [];
        if (is_array($updated_items_raw)) {
            foreach ($updated_items_raw as $item) {
                $sanitized_items[] = [
                    'books'        => htmlspecialchars($item['books'] ?? '', ENT_QUOTES, 'UTF-8'), // 'book_name' -> 'books'
                    'order_volume' => max(0, intval($item['order_volume'] ?? 0)),
                    'price'        => max(0, intval($item['price'] ?? 0)),
                    'abstract'     => htmlspecialchars($item['abstract'] ?? '', ENT_QUOTES, 'UTF-8') // 'description' -> 'abstract'
                ];
            }
        }
        $_SESSION['temp_order_items'] = $sanitized_items;

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => '品目データが更新されました。', 'items' => $sanitized_items]);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'save_order') {
        if ($customer_no === null) {
            $error_message = "顧客が選択されていません。";
        } elseif (empty($order_items)) {
            $error_message = "注文品目がありません。";
        } else {
            try {
                $pdo->beginTransaction();

                // orders テーブルに注文書を挿入 (registration_date を使用する場合としない場合で調整)
                // mbs.sql に registration_date があるので、挿入時に含めます
                $stmt_order = $pdo->prepare(
                    "INSERT INTO orders (customer_no, registration_date) VALUES (?, ?)" // registration_date を含める
                );
                // 登録日を今日の日付で設定
                $registration_date = date('Y-m-d');
                $stmt_order->execute([$customer_no, $registration_date]); // registration_date を渡す
                $order_no = $pdo->lastInsertId();

                // order_items テーブルに各品目を挿入 (カラム名を修正)
                $stmt_item = $pdo->prepare(
                    "INSERT INTO order_items (order_no, books, order_volume, price, abstract) VALUES (?, ?, ?, ?, ?)" // 'book_name', 'description' -> 'books', 'abstract'
                );
                foreach ($order_items as $item) {
                    $stmt_item->execute([
                        $order_no,
                        $item['books'],       // 'book_name' -> 'books'
                        $item['order_volume'],
                        $item['price'],
                        $item['abstract']     // 'description' -> 'abstract'
                    ]);
                }

                $pdo->commit();
                $message = "注文書が正常に登録されました。注文No: " . $order_no;

                unset($_SESSION['temp_order_customer_name']);
                unset($_SESSION['temp_order_items']);
                unset($_SESSION['temp_order_customer_no']);
                
                header('Location: order_history.php?message=' . urlencode($message));
                exit;

            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("注文書登録エラー: " . $e->getMessage());
                $error_message = "注文書の登録中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>注文書作成画面</title>
<style>
/* CSSは変更なし */
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
.section-title {
    font-size: 1.5em;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    color: #333;
}
.section-title svg {
    margin-right: 10px;
}
.info-section {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.info-item {
    flex: 1;
    padding: 0 10px;
}
.info-item label {
    font-weight: bold;
    color: #555;
    display: block;
    margin-bottom: 5px;
}
.info-item span {
    display: block;
    padding: 8px 0;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding-left: 10px;
}
.item-management-section {
    margin-top: 20px;
}
.item-management-section h3 {
    margin-bottom: 10px;
    color: #333;
}
.item-table-container {
    overflow-x: auto;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.item-table {
    width: 100%;
    border-collapse: collapse;
}
.item-table th, .item-table td {
    border: 1px solid #eee;
    padding: 8px;
    text-align: left;
    white-space: nowrap;
}
.item-table th {
    background-color: #f2f2f2;
    font-weight: bold;
    color: #555;
}
.item-table td input[type="text"],
.item-table td input[type="number"],
.item-table td textarea {
    width: 95%;
    padding: 6px;
    border: 1px solid #ccc;
    border-radius: 3px;
    box-sizing: border-box;
}
.item-table td textarea {
    resize: vertical;
    min-height: 40px;
}
.item-table td .delete-button {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85em;
    transition: background-color 0.3s ease;
}
.item-table td .delete-button:hover {
    background-color: #c82333;
}
.total-display {
    text-align: right;
    font-size: 1.2em;
    font-weight: bold;
    margin-top: 10px;
    padding-right: 10px;
}
.add-item-button {
    background-color: #17a2b8;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background-color 0.3s ease;
    margin-top: 10px;
}
.add-item-button:hover {
    background-color: #138496;
}
.action-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}
.action-buttons button {
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1em;
    font-weight: bold;
    transition: background-color 0.3s ease;
}
.action-buttons .back-button {
    background-color: #6c757d;
    color: white;
}
.action-buttons .back-button:hover {
    background-color: #5a6268;
}
.action-buttons .save-button {
    background-color: #28a745;
    color: white;
}
.action-buttons .save-button:hover {
    background-color: #218838;
}
.message {
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 5px;
    font-weight: bold;
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
            <div class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-plus">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <line x1="9" y1="15" x2="15" y2="15"></line>
                </svg>
                新規注文書作成
            </div>

            <?php if (!empty($message)): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="message error"><?= $error_message ?></div>
            <?php endif; ?>

            <div class="info-section">
                <div class="info-item">
                    <label>登録日:</label>
                    <span><?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES) ?></span> <!-- PHP側で取得した今日の日付を表示 -->
                </div>
                <div class="info-item">
                    <label>顧客名:</label>
                    <span><?= htmlspecialchars($customer_name, ENT_QUOTES) ?></span>
                </div>
                <div class="info-item">
                    <label>顧客No:</label>
                    <span><?= htmlspecialchars($customer_no ?? '未選択', ENT_QUOTES) ?></span>
                </div>
            </div>

            <div class="item-management-section">
                <h3>注文品目</h3>
                <div class="item-table-container">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>書籍名</th>
                                <th>注文数</th>
                                <th>単価</th>
                                <th>摘要</th>
                                <th>小計</th>
                                <th></th> <!-- 削除ボタン用 -->
                            </tr>
                        </thead>
                        <tbody id="itemTableBody">
                            <?php if (empty($order_items)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #777;">品目がありません。<br>「品目を追加」ボタンで品目を追加してください。</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($order_items as $index => $item): ?>
                                    <tr data-index="<?= $index ?>">
                                        <!-- mbs.sql のカラム名に合わせて books と abstract に修正 -->
                                        <td><input type="text" name="books[]" value="<?= htmlspecialchars($item['books'] ?? '', ENT_QUOTES) ?>" placeholder="書籍名"></td>
                                        <td><input type="number" name="order_volume[]" value="<?= htmlspecialchars($item['order_volume'] ?? 0, ENT_QUOTES) ?>" min="0"></td>
                                        <td><input type="number" name="price[]" value="<?= htmlspecialchars($item['price'] ?? 0, ENT_QUOTES) ?>" min="0"></td>
                                        <td><textarea name="abstract[]" placeholder="備考・要約"><?= htmlspecialchars($item['abstract'] ?? '', ENT_QUOTES) ?></textarea></td>
                                        <td class="sub-total"></td>
                                        <td><button type="button" class="delete-button" data-index="<?= $index ?>">削除</button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="total-display">合計金額: <span id="grandTotal">0</span> 円</div>
                <button type="button" id="addItemButton" class="add-item-button">品目を追加</button>
            </div>

            <div class="action-buttons">
                <button type="button" class="back-button" onclick="location.href='order_history.php'">注文書履歴に戻る</button>
                <form action="order_create.php" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="save_order">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                    <button type="submit" class="save-button" id="saveOrderButton">注文書を登録</button>
                </form>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const itemTableBody = document.getElementById('itemTableBody');
        const addItemButton = document.getElementById('addItemButton');
        const grandTotalSpan = document.getElementById('grandTotal');
        const saveOrderButton = document.getElementById('saveOrderButton');

        const csrfToken = "<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>";

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // 行のHTMLテンプレート (mbs.sql のカラム名に合わせて books と abstract に修正)
        function getItemRowHtml(item = { books: '', order_volume: 0, price: 0, abstract: '' }, index) {
            return `
                <tr data-index="${index}">
                    <td><input type="text" name="books[]" value="${item.books}" placeholder="書籍名"></td>
                    <td><input type="number" name="order_volume[]" value="${item.order_volume}" min="0"></td>
                    <td><input type="number" name="price[]" value="${item.price}" min="0"></td>
                    <td><textarea name="abstract[]" placeholder="備考・要約">${item.abstract}</textarea></td>
                    <td class="sub-total"></td>
                    <td><button type="button" class="delete-button" data-index="${index}">削除</button></td>
                </tr>
            `;
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            itemTableBody.querySelectorAll('tr').forEach(row => {
                if (row.querySelector('td[colspan="6"]')) {
                    return;
                }
                const volumeInput = row.querySelector('input[name="order_volume[]"]');
                const priceInput = row.querySelector('input[name="price[]"]');
                const subTotalCell = row.querySelector('.sub-total');

                let volume = parseInt(volumeInput ? volumeInput.value : 0) || 0;
                let price = parseInt(priceInput ? priceInput.value : 0) || 0;

                let subTotal = volume * price;
                subTotalCell.textContent = subTotal.toLocaleString();
                grandTotal += subTotal;
            });
            grandTotalSpan.textContent = grandTotal.toLocaleString();
        }

        const updateSessionItems = debounce(async () => {
            const itemsData = [];
            itemTableBody.querySelectorAll('tr').forEach(row => {
                if (row.querySelector('td[colspan="6"]')) {
                    return;
                }
                itemsData.push({
                    books: row.querySelector('input[name="books[]"]').value, // 'book_name' -> 'books'
                    order_volume: row.querySelector('input[name="order_volume[]"]').value,
                    price: row.querySelector('input[name="price[]"]').value,
                    abstract: row.querySelector('textarea[name="abstract[]"]').value // 'description' -> 'abstract'
                });
            });

            const customerNo = "<?= $customer_no ?>";
            if (!customerNo) {
                 console.warn("顧客が選択されていないため、品目データはセッションに保存されません。");
                 return;
            }

            try {
                const response = await fetch('order_create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        'update_items_js': '1',
                        'items_json': JSON.stringify(itemsData),
                        'csrf_token': csrfToken
                    })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    console.log('品目データがセッションに自動保存されました。');
                } else {
                    console.error('品目自動保存エラー:', result.message);
                }
            } catch (error) {
                console.error('品目自動保存通信エラー:', error);
            }
        }, 800);

        function addItem(item = { books: '', order_volume: 0, price: 0, abstract: '' }) { // mbs.sqlに合わせて初期値を修正
            const noItemRow = itemTableBody.querySelector('td[colspan="6"]');
            if (noItemRow) {
                noItemRow.parentElement.remove();
            }

            const existingRows = Array.from(itemTableBody.children);
            const newIndex = existingRows.length > 0 ? Math.max(...existingRows.map(row => parseInt(row.dataset.index) || 0)) + 1 : 0;
            
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-index', newIndex);
            newRow.innerHTML = getItemRowHtml(item, newIndex);
            itemTableBody.appendChild(newRow);
            
            attachEventListenersToRow(newRow);
            calculateGrandTotal();
            updateSessionItems();
        }

        function attachEventListenersToRow(row) {
            const inputs = row.querySelectorAll('input[type="text"], input[type="number"], textarea');
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    calculateGrandTotal();
                    updateSessionItems();
                });
            });

            const deleteButton = row.querySelector('.delete-button');
            if (deleteButton) {
                deleteButton.addEventListener('click', () => {
                    if (confirm('この品目を削除しますか？')) {
                        row.remove();
                        calculateGrandTotal();
                        
                        if (itemTableBody.rows.length === 0) {
                            itemTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: #777;">品目がありません。<br>「品目を追加」ボタンで品目を追加してください。</td></tr>`;
                        }
                        updateSessionItems();
                    }
                });
            }
        }

        itemTableBody.querySelectorAll('tr').forEach(row => {
            if (row.querySelector('td[colspan="6"]')) {
                return;
            }
            attachEventListenersToRow(row);
        });

        addItemButton.addEventListener('click', () => addItem());

        calculateGrandTotal();

        const customerNo = "<?= $customer_no ?>";
        if (!customerNo || customerNo === 'null') {
            saveOrderButton.disabled = true;
            console.log("顧客が選択されていないため、注文書登録ボタンは無効です。");
        }
    });
</script>
</body>
</html>