<?php 
    include(__DIR__ . '/../component/header.php');
    require_once(__DIR__ . '/../component/db.php');
?>

<?php
    try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 顧客テーブルと統計情報をJOIN（顧客名を取得する想定）
    $sql = "
    SELECT
    c.customer_no,
    c.customer_name,
    s.sales_by_customer,
    s.lead_time,
    s.delivery_amount
    FROM statistics_information s
    JOIN customers c ON s.customer_no = c.customer_no
    ORDER BY c.customer_no ASC
    ";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
    echo "DBエラー: " . htmlspecialchars($e->getMessage());
    exit;
    }
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>顧客別売上・リードタイム</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1 id="store-title">顧客別累計売上と<br>平均リードタイム</h1>

    <div class="container upload-container">
        <div class="search-area">
            <input type="text" id="searchInput" placeholder="顧客名で検索..." onkeyup="filterTable()">
        </div>

        <div class="table-container">
            <table id="customerTable">
                <thead>
                    <tr>
                        <th>顧客NO</th>
                        <th>顧客名</th>
                        <th>
                            売上（円）
                            <button class="sort-icon" data-column="sales_by_customer" data-order="asc">▲</button>
                            <button class="sort-icon" data-column="sales_by_customer" data-order="desc">▼</button>
                        </th>
                        <th>
                            リードタイム（日）
                            <button class="sort-icon" data-column="lead_time" data-order="asc">▲</button>
                            <button class="sort-icon" data-column="lead_time" data-order="desc">▼</button>
                        </th>
                        <th>
                            配達回数
                            <button class="sort-icon" data-column="delivery_amount" data-order="asc">▲</button>
                            <button class="sort-icon" data-column="delivery_amount" data-order="desc">▼</button>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <tr>
                        <td data-column="customer_no"><?= htmlspecialchars($row['customer_no']) ?></td>
                        <td data-column="customer_name"><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td data-column="sales_by_customer"><?= number_format($row['sales_by_customer']) ?></td>
                        <td data-column="lead_time">
                            <?= round((strtotime($row['lead_time']) - strtotime('1970-01-01')) / 86400, 1) ?>
                        </td>
                        <td data-column="delivery_amount"><?= $row['delivery_amount'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>