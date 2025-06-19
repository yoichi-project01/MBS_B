<?php
include(__DIR__ . '/../component/header.php');
require_once(__DIR__ . '/../component/db.php');

// ページあたりの件数
$perPage = 4;

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

// 現在のページ番号
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;

// 店舗選択と検索ワードの取得
$selectedStore = isset($_GET['store']) ? trim($_GET['store']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $rows = [];
    $totalCount = 0;

    if (!empty($selectedStore)) {
        // WHERE条件作成
        $whereConditions = "c.store_name = :store";
        if ($search !== '') {
            $whereConditions .= " AND c.customer_name LIKE :search";
        }

        // 件数取得
        $countSql = "
            SELECT COUNT(*) FROM statistics_information s
            JOIN customers c ON s.customer_no = c.customer_no
            WHERE $whereConditions
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->bindValue(':store', $selectedStore, PDO::PARAM_STR);
        if ($search !== '') {
            $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;

        // データ取得
        $sql = "
            SELECT
                c.customer_no,
                c.customer_name,
                c.store_name,
                s.sales_by_customer,
                s.lead_time,
                s.delivery_amount
            FROM statistics_information s
            JOIN customers c ON s.customer_no = c.customer_no
            WHERE $whereConditions
            ORDER BY c.customer_no ASC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':store', $selectedStore, PDO::PARAM_STR);
        if ($search !== '') {
            $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
    }

    $storeName = htmlspecialchars($selectedStore);
    $totalPages = ceil($totalCount / $perPage);
} catch (PDOException $e) {
    echo "DBエラー: " . htmlspecialchars($e->getMessage());
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>統計情報</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <h1>統計情報</h1>

    <!-- 検索フォーム -->
    <form method="GET" action="" class="search-form">
        <input type="hidden" name="store" value="<?= htmlspecialchars($selectedStore) ?>">
        <input type="text" name="search" class="search-area" value="<?= htmlspecialchars($search) ?>"
            placeholder="顧客名で検索...">
        <button type="submit" class="search-button">検索</button>
    </form>

    <div class="table-container">
        <?php if (empty($rows)): ?>
        <p style="text-align:center;">該当するデータがありません。</p>
        <?php else: ?>
        <table id="customerTable">
            <thead>
                <tr>
                    <th style="width: 30%;">顧客名</th>
                    <th style="width: 23%;">
                        売上（円）
                        <button class="sort-icon" data-column="sales_by_customer" data-order="asc">▲</button>
                        <button class="sort-icon" data-column="sales_by_customer" data-order="desc">▼</button>
                    </th>
                    <th style="width: 27%;">
                        リードタイム
                        <button class="sort-icon" data-column="lead_time" data-order="asc">▲</button>
                        <button class="sort-icon" data-column="lead_time" data-order="desc">▼</button>
                    </th>
                    <th style="width: 20%;">
                        配達回数
                        <button class="sort-icon" data-column="delivery_amount" data-order="asc">▲</button>
                        <button class="sort-icon" data-column="delivery_amount" data-order="desc">▼</button>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td data-column="customer_name"><?= htmlspecialchars($row['customer_name']) ?></td>
                    <td data-column="sales_by_customer"><?= number_format($row['sales_by_customer']) ?></td>
                    <td data-column="lead_time"><?= formatLeadTime($row['lead_time']) ?></td>
                    <td data-column="delivery_amount"><?= $row['delivery_amount'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- ページネーション -->
        <nav class="pagination"
            style="text-align: center; margin-top: 24px; position: fixed; bottom: 16px; left: 50%; transform: translateX(-50%); background: #fff; padding: 8px 12px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 100;">
            <?php
                $prevDisabled = ($page <= 1);
                $nextDisabled = ($page >= $totalPages);
                $baseUrl = "?store=" . urlencode($selectedStore) . "&search=" . urlencode($search) . "&page=";

                // 前のページリンク
                if ($prevDisabled) {
                    echo '<span style="color:#bbb; margin-right:12px;">&lt; 前へ</span>';
                } else {
                    echo '<a href="' . $baseUrl . ($page - 1) . '" style="margin-right:12px; color:#2f5d3f; font-weight:bold; text-decoration:none;">&lt; 前へ</a>';
                }

                // 現在ページ表示
                echo '<span style="margin: 0 12px;">' . $page . ' / ' . $totalPages . '</span>';

                // 次のページリンク
                if ($nextDisabled) {
                    echo '<span style="color:#bbb; margin-left:12px;">次へ &gt;</span>';
                } else {
                    echo '<a href="' . $baseUrl . ($page + 1) . '" style="margin-left:12px; color:#2f5d3f; font-weight:bold; text-decoration:none;">次へ &gt;</a>';
                }
                ?>
        </nav>
        <?php endif; ?>
    </div>

</body>

</html>