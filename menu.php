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
    <title><?php echo htmlspecialchars($storeName); ?> 受注管理システム</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="menu-page">
    <main class="container">
        <!-- 戻るボタンをタイトルの上に配置 -->
        <button class="back-button-top" onclick="window.location.href='index.html'">
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

    <script src="script.js"></script>
</body>

</html>