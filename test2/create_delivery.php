<?php
session_start();

// --- GETパラメータから顧客情報を取得 ---
$customer_no = isset($_GET['customer_no']) ? (int)$_GET['customer_no'] : 0;
$customer_name = isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : '顧客未選択';

// 有効な顧客番号がなければ、顧客選択画面に戻る
if ($customer_no === 0) {
    header('Location: customer_selection.php');
    exit;
}

// --- ダミー注文明細データ生成関数 ---
/**
 * 指定された顧客IDに関連するダミーの注文明細データを生成する関数。
 * 納品書作成時に選択可能な注文品目として表示します。
 *
 * @param int $customerId 顧客ID
 * @return array 注文明細データの配列
 */
function getDummyOrderItemsForDelivery($customerId) {
    $dummyOrderItems = [];
    $baseOrderNo = 20240000;

    // 顧客IDごとに異なる注文アイテムを生成
    switch ($customerId) {
        case 1001: // 東京商事
            $dummyOrderItems[] = ['order_item_id' => 10001, 'order_no' => $baseOrderNo + 1, 'product_name' => '週刊BCN 10月号', 'quantity' => 1, 'unit_price' => 1100, 'delivery_status' => '未納'];
            $dummyOrderItems[] = ['order_item_id' => 10002, 'order_no' => $baseOrderNo + 1, 'product_name' => '日経コンピュータ 10月号', 'quantity' => 2, 'unit_price' => 1000, 'delivery_status' => '未納'];
            $dummyOrderItems[] = ['order_item_id' => 10003, 'order_no' => $baseOrderNo + 2, 'product_name' => 'Software Design 11月号', 'quantity' => 1, 'unit_price' => 1200, 'delivery_status' => '一部納品 (1/1)']; // 例として一部納品済み
            $dummyOrderItems[] = ['order_item_id' => 10004, 'order_no' => $baseOrderNo + 3, 'product_name' => 'WEB+DB PRESS Vol.135', 'quantity' => 1, 'unit_price' => 1500, 'delivery_status' => '未納'];
            break;
        case 1002: // 大阪書店
            $dummyOrderItems[] = ['order_item_id' => 10005, 'order_no' => $baseOrderNo + 4, 'product_name' => 'PHPフレームワーク Laravel', 'quantity' => 5, 'unit_price' => 3000, 'delivery_status' => '未納'];
            $dummyOrderItems[] = ['order_item_id' => 10006, 'order_no' => $baseOrderNo + 5, 'product_name' => 'SQL実践入門', 'quantity' => 3, 'unit_price' => 2800, 'delivery_status' => '一部納品 (1/3)'];
            break;
        case 1003: // 名古屋出版
            $dummyOrderItems[] = ['order_item_id' => 10007, 'order_no' => $baseOrderNo + 6, 'product_name' => 'JavaScript本格入門', 'quantity' => 1, 'unit_price' => 2500, 'delivery_status' => '未納'];
            break;
        default:
            // その他の顧客には、簡単なダミーデータをいくつか生成
            $numItems = rand(1, 3);
            for ($i = 0; $i < $numItems; $i++) {
                $order_id_suffix = $customerId * 10 + $i;
                $dummyOrderItems[] = [
                    'order_item_id' => 20000 + $order_id_suffix,
                    'order_no' => $baseOrderNo + $order_id_suffix,
                    'product_name' => 'ダミー商品 ' . ($i + 1),
                    'quantity' => rand(1, 10),
                    'unit_price' => rand(500, 5000),
                    'delivery_status' => (rand(0,1) == 0 ? '未納' : '一部納品 (1/2)')
                ];
            }
            break;
    }
    return $dummyOrderItems;
}

$orderItems = getDummyOrderItemsForDelivery($customer_no);

// フォームが送信された場合の処理 (今回はダミーなので、成功メッセージを表示するだけ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 送信されたデータを表示用に整形
    $selected_items = [];
    $total_quantity = 0;
    $sub_total_amount = 0;

    foreach ($_POST as $key => $value) {
        if (strpos($key, 'selected_item_') === 0 && $value === 'on') {
            $index = str_replace('selected_item_', '', $key);
            // 隠しフィールドから詳細情報を取得 (ダミーなので、orderItemsから取得)
            foreach ($orderItems as $item) {
                // order_item_id が一致するものを探す
                // 注意: 実際のアプリケーションでは、hiddenフィールドのorder_item_idを使うべき
                // ここでは便宜上、元の$orderItems配列のインデックスと一致すると仮定しています
                // もしorder_item_idがPOSTされていれば、それを使う
                $posted_order_item_id = isset($_POST['order_item_id_' . $index]) ? (int)$_POST['order_item_id_' . $index] : 0;
                if ($item['order_item_id'] === $posted_order_item_id) {
                    $item_quantity = (int)$_POST['quantity_' . $index];
                    $item_amount = $item_quantity * $item['unit_price'];

                    $selected_items[] = [
                        'product_name' => $item['product_name'],
                        'quantity' => $item_quantity,
                        'unit_price' => $item['unit_price'],
                        'amount' => $item_amount
                    ];
                    $total_quantity += $item_quantity;
                    $sub_total_amount += $item_amount;
                    break;
                }
            }
        }
    }

    $tax_rate = 0.10; // 10%
    $tax_amount = floor($sub_total_amount * $tax_rate);
    $grand_total = $sub_total_amount + $tax_amount;

    $_SESSION['delivery_success_message'] = "納品書が作成されました！ (ダミー処理)<br>"
                                          . "選択された品目数: " . count($selected_items) . "件<br>"
                                          . "合計数量: " . $total_quantity . "<br>"
                                          . "合計金額 (税抜): &yen;" . number_format($sub_total_amount) . "<br>"
                                          . "税額: &yen;" . number_format($tax_amount) . "<br>"
                                          . "合計金額 (税込): &yen;" . number_format($grand_total);
    // 通常はデータベースに保存後、納品書詳細画面などにリダイレクト
    // header('Location: delivery_detail.php?delivery_no=XXXXX');
    // exit;
}

$success_message = '';
if (isset($_SESSION['delivery_success_message'])) {
    $success_message = $_SESSION['delivery_success_message'];
    unset($_SESSION['delivery_success_message']);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書作成</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8em;
        }
        .nav-buttons button {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 10px 15px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }
        .nav-buttons button:hover {
            background-color: #4cae4c;
        }
        /* --- コンテナを相対位置にする（子要素の絶対位置の基準） --- */
        .container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative; /* 子要素の絶対位置指定の基準 */
        }
        /* --- 「前の画面に」ボタンの新しいスタイル --- */
        .back-button-container {
            position: absolute; /* 親要素 .container に対する絶対位置 */
            top: 20px; /* .container のパディングに合わせて調整 */
            left: 30px; /* .container のパディングに合わせて調整 */
            z-index: 10; /* 他の要素より手前に表示 */
        }
        .back-button-container .back-button {
            background-color: #dc3545; /* 赤色 */
            color: white;
            border: 1px solid #dc3545;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .back-button-container .back-button:hover {
            background-color: #c82333; /* 濃い赤 */
            border-color: #bd2130;
        }
        /* --- コンテンツエリアのスタイル --- */
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            /* 「前の画面に」ボタンのスペースを確保するため、左にパディング */
            padding-left: 120px; /* ボタンの幅 + 余白 */
            box-sizing: border-box; /* パディングを幅に含める */
        }
        .form-header h2 {
            margin: 0;
            font-size: 1.6em;
            color: #333;
        }
        .top-buttons {
            display: flex;
            gap: 10px;
        }
        .top-buttons .save-button {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            transition: background-color 0.3s, border-color 0.3s;
        }
        .top-buttons .save-button:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        .customer-info {
            background-color: #e9f5e9; /* 薄い緑色 */
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .customer-info span {
            font-size: 1.1em;
            color: #333;
            font-weight: bold;
        }
        .date-display {
            background-color: #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #555;
        }

        .delivery-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .delivery-items-table th, .delivery-items-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .delivery-items-table th {
            background-color: #e9ecef;
            font-weight: bold;
            color: #555;
            white-space: nowrap; /* ヘッダーの折り返しを防ぐ */
        }
        .delivery-items-table td {
            vertical-align: middle;
        }
        .delivery-items-table input[type="number"] {
            width: 60px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            text-align: right;
        }
        .delivery-items-table input[type="checkbox"] {
            transform: scale(1.3); /* チェックボックスを少し大きく表示 */
            margin-right: 5px;
            vertical-align: middle;
        }
        .delivery-items-table .text-right {
            text-align: right;
        }
        .delivery-items-table .product-name {
            min-width: 200px; /* 品名列の最小幅を確保 */
        }

        .summary-section {
            display: flex;
            justify-content: flex-end; /* 右寄せ */
            margin-top: 30px;
        }
        .summary-table {
            width: 350px; /* 画像に合わせて幅を調整 */
            border-collapse: collapse;
        }
        .summary-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }
        .summary-table .label {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
            width: 120px; /* ラベル列の幅 */
        }
        .summary-table .value {
            text-align: right;
            font-weight: bold;
            color: #333;
        }
        .summary-table .grand-total {
            background-color: #e9f5e9; /* 薄い緑 */
            font-size: 1.2em;
            color: #0056b3;
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>緑橋書店 受注管理システム</h1>
        <div class="nav-buttons">
            <button onclick="location.href='#'">顧客情報</button>
            <button onclick="location.href='#'">統計情報</button>
            <button onclick="location.href='#'">注文書</button>
            <button onclick="location.href='delivery_history.php'">納品書</button>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="message"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="back-button-container">
            <button type="button" class="back-button" onclick="location.href='customer_selection.php'">前の画面に</button>
        </div>

        <div class="form-header">
            <h2></h2>
            <div class="top-buttons">
                <button type="submit" form="deliveryForm" class="save-button">保存</button>
            </div>
        </div>

        <div class="customer-info">
            <span>顧客名（企業名含む）：<?= $customer_name ?></span>
            <span class="date-display">登録日：<?= date('Y/m/d') ?></span>
        </div>

        <form id="deliveryForm" method="POST" action="create_delivery.php?customer_no=<?= $customer_no ?>&customer_name=<?= urlencode($customer_name) ?>">
            <table class="delivery-items-table">
                <thead>
                    <tr>
                        <th>選択</th>
                        <th>品名</th>
                        <th>数量</th>
                        <th>単価</th>
                        <th>金額</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orderItems)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #777;">この顧客には未納の注文品目がありません。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orderItems as $index => $item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_item_<?= $index ?>" id="selected_item_<?= $index ?>" onchange="calculateTotals()">
                                    <input type="hidden" name="order_item_id_<?= $index ?>" value="<?= htmlspecialchars($item['order_item_id']) ?>">
                                    <input type="hidden" id="unit_price_<?= $index ?>" value="<?= htmlspecialchars($item['unit_price']) ?>">
                                </td>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td>
                                    <input type="number" name="quantity_<?= $index ?>" id="quantity_<?= $index ?>" value="<?= htmlspecialchars($item['quantity']) ?>" min="0" onchange="calculateTotals()" oninput="this.value = Math.abs(this.value)">
                                </td>
                                <td class="text-right">&yen;<span id="display_unit_price_<?= $index ?>"><?= number_format(htmlspecialchars($item['unit_price'])) ?></span></td>
                                <td class="text-right amount-cell" id="amount_<?= $index ?>">&yen;0</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td class="label">合計</td>
                        <td class="value" id="total_quantity_display">0</td>
                        <td class="value" id="sub_total_amount_display">&yen;0</td>
                    </tr>
                    <tr>
                        <td class="label">税率</td>
                        <td class="value">10%</td>
                        <td class="value" id="tax_amount_display">&yen;0</td>
                    </tr>
                    <tr>
                        <td class="label grand-total">合計（税込）</td>
                        <td colspan="2" class="value grand-total" id="grand_total_amount_display">&yen;0</td>
                    </tr>
                </table>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotals(); // ページ読み込み時に合計を計算
        });

        function calculateTotals() {
            let totalQuantity = 0;
            let subTotalAmount = 0;
            const taxRate = 0.10; // 10%

            // テーブルの行を全て取得
            const rows = document.querySelectorAll('.delivery-items-table tbody tr');

            rows.forEach((row, index) => {
                // 各行のチェックボックス、数量入力、単価、金額表示セルを取得
                const checkbox = row.querySelector(`#selected_item_${index}`);
                const quantityInput = row.querySelector(`#quantity_${index}`);
                const unitPriceHidden = row.querySelector(`#unit_price_${index}`); // hiddenフィールドから単価を取得
                const amountCell = row.querySelector(`#amount_${index}`);

                let unitPrice = 0;
                if (unitPriceHidden) {
                    unitPrice = parseInt(unitPriceHidden.value);
                    if (isNaN(unitPrice)) {
                        unitPrice = 0;
                    }
                }

                let quantity = 0;
                if (quantityInput) {
                    quantity = parseInt(quantityInput.value);
                    // 数量がNaNまたは負の値の場合、0にリセット
                    if (isNaN(quantity) || quantity < 0) {
                        quantity = 0;
                        quantityInput.value = 0;
                    }
                }

                let itemAmount = 0;
                if (checkbox && checkbox.checked) {
                    itemAmount = quantity * unitPrice;
                    totalQuantity += quantity;
                    subTotalAmount += itemAmount;
                }
                
                // 品目ごとの金額を表示
                if (amountCell) {
                    amountCell.textContent = `¥${itemAmount.toLocaleString()}`;
                }
            });

            // 合計計算
            const taxAmount = Math.floor(subTotalAmount * taxRate);
            const grandTotalAmount = subTotalAmount + taxAmount;

            // 各合計値を表示要素にセット
            document.getElementById('total_quantity_display').textContent = totalQuantity.toLocaleString();
            document.getElementById('sub_total_amount_display').textContent = `¥${subTotalAmount.toLocaleString()}`;
            document.getElementById('tax_amount_display').textContent = `¥${taxAmount.toLocaleString()}`;
            document.getElementById('grand_total_amount_display').textContent = `¥${grandTotalAmount.toLocaleString()}`;
        }
    </script>
</body>
</html>