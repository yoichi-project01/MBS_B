<?php
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';
if ($storeName) {
    setcookie('selectedStore', $storeName, time() + 3600, '/');
}
?>

<?php
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';
if ($storeName) {
    setcookie('selectedStore', $storeName, time() + 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($storeName); ?> 受注管理システム</title>
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="menu-page">
    <main class="container">
        <button class="back-button-top" onclick="window.location.href='index.php'">
            <span>← 店舗選択に戻る</span>
        </button>

        <h2 class="main-page-title">
            <span class="icon">🏠</span> <?php echo htmlspecialchars($storeName); ?><br>受注管理システム
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
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
</body>
</html>

</html>