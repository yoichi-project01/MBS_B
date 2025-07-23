<?php
// セッションを最初に開始（HTMLの出力前に必須）
require_once(__DIR__ . '/session_manager.php');
SessionManager::start();

// セキュリティヘッダーの設定
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($requestUri, PHP_URL_PATH), '/');
$pathParts = explode('/', $path);

// Determine page type and configuration
$pageConfig = [
    'name' => 'top',
    'title' => '受注管理',
    'icon' => '📚',
    'showNav' => true,
    'showBackButton' => false,
    'backUrl' => '',
    'isStatisticsPage' => false
];

// Configure based on current page
if (strpos($requestUri, '/menu.php') !== false) {
    $pageConfig['name'] = 'menu';
    $pageConfig['title'] = $storeName ? $storeName : '受注管理システム';
    $pageConfig['icon'] = '🏠';
    $pageConfig['showNav'] = false;
    $pageConfig['showBackButton'] = true;
    $pageConfig['backUrl'] = '/MBS_B/index.php';
} elseif (strpos($requestUri, '/customer_information/') !== false) {
    $pageConfig['name'] = 'customer';
    $pageConfig['title'] = '顧客情報';
    $pageConfig['icon'] = '👥';
} elseif (strpos($requestUri, '/statistics/') !== false) {
    $pageConfig['name'] = 'statistics';
    $pageConfig['title'] = '統計情報';
    $pageConfig['icon'] = '📊';
    $pageConfig['isStatisticsPage'] = true;
} elseif (strpos($requestUri, '/order_list/') !== false) {
    $pageConfig['name'] = 'order_list';
    $pageConfig['title'] = '注文書';
    $pageConfig['icon'] = '📋';
} elseif (strpos($requestUri, '/delivery_list/') !== false) {
    $pageConfig['name'] = 'delivery_list';
    $pageConfig['title'] = '納品書';
    $pageConfig['icon'] = '🚚';
}

// Set page title
$displayTitle = $pageConfig['title'];
if ($storeName && $pageConfig['name'] !== 'menu') {
    $displayTitle = $storeName . ' - ' . $pageConfig['title'];
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($displayTitle) ?> - 受注管理システム</title>
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <?php if (file_exists(__DIR__ . "/../assets/css/pages/{$pageConfig['name']}.css")): ?>
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/<?= $pageConfig['name'] ?>.css">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>

<body
    class="<?= $pageConfig['showNav'] ? 'with-header' : '' ?> <?= $pageConfig['isStatisticsPage'] ? 'statistics-page' : '' ?>">

    <?php if ($pageConfig['showBackButton']): ?>
    <button class="back-button-top" onclick="window.location.href='<?= htmlspecialchars($pageConfig['backUrl']) ?>'">
        <span>← 店舗選択に戻る</span>
    </button>
    <?php endif; ?>

    <?php if ($pageConfig['showNav']): ?>
    <header class="site-header">
        <div class="header-inner">
            <div class="header-title">
                <div class="title-main">
                    <span class="title-icon"><?= $pageConfig['icon'] ?></span>
                    <span class="title-text"><?= htmlspecialchars($pageConfig['title']) ?></span>
                </div>
                <?php if ($storeName): ?>
                <div class="title-sub">
                    <span class="store-indicator">●</span>
                    <span class="store-text"><?= htmlspecialchars($storeName) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <nav class="nav" id="nav">
                <a href="/MBS_B/customer_information/index.php?store=<?= urlencode($storeName) ?>"
                    class="nav-item <?= $pageConfig['name'] === 'customer' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-users"></i>
                    <span class="nav-label">顧客情報</span>
                </a>
                <a href="/MBS_B/statistics/index.php?store=<?= urlencode($storeName) ?>"
                    class="nav-item <?= $pageConfig['name'] === 'statistics' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-chart-bar"></i>
                    <span class="nav-label">統計情報</span>
                </a>
                <a href="/MBS_B/order_list/index.php?store=<?= urlencode($storeName) ?>"
                    class="nav-item <?= $pageConfig['name'] === 'order_list' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-file-invoice"></i>
                    <span class="nav-label">注文書</span>
                </a>
                <a href="/MBS_B/delivery_list/index.php?store=<?= urlencode($storeName) ?>"
                    class="nav-item <?= $pageConfig['name'] === 'delivery_list' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-truck"></i>
                    <span class="nav-label">納品書</span>
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
    <?php endif; ?>

    <?php if ($pageConfig['isStatisticsPage']): ?>
    <div class="dashboard-container">
        <main class="main-content">
            <div class="content-scroll-area">
                <?php endif; ?>

                <script src="/MBS_B/assets/js/main.js" type="module"></script>
                <script nonce="<?= SessionManager::get('csp_nonce') ?>">
                // Set store name and page info for header manager
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.HeaderManager) {
                        const headerManager = new HeaderManager();
                        <?php if ($storeName): ?>
                        headerManager.setStoreName('<?= addslashes($storeName) ?>');
                        <?php endif; ?>
                        headerManager.setCustomPageInfo('<?= addslashes($pageConfig['title']) ?>',
                            '<?= $pageConfig['icon'] ?>');
                    }
                });
                </script>