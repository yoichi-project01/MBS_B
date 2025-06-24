<?php
$storeName = $_GET['store'] ?? '';
?>
<!-- jQuery用: チェックボックス不要 -->
<header class="site-header">
    <div class="header-inner">
        <!-- ☰ハンバーガーアイコン -->
        <label class="menu-toggle-label" id="menu-btn" tabindex="0" aria-label="メニューを開く">☰</label>
        <!-- 左側のタイトル -->
        <a id="store-title"><?php echo htmlspecialchars($storeName . " 受注管理"); ?></a>
        <!-- ナビゲーションメニュー -->
        <nav class="nav">
            <a href="/MBS_B/customer_information/index.php?store=<?= urlencode($storeName) ?>">顧客情報</a>
            <a href="/MBS_B/statistics/index.php?store=<?= urlencode($storeName) ?>">統計情報</a>
            <a href="/MBS_B/order_list/index.php?store=<?= urlencode($storeName) ?>">注文書</a>
            <a href="/MBS_B/delivery_list/index.php?store=<?= urlencode($storeName) ?>">納品書</a>
        </nav>
    </div>
    <div class="menu-overlay"></div>
</header>