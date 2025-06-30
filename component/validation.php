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
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $message = $message ?? "{$field}はYYYY-MM-DD形式で入力してください。";
            $this->addError($field, $message);
            return false;
        }

        // 日付として有効かチェック
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
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
     */
    public function validateCustomerData($data, $rowNumber = null)
    {
        $prefix = $rowNumber ? "行{$rowNumber}: " : "";

        // 顧客番号
        $customerNo = $this->positiveInteger($data[0], $prefix . "顧客番号");

        // 店舗名
        $this->required($data[1], $prefix . "店舗名");
        $allowedStores = ['緑橋本店', '今里店', '深江橋店'];
        $this->inArray(trim($data[1]), $allowedStores, $prefix . "店舗名");

        // 顧客名
        $this->required($data[2], $prefix . "顧客名");
        $this->maxLength($data[2], 100, $prefix . "顧客名");

        // 住所
        $this->required($data[4], $prefix . "住所");
        $this->maxLength($data[4], 200, $prefix . "住所");

        // 電話番号
        $this->required($data[5], $prefix . "電話番号");
        $this->phoneNumber($data[5], $prefix . "電話番号");

        // 登録日
        $this->required($data[7], $prefix . "登録日");
        $this->dateFormat($data[7], $prefix . "登録日");

        // 任意項目の長さチェック
        if (!empty($data[3])) { // 担当者名
            $this->maxLength($data[3], 50, $prefix . "担当者名");
        }

        if (!empty($data[6])) { // 配送条件
            $this->maxLength($data[6], 100, $prefix . "配送条件");
        }

        if (!empty($data[8])) { // 備考
            $this->maxLength($data[8], 500, $prefix . "備考");
        }

        return $customerNo;
    }
}