<?php
$storeName = $_GET['store'] ?? '';
?>
<header class="site-header">
    <div class="header-inner">
        <a class="store-title"><?php echo htmlspecialchars($storeName . " 受注管理"); ?></a>

        <nav class="nav" id="nav">
            <a href="/MBS_B/customer_information/index.php?store=<?= urlencode($storeName) ?>" class="nav-item">顧客情報</a>
            <a href="/MBS_B/statistics/index.php?store=<?= urlencode($storeName) ?>" class="nav-item">統計情報</a>
            <a href="/MBS_B/order_list/index.php?store=<?= urlencode($storeName) ?>" class="nav-item">注文書</a>
            <a href="/MBS_B/delivery_list/index.php?store=<?= urlencode($storeName) ?>" class="nav-item">納品書</a>
        </nav>

        <button class="menu-toggle" id="menuToggle" aria-label="メニューを開く">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>
    <div class="menu-overlay" id="menuOverlay"></div>
</header>