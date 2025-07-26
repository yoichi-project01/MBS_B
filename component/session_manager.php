<?php

/**
 * セッション管理コンポーネント（修正版）
 */
class SessionManager
{
    /**
     * セッションを安全に開始
     */
    public static function start()
    {
        // セッションが既に開始されている場合は何もしない
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // ヘッダーが送信されている場合は設定をスキップ
        if (!headers_sent()) {
            // セッションのセキュリティ設定
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            ini_set('session.cookie_samesite', 'Strict');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * フラッシュメッセージを設定
     */
    public static function setFlash($key, $value)
    {
        self::start();
        $_SESSION['flash_' . $key] = $value;
    }

    /**
     * フラッシュメッセージを取得（取得後に削除）
     */
    public static function getFlash($key, $default = null)
    {
        self::start();
        $flashKey = 'flash_' . $key;

        if (isset($_SESSION[$flashKey])) {
            $value = $_SESSION[$flashKey];
            unset($_SESSION[$flashKey]);
            return $value;
        }

        return $default;
    }

    /**
     * フラッシュメッセージが存在するかチェック
     */
    public static function hasFlash($key)
    {
        self::start();
        return isset($_SESSION['flash_' . $key]);
    }

    /**
     * アップロード結果を設定
     */
    public static function setUploadResult($status, $data = [])
    {
        self::setFlash('upload_status', $status);

        foreach ($data as $key => $value) {
            self::setFlash($key, $value);
        }
    }

    /**
     * アップロード結果を取得
     */
    public static function getUploadResult()
    {
        return [
            'status' => self::getFlash('upload_status'),
            'insert_count' => self::getFlash('insert_count', 0),
            'update_count' => self::getFlash('update_count', 0),
            'total_rows' => self::getFlash('total_rows', 0),
            'error_rows' => self::getFlash('error_rows', []),
            'error_message' => self::getFlash('error_message', '')
        ];
    }

    /**
     * セッション値を設定
     */
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * セッション値を取得
     */
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * セッション値を削除
     */
    public static function remove($key)
    {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * セッションを破棄
     */
    public static function destroy()
    {
        self::start();
        session_destroy();
    }

    /**
     * セッションIDを再生成（セッションハイジャック対策）
     */
    public static function regenerateId()
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * ログイン状態をチェック（統計ページなどで使用）
     */
    public static function isLoggedIn()
    {
        self::start();
        return isset($_SESSION['user_id']);
    }

    /**
     * 拡張セッション検証（セキュリティ強化版）
     */
    public static function isValidSession()
    {
        self::start();
        
        // ユーザーIDの存在確認
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // セッションタイムアウトチェック（30分）
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 1800)) {
            self::destroy();
            return false;
        }
        
        // IPアドレスの確認（セッションハイジャック対策）
        if (isset($_SESSION['user_ip']) && 
            $_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroy();
            return false;
        }
        
        // User-Agentの確認
        if (isset($_SESSION['user_agent']) && 
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            self::destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * ユーザーログイン時のセッション初期化
     */
    public static function initializeUserSession($userId)
    {
        self::start();
        self::regenerateId();
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time();
    }

    /**
     * セッションの有効期限チェック
     */
    public static function checkSessionTimeout()
    {
        if (!self::isValidSession()) {
            self::setFlash('error', 'セッションが無効です。再度ログインしてください。');
            return false;
        }
        return true;
    }
}