<?php

/**
 * データバリデーションコンポーネント
 */
class Validator
{
    private $errors = [];

    /**
     * エラーを追加
     */
    public function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * エラーがあるかチェック
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * すべてのエラーを取得
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * 特定フィールドのエラーを取得
     */
    public function getError($field)
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * 必須項目チェック
     */
    public function required($value, $field, $message = null)
    {
        if (is_null($value) || trim($value) === '') {
            $message = $message ?? "{$field}は必須項目です。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * 整数値チェック
     */
    public function integer($value, $field, $message = null)
    {
        // 空の場合はスキップ
        if (trim($value) === '') {
            return true;
        }

        $intValue = filter_var($value, FILTER_VALIDATE_INT);
        if ($intValue === false) {
            $message = $message ?? "{$field}は整数である必要があります。";
            $this->addError($field, $message);
            return false;
        }
        return $intValue;
    }

    /**
     * 正の整数チェック
     */
    public function positiveInteger($value, $field, $message = null)
    {
        $intValue = $this->integer($value, $field, $message);
        if ($intValue !== false && $intValue <= 0) {
            $message = $message ?? "{$field}は正の整数である必要があります。";
            $this->addError($field, $message);
            return false;
        }
        return $intValue;
    }

    /**
     * 文字列長チェック
     */
    public function maxLength($value, $maxLength, $field, $message = null)
    {
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            $message = $message ?? "{$field}は{$maxLength}文字以内で入力してください。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * 最小文字列長チェック
     */
    public function minLength($value, $minLength, $field, $message = null)
    {
        if (mb_strlen($value, 'UTF-8') < $minLength) {
            $message = $message ?? "{$field}は{$minLength}文字以上で入力してください。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * 電話番号形式チェック
     */
    public function phoneNumber($value, $field, $message = null)
    {
        // 空の場合はスキップ
        if (trim($value) === '') {
            return true;
        }

        // 日本の電話番号形式をチェック
        $pattern = '/^(0[1-9]{1}[0-9]{8,9}|0[1-9]{2}[0-9]{7,8}|0[1-9]{3}[0-9]{6,7}|0[1-9]{4}[0-9]{5,6})$/';
        $cleanValue = preg_replace('/[-\s()]/', '', $value);

        if (!preg_match($pattern, $cleanValue)) {
            $message = $message ?? "{$field}は正しい電話番号形式で入力してください。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * 日付形式チェック（YYYY-MM-DD）
     */
    public function dateFormat($value, $field, $message = null)
    {
        // 空の場合はスキップ
        if (trim($value) === '') {
            return true;
        }

        // スラッシュ区切りをハイフン区切りに変換
        $value = str_replace('/', '-', trim($value));

        // 基本的な形式チェック
        if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            $message = $message ?? "{$field}はYYYY-MM-DD形式で入力してください。";
            $this->addError($field, $message);
            return false;
        }

        // 日付の妥当性チェック
        $parts = explode('-', $value);
        if (count($parts) !== 3) {
            $message = $message ?? "{$field}は正しい日付形式ではありません。";
            $this->addError($field, $message);
            return false;
        }

        $year = (int)$parts[0];
        $month = (int)$parts[1];
        $day = (int)$parts[2];

        // 年の範囲チェック（1900年～2100年）
        if ($year < 1900 || $year > 2100) {
            $message = $message ?? "{$field}の年は1900年から2100年の間で入力してください。";
            $this->addError($field, $message);
            return false;
        }

        // PHPのcheckdate関数で妥当性をチェック
        if (!checkdate($month, $day, $year)) {
            $message = $message ?? "{$field}は存在しない日付です。";
            $this->addError($field, $message);
            return false;
        }

        // 正規化された日付文字列を返す
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * 許可リストチェック
     */
    public function inArray($value, $allowedValues, $field, $message = null)
    {
        if (!in_array($value, $allowedValues, true)) {
            $message = $message ?? "{$field}は許可されていない値です。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * メールアドレス形式チェック
     */
    public function email($value, $field, $message = null)
    {
        // 空の場合はスキップ
        if (trim($value) === '') {
            return true;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $message = $message ?? "{$field}は正しいメールアドレス形式で入力してください。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * 顧客データの一括バリデーション
     */
    public function validateCustomerData($data, $rowNumber = null)
    {
        $prefix = $rowNumber ? "行{$rowNumber}: " : "";
        $isValid = true;

        // データが配列で9つ以上の要素があることを確認
        // 期待されるヘッダー数に基づいて列数をチェック
        $expectedColumnCount = count($this->getExpectedCustomerHeaders()); // 新しいヘルパー関数を呼び出す
        if (!is_array($data) || count($data) < $expectedColumnCount) {
            $this->addError($prefix . "データ形式", "CSVの列数が不足しています。");
            return false;
        }

        // 各要素をトリム
        $data = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);

        // 顧客番号（必須）
        if (!$this->required($data[0], $prefix . "顧客番号")) {
            $isValid = false;
        } else {
            $customerNo = $this->positiveInteger($data[0], $prefix . "顧客番号");
            if ($customerNo === false) {
                $isValid = false;
            } elseif ($customerNo > 2147483647) { // INT型の最大値チェック
                $this->addError($prefix . "顧客番号", "顧客番号が大きすぎます。");
                $isValid = false;
            }
        }

        // 店舗名（必須）
        if (!$this->required($data[1], $prefix . "店舗名")) {
            $isValid = false;
        } else {
            $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
            if (!$this->inArray(trim($data[1]), $allowedStores, $prefix . "店舗名")) {
                $isValid = false;
            }
        }

        // 顧客名（必須）
        if (!$this->required($data[2], $prefix . "顧客名")) {
            $isValid = false;
        } else {
            if (!$this->maxLength($data[2], 255, $prefix . "顧客名")) {
                $isValid = false;
            }
        }

        // 担当者名（任意）
        if (!empty($data[3])) {
            if (!$this->maxLength($data[3], 255, $prefix . "担当者名")) {
                $isValid = false;
            }
        }

        // 住所（必須）
        if (!$this->required($data[4], $prefix . "住所")) {
            $isValid = false;
        } else {
            if (!$this->maxLength($data[4], 500, $prefix . "住所")) {
                $isValid = false;
            }
        }

        // 電話番号（必須）
        if (!$this->required($data[5], $prefix . "電話番号")) {
            $isValid = false;
        } else {
            if (!$this->phoneNumber($data[5], $prefix . "電話番号")) {
                $isValid = false;
            }
        }

        // 配送条件（任意）
        if (!empty($data[6])) {
            if (!$this->maxLength($data[6], 500, $prefix . "配送条件")) {
                $isValid = false;
            }
        }

        // 備考（任意）
        if (!empty($data[7])) {
            if (!$this->maxLength($data[7], 1000, $prefix . "備考")) {
                $isValid = false;
            }
        }

        // 登録日（必須）
        if (!$this->required($data[8], $prefix . "登録日")) {
            $isValid = false;
        } else {
            $normalizedDate = $this->dateFormat($data[8], $prefix . "登録日");
            if ($normalizedDate === false) {
                $isValid = false;
            }
        }

        return $isValid ? (int)$data[0] : false;
    }

    /**
     * CSVファイルのヘッダー行チェック
     */
    public function validateCSVHeader($headers)
    {
        $expectedHeaders = [
            '顧客ID',
            '店舗名',
            '顧客名',
            '担当者名',
            '住所',
            '電話番号',
            '配送条件',
            '備考',
            '顧客登録日'
        ];

        if (!is_array($headers)) {
            $this->addError('header', 'ヘッダー行が読み取れません。');
            return false;
        }

        $normalizedHeaders = array_map(function ($header) {
            return trim($header);
        }, $headers);

        // 最低限必要な列数をチェック
        if (count($normalizedHeaders) < count($this->getExpectedCustomerHeaders())) {
            $this->addError('header', 'CSVファイルの列数が不足しています。最低9列必要です。');
            return false;
        }

        // ヘッダー名の妥当性チェック（部分一致で許可）
        for ($i = 0; $i < count($this->getExpectedCustomerHeaders()); $i++) {
            if (empty($normalizedHeaders[$i])) {
                $this->addError('header', "列" . ($i + 1) . "のヘッダーが空です。");
                return false;
            }
        }

        return true;
    }

    /**
     * 文字コード検証
     */
    public function validateEncoding($content)
    {
        if (empty($content)) {
            $this->addError('encoding', 'ファイルが空です。');
            return false;
        }

        $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-WIN', 'SJIS', 'EUC-JP'], true);
        if ($encoding === false) {
            $this->addError('encoding', 'ファイルの文字コードが認識できません。UTF-8またはShift-JISで保存してください。');
            return false;
        }
        return $encoding;
    }

    /**
     * SQLインジェクション攻撃パターンチェック
     */
    public function validateSQLInjection($value, $field)
    {
        $suspiciousPatterns = [
            '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\btruncate\b)/i',
            '/(\-\-|\#|\/\*|\*\/)/i',
            '/(\bor\b\s+\d+\s*=\s*\d+|\band\b\s+\d+\s*=\s*\d+)/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $this->addError($field, "不正な文字列が含まれています。");
                return false;
            }
        }
        return true;
    }

    /**
     * XSS攻撃パターンチェック
     */
    public function validateXSS($value, $field)
    {
        $suspiciousPatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i'
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $this->addError($field, "不正なスクリプトが含まれています。");
                return false;
            }
        }
        return true;
    }

    /**
     * ファイルサイズチェック
     */
    public function validateFileSize($size, $maxSize, $field, $message = null)
    {
        if ($size > $maxSize) {
            $message = $message ?? "{$field}のサイズが大きすぎます。最大" . $this->formatFileSize($maxSize) . "まで対応しています。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * ファイル拡張子チェック
     */
    public function validateFileExtension($filename, $allowedExtensions, $field, $message = null)
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            $allowedList = implode('、', array_map('strtoupper', $allowedExtensions));
            $message = $message ?? "{$field}は{$allowedList}ファイルのみアップロード可能です。";
            $this->addError($field, $message);
            return false;
        }
        return true;
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
     * バリデーションエラーをリセット
     */
    public function reset()
    {
        $this->errors = [];
    }

    /**
     * 全エラーを文字列として取得
     */
    public function getErrorsAsString($separator = "\n")
    {
        $errorMessages = [];
        foreach ($this->errors as $field => $messages) {
            if (is_array($messages)) {
                $errorMessages = array_merge($errorMessages, $messages);
            } else {
                $errorMessages[] = $messages;
            }
        }
        return implode($separator, $errorMessages);
    }

    /**
     * 顧客データCSVの期待されるヘッダーリストを取得
     */
    private function getExpectedCustomerHeaders()
    {
        return [
            '顧客ID',
            '店舗名',
            '顧客名',
            '担当者名',
            '住所',
            '電話番号',
            '配送条件',
            '備考',
            '顧客登録日'
        ];
    }
}