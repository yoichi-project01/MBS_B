<?php
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';
if ($storeName) {
    setcookie('selectedStore', $storeName, time() + 3600, '/');
}

// Include common header
include(__DIR__ . '/component/header.php');
?>

<main class="container">
    <h2 class="main-page-title">
        <span class="icon">🏠</span> <?= htmlspecialchars($storeName) ?><br>受注管理システム
    </h2>

    <div class="menu">
        <button class="menu-button" data-path="customer_information/index.php">
            <span>
                <i class="fas fa-users"></i>
                顧客情報
            </span>
        </button>
        <button class="menu-button" data-path="statistics/index.php">
            <span>
                <i class="fas fa-chart-bar"></i>
                統計情報
            </span>
        </button>
        <button class="menu-button" data-path="order_list/index.php">
            <span>
                <i class="fas fa-file-invoice"></i>
                注文書
            </span>
        </button>
        <button class="menu-button" data-path="delivery_list/index.php">
            <span>
                <i class="fas fa-truck"></i>
                納品書
            </span>
        </button>
    </div>
</main>

</body>

</html>