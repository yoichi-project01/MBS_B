<?php
$storeName = $_GET['store'] ?? '';
?>
<!-- ☰ のトグル用 hidden チェックボックス -->
<input type="checkbox" id="menu-toggle" class="menu-toggle-checkbox">

<header class="site-header">
    <div class="header-inner">
        <!-- ☰ハンバーガーアイコン -->
        <label for="menu-toggle" class="menu-toggle-label">☰</label>

        <!-- 左側のタイトル -->
        <a id="store-title"><?php echo htmlspecialchars($storeName . " 受注管理"); ?></a>

        <!-- ナビゲーションメニュー -->
        <nav class="nav">
            <a href="/MBS_B/customer_infomation/index.php?store=<?= urlencode($storeName) ?>">顧客情報</a>
            <a href="#">統計情報</a>
            <a href="#">注文書</a>
            <a href="#">納品書</a>
        </nav>
    </div>
</header