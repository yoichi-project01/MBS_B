<?php
session_start();
require_once 'db_connect.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$customer_name = $_SESSION['temp_order_customer_name'] ?? '未選択';
$customer_no = $_SESSION['temp_order_customer_no'] ?? null;
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
                    'books'        => htmlspecialchars($item['books'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'order_volume' => max(0, intval($item['order_volume'] ?? 0)),
                    'price'        => max(0, intval($item['price'] ?? 0)),
                    'abstract'     => htmlspecialchars($item['abstract'] ?? '', ENT_QUOTES, 'UTF-8')
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

                $stmt_order = $pdo->prepare(
                    "INSERT INTO orders (customer_no, registration_date) VALUES (?, ?)"
                );
                $registration_date = date('Y-m-d');
                $stmt_order->execute([$customer_no, $registration_date]);
                $order_no = $pdo->lastInsertId();

                $stmt_item = $pdo->prepare(
                    "INSERT INTO order_items (order_no, books, order_volume, price, abstract) VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($order_items as $item) {
                    $stmt_item->execute([
                        $order_no,
                        $item['books'],
                        $item['order_volume'],
                        $item['price'],
                        $item['abstract']
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
<link rel="stylesheet" href="../style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a id="store-title">緑橋書店 受注管理システム</a>
            <nav class="nav">
                <a href="#">顧客情報</a>
                <a href="#">統計情報</a>
                <a href="order_history.php">注文書</a>
                <a href="#">納品書</a>
            </nav>
        </div>
    </header>
    <main class="main-content" style="max-width:900px;">
        <h1 class="section-title" style="margin-bottom: 18px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-plus" style="vertical-align: middle;">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="12" y1="18" x2="12" y2="12"></line>
                <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
            新規注文書作成
        </h1>

        <?php if (!empty($message)): ?>
            <div class="message success"><?= $message ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>

        <div class="menu" style="margin-bottom: 24px; flex-direction: row; gap: 32px;">
            <div>
                <strong>登録日:</strong>
                <?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES) ?>
            </div>
            <div>
                <strong>顧客名:</strong>
                <?= htmlspecialchars($customer_name, ENT_QUOTES) ?>
            </div>
            <div>
                <strong>顧客No:</strong>
                <?= htmlspecialchars($customer_no ?? '未選択', ENT_QUOTES) ?>
            </div>
        </div>

        <div class="item-management-section" style="width:100%;">
            <h3 style="margin-bottom:10px;">注文品目</h3>
            <div class="table-container" style="padding:0;">
                <table id="itemTable" style="width:100%;">
                    <thead>
                        <tr>
                            <th>書籍名</th>
                            <th>注文数</th>
                            <th>単価</th>
                            <th>摘要</th>
                            <th>小計</th>
                            <th></th>
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
            <div class="total-display" style="text-align:right; margin-top:10px;">合計金額: <span id="grandTotal">0</span> 円</div>
            <button type="button" id="addItemButton" class="add-item-button">品目を追加</button>
        </div>

        <div class="menu" style="margin-top: 32px; flex-direction: row; gap: 32px;">
            <button type="button" class="back-button" onclick="location.href='order_history.php'">注文書履歴に戻る</button>
            <form action="order_create.php" method="POST" style="display: inline;">
                <input type="hidden" name="action" value="save_order">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
                <button type="submit" class="save-button" id="saveOrderButton">注文書を登録</button>
            </form>
        </div>
    </main>

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
                    books: row.querySelector('input[name="books[]"]').value,
                    order_volume: row.querySelector('input[name="order_volume[]"]').value,
                    price: row.querySelector('input[name="price[]"]').value,
                    abstract: row.querySelector('textarea[name="abstract[]"]').value
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

        function addItem(item = { books: '', order_volume: 0, price: 0, abstract: '' }) {
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