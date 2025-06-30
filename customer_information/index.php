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
        <?php
                    $insertCount = $_SESSION['insert_count'] ?? 0;
                    $updateCount = $_SESSION['update_count'] ?? 0;
                    $totalRows = $_SESSION['total_rows'] ?? 0;

                    // メッセージを構築
                    $message = "CSVファイルが正常にアップロードされました。\n\n";
                    $message .= "処理結果:\n";
                    $message .= "• 新規追加: {$insertCount}件\n";
                    $message .= "• 更新: {$updateCount}件\n";
                    $message .= "• 合計処理: {$totalRows}件";
                    ?>

        Swal.fire({
            icon: 'success',
            title: '登録が成功しました',
            html: `
                <div style="text-align: left; margin: 20px 0;">
                    <p style="margin-bottom: 15px;">CSVファイルが正常にアップロードされました。</p>
                    <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                        <strong>📊 処理結果</strong><br>
                        <div style="margin-top: 10px; line-height: 1.8;">
                            <div><span style="color: #28a745;">✅ 新規追加:</span> <strong><?php echo $insertCount; ?>件</strong></div>
                            <div><span style="color: #17a2b8;">🔄 更新:</span> <strong><?php echo $updateCount; ?>件</strong></div>
                            <div style="border-top: 1px solid #dee2e6; margin-top: 8px; padding-top: 8px;">
                                <span style="color: #6c757d;">📈 合計処理:</span> <strong><?php echo $totalRows; ?>件</strong>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'OK',
            confirmButtonColor: '#2f5d3f',
            width: '500px',
            timer: 8000,
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
    <?php
        // セッション変数をクリア
        unset($_SESSION['upload_status']);
        unset($_SESSION['insert_count']);
        unset($_SESSION['update_count']);
        unset($_SESSION['total_rows']);
        ?>
    <?php endif; ?>

    <script src="../script.js"></script>
    <script src="customer.js"></script>
</body>

</html>