<?php
include(__DIR__ . '/../component/header.php');
require_once(__DIR__ . '/../component/db.php');

// リードタイム（秒数）を「日 時間 分 秒」に変換する関数
function formatLeadTime($secondsFloat)
{
    $totalSeconds = (int) round($secondsFloat);
    $days = floor($totalSeconds / 86400);
    $hours = floor(($totalSeconds % 86400) / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;

    $result = "";
    if ($days > 0) $result .= "{$days}日 ";
    if ($hours > 0) $result .= "{$hours}時間 ";
    if ($minutes > 0) $result .= "{$minutes}分 ";
    $result .= "{$seconds}秒";

    return trim($result);
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

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
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <main class="main-content">
        <h1 id="store-title">統計情報</h1>

        <div class="search-area">
            <input type="text" id="searchInput" placeholder="顧客名で検索..." onkeyup="filterTable()">
        </div>

        <div class="table-container">
            <table id="customerTable">
                <thead>
                    <tr>
                        <!-- 顧客NO 列を削除 -->
                        <th>顧客名</th>
                        <th>
                            売上（円）
                            <button class="sort-icon" data-column="sales_by_customer" data-order="asc">▲</button>
                            <button class="sort-icon" data-column="sales_by_customer" data-order="desc">▼</button>
                        </th>
                        <th>
                            リードタイム
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
                        <!-- 顧客NOのセルを削除 -->
                        <td data-column="customer_name"><?= htmlspecialchars($row['customer_name']) ?></td>
                        <td data-column="sales_by_customer"><?= number_format($row['sales_by_customer']) ?></td>
                        <td data-column="lead_time"><?= formatLeadTime($row['lead_time']) ?></td>
                        <td data-column="delivery_amount"><?= $row['delivery_amount'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="statistics.js"></script>
</body>

</html>