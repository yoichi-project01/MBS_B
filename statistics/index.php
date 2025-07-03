<?php
require_once(__DIR__ . '/../component/autoloader.php');
include(__DIR__ . '/../component/header.php');

// セキュリティヘッダーの設定
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$perPage = 4;

/**
 * リードタイム（秒）を人間が読みやすい形式にフォーマット
 */
function formatLeadTime($secondsFloat)
{
    $totalSeconds = (int) round($secondsFloat);

    if ($totalSeconds <= 0) {
        return '0秒';
    }

    $days = floor($totalSeconds / 86400);
    $hours = floor(($totalSeconds % 86400) / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;

    $result = "";
    if ($days > 0) $result .= "{$days}日 ";
    if ($hours > 0) $result .= "{$hours}時間 ";
    if ($minutes > 0) $result .= "{$minutes}分 ";
    if ($seconds > 0 || empty($result)) $result .= "{$seconds}秒";

    return trim($result);
}

/**
 * 入力値のサニタイズ
 */
function sanitizeInput($input, $maxLength = 100)
{
    if (!is_string($input)) {
        return '';
    }

    $sanitized = trim($input);
    $sanitized = htmlspecialchars($sanitized, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (mb_strlen($sanitized, 'UTF-8') > $maxLength) {
        $sanitized = mb_substr($sanitized, 0, $maxLength, 'UTF-8');
    }

    return $sanitized;
}

// 入力パラメータの処理と検証
$validator = new Validator();
$page = 1;
$selectedStore = '';
$search = '';

// ページ番号の検証
if (isset($_GET['page'])) {
    $pageInput = filter_var($_GET['page'], FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 10000]
    ]);
    if ($pageInput !== false) {
        $page = $pageInput;
    }
}

// 店舗名の検証
if (isset($_GET['store'])) {
    $storeInput = sanitizeInput($_GET['store'], 50);
    $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
    if (in_array($storeInput, $allowedStores, true)) {
        $selectedStore = $storeInput;
    }
}

// 検索文字列の検証
if (isset($_GET['search'])) {
    $searchInput = sanitizeInput($_GET['search'], 100);

    // SQLインジェクション・XSS対策
    if (
        $validator->validateSQLInjection($searchInput, '検索文字列') &&
        $validator->validateXSS($searchInput, '検索文字列')
    ) {
        $search = $searchInput;
    }
}

$escapedSelectedStore = htmlspecialchars($selectedStore, ENT_QUOTES, 'UTF-8');
$escapedSearch = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');

try {
    $rows = [];
    $totalCount = 0;
    $pagination = null;

    if (!empty($selectedStore)) {
        // パラメータの準備
        $whereConditions = "c.store_name = :store";
        $params = [':store' => $selectedStore];

        if ($search !== '') {
            $whereConditions .= " AND c.customer_name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        // 総件数の取得（準備済みステートメント使用）
        $countSql = "SELECT COUNT(*) FROM statistics_information s 
                     JOIN customers c ON s.customer_no = c.customer_no 
                     WHERE $whereConditions";

        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = (int) $countStmt->fetchColumn();

        if ($totalCount > 0) {
            // ページネーション設定
            $queryParams = array_filter([
                'store' => $selectedStore,
                'search' => $search
            ]);
            $pagination = new Pagination($page, $totalCount, $perPage, '', $queryParams);

            // ページ番号のバリデーション
            if ($pagination->needsRedirect()) {
                $redirectUrl = $pagination->getRedirectUrl();
                header("Location: " . $redirectUrl);
                exit;
            }

            $offset = $pagination->getOffset($perPage);

            // データ取得クエリ（セキュリティを考慮した準備済みステートメント）
            $sql = "SELECT 
                        c.customer_no, 
                        c.customer_name, 
                        c.store_name, 
                        s.sales_by_customer, 
                        s.lead_time, 
                        s.delivery_amount,
                        s.last_order_date
                    FROM statistics_information s 
                    JOIN customers c ON s.customer_no = c.customer_no 
                    WHERE $whereConditions 
                    ORDER BY c.customer_no ASC 
                    LIMIT :limit OFFSET :offset";

            $stmt = $pdo->prepare($sql);

            // パラメータのバインド
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // データの後処理（XSS対策）
            $rows = array_map(function ($row) {
                return [
                    'customer_no' => (int)$row['customer_no'],
                    'customer_name' => htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8'),
                    'store_name' => htmlspecialchars($row['store_name'], ENT_QUOTES, 'UTF-8'),
                    'sales_by_customer' => (float)$row['sales_by_customer'],
                    'lead_time' => (float)$row['lead_time'],
                    'delivery_amount' => (int)$row['delivery_amount'],
                    'last_order_date' => $row['last_order_date']
                ];
            }, $rows);
        }
    }
} catch (PDOException $e) {
    // データベースエラーの処理
    error_log('Statistics page database error: ' . $e->getMessage());
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';

    if ($environment === 'production') {
        $errorMessage = "データベースエラーが発生しました。管理者にお問い合わせください。";
    } else {
        $errorMessage = "DBエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }

    echo '<div class="error-container">';
    echo '<h2>⚠️ エラーが発生しました</h2>';
    echo '<p>' . $errorMessage . '</p>';
    echo '<p><a href="javascript:history.back()">前のページに戻る</a></p>';
    echo '</div>';
    exit;
} catch (Exception $e) {
    // 一般的なエラーの処理
    error_log('Statistics page general error: ' . $e->getMessage());
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';

    if ($environment === 'production') {
        $errorMessage = "システムエラーが発生しました。しばらく時間をおいてから再度お試しください。";
    } else {
        $errorMessage = "エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }

    echo '<div class="error-container">';
    echo '<h2>⚠️ エラーが発生しました</h2>';
    echo '<p>' . $errorMessage . '</p>';
    echo '<p><a href="javascript:history.back()">前のページに戻る</a></p>';
    echo '</div>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報 - 受注管理システム</title>
    <link rel="stylesheet" href="../style.css">

    <!-- Chart.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"
        integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"
        integrity="sha256-l1jWCf08VhUNCv3bVH3F4rCy0wJKqCJSfPjpZhJGGdA=" crossorigin="anonymous"></script>

    <!-- CSRFトークンのメタタグ（必要に応じて） -->
    <meta name="csrf-token" content="<?= CSRFProtection::getToken() ?>">

    <!-- セキュリティ設定 -->
    <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;">
</head>

<body class="with-header statistics-page">
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
                    placeholder="顧客名で検索..." maxlength="100" autocomplete="off" aria-label="顧客名で検索">
                <button type="submit" class="search-button" aria-label="検索を実行">
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
                <table class="enhanced-statistics-table" id="customerTable" role="table" aria-label="顧客統計情報">
                    <thead>
                        <tr role="row">
                            <th scope="col" style="width: 25%;">
                                <span>顧客名</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="customer_name" data-order="asc"
                                        title="顧客名を昇順でソート" aria-label="顧客名を昇順でソート">▲</button>
                                    <button class="sort-btn" data-column="customer_name" data-order="desc"
                                        title="顧客名を降順でソート" aria-label="顧客名を降順でソート">▼</button>
                                </div>
                            </th>
                            <th scope="col" style="width: 20%;">
                                <span>売上（円）</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="sales_by_customer" data-order="asc"
                                        title="売上を昇順でソート" aria-label="売上を昇順でソート">▲</button>
                                    <button class="sort-btn" data-column="sales_by_customer" data-order="desc"
                                        title="売上を降順でソート" aria-label="売上を降順でソート">▼</button>
                                </div>
                            </th>
                            <th scope="col" style="width: 25%;">
                                <span>リードタイム</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="lead_time" data-order="asc"
                                        title="リードタイムを昇順でソート" aria-label="リードタイムを昇順でソート">▲</button>
                                    <button class="sort-btn" data-column="lead_time" data-order="desc"
                                        title="リードタイムを降順でソート" aria-label="リードタイムを降順でソート">▼</button>
                                </div>
                            </th>
                            <th scope="col" style="width: 15%;">
                                <span>配達回数</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" data-column="delivery_amount" data-order="asc"
                                        title="配達回数を昇順でソート" aria-label="配達回数を昇順でソート">▲</button>
                                    <button class="sort-btn" data-column="delivery_amount" data-order="desc"
                                        title="配達回数を降順でソート" aria-label="配達回数を降順でソート">▼</button>
                                </div>
                            </th>
                            <th scope="col" style="width: 15%;">グラフ</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <?php foreach ($rows as $row): ?>
                        <tr class="enhanced-table-row" data-customer-no="<?= (int)$row['customer_no'] ?>" role="row">
                            <td data-column="customer_name" role="gridcell">
                                <span><?= $row['customer_name'] ?></span>
                            </td>
                            <td data-column="sales_by_customer" role="gridcell">
                                <span class="amount-value"><?= number_format($row['sales_by_customer']) ?></span>
                            </td>
                            <td data-column="lead_time" role="gridcell">
                                <span class="time-value"><?= formatLeadTime($row['lead_time']) ?></span>
                            </td>
                            <td data-column="delivery_amount" role="gridcell">
                                <span class="count-value"><?= (int)$row['delivery_amount'] ?></span>
                            </td>
                            <td role="gridcell">
                                <button class="graph-btn" type="button"
                                    data-customer-no="<?= (int)$row['customer_no'] ?>"
                                    data-customer-name="<?= htmlspecialchars($row['customer_name'], ENT_QUOTES, 'UTF-8') ?>"
                                    aria-label="<?= $row['customer_name'] ?>のグラフを表示">
                                    📊 グラフ
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <?php if ($pagination): ?>
            <?= $pagination->render() ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- グラフモーダル -->
    <div id="graphModal" class="modal" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title">売上推移グラフ</h2>
                <button class="close" type="button" onclick="closeModal()" aria-label="モーダルを閉じる">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="modalCanvas" aria-label="売上推移グラフ"></canvas>
            </div>
        </div>
    </div>

    <!-- 統合されたJavaScript -->
    <script src="../script.js"></script>
</body>

</html>