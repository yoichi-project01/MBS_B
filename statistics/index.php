<?php
require_once(__DIR__ . '/../component/autoloader.php');
include(__DIR__ . '/../component/header.php');

$perPage = 4;

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

$validator = new Validator();
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$selectedStore = isset($_GET['store']) ? trim($_GET['store']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$allowedStores = ['緑橋本店', '今里店', '深江橋店'];
if ($selectedStore && !$validator->inArray($selectedStore, $allowedStores, '店舗名')) {
    $selectedStore = '';
}

if (!$validator->maxLength($search, 100, '検索文字列')) {
    $search = substr($search, 0, 100);
}

$escapedSelectedStore = htmlspecialchars($selectedStore);
$escapedSearch = htmlspecialchars($search);

try {
    $rows = [];
    $totalCount = 0;

    if (!empty($selectedStore)) {
        $whereConditions = "c.store_name = :store";
        $params = [':store' => $selectedStore];

        if ($search !== '') {
            $whereConditions .= " AND c.customer_name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $countSql = "SELECT COUNT(*) FROM statistics_information s JOIN customers c ON s.customer_no = c.customer_no WHERE $whereConditions";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = (int) $countStmt->fetchColumn();

        if ($totalCount > 0) {
            $queryParams = array_filter(['store' => $selectedStore, 'search' => $search]);
            $pagination = new Pagination($page, $totalCount, $perPage, '', $queryParams);

            if ($pagination->needsRedirect()) {
                header("Location: " . $pagination->getRedirectUrl());
                exit;
            }

            $offset = $pagination->getOffset($perPage);

            $sql = "SELECT c.customer_no, c.customer_name, c.store_name, s.sales_by_customer, s.lead_time, s.delivery_amount 
                    FROM statistics_information s JOIN customers c ON s.customer_no = c.customer_no 
                    WHERE $whereConditions ORDER BY c.customer_no ASC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log('Statistics page database error: ' . $e->getMessage());
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';
    $errorMessage = $environment === 'production'
        ? "データベースエラーが発生しました。管理者にお問い合わせください。"
        : "DBエラー: " . htmlspecialchars($e->getMessage());
    echo '<div style="text-align: center; padding: 50px; color: #dc3545;">' . $errorMessage . '</div>';
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
                <input type="hidden" name="store" value="<?= $escapedSelectedStore ?>">
                <div class="search-input-container">
                    <input type="text" name="search" class="search-input" value="<?= $escapedSearch ?>"
                        placeholder="顧客名で検索..." maxlength="100" autocomplete="off">
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
                <?php if (empty($selectedStore)): ?>
                <p>店舗を選択してください。</p>
                <p class="sub-message">ヘッダーのナビゲーションから店舗を選択してデータを表示してください。</p>
                <?php else: ?>
                <p>該当するデータがありません。</p>
                <p class="sub-message">別の条件で検索してみてください。</p>
                <?php endif; ?>
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
                                <span class="count-value"><?= htmlspecialchars($row['delivery_amount']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <?php if (isset($pagination)): ?>
            <?= $pagination->render() ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for table sorting -->
    <script>
    (function() {
        'use strict';

        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('customerTable');
            const sortButtons = document.querySelectorAll('.sort-btn');

            if (!table) return;

            sortButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const column = this.getAttribute('data-column');
                    const order = this.getAttribute('data-order');

                    if (column && order) {
                        sortTable(table, column, order);

                        // Update button states
                        sortButtons.forEach(function(btn) {
                            btn.classList.remove('active');
                        });
                        this.classList.add('active');
                    }
                });
            });
        });

        function sortTable(table, column, order) {
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));

            rows.sort(function(a, b) {
                const aCell = a.querySelector('[data-column="' + column + '"]');
                const bCell = b.querySelector('[data-column="' + column + '"]');

                if (!aCell || !bCell) return 0;

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
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
        }

        function parseLeadTime(timeStr) {
            // Convert lead time string to seconds for comparison
            let totalSeconds = 0;
            const dayMatch = timeStr.match(/(\d+)日/);
            const hourMatch = timeStr.match(/(\d+)時間/);
            const minuteMatch = timeStr.match(/(\d+)分/);
            const secondMatch = timeStr.match(/(\d+)秒/);

            if (dayMatch) totalSeconds += parseInt(dayMatch[1], 10) * 86400;
            if (hourMatch) totalSeconds += parseInt(hourMatch[1], 10) * 3600;
            if (minuteMatch) totalSeconds += parseInt(minuteMatch[1], 10) * 60;
            if (secondMatch) totalSeconds += parseInt(secondMatch[1], 10);

            return totalSeconds;
        }
    })();
    </script>
</body>

</html>