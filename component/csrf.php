<?php

/**
 * CSRF保護コンポーネント（修正版）
 */
class CSRFProtection
{
    /**
     * CSRFトークンを生成・取得
     */
    public static function getToken()
    {
        // セッションが開始されていない場合のみ開始
        if (session_status() === PHP_SESSION_NONE) {
            // ヘッダーが送信されていないことを確認
            if (!headers_sent()) {
                session_start();
            } else {
                // エラーログに記録
                error_log('CSRF: Cannot start session - headers already sent');
                return null;
            }
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * CSRFトークンを検証
     */
    public static function validateToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                session_start();
            } else {
                error_log('CSRF: Cannot start session for validation - headers already sent');
                return false;
            }
        }

        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * CSRFトークンのHTMLフィールドを生成
     */
    public static function getTokenField()
    {
        $token = self::getToken();
        if ($token === null) {
            return '<input type="hidden" name="csrf_token" value="">';
        }
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * リクエストのCSRFトークンを検証し、無効な場合はリダイレクト
     */
    public static function validateRequest($redirectUrl = null)
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!self::validateToken($token)) {
            // セッションが利用可能な場合のみエラーメッセージを設定
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['upload_status'] = 'error';
                $_SESSION['error_message'] = 'セキュリティエラーが発生しました。ページを再読み込みしてからもう一度お試しください。';
            }

            $redirectUrl = $redirectUrl ?? $_SERVER['HTTP_REFERER'] ?? '/';
            header('Location: ' . $redirectUrl);
            exit;
        }

        return true;
    }
}