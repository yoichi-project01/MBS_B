// customer.js - 顧客情報CSVアップロード専用JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const csvFile = document.getElementById('csvFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadButton = document.getElementById('uploadButton');

    // ファイル選択エリアのクリックイベント
    fileUploadArea.addEventListener('click', function(e) {
        if (e.target !== csvFile) {
            csvFile.click();
        }
    });

    // ドラッグ&ドロップ機能
    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        fileUploadArea.classList.add('drag-over');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('drag-over');
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        fileUploadArea.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    // ファイル選択イベント
    csvFile.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    // ファイル選択処理
    function handleFileSelect(file) {
        // ファイル形式チェック
        if (!file.name.toLowerCase().endsWith('.csv')) {
            Swal.fire({
                icon: 'error',
                title: 'ファイル形式エラー',
                text: 'CSVファイルを選択してください。',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        // ファイルサイズチェック (5MB)
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'ファイルサイズエラー',
                text: 'ファイルサイズは5MB以下にしてください。',
                confirmButtonColor: '#dc3545'
            });
            return;
        }

        // ファイル情報を表示
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.style.display = 'flex';
        fileUploadArea.classList.add('file-selected');
        uploadButton.disabled = false;
    }

    // ファイルサイズのフォーマット
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // フォーム送信時の処理
    const form = document.querySelector('.upload-form');
    form.addEventListener('submit', function(e) {
        uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> アップロード中...';
        uploadButton.disabled = true;
    });
});