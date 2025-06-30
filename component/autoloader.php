<?php

/**
 * シンプルなオートローダー
 * 全てのコンポーネントを一括で読み込む
 */

// 基本パスの設定
$componentPath = __DIR__;

// 読み込むコンポーネントファイルのリスト
$components = [
    'db.php',
    'csrf.php',
    'session_manager.php',
    'file_upload.php',
    'validation.php',
    'pagination.php',
    'alert.php'
];

// コンポーネントを順次読み込み
foreach ($components as $component) {
    $filePath = $componentPath . '/' . $component;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        error_log("Component not found: {$filePath}");
    }
}

/**
 * 利用可能なコンポーネントクラスの一覧
 * - CSRFProtection: CSRF保護機能
 * - FileUploadHandler: ファイルアップロード処理
 * - SessionManager: セッション管理
 * - Validator: データバリデーション
 * - Pagination: ページネーション
 * - AlertComponent: アラート表示
 */

/**
 * 環境設定の初期化
 */
function initializeEnvironment()
{
    // エラー報告レベルの設定
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';

    if ($environment === 'production') {
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);

        // ログファイルの設定
        $logFile = $_ENV['LOG_FILE'] ?? '/var/log/mbs/application.log';
        if (is_writable(dirname($logFile))) {
            ini_set('error_log', $logFile);
        }
    } else {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
    }

    // タイムゾーンの設定
    date_default_timezone_set('Asia/Tokyo');
}

/**
 * セキュリティヘッダーの設定
 */
function setSecurityHeaders()
{
    // XSS保護
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');

    // HTTPS強制（本番環境）
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';
    if ($environment === 'production' && isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // コンテンツセキュリティポリシー（基本設定）
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;");
}

// 環境設定とセキュリティヘッダーの初期化
initializeEnvironment();
setSecurityHeaders();