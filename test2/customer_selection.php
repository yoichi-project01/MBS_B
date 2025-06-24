<?php
session_start();

// --- ダミー顧客データ生成関数 ---
/**
 * ダミーの顧客データを生成し、検索クエリに基づいてフィルタリングする関数。
 *
 * @param string $search_term 検索文字列 (顧客名または企業名)
 * @return array フィルタリングされた顧客データの配列
 */
function getDummyCustomers($search_term) {
    $allCustomers = [
        ['customer_no' => 1001, 'customer_name' => '東京商事', 'store_name' => ''],
        ['customer_no' => 1002, 'customer_name' => '大阪書店', 'store_name' => '梅田店'],
        ['customer_no' => 1003, 'customer_name' => '名古屋出版', 'store_name' => ''],
        ['customer_no' => 1004, 'customer_name' => '福岡ストア', 'store_name' => '天神支店'],
        ['customer_no' => 1005, 'customer_name' => '札幌印刷', 'store_name' => ''],
        ['customer_no' => 1006, 'customer_name' => '横浜ブックス', 'store_name' => '中華街店'],
        ['customer_no' => 1007, 'customer_name' => '京都古書', 'store_name' => '本店'],
        ['customer_no' => 1008, 'customer_name' => '神戸文具', 'store_name' => '三宮店'],
        ['customer_no' => 1009, 'customer_name' => '広島カンパニー', 'store_name' => ''],
        ['customer_no' => 1010, 'customer_name' => '仙台メディア', 'store_name' => ''],
        ['customer_no' => 1011, 'customer_name' => '沖縄物産', 'store_name' => '那覇支店'],
        ['customer_no' => 1012, 'customer_name' => '高松情報', 'store_name' => ''],
        ['customer_no' => 1013, 'customer_name' => '金沢アート', 'store_name' => ''],
        ['customer_no' => 1014, 'customer_name' => '鹿児島テック', 'store_name' => ''],
        ['customer_no' => 1015, 'customer_name' => '静岡システム', 'store_name' => '静岡本社'],
        ['customer_no' => 1016, 'customer_name' => '岡山エンジニア', 'store_name' => ''],
        ['customer_no' => 1017, 'customer_name' => '千葉デザイン', 'store_name' => ''],
        ['customer_no' => 1018, 'customer_name' => '奈良観光', 'store_name' => ''],
        ['customer_no' => 1019, 'customer_name' => '滋賀フーズ', 'store_name' => ''],
        ['customer_no' => 1020, 'customer_name' => '和歌山リゾート', 'store_name' => '白浜支店'],
    ];

    $filteredCustomers = [];
    $search_term_lower = mb_strtolower($search_term, 'UTF-8');

    foreach ($allCustomers as $customer) {
        $customer_name_lower = mb_strtolower($customer['customer_name'], 'UTF-8');
        $store_name_lower = mb_strtolower($customer['store_name'], 'UTF-8');

        // 検索クエリが空の場合、または顧客名・企業名にクエリが含まれる場合
        if (empty($search_term) ||
            mb_strpos($customer_name_lower, $search_term_lower) !== false ||
            mb_strpos($store_name_lower, $search_term_lower) !== false) {
            $filteredCustomers[] = $customer;
        }
    }

    // 顧客番号でソート
    usort($filteredCustomers, function($a, $b) {
        return $a['customer_no'] <=> $b['customer_no'];
    });

    return $filteredCustomers;
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$customers = getDummyCustomers($search_term);

// 顧客名に企業名が含まれているかチェックし、表示を調整する関数
function formatCustomerDisplayName($customer_name, $store_name) {
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
    <title>顧客選択画面</title>
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
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .title-section h2 {
            margin: 0;
            font-size: 1.6em;
            color: #333;
        }
        .search-bar {
            display: flex;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            width: 60%; /* 検索バーの幅を調整 */
            max-width: 350px;
        }
        .search-bar input[type="text"] {
            border: none;
            padding: 8px 12px;
            font-size: 1em;
            flex-grow: 1; /* 入力フィールドが利用可能なスペースを埋める */
            outline: none;
        }
        .search-bar button {
            background-color: #f0f0f0;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
        }
        .search-bar button img {
            height: 18px;
            vertical-align: middle;
        }
        .customer-list {
            list-style: none;
            padding: 0;
            margin: 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .customer-list li {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .customer-list li:last-child {
            border-bottom: none;
        }
        .customer-list li:hover {
            background-color: #f5f5f5;
        }
        .customer-list li span {
            font-size: 1.1em;
            color: #333;
        }
        .customer-list li .customer-no {
            font-size: 0.9em;
            color: #777;
            margin-left: 15px;
        }
        .customer-list li button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        .customer-list li button:hover {
            background-color: #0056b3;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #777;
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
        <div class="title-section">
            <h2>顧客選択</h2>
            <div class="search-bar">
                <input type="text" placeholder="顧客名（企業名含む）で検索" id="search_input" value="<?= htmlspecialchars($search_term) ?>">
                <button type="button" onclick="performSearch()">
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iY3VycmVudENvbG9yIiBjbGFzcz0iYmkgYmktc2VhcmNoIiB2aWV3Qm94PSIwIDAgMTYgMTYiPgogIDxwYXRoIGQ9Ik0xMS43NDIgMTAuMzQ0YTYuNSAxMy4xMyAwIDEgMC0xLjM5Ni0xLjM5NmwuNzU4LS43NThhNy41IDcuNSAwIDEgMSAxLjMyNi0xLjMzMWwtLjc1OC0uNzU4eiIvPgogIDxwYXRoIGQ9Ik02LjUgMi4yNWExLjc1IDEuNzUgMCAxIDAgMCAzLjUgMS43NSAxLjc1IDAgMCAwIDAtMy41VjIuMjV6Ii8+Cjwvc3ZnPg==" alt="検索">
                </button>
            </div>
        </div>

        <ul class="customer-list">
            <?php if (empty($customers)): ?>
                <li class="no-results">該当する顧客が見つかりませんでした。</li>
            <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                    <li>
                        <span><?= formatCustomerDisplayName($customer['customer_name'], $customer['store_name']) ?></span>
                        <span class="customer-no">顧客No.<?= htmlspecialchars($customer['customer_no']) ?></span>
                        <button onclick="selectCustomer(<?= htmlspecialchars($customer['customer_no']) ?>, '<?= formatCustomerDisplayName($customer['customer_name'], $customer['store_name']) ?>')">選択</button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <script>
        function performSearch() {
            const searchInput = document.getElementById('search_input').value;
            window.location.href = `customer_selection.php?search=${encodeURIComponent(searchInput)}`;
        }

        function selectCustomer(customerNo, customerName) {
            // 顧客情報を持ってcreate_delivery.phpへ遷移
            window.location.href = `create_delivery.php?customer_no=${customerNo}&customer_name=${encodeURIComponent(customerName)}`;
        }
    </script>
</body>
</html>