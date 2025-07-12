<?php
require_once(__DIR__ . '/../component/autoloader.php');
include(__DIR__ . '/../component/header.php');
SessionManager::start();
$csrfToken = CSRFProtection::getToken();
$uploadResult = SessionManager::getUploadResult();
?>
<body class="with-header">
    <div class="container">
        <div class="upload-container">
            <form action="upload.php" method="post" enctype="multipart/form-data" class="upload-form">
                <?= CSRFProtection::getTokenField() ?>
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
                <button type="submit" class="upload-button" id="uploadButton" disabled>
                    <i class="fas fa-upload"></i>
                    アップロード
                </button>
            </form>
        </div>
    </div>
    <?= AlertComponent::renderUploadAlert($uploadResult) ?>
</body>

</html>