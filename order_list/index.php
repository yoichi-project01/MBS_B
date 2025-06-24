<?php
include(__DIR__ . '/../component/header.php');
session_start();

$search_keyword = $_GET['search'] ?? '';
$message = $_SESSION['message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['message'], $_SESSION['error_message']);

$records_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

$all_dummy_orders = [];
for ($i = 1; $i <= 50; $i++) {
    $customer_name = ($i % 5 === 0)
        ? "テスト顧客 " . str_pad($i, 2, '0', STR_PAD_LEFT)
        : "ダミー顧客 " . str_pad($i, 2, '0', STR_PAD_LEFT);
    $all_dummy_orders[] = [
        'order_no' => 'ORD' . str_pad($i, 4, '0', STR_PAD_LEFT),
        'customer_no' => 'CUST' . str_pad($i, 3, '0', STR_PAD_LEFT),
        'customer_name' => $customer_name,
        'registration_date' => date('Y-m-d', strtotime('-' . $i . ' days')),
    ];
}

$filtered = array_filter(
    $all_dummy_orders,
    fn($o) =>
    empty($search_keyword)
        || stripos($o['customer_name'], $search_keyword) !== false
        || strtoupper($search_keyword) === $o['order_no']
);
$total_orders = count($filtered);
$orders = array_slice($filtered, $offset, $records_per_page);
$total_pages = max(1, ceil($total_orders / $records_per_page));
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>注文書履歴</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <?php if ($message): ?>
    <div class="message success"><?= htmlspecialchars($message, ENT_QUOTES) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
    <div class="message error"><?= htmlspecialchars($error_message, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <!-- ヘッダーなどの前に main タグは使いません -->

    <div class="search-form-wrapper" style="max-width: 480px; margin: 120px auto 24px; padding: 0 16px;">
        <form id="searchForm" action="order_history.php" method="GET" class="search-form"
            style="flex-direction: column; gap: 16px;">
            <input type="text" name="search" placeholder="検索キーワード（顧客名 or 注文書No）" autocomplete="off"
                value="<?= htmlspecialchars($search_keyword, ENT_QUOTES) ?>">
            <input type="hidden" name="page" id="hiddenPageInput" value="1">

            <!-- 横並びのボタンエリア -->
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="search-button">検索</button>
                <button type="button" class="search-button" onclick="location.href='order_create.php'">新規作成</button>
            </div>
        </form>
    </div>

    <div class="table-container">
        <table id="customerTable">
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
                    <td colspan="3" style="text-align:center;">表示する注文書がありません。</td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><a href="order_detail.php?order_no=<?= htmlspecialchars($o['order_no'], ENT_QUOTES) ?>">
                            <?= htmlspecialchars($o['order_no'], ENT_QUOTES) ?></a></td>
                    <td><?= htmlspecialchars($o['customer_name'], ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($o['registration_date'], ENT_QUOTES) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php if ($current_page > 1): ?>
        <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_keyword) ?>">前へ</a>
        <?php else: ?>
        <span class="placeholder">前へ</span>
        <?php endif; ?>
        <span><?= $current_page ?> / <?= $total_pages ?></span>
        <?php if ($current_page < $total_pages): ?>
        <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_keyword) ?>">次へ</a>
        <?php else: ?>
        <span class="placeholder">次へ</span>
        <?php endif; ?>
    </div>
    </main>

    <script>
    function debounce(fn, wait) {
        let t;
        return function(...a) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, a), wait);
        };
    }
    const searchInput = document.querySelector('.search-form input[type="text"]');
    const form = document.getElementById('searchForm');
    const pageInput = document.getElementById('hiddenPageInput');
    searchInput.addEventListener('input', debounce(() => {
        pageInput.value = 1;
        form.submit();
    }, 500));
    </script>
</body>

</html>