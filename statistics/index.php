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
    <!-- Chart.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    /* 画面全体を最適化 */
    body.with-header {
        height: 100vh;
        overflow: hidden;
    }

    body.with-header .container {
        height: calc(100vh - var(--header-height));
        max-height: calc(100vh - var(--header-height));
        overflow: hidden;
        display: flex;
        flex-direction: column;
        padding: 40px 20px 15px 20px;
        min-height: auto;
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* タイトルを顧客情報ページと同じスタイルに */
    .page-title {
        font-size: clamp(28px, 6vw, 36px);
        font-weight: 800;
        color: var(--main-green);
        margin-bottom: 30px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        letter-spacing: 1px;
        flex-shrink: 0;
    }

    .page-title::before {
        content: '📊';
        font-size: 32px;
        color: var(--accent-green);
    }

    /* コントロールパネルを小さく */
    .enhanced-controls {
        padding: 15px;
        margin-bottom: 20px;
        flex-shrink: 0;
    }

    /* 統計コンテナを画面に収める */
    .statistics-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-height: 0;
    }

    /* テーブルコンテナを調整 */
    .enhanced-table-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        margin-bottom: 15px;
    }

    /* テーブルを画面に収める */
    .enhanced-statistics-table {
        width: 100%;
        border-collapse: collapse;
    }

    .enhanced-statistics-table thead {
        flex-shrink: 0;
    }

    .enhanced-statistics-table tbody {
        display: block;
        overflow-y: auto;
        flex: 1;
        width: 100%;
    }

    .enhanced-statistics-table thead tr {
        display: table;
        width: 100%;
        table-layout: fixed;
    }

    .enhanced-statistics-table tbody tr {
        display: table;
        width: 100%;
        table-layout: fixed;
    }

    /* 列幅を固定して位置ずれを防ぐ */
    .enhanced-statistics-table th:nth-child(1),
    .enhanced-statistics-table td:nth-child(1) {
        width: 25%;
    }

    .enhanced-statistics-table th:nth-child(2),
    .enhanced-statistics-table td:nth-child(2) {
        width: 20%;
    }

    .enhanced-statistics-table th:nth-child(3),
    .enhanced-statistics-table td:nth-child(3) {
        width: 25%;
    }

    .enhanced-statistics-table th:nth-child(4),
    .enhanced-statistics-table td:nth-child(4) {
        width: 15%;
    }

    .enhanced-statistics-table th:nth-child(5),
    .enhanced-statistics-table td:nth-child(5) {
        width: 15%;
    }

    /* セルの高さを調整 */
    .enhanced-statistics-table th,
    .enhanced-statistics-table td {
        padding: 10px 8px;
    }

    /* ページネーションを小さく */
    .pagination-container {
        padding: 10px 15px;
        flex-shrink: 0;
        position: static;
    }

    /* データなしメッセージを調整 */
    .enhanced-no-data {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
    }

    .enhanced-no-data .icon {
        font-size: 48px;
        margin-bottom: 15px;
    }

    /* モーダルを小さく */
    .modal-content {
        width: 80%;
        max-width: 700px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .chart-container {
        height: 300px;
        padding: 15px;
    }

    /* 検索フォームを中央配置 */
    .search-form-container {
        display: flex;
        gap: 12px;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    /* ボタンを小さく */
    .graph-btn {
        padding: 6px 12px;
        font-size: 12px;
    }

    .search-input {
        padding: 10px 14px !important;
        font-size: 14px !important;
        flex: 1;
        max-width: 300px;
    }

    .search-button {
        padding: 10px 16px;
        font-size: 14px;
        white-space: nowrap;
    }

    /* レスポンシブ調整 */
    @media (max-width: 768px) {
        body.with-header .container {
            padding: 20px 16px 10px 16px;
        }

        .page-title {
            font-size: 24px;
            margin-bottom: 20px;
            flex-direction: column;
            gap: 8px;
        }

        .enhanced-controls {
            padding: 10px;
            margin-bottom: 10px;
        }

        .search-form-container {
            flex-direction: column;
            gap: 8px;
        }

        .search-input {
            max-width: none !important;
        }

        .enhanced-statistics-table th,
        .enhanced-statistics-table td {
            padding: 8px 6px;
            font-size: 13px;
        }

        /* モバイルでも列幅を保持 */
        .enhanced-statistics-table th:nth-child(1),
        .enhanced-statistics-table td:nth-child(1) {
            width: 30%;
        }

        .enhanced-statistics-table th:nth-child(2),
        .enhanced-statistics-table td:nth-child(2) {
            width: 25%;
        }

        .enhanced-statistics-table th:nth-child(3),
        .enhanced-statistics-table td:nth-child(3) {
            width: 25%;
        }

        .enhanced-statistics-table th:nth-child(4),
        .enhanced-statistics-table td:nth-child(4) {
            width: 10%;
        }

        .enhanced-statistics-table th:nth-child(5),
        .enhanced-statistics-table td:nth-child(5) {
            width: 10%;
        }

        .graph-btn {
            padding: 4px 8px;
            font-size: 11px;
        }

        .modal-content {
            width: 95%;
            max-height: 90vh;
        }

        .chart-container {
            height: 250px;
            padding: 10px;
        }
    }

    @media (max-width: 480px) {

        .enhanced-statistics-table th,
        .enhanced-statistics-table td {
            padding: 6px 4px;
            font-size: 12px;
        }

        /* 極小画面でも列の整列を保持 */
        .enhanced-statistics-table th:nth-child(1),
        .enhanced-statistics-table td:nth-child(1) {
            width: 25%;
        }

        .enhanced-statistics-table th:nth-child(2),
        .enhanced-statistics-table td:nth-child(2) {
            width: 22%;
        }

        .enhanced-statistics-table th:nth-child(3),
        .enhanced-statistics-table td:nth-child(3) {
            width: 25%;
        }

        .enhanced-statistics-table th:nth-child(4),
        .enhanced-statistics-table td:nth-child(4) {
            width: 13%;
        }

        .enhanced-statistics-table th:nth-child(5),
        .enhanced-statistics-table td:nth-child(5) {
            width: 15%;
        }

        .page-title {
            font-size: 18px;
            flex-direction: row;
            gap: 8px;
        }

        .enhanced-controls {
            padding: 8px;
        }
    }

    /* スクロールバーの幅を考慮した調整 */
    .enhanced-statistics-table tbody::-webkit-scrollbar {
        width: 8px;
    }

    .enhanced-statistics-table tbody::-webkit-scrollbar-track {
        background: rgba(47, 93, 63, 0.1);
        border-radius: 4px;
    }

    .enhanced-statistics-table tbody::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, var(--main-green), var(--sub-green));
        border-radius: 4px;
    }

    .enhanced-statistics-table tbody::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(90deg, var(--sub-green), var(--accent-green));
    }

    /* Firefox用スクロールバー */
    .enhanced-statistics-table tbody {
        scrollbar-width: thin;
        scrollbar-color: var(--main-green) rgba(47, 93, 63, 0.1);
    }

    /* テーブル内容の位置調整 */
    .enhanced-statistics-table td {
        vertical-align: middle;
        text-align: left;
    }

    .enhanced-statistics-table td:nth-child(2) {
        text-align: right;
        /* 売上金額は右寄せ */
    }

    .enhanced-statistics-table td:nth-child(4),
    .enhanced-statistics-table td:nth-child(5) {
        text-align: center;
        /* 配達回数とグラフボタンは中央寄せ */
    }
    </style>
</head>

<body class="with-header">
    <div class="container">
        <!-- ページタイトル -->
        <h1 class="page-title">
            統計情報
        </h1>

        <!-- 検索コントロール -->
        <div class="enhanced-controls">
            <form method="GET" action="" class="search-form-container">
                <input type="hidden" name="store" value="<?= $escapedSelectedStore ?>">
                <input type="text" name="search" class="search-input" value="<?= $escapedSearch ?>"
                    placeholder="顧客名で検索..." maxlength="100" autocomplete="off">
                <button type="submit" class="search-button">
                    🔍 検索
                </button>
            </form>
        </div>

        <!-- 統計情報テーブル -->
        <div class="statistics-container">
            <?php if (empty($rows)): ?>
            <div class="enhanced-no-data">
                <span class="icon">📋</span>
                <?php if (empty($selectedStore)): ?>
                <p>店舗を選択してください。</p>
                <p class="sub-message">ヘッダーのナビゲーションから店舗を選択してデータを表示してください。</p>
                <?php else: ?>
                <p>該当するデータがありません。</p>
                <p class="sub-message">別の条件で検索してみてください。</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="enhanced-table-container">
                <table class="enhanced-statistics-table" id="customerTable">
                    <thead>
                        <tr>
                            <th style="width: 25%;">
                                <span>顧客名</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="customer_name" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="customer_name" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 20%;">
                                <span>売上（円）</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="sales_by_customer" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="sales_by_customer" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 25%;">
                                <span>リードタイム</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="lead_time" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="lead_time" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 15%;">
                                <span>配達回数</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="delivery_amount" data-order="asc"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" data-column="delivery_amount" data-order="desc"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 15%;">グラフ</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <?php foreach ($rows as $row): ?>
                        <tr class="enhanced-table-row" data-customer-no="<?= htmlspecialchars($row['customer_no']) ?>">
                            <td data-column="customer_name">
                                <span><?= htmlspecialchars($row['customer_name']) ?></span>
                            </td>
                            <td data-column="sales_by_customer">
                                <span class="amount-value"><?= number_format($row['sales_by_customer']) ?></span>
                            </td>
                            <td data-column="lead_time">
                                <span class="time-value"><?= formatLeadTime($row['lead_time']) ?></span>
                            </td>
                            <td data-column="delivery_amount">
                                <span class="count-value"><?= htmlspecialchars($row['delivery_amount']) ?></span>
                            </td>
                            <td>
                                <button class="graph-btn"
                                    onclick="showSalesGraph(<?= $row['customer_no'] ?>, '<?= htmlspecialchars($row['customer_name'], ENT_QUOTES) ?>')">
                                    📊 グラフ
                                </button>
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

    <!-- グラフモーダル -->
    <div id="graphModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title">売上推移グラフ</h2>
                <button class="close" onclick="closeModal()" aria-label="モーダルを閉じる">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../script.js"></script>
</body>

</html>