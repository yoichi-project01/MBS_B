<?php 
    include(__DIR__ . '/../component/header.php');

    $message = '';
?>



<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <title>顧客情報CSVアップロード</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../style.css" />
</head>

<body>
    <div class="container upload-container">
        <h1>顧客情報<br>CSVアップロード</h1>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required />
            <button type="submit">アップロード</button>
        </form>
        <div id="result"><?php htmlspecialchars($message) ?></div>
    </div>
</body>

</html>