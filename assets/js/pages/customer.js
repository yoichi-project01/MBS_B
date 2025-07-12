
/* ==================================
   Customer Page (CSV Upload) Logic
   ================================== */

import { showErrorMessage } from '../components/notification.js';

function initializeCustomerUpload() {
    const fileUploadArea = document.getElementById('fileUploadArea');
    const csvFile = document.getElementById('csvFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadButton = document.getElementById('uploadButton');

    if (!fileUploadArea || !csvFile) {
        return;
    }

    fileUploadArea.addEventListener('click', function(e) {
        if (e.target !== csvFile) {
            csvFile.click();
        }
    });

    fileUploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    });

    fileUploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
    });

    fileUploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    csvFile.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showErrorMessage('CSVファイルを選択してください。');
            resetFileInput();
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            showErrorMessage('ファイルサイズは5MB以下にしてください。');
            resetFileInput();
            return;
        }

        if (file.size === 0) {
            showErrorMessage('空のファイルはアップロードできません。');
            resetFileInput();
            return;
        }

        if (fileName) fileName.textContent = file.name;
        if (fileSize) fileSize.textContent = formatFileSize(file.size);
        if (fileInfo) fileInfo.style.display = 'flex';
        fileUploadArea.classList.add('file-selected');
        if (uploadButton) uploadButton.disabled = false;
    }

    function resetFileInput() {
        csvFile.value = '';
        if (fileInfo) fileInfo.style.display = 'none';
        fileUploadArea.classList.remove('file-selected');
        if (uploadButton) uploadButton.disabled = true;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    const form = document.querySelector('.upload-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (uploadButton) {
                uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> アップロード中...';
                uploadButton.disabled = true;
            }
        });
    }
}

initializeCustomerUpload();
