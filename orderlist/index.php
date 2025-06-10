<!--
<?php
require_once(__DIR__ . '/../component/db.php');

try {
    $stmt = $pdo->query("SELECT customer_no, customer_name FROM customers ORDER BY customer_no DESC");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("データ取得エラー: " . $e->getMessage());
}
?> <!DOCTYPE html>
    <html lang="ja">

    <head>
        <meta charset="UTF-8">
        <title>顧客一覧</title>
        <link rel="stylesheet" href="style.css">
    </head>

    <body>
        <header class="site-header">
            <div class="header-inner">
                <nav class="nav">
                    <a href="index.php">CSVアップロード</a>
                    <a href="customer_list.php">顧客一覧</a>
                </nav>
            </div>
        </header>

        <div class="container">
            <h1>顧客一覧</h1>
            <?php if (empty($customers)): ?>
            <p>顧客データが登録されていません。</p>
            <?php else: ?>
            <ul>
                <?php foreach ($customers as $customer): ?>
                <li>顧客番号: <?= htmlspecialchars($customer['customer_no']) ?> / 顧客名:
                    <?= htmlspecialchars($customer['customer_name']) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </body>

    </html>