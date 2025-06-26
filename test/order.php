<?php
include(__DIR__ . '/../component/header.php');
session_start();

$search_keyword = $_GET['search'] ?? '';
$shop_name = $_GET['shop_name'] ?? '';
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
        'status' => ['待機中', '処理中', '完了', '保留'][rand(0, 3)],
        'amount' => rand(1000, 50000)
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注文書履歴</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
    /* ページ固有のスタイル */
    .page-header {
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
        color: white;
        padding: 2rem 0;
        margin-top: 68px;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        opacity: 0.3;
    }

    .page-header-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }

    .page-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        max-width: 1200px;
        margin: -3rem auto 2rem;
        padding: 0 20px;
        position: relative;
        z-index: 2;
    }

    .stats-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.1);
        border: 1px solid rgba(126, 217, 87, 0.2);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(47, 93, 63, 0.15);
    }

    .stats-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .stats-card-icon.orders {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #1976d2;
    }

    .stats-card-icon.customers {
        background: linear-gradient(135deg, #f3e5f5, #e1bee7);
        color: #7b1fa2;
    }

    .stats-card-icon.revenue {
        background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
        color: #388e3c;
    }

    .stats-card-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--main-green);
        margin: 0.5rem 0;
    }

    .stats-card-label {
        color: #666;
        font-size: 0.9rem;
    }

    .enhanced-search {
        max-width: 800px;
        margin: 0 auto 3rem;
        padding: 0 20px;
    }

    .search-container {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.08);
        border: 1px solid rgba(126, 217, 87, 0.2);
    }

    .search-form-enhanced {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .search-input-group {
        position: relative;
    }

    .search-input-enhanced {
        width: 100%;
        padding: 1.2rem 1.5rem 1.2rem 3.5rem;
        border: 2px solid #e0e0e0;
        border-radius: 16px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        background: #fafafa;
    }

    .search-input-enhanced:focus {
        outline: none;
        border-color: var(--accent-green);
        background: white;
        box-shadow: 0 0 0 4px rgba(126, 217, 87, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 1.2rem;
    }

    .button-group {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .btn {
        padding: 1rem 2rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        flex: 1;
        justify-content: center;
        min-width: 140px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--sub-green), var(--main-green));
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(47, 93, 63, 0.3);
    }

    .btn-secondary {
        background: linear-gradient(135deg, var(--accent-green), #7ed957);
        color: white;
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(126, 217, 87, 0.3);
    }

    .enhanced-table-container {
        max-width: 1200px;
        margin: 0 auto 6rem;
        padding: 0 20px;
    }

    .table-wrapper {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.08);
        border: 1px solid rgba(126, 217, 87, 0.2);
    }

    .table-header {
        background: linear-gradient(135deg, var(--bg-light), #e3efe6);
        padding: 1.5rem;
        border-bottom: 2px solid rgba(126, 217, 87, 0.2);
    }

    .table-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--main-green);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .enhanced-table {
        width: 100%;
        border-collapse: collapse;
    }

    .enhanced-table th {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 1.2rem 1rem;
        text-align: left;
        font-weight: 600;
        color: var(--main-green);
        border-bottom: 2px solid rgba(126, 217, 87, 0.2);
        position: relative;
    }

    .enhanced-table td {
        padding: 1.2rem 1rem;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s ease;
    }

    .enhanced-table tbody tr:hover {
        background: linear-gradient(135deg, #fafbfc, #f8f9fa);
    }

    .order-link {
        color: var(--main-green);
        font-weight: 600;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        display: inline-block;
    }

    .order-link:hover {
        background: var(--accent-green);
        color: white;
        transform: translateX(4px);
    }

    .status-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-align: center;
        min-width: 70px;
        display: inline-block;
    }

    .status-waiting {
        background: #fff3cd;
        color: #856404;
    }

    .status-processing {
        background: #d1ecf1;
        color: #0c5460;
    }

    .status-completed {
        background: #d4edda;
        color: #155724;
    }

    .status-hold {
        background: #f8d7da;
        color: #721c24;
    }

    .amount {
        font-weight: 600;
        color: var(--main-green);
    }

    .enhanced-pagination {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 249, 0.95));
        backdrop-filter: blur(10px);
        border-top: 2px solid rgba(126, 217, 87, 0.2);
        padding: 1rem 0;
        z-index: 1000;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
    }

    .pagination-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        padding: 0 20px;
    }

    .pagination-btn {
        padding: 0.8rem 1.5rem;
        border: 2px solid var(--accent-green);
        background: white;
        color: var(--main-green);
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pagination-btn:hover {
        background: var(--accent-green);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(126, 217, 87, 0.3);
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }

    .pagination-info {
        padding: 0.8rem 1.5rem;
        background: var(--main-green);
        color: white;
        border-radius: 12px;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #666;
    }

    .empty-state-icon {
        font-size: 4rem;
        color: #ccc;
        margin-bottom: 1rem;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--main-green);
    }

    .message-container {
        max-width: 800px;
        margin: 1rem auto;
        padding: 0 20px;
    }

    .message {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-weight: 500;
    }

    .message.success {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        color: #155724;
        border: 2px solid #b8dabc;
    }

    .message.error {
        background: linear-gradient(135deg, #f8d7da, #f1b0b7);
        color: #721c24;
        border: 2px solid #f5b7b1;
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 2rem;
        }

        .stats-cards {
            grid-template-columns: 1fr;
            margin-top: -2rem;
        }

        .button-group {
            flex-direction: column;
        }

        .btn {
            min-width: auto;
        }

        .enhanced-table-container {
            margin-bottom: 8rem;
        }

        .table-wrapper {
            border-radius: 16px;
            overflow-x: auto;
        }

        .enhanced-table {
            min-width: 600px;
        }

        .pagination-content {
            flex-direction: column;
            gap: 0.5rem;
        }

        .pagination-btn {
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
        }
    }
    </style>
</head>

<body>
    <?php if ($message || $error_message): ?>
    <div class="message-container">
        <?php if ($message): ?>
        <div class="message success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($message, ENT_QUOTES) ?>
        </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="message error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error_message, ENT_QUOTES) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ページヘッダー -->
    <div class="page-header">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-file-invoice"></i>
                注文書履歴管理
            </h1>
        </div>
    </div>

    <!-- 統計カード -->
    <div class="stats-cards">
        <div class="stats-card">
            <div class="stats-card-icon orders">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stats-card-value"><?= $total_orders ?></div>
            <div class="stats-card-label">総注文数</div>
        </div>
        <div class="stats-card">
            <div class="stats-card-icon customers">
                <i class="fas fa-users"></i>
            </div>
            <div class="stats-card-value"><?= count(array_unique(array_column($filtered, 'customer_name'))) ?></div>
            <div class="stats-card-label">顧客数</div>
        </div>
        <div class="stats-card">
            <div class="stats-card-icon revenue">
                <i class="fas fa-yen-sign"></i>
            </div>
            <div class="stats-card-value">¥<?= number_format(array_sum(array_column($filtered, 'amount'))) ?></div>
            <div class="stats-card-label">総売上</div>
        </div>
    </div>

    <!-- 検索フォーム -->
    <div class="enhanced-search">
        <div class="search-container">
            <form id="searchForm" action="order_history.php" method="GET" class="search-form-enhanced">
                <div class="search-input-group">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" class="search-input-enhanced" placeholder="顧客名または注文書番号で検索..."
                        autocomplete="off" value="<?= htmlspecialchars($search_keyword, ENT_QUOTES) ?>">
                </div>
                <input type="hidden" name="page" id="hiddenPageInput" value="1">

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        検索
                    </button>
                    <a href="order_create.php<?= $shop_name ? '?shop_name=' . urlencode($shop_name) : '' ?>"
                        class="btn btn-secondary">
                        <i class="fas fa-plus"></i>
                        新規作成
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- テーブル -->
    <div class="enhanced-table-container">
        <div class="table-wrapper">
            <div class="table-header">
                <h2 class="table-title">
                    <i class="fas fa-table"></i>
                    注文書一覧
                </h2>
            </div>

            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>注文書が見つかりません</h3>
                <p>検索条件を変更するか、新しい注文書を作成してください。</p>
            </div>
            <?php else: ?>
            <table class="enhanced-table">
                <thead>
                    <tr>
                        <th>注文書No</th>
                        <th>顧客名</th>
                        <th>登録日</th>
                        <th>ステータス</th>
                        <th>金額</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>
                            <a href="order_detail.php?order_no=<?= htmlspecialchars($o['order_no'], ENT_QUOTES) ?>"
                                class="order-link">
                                <?= htmlspecialchars($o['order_no'], ENT_QUOTES) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($o['customer_name'], ENT_QUOTES) ?></td>
                        <td><?= htmlspecialchars($o['registration_date'], ENT_QUOTES) ?></td>
                        <td>
                            <span class="status-badge status-<?=
                                                                        $o['status'] === '待機中' ? 'waiting' : ($o['status'] === '処理中' ? 'processing' : ($o['status'] === '完了' ? 'completed' : 'hold'))
                                                                        ?>">
                                <?= htmlspecialchars($o['status'], ENT_QUOTES) ?>
                            </span>
                        </td>
                        <td class="amount">¥<?= number_format($o['amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- ページネーション -->
    <div class="enhanced-pagination">
        <div class="pagination-content">
            <?php if ($current_page > 1): ?>
            <a href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search_keyword) ?>" class="pagination-btn">
                <i class="fas fa-chevron-left"></i>
                前へ
            </a>
            <?php else: ?>
            <span class="pagination-btn disabled">
                <i class="fas fa-chevron-left"></i>
                前へ
            </span>
            <?php endif; ?>

            <span class="pagination-info">
                <?= $current_page ?> / <?= $total_pages ?>
            </span>

            <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search_keyword) ?>" class="pagination-btn">
                次へ
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php else: ?>
            <span class="pagination-btn disabled">
                次へ
                <i class="fas fa-chevron-right"></i>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function debounce(fn, wait) {
        let t;
        return function(...a) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, a), wait);
        };
    }

    const searchInput = document.querySelector('.search-input-enhanced');
    const form = document.getElementById('searchForm');
    const pageInput = document.getElementById('hiddenPageInput');

    searchInput.addEventListener('input', debounce(() => {
        pageInput.value = 1;
        form.submit();
    }, 500));

    // アニメーション効果
    document.addEventListener('DOMContentLoaded', function() {
        const statsCards = document.querySelectorAll('.stats-card');
        const tableRows = document.querySelectorAll('.enhanced-table tbody tr');

        // カードのアニメーション
        statsCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';

                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });

        // テーブル行のアニメーション
        tableRows.forEach((row, index) => {
            setTimeout(() => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                row.style.transition = 'all 0.4s ease';

                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 50);
            }, index * 50);
        });
    });
    </script>
</body>

</html>