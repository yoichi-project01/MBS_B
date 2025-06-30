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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報 - 在庫管理システム</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body class="with-header">
    <div class="container">
        <h1 class="page-title">
            <i>📊</i>
            統計情報
        </h1>

        <!-- 検索フォーム -->
        <div class="search-container">
            <form method="GET" action="" class="search-form">
                <input type="hidden" name="store" value="<?= htmlspecialchars($selectedStore) ?>">
                <div class="search-input-container">
                    <input type="text" name="search" class="search-input" value="<?= htmlspecialchars($search) ?>"
                        placeholder="顧客名で検索...">
                    <button type="submit" class="search-button">
                        <i>🔍</i>
                        検索
                    </button>
                </div>
            </form>
        </div>

        <!-- 統計情報テーブル -->
        <div class="statistics-container">
            <?php if (empty($rows)): ?>
            <div class="no-data-message">
                <i>📭</i>
                <p>該当するデータがありません。</p>
                <p class="sub-message">別の条件で検索してみてください。</p>
            </div>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="statistics-table" id="customerTable">
                    <thead>
                        <tr>
                            <th class="customer-name-col">
                                <span>顧客名</span>
                            </th>
                            <th class="sales-col">
                                <span>売上（円）</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="sales_by_customer" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="sales_by_customer" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th class="lead-time-col">
                                <span>リードタイム</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="lead_time" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="lead_time" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th class="delivery-col">
                                <span>配達回数</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="delivery_amount" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="delivery_amount" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr class="table-row">
                            <td class="customer-name" data-column="customer_name">
                                <span><?= htmlspecialchars($row['customer_name']) ?></span>
                            </td>
                            <td class="sales-amount" data-column="sales_by_customer">
                                <span class="amount-value"><?= number_format($row['sales_by_customer']) ?></span>
                            </td>
                            <td class="lead-time" data-column="lead_time">
                                <span class="time-value"><?= formatLeadTime($row['lead_time']) ?></span>
                            </td>
                            <td class="delivery-count" data-column="delivery_amount">
                                <span class="count-value"><?= $row['delivery_amount'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <?php if ($totalPages > 1): ?>
            <nav class="pagination-nav">
                <div class="pagination-container">
                    <?php
                            $prevDisabled = ($page <= 1);
                            $nextDisabled = ($page >= $totalPages);
                            $baseUrl = "?store=" . urlencode($selectedStore) . "&search=" . urlencode($search) . "&page=";
                            ?>

                    <!-- 前のページボタン -->
                    <?php if ($prevDisabled): ?>
                    <span class="pagination-btn disabled">
                        <i>⬅️</i>
                        前へ
                    </span>
                    <?php else: ?>
                    <a href="<?= $baseUrl . ($page - 1) ?>" class="pagination-btn">
                        <i>⬅️</i>
                        前へ
                    </a>
                    <?php endif; ?>

                    <!-- ページ情報 -->
                    <div class="page-info">
                        <span class="current-page"><?= $page ?></span>
                        <span class="page-separator">/</span>
                        <span class="total-pages"><?= $totalPages ?></span>
                    </div>

                    <!-- 次のページボタン -->
                    <?php if ($nextDisabled): ?>
                    <span class="pagination-btn disabled">
                        次へ
                        <i>➡️</i>
                    </span>
                    <?php else: ?>
                    <a href="<?= $baseUrl . ($page + 1) ?>" class="pagination-btn">
                        次へ
                        <i>➡️</i>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for table sorting -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('customerTable');
        const sortButtons = document.querySelectorAll('.sort-btn');

        if (!table) return;

        sortButtons.forEach(button => {
            button.addEventListener('click', function() {
                const column = this.getAttribute('data-column');
                const order = this.getAttribute('data-order');

                sortTable(table, column, order);

                // Update button states
                sortButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });

    function sortTable(table, column, order) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const aCell = a.querySelector(`[data-column="${column}"]`);
            const bCell = b.querySelector(`[data-column="${column}"]`);

            let aValue = aCell.textContent.trim();
            let bValue = bCell.textContent.trim();

            // Handle numeric values
            if (column === 'sales_by_customer' || column === 'delivery_amount') {
                aValue = parseFloat(aValue.replace(/[,円]/g, '')) || 0;
                bValue = parseFloat(bValue.replace(/[,円]/g, '')) || 0;
            } else if (column === 'lead_time') {
                aValue = parseLeadTime(aValue);
                bValue = parseLeadTime(bValue);
            }

            if (order === 'asc') {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        // Clear tbody and append sorted rows
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));
    }

    function parseLeadTime(timeStr) {
        // Convert lead time string to seconds for comparison
        let totalSeconds = 0;
        const dayMatch = timeStr.match(/(\d+)日/);
        const hourMatch = timeStr.match(/(\d+)時間/);
        const minuteMatch = timeStr.match(/(\d+)分/);
        const secondMatch = timeStr.match(/(\d+)秒/);

        if (dayMatch) totalSeconds += parseInt(dayMatch[1]) * 86400;
        if (hourMatch) totalSeconds += parseInt(hourMatch[1]) * 3600;
        if (minuteMatch) totalSeconds += parseInt(minuteMatch[1]) * 60;
        if (secondMatch) totalSeconds += parseInt(secondMatch[1]);

        return totalSeconds;
    }
    </script>

    <style>
    /* 統計情報ページ専用スタイル */
    .search-container {
        max-width: 600px;
        margin: 0 auto 40px auto;
        padding: 0 20px;
    }

    .search-form {
        width: 100%;
    }

    .search-input-container {
        display: flex;
        gap: 12px;
        align-items: center;
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        border-radius: var(--radius);
        padding: 8px;
        box-shadow: var(--shadow);
        border: 2px solid transparent;
        transition: all var(--transition);
    }

    .search-input-container:focus-within {
        border-color: var(--accent-green);
        box-shadow: var(--shadow-hover);
    }

    .search-input {
        flex: 1;
        padding: 12px 16px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-family: var(--font-family);
        background: transparent;
        color: var(--font-color);
        outline: none;
    }

    .search-input::placeholder {
        color: var(--sub-green);
    }

    .search-button {
        padding: 12px 20px;
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        font-family: var(--font-family);
        cursor: pointer;
        transition: all var(--transition);
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    .search-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(47, 93, 63, 0.3);
    }

    .statistics-container {
        width: 100%;
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .no-data-message {
        text-align: center;
        padding: 60px 20px;
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 2px dashed var(--sub-green);
    }

    .no-data-message i {
        font-size: 48px;
        margin-bottom: 16px;
        display: block;
    }

    .no-data-message p {
        font-size: 18px;
        color: var(--main-green);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .sub-message {
        font-size: 14px !important;
        color: var(--sub-green) !important;
        font-weight: 400 !important;
    }

    .table-wrapper {
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .statistics-table {
        width: 100%;
        border-collapse: collapse;
        font-family: var(--font-family);
    }

    .statistics-table thead {
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
    }

    .statistics-table th {
        padding: 16px 12px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 14px;
        position: relative;
    }

    .statistics-table th span {
        display: block;
        margin-bottom: 4px;
    }

    .sort-buttons {
        display: flex;
        gap: 4px;
        justify-content: flex-start;
    }

    .sort-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 10px;
        transition: all 0.2s ease;
    }

    .sort-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }

    .sort-btn.active {
        background: var(--accent-green);
        color: var(--main-green);
    }

    .statistics-table td {
        padding: 16px 12px;
        border-bottom: 1px solid rgba(47, 93, 63, 0.1);
        color: var(--font-color);
    }

    .table-row:hover {
        background: rgba(126, 217, 87, 0.05);
    }

    .customer-name-col {
        width: 30%;
    }

    .sales-col {
        width: 23%;
    }

    .lead-time-col {
        width: 27%;
    }

    .delivery-col {
        width: 20%;
    }

    .amount-value {
        font-weight: 600;
        color: var(--main-green);
    }

    .time-value {
        font-weight: 500;
        color: var(--sub-green);
    }

    .count-value {
        font-weight: 600;
        color: var(--main-green);
        text-align: center;
        display: inline-block;
        min-width: 30px;
    }

    .pagination-nav {
        margin-top: 32px;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        padding: 16px 24px;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        position: sticky;
        bottom: 20px;
        z-index: 10;
    }

    .pagination-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        transition: all var(--transition);
        white-space: nowrap;
    }

    .pagination-btn:hover:not(.disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(47, 93, 63, 0.3);
    }

    .pagination-btn.disabled {
        background: #ccc;
        color: #888;
        cursor: not-allowed;
    }

    .page-info {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: var(--main-green);
    }

    .current-page {
        font-size: 18px;
        color: var(--accent-green);
    }

    .page-separator {
        color: var(--sub-green);
    }

    .total-pages {
        font-size: 16px;
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .search-input-container {
            flex-direction: column;
            gap: 8px;
            padding: 12px;
        }

        .search-input,
        .search-button {
            width: 100%;
        }

        .statistics-table {
            font-size: 14px;
        }

        .statistics-table th,
        .statistics-table td {
            padding: 12px 8px;
        }

        .statistics-table th span {
            font-size: 12px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .pagination-container {
            flex-direction: column;
            gap: 12px;
            padding: 16px;
        }

        .pagination-btn {
            width: 100%;
            max-width: 200px;
            justify-content: center;
        }
    }

    @media (max-width: 480px) {
        .statistics-container {
            padding: 0 12px;
        }

        .search-container {
            padding: 0 12px;
        }

        .statistics-table {
            font-size: 12px;
        }

        .statistics-table th,
        .statistics-table td {
            padding: 8px 6px;
        }
    }
    </style>
</body>

</html>