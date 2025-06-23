<?php
session_start();

require_once 'db_connect.php'; // 正しくデータベースに接続されることを確認してください

$search_keyword = $_GET['search'] ?? '';
$message = $_SESSION['message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// セッションメッセージは一度表示したらクリア
unset($_SESSION['message']);
unset($_SESSION['error_message']);

// --- ページネーション設定 ---
$records_per_page = 10; // 1ページあたりの表示件数
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// --- データベースから注文書データを取得 ---
$orders = [];
$total_orders = 0;

try {
    // まず、条件に合う全件数を取得
    $count_sql = "SELECT COUNT(*) FROM orders o JOIN customers c ON o.customer_no = c.customer_no";
    $data_sql = "SELECT o.order_no, o.customer_no, c.customer_name, o.registration_date FROM orders o JOIN customers c ON o.customer_no = c.customer_no";
    $params = [];
    $where_clauses = [];

    if (!empty($search_keyword)) {
        // 顧客名または注文IDで検索
        $where_clauses[] = "(c.customer_name LIKE ? OR o.order_no = ?)";
        $params[] = '%' . $search_keyword . '%';
        $params[] = $search_keyword;
    }

    if (!empty($where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
        $data_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $stmt_count = $pdo->prepare($count_sql);
    $stmt_count->execute($params);
    $total_orders = $stmt_count->fetchColumn();

    // 取得順序とページネーションの追加
    $data_sql .= " ORDER BY o.order_no DESC LIMIT ? OFFSET ?";
    $params[] = $records_per_page;
    $params[] = $offset;

    $stmt_data = $pdo->prepare($data_sql);
    $stmt_data->execute($params);
    $orders = $stmt_data->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("データベースエラー (order_history.php): " . $e->getMessage());
    $error_message = "注文書データの読み込み中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
}

// ページ数が0になるのを防ぐ
$total_pages = max(1, ceil($total_orders / $records_per_page));
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>注文書履歴</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
    }

    .container {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        width: 90%;
        max-width: 900px;
        margin-top: 20px;
        overflow: hidden;
    }

    .header {
        background-color: #28a745;
        /* Green */
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header h1 {
        margin: 0;
        font-size: 1.2em;
    }

    .nav-buttons {
        display: flex;
    }

    .nav-buttons button {
        background-color: #218838;
        color: white;
        border: none;
        padding: 10px 15px;
        margin-left: 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
    }

    .nav-buttons button:hover {
        background-color: #1e7e34;
    }

    .content {
        padding: 20px;
    }

    .message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .section-title {
        font-size: 1.5em;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        color: #333;
    }

    .section-title svg {
        margin-right: 10px;
    }

    .search-bar-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 20px 0 20px;
        gap: 10px;
    }

    .search-area {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-grow: 1;
    }

    .search-area input[type="text"] {
        flex-grow: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1em;
    }

    .search-area button[type="submit"] {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .search-area button[type="submit"]:hover {
        background-color: #0056b3;
    }

    .new-creation-button {
        background-color: #ffc107;
        /* Yellow */
        color: #333;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        font-weight: bold;
        transition: background-color 0.3s ease;
        white-space: nowrap;
    }

    .new-creation-button:hover {
        background-color: #e0a800;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table th,
    table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    table th {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #555;
    }

    table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    table td a {
        color: #007bff;
        text-decoration: none;
    }

    table td a:hover {
        text-decoration: underline;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
        gap: 10px;
    }

    .pagination button {
        background-color: #6c757d;
        /* Gray */
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
    }

    .pagination button:hover {
        background-color: #5a6268;
    }

    .pagination span {
        font-size: 0.9em;
        color: #555;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>緑橋書店 受注管理システム</h1>
            <div class="nav-buttons">
                <button onclick="location.href='#'">顧客情報</button>
                <button onclick="location.href='#'">統計情報</button>
                <button onclick="location.href='order_history.php'">注文書</button>
                <button onclick="location.href='#'">納品書</button>
            </div>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
            <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <div class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file-text">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                注文書履歴
            </div>

            <div class="search-bar-wrapper">
                <form id="searchForm" action="order_history.php" method="GET" class="search-area">
                    <input type="text" id="searchInput" name="search" placeholder="検索キーワードを入力..." autocomplete="off"
                        value="<?= htmlspecialchars($search_keyword, ENT_QUOTES) ?>" />
                    <input type="hidden" name="page" id="hiddenPageInput" value="1">
                    <button type="submit">検索</button>
                </form>
                <button type="button" class="new-creation-button"
                    onclick="location.href='customer_select.php'">新規作成</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>注文書No</th>
                        <th>顧客名</th>
                        <th>登録日</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center;">表示する注文書がありません。</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><a
                                href="order_detail.php?order_no=<?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?>"><?= htmlspecialchars($order['order_no'], ENT_QUOTES) ?></a>
                        </td>
                        <td><?= htmlspecialchars($order['customer_name'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($order['registration_date'], ENT_QUOTES) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($current_page > 1): ?>
                <button
                    onclick="location.href='order_history.php?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_keyword) ?>'">前</button>
                <?php endif; ?>
                <span><?= $current_page ?>/<?= $total_pages ?></span>
                <?php if ($current_page < $total_pages): ?>
                <button
                    onclick="location.href='order_history.php?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_keyword) ?>'">次</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // debounce関数 (遅延実行)
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    const hiddenPageInput = document.getElementById('hiddenPageInput'); // hidden inputのIDを取得

    const handleInput = debounce(() => {
        // 検索時はページを1に戻す
        if (hiddenPageInput) {
            hiddenPageInput.value = 1;
        }
        searchForm.submit();
    }, 500); // 0.5秒の遅延

    searchInput.addEventListener('input', handleInput);
    </script>
</body>

</html>