<?php
session_start();

// --- GETパラメータから納品書番号を取得 ---
$delivery_no = isset($_GET['delivery_no']) ? (int)$_GET['delivery_no'] : 0;

// 有効な納品書番号がなければ、納品書履歴画面に戻る
if ($delivery_no === 0) {
    header('Location: delivery_history.php');
    exit;
}

// --- ダミー納品書詳細データ生成関数 ---
/**
 * 指定された納品書番号に関連するダミーの納品書詳細データを生成する関数。
 *
 * @param int $deliveryNo 納品書番号
 * @return array 納品書詳細データの配列、または空の配列
 */
function generateDummyDeliveryDetail($deliveryNo) {
    $deliveryDetails = [];

    // 固定の顧客データ (delivery_history.php と合わせる)
    $customers = [
        ['customer_no' => 101, 'customer_name' => 'テスト商事', 'store_name' => '新宿支店'],
        ['customer_no' => 102, 'customer_name' => 'サンプルストア', 'store_name' => '緑橋本店'],
        ['customer_no' => 103, 'customer_name' => '架空出版', 'store_name' => ''],
        ['customer_no' => 104, 'customer_name' => 'デモ会社', 'store_name' => ''],
        ['customer_no' => 105, 'customer_name' => '未来書店', 'store_name' => '大阪中央店'],
        ['customer_no' => 106, 'customer_name' => 'プロトタイプ株式会社', 'store_name' => ''],
        ['customer_no' => 107, 'customer_name' => 'アイデア工房', 'store_name' => ''],
        ['customer_no' => 108, 'customer_name' => 'クリエイティブ・ラボ', 'store_name' => ''],
        ['customer_no' => 109, 'customer_name' => '技術研究所', 'store_name' => '渋谷支社'],
        ['customer_no' => 110, 'customer_name' => '学び舎出版', 'store_name' => ''],
        ['customer_no' => 111, 'customer_name' => 'クリエイティブワークス', 'store_name' => ''],
        ['customer_no' => 112, 'customer_name' => 'デジタルコンテンツラボ', 'store_name' => ''],
        ['customer_no' => 113, 'customer_name' => 'イノベーションファクトリー', 'store_name' => '']
    ];

    // delivery_history.phpのgenerateDummyDeliveries関数から顧客情報を逆引きするようなロジック
    // 実際にはDBから取得する
    $customer_info = null;
    $delivery_index = $deliveryNo - 20230000; // 納品書番号からインデックスを推定
    if ($delivery_index >= 1 && $delivery_index <= 100) { // 100件のダミーデータがあるため
        $customerIndex = ($delivery_index - 1) % count($customers);
        $customer_info = $customers[$customerIndex];
    }

    if ($customer_info) {
        // ダミーの納品日を生成 (例: 納品書番号の末尾で日付を変える)
        $day_offset = $deliveryNo % 30; // 日付をばらけさせる
        $delivery_date = date('Y/m/d', strtotime("2023-01-01 +{$day_offset} days"));

        // ダミーの納品書明細を生成 (2～4品目)
        $num_items = rand(2, 4);
        $items = [];
        $sub_total_amount = 0;

        for ($i = 0; $i < $num_items; $i++) {
            $product_name_prefix = ['書籍', '雑誌', 'DVD', '文具', 'ソフトウェア'][array_rand(['書籍', '雑誌', 'DVD', '文具', 'ソフトウェア'])];
            $product_name = $product_name_prefix . ' ' . sprintf('%03d', rand(1, 999));
            $quantity = rand(1, 5);
            $unit_price = rand(500, 3000);
            $amount = $quantity * $unit_price;
            $sub_total_amount += $amount;

            $items[] = [
                'product_name' => $product_name,
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'amount' => $amount
            ];
        }

        $tax_rate = 0.10;
        $tax_amount = floor($sub_total_amount * $tax_rate);
        $grand_total = $sub_total_amount + $tax_amount;

        $deliveryDetails = [
            'delivery_no' => $deliveryNo,
            'customer_name' => $customer_info['customer_name'],
            'store_name' => $customer_info['store_name'],
            'delivery_date' => $delivery_date,
            'items' => $items,
            'sub_total_amount' => $sub_total_amount,
            'tax_amount' => $tax_amount,
            'grand_total' => $grand_total
        ];
    }

    return $deliveryDetails;
}

// 納品書詳細データを取得
$delivery = generateDummyDeliveryDetail($delivery_no);

// データが存在しない場合（例: 20230001-20230005の範囲外の番号が指定された場合）
if (empty($delivery)) {
    // エラーメッセージをセッションに保存してリダイレクト、またはエラーページ表示
    $_SESSION['error_message'] = '指定された納品書番号のデータが見つかりませんでした。';
    header('Location: delivery_history.php');
    exit;
}

/**
 * 顧客名と企業名を整形して表示する関数。
 * 企業名があり、それが顧客名に含まれていない場合、"企業名 顧客名" の形式で返します。
 *
 * @param string $customer_name 顧客名
 * @param string $store_name 企業名（店舗名）
 * @return string 整形された顧客表示名
 */
function formatCustomerName($customer_name, $store_name) {
    if (!empty($store_name) && mb_strpos($customer_name, $store_name) === false) {
        return htmlspecialchars($store_name) . ' ' . htmlspecialchars($customer_name);
    }
    return htmlspecialchars($customer_name);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書詳細</title>
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
        .container {
            max-width: 800px; /* 詳細画面なので少し幅を狭める */
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative; /* 「戻る」ボタンの基準 */
        }
        .back-button-container {
            position: absolute;
            top: 20px;
            left: 30px;
            z-index: 10;
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
            background-color: #c82333;
            border-color: #bd2130;
        }
        .detail-header {
            text-align: center;
            margin-bottom: 30px;
            padding-left: 100px; /* 戻るボタンのスペース */
        }
        .detail-header h2 {
            font-size: 2em;
            color: #333;
            margin-bottom: 5px;
        }
        .detail-header p {
            font-size: 1.1em;
            color: #555;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .info-block {
            flex: 1;
        }
        .info-block p {
            margin: 5px 0;
            font-size: 1em;
            color: #444;
        }
        .info-block strong {
            display: inline-block;
            width: 80px; /* ラベルの幅を固定 */
            color: #333;
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
            white-space: nowrap;
        }
        .delivery-items-table td {
            vertical-align: middle;
        }
        .text-right {
            text-align: right;
        }
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        .summary-table {
            width: 300px;
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
            width: 100px;
        }
        .summary-table .value {
            text-align: right;
            font-weight: bold;
            color: #333;
        }
        .summary-table .grand-total {
            background-color: #e9f5e9;
            font-size: 1.2em;
            color: #0056b3;
        }
        .no-data-message {
            text-align: center;
            color: #777;
            padding: 20px;
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
        <div class="back-button-container">
            <button type="button" class="back-button" onclick="location.href='delivery_history.php'">一覧に戻る</button>
        </div>

        <?php if (!empty($delivery)): ?>
            <div class="detail-header">
                <h2>納品書</h2>
                <p>No. <?= htmlspecialchars($delivery['delivery_no']) ?></p>
            </div>

            <div class="info-section">
                <div class="info-block">
                    <p><strong>顧客名:</strong> <?= formatCustomerName($delivery['customer_name'], $delivery['store_name']) ?></p>
                </div>
                <div class="info-block" style="text-align: right;">
                    <p><strong>納品日:</strong> <?= htmlspecialchars($delivery['delivery_date']) ?></p>
                </div>
            </div>

            <table class="delivery-items-table">
                <thead>
                    <tr>
                        <th>品名</th>
                        <th>数量</th>
                        <th>単価</th>
                        <th>金額</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($delivery['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="text-right"><?= number_format(htmlspecialchars($item['quantity'])) ?></td>
                            <td class="text-right">&yen;<?= number_format(htmlspecialchars($item['unit_price'])) ?></td>
                            <td class="text-right">&yen;<?= number_format(htmlspecialchars($item['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td class="label">小計</td>
                        <td class="value">&yen;<?= number_format(htmlspecialchars($delivery['sub_total_amount'])) ?></td>
                    </tr>
                    <tr>
                        <td class="label">消費税 (10%)</td>
                        <td class="value">&yen;<?= number_format(htmlspecialchars($delivery['tax_amount'])) ?></td>
                    </tr>
                    <tr>
                        <td class="label grand-total">合計金額</td>
                        <td class="value grand-total">&yen;<?= number_format(htmlspecialchars($delivery['grand_total'])) ?></td>
                    </tr>
                </table>
            </div>
        <?php else: ?>
            <div class="no-data-message">
                <p>この納品書番号のデータは見つかりませんでした。</p>
                <p><button onclick="location.href='delivery_history.php'">納品書履歴に戻る</button></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>