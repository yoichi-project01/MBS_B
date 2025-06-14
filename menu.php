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
</head>

<body>
    <main class="container">
        <h1 id="store-title"><?php echo htmlspecialchars($storeName); ?><br>受注管理システム</h1>

        <div class="menu">
            <button class="menu-button" data-path="customer_information/index.php">顧客情報</button>
            <button class="menu-button" data-path="statistics/index.php">統計情報</button>
            <button class="menu-button" data-path="order_list/index.php">注文書</button>
            <button class="menu-button" data-path="delivery/index.php">納品書</button>
        </div>
    </main>

    <script src="script.js"></script>
</body>

</html>