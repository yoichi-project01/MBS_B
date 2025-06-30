<?php

/**
 * ファイルアップロード処理コンポーネント
 */
class FileUploadHandler
{
    private $maxFileSize;
    private $allowedExtensions;
    private $allowedMimeTypes;

    public function __construct($maxFileSize = 5242880, $allowedExtensions = ['csv'])
    {
        $this->maxFileSize = $maxFileSize; // デフォルト5MB
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes = [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel'
        ];
    }

    /**
     * アップロードされたファイルを検証
     */
    public function validateFile($file)
    {
        $errors = [];

        // ファイルアップロードエラーのチェック
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
        }

        // ファイルサイズチェック
        if ($file['size'] > $this->maxFileSize) {
            $errors[] = 'ファイルサイズが大きすぎます。' . $this->formatFileSize($this->maxFileSize) . '以下のファイルを選択してください。';
        }

        // ファイル拡張子チェック
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->allowedExtensions)) {
            $errors[] = implode('、', array_map('strtoupper', $this->allowedExtensions)) . 'ファイルのみアップロード可能です。';
        }

        // MIMEタイプチェック
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                $errors[] = 'ファイル形式が無効です。正しいファイルを選択してください。';
            }
        }

        return $errors;
    }

    /**
     * アップロードエラーメッセージを取得
     */
    private function getUploadErrorMessage($errorCode)
    {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'ファイルサイズが大きすぎます。',
            UPLOAD_ERR_FORM_SIZE => 'ファイルサイズが大きすぎます。',
            UPLOAD_ERR_PARTIAL => 'ファイルのアップロードが中断されました。',
            UPLOAD_ERR_NO_FILE => 'ファイルが選択されていません。',
            UPLOAD_ERR_NO_TMP_DIR => 'サーバーエラーが発生しました。',
            UPLOAD_ERR_CANT_WRITE => 'サーバーエラーが発生しました。',
            UPLOAD_ERR_EXTENSION => 'ファイル形式が無効です。',
        ];

        return $errorMessages[$errorCode] ?? 'アップロードエラーが発生しました。';
    }

    /**
     * ファイルサイズを人間が読みやすい形式に変換
     */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * CSVファイルの内容を検証
     */
    public function validateCSVContent($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['ファイルを開けませんでした。'];
        }

        // ファイルが空でないかチェック
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return ['ファイルが空です。'];
        }

        // ファイルポインタを先頭に戻す
        rewind($handle);
        fclose($handle);

        return []; // エラーなし
    }
}