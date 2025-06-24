<?php
session_start();

// --- ダミーデータ生成関数 ---
/**
 * ダミーの納品書データを生成する関数。
 * 検索クエリとページネーションに基づいてデータをフィルタリングし、スライスします。
 *
 * @param int $records_per_page 1ページあたりの表示件数
 * @param int $offset データ取得開始位置
 * @param string $search_query 検索文字列 (顧客名または企業名)
 * @return array フィルタリングされ、ページネーションが適用された納品書データの配列
 */
function generateDummyDeliveries($records_per_page, $offset, $search_query) {
    $allDeliveries = [];

    // 固定の顧客データ (実際のシステムではDBから取得)
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

    // ダミーの納品書データを大量に生成
    for ($i = 1; $i <= 100; $i++) { // 100件のダミー納品書を生成
        $customerIndex = ($i - 1) % count($customers);
        $customer = $customers[$customerIndex];
        $totalAmount = ($i * 1000) + (($i % 5) * 100); // 適当な合計金額

        $delivery = [
            'delivery_no' => 20230000 + $i, // 納品書番号
            'customer_name' => $customer['customer_name'],
            'store_name' => $customer['store_name'],
            'total_amount' => $totalAmount
        ];
        $allDeliveries[] = $delivery;
    }

    $filteredDeliveries = [];
    $search_query_lower = mb_strtolower($search_query, 'UTF-8');

    foreach ($allDeliveries as $delivery) {
        $customer_name_lower = mb_strtolower($delivery['customer_name'], 'UTF-8');
        $store_name_lower = mb_strtolower($delivery['store_name'], 'UTF-8');

        // 検索クエリが空の場合、または顧客名・企業名にクエリが含まれる場合
        if (empty($search_query) ||
            mb_strpos($customer_name_lower, $search_query_lower) !== false ||
            mb_strpos($store_name_lower, $search_query_lower) !== false) {
            $filteredDeliveries[] = $delivery;
        }
    }

    // 納品書番号でソート (昇順)
    usort($filteredDeliveries, function($a, $b) {
        return $a['delivery_no'] <=> $b['delivery_no'];
    });

    // ページネーションを適用
    $paginatedDeliveries = array_slice($filteredDeliveries, $offset, $records_per_page);

    return [
        'data' => $paginatedDeliveries,
        'total_records' => count($filteredDeliveries)
    ];
}

// --- ページネーション設定 ---
$records_per_page = 5; // 1ページあたりの表示件数
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// --- 検索処理 ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- 納品書データの取得 (ダミーデータを使用) ---
$offset = ($current_page - 1) * $records_per_page;

$dummyDataResult = generateDummyDeliveries($records_per_page, $offset, $search_query);
$deliveries = $dummyDataResult['data'];
$total_records = $dummyDataResult['total_records'];

// total_pages の計算
$total_pages = 1;
if ($total_records > 0) {
    $total_pages = ceil($total_records / $records_per_page);
}

// 現在のページが総ページ数を超えないように調整
if ($current_page > $total_pages) {
    $current_page = $total_pages;
    // offset も再計算が必要 (ただし、この関数呼び出しの前に行われるため、実際には現在のoffsetは正しいはず)
    $offset = ($current_page - 1) * $records_per_page;
    // もしページが調整された場合、再度データを取得し直す
    // $dummyDataResult = generateDummyDeliveries($records_per_page, $offset, $search_query);
    // $deliveries = $dummyDataResult['data'];
}
// データがない場合はoffsetを0にする (generateDummyDeliveries側でフィルタリング済みなので不要だが念のため)
if ($total_records === 0) {
    $offset = 0;
}


// 納品ステータスを生成するヘルパー関数 (ダミーデータ)
/**
 * ダミーの納品ステータスを生成する関数。
 * total_amount に基づいてランダムなステータスを返します。
 * 実際の進捗は、納品明細と注文明細の比較から計算されます。
 *
 * @param int $delivery_no 納品書番号
 * @param float $total_amount 納品書の合計金額
 * @return array ステータステキストと色の配列
 */
function getDeliveryStatus($delivery_no, $total_amount) {
    // 実際の進捗は delivery_items と order_items の比較から計算されますが、
    // 今回は表示のため、total_amountを基にランダムなダミーステータスを生成
    // 例: total_amount が大きいほど進捗があるように見せる
    if ($total_amount % 3 == 0) {
        return ['text' => '納品済み 3/3', 'color' => 'blue']; // 納品完了
    } elseif ($total_amount % 2 == 0) {
        return ['text' => '一部納品 2/3', 'color' => 'orange']; // 一部納品
    } else {
        return ['text' => '未納品 0/3', 'color' => 'red']; // 未納品 (またはランダム)
    }
}

// 顧客名に企業名が含まれているかチェックし、表示を調整する関数
/**
 * 顧客名と企業名を整形して表示する関数。
 * 企業名があり、それが顧客名に含まれていない場合、"企業名 顧客名" の形式で返します。
 *
 * @param string $customer_name 顧客名
 * @param string $store_name 企業名（店舗名）
 * @return string 整形された顧客表示名
 */
function formatCustomerName($customer_name, $store_name) {
    // '緑橋本店' は特定のケースとして、企業名として追加しないようにする
    if (!empty($store_name) && $store_name !== '緑橋本店' && mb_strpos($customer_name, $store_name) === false) {
        // store_name があり、かつ customer_name に含まれていない場合のみ追加
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
    <title>納品書履歴画面</title>
    <style>
        /* スタイルシートは変更なし */
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
            max-width: 900px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .top-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .title-search {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .title-search h2 {
            margin: 0;
            font-size: 1.6em;
            color: #333;
        }
        .search-bar {
            display: flex;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
        }
        .search-bar input[type="text"] {
            border: none;
            padding: 8px 12px;
            font-size: 1em;
            width: 250px;
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
        .new-create-button button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .new-create-button button:hover {
            background-color: #0056b3;
        }
        .delivery-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .delivery-table th, .delivery-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .delivery-table th {
            background-color: #e9ecef;
            font-weight: bold;
            color: #555;
        }
        .delivery-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .delivery-table tr:hover {
            background-color: #f1f1f1;
        }
        .delivery-table a {
            color: #007bff;
            text-decoration: none;
        }
        .delivery-table a:hover {
            text-decoration: underline;
        }
        .status-button {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            color: white;
            text-align: center;
        }
        .status-button.blue { background-color: #007bff; }
        .status-button.red { background-color: #dc3545; }
        .status-button.orange { background-color: #ffc107; color: #333;} /* 黄色系の背景色の場合、文字色を濃くする */

        .pagination {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 25px;
            gap: 10px;
        }
        .pagination button {
            background-color: #e9ecef;
            border: 1px solid #ccc;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }
        .pagination button:hover:not(:disabled) {
            background-color: #d8dee3;
        }
        .pagination button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .pagination span {
            font-weight: bold;
            font-size: 1em;
            color: #555;
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
            <button onclick="location.href='#'">納品書</button>
        </div>
    </div>

    <div class="container">
        <div class="top-section">
            <div class="title-search">
                <h2>納品書</h2>
                <div class="search-bar">
                    <input type="text" placeholder="顧客名（企業名含む）で検索" name="search" id="search_input" value="<?= htmlspecialchars($search_query) ?>">
                    <button type="button" onclick="performSearch()">
                        <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iY3VycmVudENvbG9yIiBjbGFzcz0iYmkgYmktc2VhcmNoIiB2aWV3Qm94PSIwIDAgMTYgMTYiPgogIDxwYXRoIGQ9Ik0xMS43NDIgMTAuMzQ0YTYuNSAxMy4xMyAwIDEgMC0xLjM5Ni0xLjM5NmwuNzU4LS43NThhNy41IDcuNSAwIDEgMSAxLjMyNi0xLjMzMWwtLjc1OC0uNzU4eiIvPgogIDxwYXRoIGQ9TTYuNSAyLjI1YTEuNzUgMS43NSAwIDEgMCAwIDMuNSAxLjc1IDEu3NSAwIDAgMCAwLTMuVjIuMjV6Ii8+Cjwvc3ZnPg==" alt="検索">
                    </button>
                </div>
            </div>
            <div class="new-create-button">
                <button onclick="location.href='customer_selection.php'">新規作成</button>
            </div>
        </div>

        <table class="delivery-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>顧客名（企業名含む）</th>
                    <th>ステータス</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deliveries)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; color: #777;">該当する納品書が見つかりませんでした。</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($deliveries as $delivery): ?>
                        <?php $status = getDeliveryStatus($delivery['delivery_no'], $delivery['total_amount']); // ダミー生成 ?>
                        <tr>
                            <td><a href="delivery_detail.php?delivery_no=<?= htmlspecialchars($delivery['delivery_no']) ?>"><?= htmlspecialchars($delivery['delivery_no']) ?></a></td>
                            <td><?= formatCustomerName($delivery['customer_name'], $delivery['store_name']) ?></td>
                            <td><span class="status-button <?= htmlspecialchars($status['color']) ?>"><?= htmlspecialchars($status['text']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <button onclick="goToPage(<?= $current_page - 1 ?>)" <?= $current_page <= 1 ? 'disabled' : '' ?>>前</button>
            <span><?= htmlspecialchars((string)$current_page) ?>/<?= htmlspecialchars((string)$total_pages) ?></span>
            <button onclick="goToPage(<?= $current_page + 1 ?>)" <?= $current_page >= $total_pages ? 'disabled' : '' ?>>次</button>
        </div>
    </div>

    <script>
        function performSearch() {
            const searchInput = document.getElementById('search_input').value;
            // 検索時は常に1ページ目に戻る
            window.location.href = `delivery_history.php?search=${encodeURIComponent(searchInput)}&page=1`;
        }

        function goToPage(page) {
            const searchInput = document.getElementById('search_input').value;
            window.location.href = `delivery_history.php?search=${encodeURIComponent(searchInput)}&page=${page}`;
        }
    </script>
</body>
</html>