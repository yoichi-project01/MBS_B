<?php
// 最初にオートローダーを読み込み
require_once(__DIR__ . '/../component/autoloader.php');

// その後にヘッダーを読み込み
include(__DIR__ . '/../component/header.php');

// セッション開始とCSRFトークン生成
SessionManager::start();
$csrfToken = CSRFProtection::getToken();
$uploadResult = SessionManager::getUploadResult();
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
        <h2 class="main-page-title">
            <span class="icon">👥</span> 顧客情報
        </h2>
        <!-- アップロードフォーム -->
        <div class="upload-container">
            <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                <!-- CSRF保護 -->
                <?= CSRFProtection::getTokenField() ?>

                <!-- ファイル選択エリア -->
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="file-upload-content">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h3>CSVファイルをアップロード</h3>
                        <p>ファイルをドラッグ&ドロップするか、クリックして選択してください</p>
                        <p class="file-requirements">
                            <small>
                                • CSVファイルのみ対応<br>
                                • 最大ファイルサイズ: 5MB<br>
                                • 文字コード: Shift-JIS または UTF-8
                            </small>
                        </p>
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

    <!-- アップロード結果のアラート表示 -->
    <?= AlertComponent::renderUploadAlert($uploadResult) ?>

    <script src="../script.js"></script>
</body>

</html>