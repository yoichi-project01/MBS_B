<?php
$storeName = $_GET['store'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($requestUri, PHP_URL_PATH), '/');
$pathParts = explode('/', $path);

// Determine page name from URL
$pageName = 'top'; // Default
if (isset($pathParts[1])) {
    if ($pathParts[1] === 'menu.php') {
        $pageName = 'menu';
    } elseif (isset($pathParts[2])) {
        $pageName = $pathParts[1];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>受注管理システム</title>
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <?php if (file_exists("C:/xampp/htdocs/MBS_B/assets/css/pages/{$pageName}.css")): ?>
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/<?php echo $pageName; ?>.css">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="with-header">

<header class="site-header">
    <div class="header-inner">
        <div class="store-title">
            <span class="page-icon">📚</span>
            <span class="page-text">受注管理</span>
        </div>

        <nav class="nav" id="nav">
            <a href="/MBS_B/customer_information/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item">
                顧客情報
            </a>
            <a href="/MBS_B/statistics/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item">
                統計情報
            </a>
            <a href="/MBS_B/order_list/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item">
                注文書
            </a>
            <a href="/MBS_B/delivery_list/index.php?store=<?= urlencode($storeName) ?>"
                class="nav-item">
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
<script src="/MBS_B/assets/js/main.js" type="module"></script>
