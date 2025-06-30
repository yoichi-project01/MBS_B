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
        $this->errors[$field] = $message;
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
        if (empty(trim($value))) {
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
        if (mb_strlen($value) > $maxLength) {
            $message = $message ?? "{$field}は{$maxLength}文字以内で入力してください。";
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
        if (!preg_match('/^[0-9\-]+$/', $value)) {
            $message = $message ?? "{$field}は数字とハイフンのみで入力してください。";
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
        if (empty($value)) {
            return true;
        }

        // スラッシュ区切りをハイフン区切りに変換
        $value = str_replace('/', '-', $value);

        if (!preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $value)) {
            $message = $message ?? "{$field}はYYYY-MM-DD形式で入力してください。";
            $this->addError($field, $message);
            return false;
        }

        // 日付として有効かチェック
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            // ゼロパディングなしの日付も許可
            $parts = explode('-', $value);
            if (count($parts) === 3) {
                $formattedDate = sprintf('%04d-%02d-%02d', $parts[0], $parts[1], $parts[2]);
                $date = DateTime::createFromFormat('Y-m-d', $formattedDate);
                if ($date && $date->format('Y-m-d') === $formattedDate) {
                    return true;
                }
            }

            $message = $message ?? "{$field}は有効な日付ではありません。";
            $this->addError($field, $message);
            return false;
        }

        return true;
    }

    /**
     * 許可リストチェック
     */
    public function inArray($value, $allowedValues, $field, $message = null)
    {
        if (!in_array($value, $allowedValues)) {
            $message = $message ?? "{$field}は許可されていない値です。";
            $this->addError($field, $message);
            return false;
        }
        return true;
    }

    /**
     * 顧客データの一括バリデーション
     * CSVの列構造に合わせて修正
     */
    public function validateCustomerData($data, $rowNumber = null)
    {
        $prefix = $rowNumber ? "行{$rowNumber}: " : "";

        // 顧客番号（必須）
        if (empty($data[0])) {
            $this->addError($prefix . "顧客番号", "顧客番号は必須です。");
            return false;
        }
        $customerNo = $this->positiveInteger($data[0], $prefix . "顧客番号");
        if ($customerNo === false) {
            return false;
        }

        // 店舗名（必須）
        if (empty($data[1])) {
            $this->addError($prefix . "店舗名", "店舗名は必須です。");
            return false;
        }
        $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
        if (!$this->inArray(trim($data[1]), $allowedStores, $prefix . "店舗名")) {
            return false;
        }

        // 顧客名（必須）
        if (empty($data[2])) {
            $this->addError($prefix . "顧客名", "顧客名は必須です。");
            return false;
        }
        if (!$this->maxLength($data[2], 255, $prefix . "顧客名")) {
            return false;
        }

        // 住所（必須）
        if (empty($data[4])) {
            $this->addError($prefix . "住所", "住所は必須です。");
            return false;
        }
        if (!$this->maxLength($data[4], 255, $prefix . "住所")) {
            return false;
        }

        // 電話番号（必須）
        if (empty($data[5])) {
            $this->addError($prefix . "電話番号", "電話番号は必須です。");
            return false;
        }
        if (!$this->phoneNumber($data[5], $prefix . "電話番号")) {
            return false;
        }
        if (!$this->maxLength($data[5], 20, $prefix . "電話番号")) {
            return false;
        }

        // 登録日（必須）
        if (empty($data[8])) {
            $this->addError($prefix . "登録日", "登録日は必須です。");
            return false;
        }
        if (!$this->dateFormat($data[8], $prefix . "登録日")) {
            return false;
        }

        // 任意項目の長さチェック
        if (!empty($data[3]) && !$this->maxLength($data[3], 255, $prefix . "担当者名")) {
            return false;
        }

        if (!empty($data[6]) && !$this->maxLength($data[6], 255, $prefix . "配送条件")) {
            return false;
        }

        if (!empty($data[7]) && !$this->maxLength($data[7], 500, $prefix . "備考")) {
            return false;
        }

        return $customerNo;
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

        $normalizedHeaders = array_map('trim', $headers);

        // 最低限必要な列数をチェック
        if (count($normalizedHeaders) < 9) {
            $this->addError('header', 'CSVファイルの列数が不足しています。最低9列必要です。');
            return false;
        }

        return true;
    }

    /**
     * 文字コード検証
     */
    public function validateEncoding($content)
    {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-WIN', 'SJIS', 'EUC-JP'], true);
        if ($encoding === false) {
            $this->addError('encoding', 'ファイルの文字コードが認識できません。UTF-8またはShift-JISで保存してください。');
            return false;
        }
        return $encoding;
    }

    /**
     * バリデーションエラーをリセット
     */
    public function reset()
    {
        $this->errors = [];
    }
}