<?php
$storeName = $_GET['store'] ?? '';

// 現在のページを判定してページ名を設定
$currentPage = basename($_SERVER['PHP_SELF']);
$requestUri = $_SERVER['REQUEST_URI'];

// ページ名とアイコンのマッピング
$pageConfig = [
    'index.php' => [
        'name' => '顧客情報CSVアップロード',
        'icon' => '👥'
    ],
    'upload.php' => [
        'name' => '顧客情報CSVアップロード',
        'icon' => '👥'
    ]
];

// ディレクトリベースでのページ判定
if (strpos($requestUri, '/customer_information/') !== false) {
    $pageTitle = '顧客情報CSVアップロード';
    $pageIcon = '👥';
} elseif (strpos($requestUri, '/statistics/') !== false) {
    $pageTitle = '統計情報';
    $pageIcon = '📊';
} elseif (strpos($requestUri, '/order_list/') !== false) {
    $pageTitle = '注文書';
    $pageIcon = '📋';
} elseif (strpos($requestUri, '/delivery_list/') !== false) {
    $pageTitle = '納品書';
    $pageIcon = '🚚';
} else {
    // デフォルト（店舗選択やメインページ）
    $pageTitle = htmlspecialchars($storeName . " 受注管理");
    $pageIcon = '📋';
}

// 店舗名がある場合は組み合わせ、ない場合はページ名のみ
if (!empty($storeName) && !in_array($pageTitle, ['顧客情報CSVアップロード', '統計情報', '注文書', '納品書'])) {
    $displayTitle = htmlspecialchars($storeName . " 受注管理");
} elseif (!empty($storeName)) {
    $displayTitle = htmlspecialchars($storeName . " - " . $pageTitle);
} else {
    $displayTitle = $pageTitle;
}
?>
<header class="site-header">
    <div class="header-inner">
        <div class="store-title">
            <span class="page-icon"><?php echo $pageIcon; ?></span>
            <span class="page-text"><?php echo $displayTitle; ?></span>
        </div>

        <nav class="nav" id="nav">
            <a href="/MBS_B/customer_information/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item <?= (strpos($requestUri, '/customer_information/') !== false) ? 'active' : '' ?>">
                顧客情報
            </a>
            <a href="/MBS_B/statistics/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item <?= (strpos($requestUri, '/statistics/') !== false) ? 'active' : '' ?>">
                統計情報
            </a>
            <a href="/MBS_B/order_list/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item <?= (strpos($requestUri, '/order_list/') !== false) ? 'active' : '' ?>">
                注文書
            </a>
            <a href="/MBS_B/delivery_list/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item <?= (strpos($requestUri, '/delivery_list/') !== false) ? 'active' : '' ?>">
                納品書
            </a>
        </nav>

        <button class="menu-toggle" id="menuToggle" aria-label="メニューを開く">
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
            <span class="hamburger-line"></span>
        </button>
    </div>
    <div class="menu-overlay" id="menuOverlay"></div>
</header>