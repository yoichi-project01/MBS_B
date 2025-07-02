<?php
$storeName = $_GET['store'] ?? '';

// 現在のページを判定してページ名を設定
$currentPage = basename($_SERVER['PHP_SELF']);
$requestUri = $_SERVER['REQUEST_URI'];

// ページ設定の統一化
$pageConfig = [
    // ファイル名ベース
    'index.php' => [
        'name' => '顧客情報CSVアップロード',
        'icon' => '👥'
    ],
    'upload.php' => [
        'name' => '顧客情報CSVアップロード',
        'icon' => '👥'
    ],

    // ディレクトリベース（優先度高）
    'customer_information' => [
        'name' => '顧客情報',
        'icon' => '👥'
    ],
    'statistics' => [
        'name' => '統計情報',
        'icon' => '📊'
    ],
    'order_list' => [
        'name' => '注文書',
        'icon' => '📋'
    ],
    'delivery_list' => [
        'name' => '納品書',
        'icon' => '🚚'
    ]
];

// ページ情報を取得する関数
function getPageInfo($requestUri, $currentPage, $pageConfig)
{
    // ディレクトリベースでの判定（優先）
    foreach ($pageConfig as $key => $config) {
        if (strpos($requestUri, "/$key/") !== false) {
            return $config;
        }
    }

    // ファイル名ベースでの判定
    if (isset($pageConfig[$currentPage])) {
        return $pageConfig[$currentPage];
    }

    // デフォルト
    return [
        'name' => '受注管理',
        'icon' => '📋'
    ];
}

$pageInfo = getPageInfo($requestUri, $currentPage, $pageConfig);

// 表示タイトルの決定
if (!empty($storeName)) {
    $displayTitle = htmlspecialchars($storeName . " - " . $pageInfo['name']);
    $pageTitle = htmlspecialchars($storeName . " - " . $pageInfo['name'] . " - 受注管理システム");
} else {
    $displayTitle = $pageInfo['name'];
    $pageTitle = $pageInfo['name'] . " - 受注管理システム";
}
?>
<header class="site-header">
    <div class="header-inner">
        <div class="store-title">
            <span class="page-icon"><?php echo $pageInfo['icon']; ?></span>
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

<!-- ページタイトルを設定 -->
<script>
document.title = '<?php echo addslashes($pageTitle); ?>';

// データ属性でページ情報を提供（JavaScript用）
document.documentElement.setAttribute('data-current-page', '<?php echo addslashes($pageInfo['name']); ?>');
document.documentElement.setAttribute('data-current-icon', '<?php echo addslashes($pageInfo['icon']); ?>');
document.documentElement.setAttribute('data-store-name', '<?php echo addslashes($storeName); ?>');
</script>