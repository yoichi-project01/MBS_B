<?php include(__DIR__ . '/../component/header.php');?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>顧客別売上・リードタイム</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- メインコンテンツ -->
    <h1>顧客別累計売上と平均リードタイム</h1>
    <div class="upload-area">
        <div class="search-area">
            <input type="text" id="searchInput" placeholder="顧客名で検索...">
        </div>
        <button id="loadData">データを読み込む</button>
        <div id="result"></div>
    </div>

    <!-- 並べ替えボタン -->
    <div class="sort-controls">
        <button class="sort-btn" data-column="totalSales" data-order="desc">売上↓</button>
        <button class="sort-btn" data-column="totalSales" data-order="asc">売上↑</button>
        <button class="sort-btn" data-column="avgLeadTime" data-order="desc">リードタイム↓</button>
        <button class="sort-btn" data-column="avgLeadTime" data-order="asc">リードタイム↑</button>
        <button class="sort-btn" data-column="deliveryCount" data-order="desc">配達回数↓</button>
        <button class="sort-btn" data-column="deliveryCount" data-order="asc">配達回数↑</button>
    </div>

    <!-- 結果テーブル -->
    <div class="table-container">
        <table id="customerTable">
            <thead>
                <tr>
                    <th>顧客NO</th>
                    <th>顧客名</th>
                    <th>累計売上（円）</th>
                    <th>平均リードタイム（日）</th>
                    <th>配達回数</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script src="script.js"></script>
</body>

</html>