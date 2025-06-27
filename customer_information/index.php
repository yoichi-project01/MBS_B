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
    <link rel="stylesheet" href="customer.css" />

    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="with-header">
    <!-- メインコンテナ -->
    <div class="container">
        <!-- ページタイトル -->
        <h1 class="page-title">
            <i class="fas fa-users"></i>
            顧客情報CSVアップロード
        </h1>

        <!-- アップロードフォーム -->
        <div class="upload-container">
            <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                <!-- ファイル選択エリア -->
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="file-upload-content">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h3>CSVファイルをアップロード</h3>
                        <p>ファイルをドラッグ&ドロップするか、クリックして選択してください</p>
                        <input type="file" name="csv_file" id="csvFile" accept=".csv" required hidden />
                    </div>
                    <div class="file-info" id="fileInfo" style="display: none;">
                        <i class="fas fa-file-csv"></i>
                        <span class="file-name" id="fileName"></span>
                        <span class="file-size" id="fileSize"></span>
                    </div>
                </div>

                <!-- アップロードボタン -->
                <button type="submit" class="upload-button" id="uploadButton" disabled>
                    <i class="fas fa-upload"></i>
                    アップロード
                </button>
            </form>
        </div>
    </div>

    <!-- SweetAlert 表示 -->
    <?php if (isset($_SESSION['upload_status'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($_SESSION['upload_status'] === 'success'): ?>
        Swal.fire({
            icon: 'success',
            title: '登録が成功しました',
            text: 'CSVファイルが正常にアップロードされました。',
            confirmButtonText: 'OK',
            confirmButtonColor: '#2f5d3f',
            timer: 3000,
            timerProgressBar: true
        });
        <?php else: ?>
        Swal.fire({
            icon: 'error',
            title: '登録できませんでした',
            text: 'ファイルの形式やサイズを確認してください。',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
        <?php endif; ?>
    });
    </script>
    <?php unset($_SESSION['upload_status']); ?>
    <?php endif; ?>

    <script src="../script.js"></script>
    <script src="customer.js"></script>
</body>

</html>