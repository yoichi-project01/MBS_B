<?php

/**
 * ファイルアップロード処理コンポーネント（セキュリティ強化版）
 */
class FileUploadHandler
{
    private $maxFileSize;
    private $allowedExtensions;
    private $allowedMimeTypes;
    private $uploadPath;
    private $maxFiles;

    public function __construct($maxFileSize = 5242880, $allowedExtensions = ['csv'], $uploadPath = null)
    {
        $this->maxFileSize = $maxFileSize; // デフォルト5MB
        $this->allowedExtensions = array_map('strtolower', $allowedExtensions);
        $this->allowedMimeTypes = [
            'text/plain',
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'text/x-csv',
            'application/x-csv'
        ];
        $this->uploadPath = $uploadPath ?? sys_get_temp_dir();
        $this->maxFiles = 1; // 同時アップロード可能ファイル数
    }

    /**
     * アップロードされたファイルを包括的に検証
     */
    public function validateFile($file)
    {
        $errors = [];

        // 基本的な検証
        $basicErrors = $this->validateBasicFile($file);
        if (!empty($basicErrors)) {
            return $basicErrors;
        }

        // セキュリティ検証
        $securityErrors = $this->validateFileSecurity($file);
        if (!empty($securityErrors)) {
            return $securityErrors;
        }

        // 内容検証
        $contentErrors = $this->validateFileContent($file);
        if (!empty($contentErrors)) {
            return $contentErrors;
        }

        return [];
    }

    /**
     * 基本的なファイル検証
     */
    private function validateBasicFile($file)
    {
        $errors = [];

        // ファイル配列の構造確認
        if (!is_array($file) || !isset($file['tmp_name'], $file['name'], $file['size'], $file['error'])) {
            $errors[] = 'ファイル情報が正しくありません。';
            return $errors;
        }

        // ファイルアップロードエラーのチェック
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($file['error']);
        }

        // ファイル存在確認
        if (!file_exists($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'アップロードされたファイルが見つかりません。';
        }

        // ファイル名の検証
        if (empty($file['name']) || strlen($file['name']) > 255) {
            $errors[] = 'ファイル名が無効です。';
        }

        // ファイルサイズチェック
        if ($file['size'] <= 0) {
            $errors[] = '空のファイルはアップロードできません。';
        } elseif ($file['size'] > $this->maxFileSize) {
            $errors[] = 'ファイルサイズが大きすぎます。' . $this->formatFileSize($this->maxFileSize) . '以下のファイルを選択してください。';
        }

        // ファイル拡張子チェック
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $this->allowedExtensions, true)) {
            $allowedList = implode('、', array_map('strtoupper', $this->allowedExtensions));
            $errors[] = $allowedList . 'ファイルのみアップロード可能です。';
        }

        return $errors;
    }

    /**
     * セキュリティ関連の検証
     */
    private function validateFileSecurity($file)
    {
        $errors = [];

        // ファイル名のセキュリティチェック
        if (!$this->validateFileName($file['name'])) {
            $errors[] = 'ファイル名に使用できない文字が含まれています。';
        }

        // MIMEタイプチェック
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $this->allowedMimeTypes, true)) {
                $errors[] = 'ファイル形式が無効です。正しいCSVファイルを選択してください。';
            }
        }

        // ファイルシグネチャ（マジックナンバー）の確認
        if (!$this->validateFileSignature($file['tmp_name'])) {
            $errors[] = 'ファイルの内部形式が無効です。';
        }

        // 実行可能ファイルの混入チェック
        if ($this->containsExecutableContent($file['tmp_name'])) {
            $errors[] = 'セキュリティ上の理由によりこのファイルはアップロードできません。';
        }

        return $errors;
    }

    /**
     * ファイル内容の検証
     */
    private function validateFileContent($file)
    {
        $errors = [];

        // CSVファイルの内容検証
        if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
            $contentErrors = $this->validateCSVContent($file['tmp_name']);
            if (!empty($contentErrors)) {
                $errors = array_merge($errors, $contentErrors);
            }
        }

        return $errors;
    }

    /**
     * ファイル名のセキュリティ検証
     */
    private function validateFileName($filename)
    {
        // 危険な文字のチェック
        $dangerousChars = ['..', '/', '\\', '<', '>', ':', '"', '|', '?', '*', "\0"];
        foreach ($dangerousChars as $char) {
            if (strpos($filename, $char) !== false) {
                return false;
            }
        }

        // 制御文字のチェック
        if (preg_match('/[\x00-\x1F\x7F]/', $filename)) {
            return false;
        }

        // 予約語のチェック（Windows）
        $reservedNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        if (in_array(strtoupper($nameWithoutExt), $reservedNames, true)) {
            return false;
        }

        return true;
    }

    /**
     * ファイルシグネチャの検証
     */
    private function validateFileSignature($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 16);
        fclose($handle);

        // CSVファイルとして妥当かチェック
        // UTF-8 BOMの確認
        if (substr($header, 0, 3) === "\xEF\xBB\xBF") {
            $header = substr($header, 3);
        }

        // 基本的なテキストファイルかどうかの確認
        return mb_check_encoding($header, 'UTF-8') || mb_check_encoding($header, 'SJIS-WIN');
    }

    /**
     * 実行可能コンテンツの検出
     */
    private function containsExecutableContent($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return true; // 読み込めない場合は危険とみなす
        }

        $content = fread($handle, 8192); // 最初の8KBを確認
        fclose($handle);

        // 実行可能ファイルのシグネチャ
        $executableSignatures = [
            "\x4D\x5A", // PE/COFF (Windows executable)
            "\x7F\x45\x4C\x46", // ELF (Linux executable)
            "\xCA\xFE\xBA\xBE", // Mach-O (macOS executable)
            "\xFE\xED\xFA\xCE", // Mach-O (macOS executable, different endian)
            "\x50\x4B\x03\x04", // ZIP (potential executable archive)
        ];

        foreach ($executableSignatures as $signature) {
            if (strpos($content, $signature) === 0) {
                return true;
            }
        }

        // スクリプト系のパターン
        $scriptPatterns = [
            '/^#!/', // Shebang
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
        ];

        foreach ($scriptPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * CSVファイルの内容検証
     */
    public function validateCSVContent($filePath)
    {
        $errors = [];

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['ファイルを開けませんでした。'];
        }

        // ファイルが空でないかチェック
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            fclose($handle);
            return ['ファイルが空です。'];
        }

        // 最初の行を読み込んでヘッダーをチェック
        $firstLine = fgetcsv($handle, 8192);
        if ($firstLine === false || empty($firstLine)) {
            fclose($handle);
            return ['CSVファイルの形式が正しくありません。'];
        }

        // 列数のチェック
        if (count($firstLine) < 9) {
            fclose($handle);
            return ['CSVファイルの列数が不足しています。最低9列必要です。'];
        }

        // データ行の存在チェック
        $hasDataRows = false;
        $rowCount = 0;
        $maxRows = 10000; // 最大行数制限

        while (($row = fgetcsv($handle, 8192)) !== false && $rowCount < $maxRows) {
            $rowCount++;

            // 空行をスキップ
            if (empty(array_filter($row, function ($value) {
                return trim($value) !== '';
            }))) {
                continue;
            }

            $hasDataRows = true;

            // 基本的なデータ形式チェック
            if (count($row) < 9) {
                $errors[] = "{$rowCount}行目: 列数が不足しています。";
                if (count($errors) >= 5) { // エラー数制限
                    break;
                }
            }
        }

        fclose($handle);

        if ($rowCount >= $maxRows) {
            $errors[] = "ファイルの行数が多すぎます。最大{$maxRows}行まで処理可能です。";
        }

        if (!$hasDataRows) {
            $errors[] = 'ヘッダー行以外にデータが見つかりません。';
        }

        return $errors;
    }

    /**
     * アップロードエラーメッセージを取得
     */
    private function getUploadErrorMessage($errorCode)
    {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'ファイルサイズがサーバーの制限を超えています。',
            UPLOAD_ERR_FORM_SIZE => 'ファイルサイズが大きすぎます。',
            UPLOAD_ERR_PARTIAL => 'ファイルのアップロードが中断されました。',
            UPLOAD_ERR_NO_FILE => 'ファイルが選択されていません。',
            UPLOAD_ERR_NO_TMP_DIR => 'サーバーの一時ディレクトリが見つかりません。',
            UPLOAD_ERR_CANT_WRITE => 'ファイルの書き込みに失敗しました。',
            UPLOAD_ERR_EXTENSION => 'ファイル拡張子が無効です。',
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
     * 安全なファイル名を生成
     */
    public function generateSafeFileName($originalName, $prefix = 'upload_')
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = date('YmdHis');
        $random = bin2hex(random_bytes(8));

        return $prefix . $timestamp . '_' . $random . '.' . $extension;
    }

    /**
     * 一時ファイルを安全に作成
     */
    public function createTempFile($prefix = 'csv_upload_')
    {
        $tempFile = tempnam($this->uploadPath, $prefix);
        if ($tempFile === false) {
            throw new Exception('一時ファイルの作成に失敗しました。');
        }

        // 権限設定（読み書き可能、実行不可）
        chmod($tempFile, 0644);

        return $tempFile;
    }

    /**
     * ファイルを安全にクリーンアップ
     */
    public function cleanup($filePath)
    {
        if (file_exists($filePath)) {
            // セキュリティのため、削除前に内容を上書き
            $fileSize = filesize($filePath);
            if ($fileSize > 0 && $fileSize < 1048576) { // 1MB以下の場合のみ
                $handle = fopen($filePath, 'w');
                if ($handle) {
                    fwrite($handle, str_repeat('0', min($fileSize, 8192)));
                    fclose($handle);
                }
            }

            unlink($filePath);
        }
    }

    /**
     * アップロード制限の設定
     */
    public function setMaxFileSize($bytes)
    {
        $this->maxFileSize = max(0, (int)$bytes);
    }

    public function setAllowedExtensions(array $extensions)
    {
        $this->allowedExtensions = array_map('strtolower', $extensions);
    }

    public function setAllowedMimeTypes(array $mimeTypes)
    {
        $this->allowedMimeTypes = $mimeTypes;
    }

    /**
     * 設定情報の取得
     */
    public function getMaxFileSize()
    {
        return $this->maxFileSize;
    }

    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    public function getAllowedMimeTypes()
    {
        return $this->allowedMimeTypes;
    }

    /**
     * サーバーの設定チェック
     */
    public function checkServerConfig()
    {
        $issues = [];

        // PHPの設定チェック
        $uploadMaxFilesize = $this->parseSize(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseSize(ini_get('post_max_size'));
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));

        if ($uploadMaxFilesize < $this->maxFileSize) {
            $issues[] = "upload_max_filesize ({$this->formatFileSize($uploadMaxFilesize)}) が設定されたサイズ ({$this->formatFileSize($this->maxFileSize)}) より小さいです。";
        }

        if ($postMaxSize < $this->maxFileSize) {
            $issues[] = "post_max_size ({$this->formatFileSize($postMaxSize)}) が設定されたサイズ ({$this->formatFileSize($this->maxFileSize)}) より小さいです。";
        }

        // アップロード有効性チェック
        if (!ini_get('file_uploads')) {
            $issues[] = 'ファイルアップロードが無効になっています。';
        }

        // 一時ディレクトリの確認
        if (!is_writable($this->uploadPath)) {
            $issues[] = 'アップロード用ディレクトリに書き込み権限がありません。';
        }

        return $issues;
    }

    /**
     * サイズ文字列を数値に変換
     */
    private function parseSize($sizeStr)
    {
        $sizeStr = trim($sizeStr);
        $last = strtolower($sizeStr[strlen($sizeStr) - 1]);
        $value = (int)$sizeStr;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}