<?php
$storeName = $_GET['store'] ?? '';
?>
<header class="site-header">
    <div class="header-inner">
        <nav class="nav">
            <div class="nav-left">
                <b id="store-title"><?php echo htmlspecialchars($storeName); ?></b>
            </div>
            <a href="/customer_infomation/index.php?store=<?= urlencode($storeName) ?>">顧客情報</a>
            <a href="/MBS_B/statistics/index.php?store=<?= urlencode($storeName) ?>">統計情報</a>
            <a href="/MBS_B/orderlist/index.php?store=<?= urlencode($storeName) ?>">注文書</a>
            <a href="/MBS_B/delivery/index.php?store=<?= urlencode($storeName) ?>">納品書</a>
        </nav>
    </div>
</header>