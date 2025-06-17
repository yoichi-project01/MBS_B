<?php
include(__DIR__ . '/../component/header.php');
session_start();
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <title>顧客情報CSVアップロード</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../style.css" />

    <!-- ✅ SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <h1>顧客情報<br>CSVアップロード</h1>

    <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
        <input type="file" name="csv_file" accept=".csv" required />
        <button type="submit">アップロード</button>
    </form>

    <!-- ✅ SweetAlert 表示 -->
    <?php if (isset($_SESSION['upload_status'])): ?>
    <script>
    <?php if ($_SESSION['upload_status'] === 'success'): ?>
    Swal.fire({
        icon: 'success',
        title: '登録が成功しました',
        confirmButtonText: 'OK'
    });
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: '登録できませんでした',
        confirmButtonText: 'OK'
    });
    <?php endif; ?>
    </script>
    <?php unset($_SESSION['upload_status']); ?>
    <?php endif; ?>
</body>

</html>