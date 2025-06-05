<?php
// GETパラメータとローカルストレージ連携用に store を取得
$storeName = $_GET['store'] ?? '緑橋書店';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($storeName); ?> 受注管理システム</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>

<body>
    <div class="container">
        <h1 id="store-title"><?php echo htmlspecialchars($storeName); ?><br>受注管理システム</h1>

        <div class="menu">
            <button class="menu-button" onclick="location.href='./customer_infomation/index.php'">顧客情報</button>
            <button class="menu-button" onclick="location.href=''">統計情報</button>
            <button class="menu-button" onclick="location.href='./orderlist/index.php'">注文書</button>
            <button class="menu-button" onclick="location.href=''">納品書</button>
        </div>
    </div>
</body>

</html>