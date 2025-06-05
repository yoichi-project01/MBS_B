<?php
// store名をGETパラメータから取得、なければデフォルトを設定
$storeName = isset($_GET['store']) && $_GET['store'] !== '' ? $_GET['store'] : '緑橋書店';
?>
<header class="site-header">
    <div class="header-inner" style="display:flex; align-items:center; justify-content:center; gap:20px;">
        <h1 style="color:#fff; font-size:18px; margin:0; white-space:nowrap;">
            <?php echo htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8'); ?> 受注管理システム
        </h1>
        <nav class="nav">
            <a href="../customer_infomation/index.php?store=<?php echo urlencode($storeName); ?>">顧客情報</a>
            <a href="../orderlist/index.php?store=<?php echo urlencode($storeName); ?>">統計情報</a>
            <a href="#">注文書</a>
            <a href="#">納品書</a>
        </nav>
    </div>
</header>