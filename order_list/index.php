<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();

$storeName = $_GET['store'] ?? '';

// ページネーションの設定
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // 1ページあたりの表示件数
$offset = ($page - 1) * $limit;

// 検索条件
$search_customer = $_GET['search_customer'] ?? '';

// ソート条件
$sort_column = $_GET['sort'] ?? 'registration_date';
$sort_order = $_GET['order'] ?? 'DESC';

// パラメータのバリデーション
$page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$allowed_sort_columns = ['order_no', 'customer_name', 'registration_date', 'total_amount', 'status'];
if (!in_array($sort_column, $allowed_sort_columns)) {
    $sort_column = 'registration_date';
}
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

try {
    // 基本クエリ
    $sql = "
        SELECT 
            o.order_no, 
            c.customer_name, 
            o.registration_date, 
            o.status,
            (SELECT SUM(oi.order_volume * oi.price) FROM order_items oi WHERE oi.order_no = o.order_no) as total_amount
        FROM orders o
        JOIN customers c ON o.customer_no = c.customer_no
    ";

    // 条件句
    $where_clauses = [];
    $params = [];

    if (!empty($storeName)) {
        $where_clauses[] = "c.store_name = :store_name";
        $params[':store_name'] = $storeName;
    }

    if (!empty($search_customer)) {
        $where_clauses[] = "c.customer_name LIKE :search_customer";
        $params[':search_customer'] = '%' . $search_customer . '%';
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // 総件数を取得
    $count_sql = str_replace("SELECT ... FROM", "SELECT COUNT(*) FROM", $sql);
    $total_stmt = $pdo->prepare($sql);
    $total_stmt->execute($params);
    $total_orders = $total_stmt->rowCount();
    $total_pages = ceil($total_orders / $limit);

    // ソートとページネーションを追加
    $sql .= " ORDER BY $sort_column $sort_order LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("データベースエラー: " . $e->getMessage());
}

// ステータスの日本語変換
function translate_status($status) {
    switch ($status) {
        case 'pending': return '保留';
        case 'processing': return '処理中';
        case 'completed': return '完了';
        case 'cancelled': return 'キャンセル';
        default: return $status;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文書一覧</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="with-header">
    <div class="container">
        <h1 class="page-title">注文書一覧</h1>

        <!-- 検索・フィルタリング -->
        <div class="search-container">
            <form action="" method="GET">
                <input type="hidden" name="store" value="<?= htmlspecialchars($storeName) ?>">
                <input type="text" name="search_customer" placeholder="顧客名で検索..." value="<?= htmlspecialchars($search_customer) ?>">
                <button type="submit">検索</button>
            </form>
        </div>

        <!-- 注文一覧テーブル -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><a href="?store=<?= $storeName ?>&sort=order_no&order=<?= $sort_column == 'order_no' && $sort_order == 'ASC' ? 'DESC' : 'ASC' ?>">注文番号</a></th>
                        <th><a href="?store=<?= $storeName ?>&sort=customer_name&order=<?= $sort_column == 'customer_name' && $sort_order == 'ASC' ? 'DESC' : 'ASC' ?>">顧客名</a></th>
                        <th><a href="?store=<?= $storeName ?>&sort=registration_date&order=<?= $sort_column == 'registration_date' && $sort_order == 'ASC' ? 'DESC' : 'ASC' ?>">注文日</a></th>
                        <th><a href="?store=<?= $storeName ?>&sort=total_amount&order=<?= $sort_column == 'total_amount' && $sort_order == 'ASC' ? 'DESC' : 'ASC' ?>">合計金額</a></th>
                        <th><a href="?store=<?= $storeName ?>&sort=status&order=<?= $sort_column == 'status' && $sort_order == 'ASC' ? 'DESC' : 'ASC' ?>">ステータス</a></th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center">該当する注文はありません。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['order_no']) ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= htmlspecialchars($order['registration_date']) ?></td>
                                <td>¥<?= number_format($order['total_amount']) ?></td>
                                <td><span class="status-<?= htmlspecialchars($order['status']) ?>"><?= translate_status($order['status']) ?></span></td>
                                <td>
                                    <a href="detail.php?order_no=<?= htmlspecialchars($order['order_no']) ?>&store=<?= htmlspecialchars($storeName) ?>" class="btn-detail">詳細</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ページネーション -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?store=<?= $storeName ?>&page=<?= $page - 1 ?>&search_customer=<?= $search_customer ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>">前へ</a>
            <?php endif; ?>
            <span>ページ <?= $page ?> / <?= $total_pages ?></span>
            <?php if ($page < $total_pages): ?>
                <a href="?store=<?= $storeName ?>&page=<?= $page + 1 ?>&search_customer=<?= $search_customer ?>&sort=<?= $sort_column ?>&order=<?= $sort_order ?>">次へ</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
