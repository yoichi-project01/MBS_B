<?php

/**
 * データベース接続コンポーネント
 * セキュリティとパフォーマンスを向上させた版
 */

// 環境変数から設定を読み込む
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'mbs';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';
$port = $_ENV['DB_PORT'] ?? 3306;

// DSN作成（セキュリティ向上のため詳細な設定）
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

// PDOオプション（セキュリティとパフォーマンス向上）
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false, // 本番環境では適宜調整
    PDO::ATTR_TIMEOUT            => 30,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
    PDO::MYSQL_ATTR_FOUND_ROWS   => true,
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // SSL設定は環境に応じて調整
];

// SSL設定（本番環境推奨）
if (isset($_ENV['DB_SSL']) && $_ENV['DB_SSL'] === 'true') {
    $options[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'] ?? '';
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    // データベース接続の確立
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 接続成功時の追加設定
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    $pdo->exec("SET time_zone = '+09:00'"); // 日本時間に設定

    // セッション変数の設定（セキュリティ向上）
    $pdo->exec("SET SESSION sql_safe_updates = 1");
    $pdo->exec("SET SESSION max_execution_time = 30000"); // 30秒でタイムアウト

} catch (PDOException $e) {
    // エラーログの記録
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';
    $errorMessage = 'Database connection failed';

    // 詳細なエラー情報のログ記録（本番環境では機密情報を除外）
    if ($environment === 'production') {
        error_log($errorMessage . ': [Error Code: ' . $e->getCode() . ']');

        // 本番環境では一般的なエラーメッセージのみ表示
        http_response_code(503);
        echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>システムメンテナンス中</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .error-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 500px; margin: 0 auto; }
        h1 { color: #e74c3c; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; }
        .retry-btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-top: 20px; }
        .retry-btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>🔧 システムメンテナンス中</h1>
        <p>申し訳ございませんが、現在システムメンテナンス中です。<br>しばらく時間をおいてから再度アクセスしてください。</p>
        <p>お急ぎの場合は、システム管理者にお問い合わせください。</p>
        <button class="retry-btn" onclick="location.reload()">再試行</button>
    </div>
</body>
</html>';
    } else {
        // 開発環境では詳細なエラー情報を表示
        error_log($errorMessage . ': ' . $e->getMessage());
        echo '<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>データベース接続エラー</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .error-container { background: #000; padding: 20px; border: 1px solid #00ff00; border-radius: 4px; }
        h1 { color: #ff0000; }
        .error-details { background: #333; padding: 15px; margin: 10px 0; border-left: 4px solid #ff0000; }
        .config-info { background: #2a2a2a; padding: 10px; margin: 10px 0; border-left: 4px solid #ffff00; }
        code { background: #444; padding: 2px 4px; border-radius: 2px; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>🚨 Database Connection Error (Development Mode)</h1>
        <div class="error-details">
            <strong>Error Message:</strong><br>
            <code>' . htmlspecialchars($e->getMessage()) . '</code>
        </div>
        <div class="error-details">
            <strong>Error Code:</strong> <code>' . $e->getCode() . '</code><br>
            <strong>File:</strong> <code>' . $e->getFile() . '</code><br>
            <strong>Line:</strong> <code>' . $e->getLine() . '</code>
        </div>
        <div class="config-info">
            <strong>Connection Configuration:</strong><br>
            <code>Host: ' . htmlspecialchars($host) . '</code><br>
            <code>Database: ' . htmlspecialchars($db) . '</code><br>
            <code>User: ' . htmlspecialchars($user) . '</code><br>
            <code>Port: ' . htmlspecialchars($port) . '</code><br>
            <code>Charset: ' . htmlspecialchars($charset) . '</code>
        </div>
        <div class="config-info">
            <strong>Troubleshooting Tips:</strong><br>
            • データベースサーバーが起動しているか確認してください<br>
            • データベース名、ユーザー名、パスワードが正しいか確認してください<br>
            • ファイアウォールの設定を確認してください<br>
            • 環境変数 (.env) の設定を確認してください
        </div>
    </div>
</body>
</html>';
    }
    exit;
}

/**
 * データベース接続のヘルスチェック
 */
function checkDatabaseHealth($pdo)
{
    try {
        $stmt = $pdo->query('SELECT 1');
        return $stmt !== false;
    } catch (PDOException $e) {
        error_log('Database health check failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * 安全なクエリ実行のためのヘルパー関数
 */
function executeSafeQuery($pdo, $sql, $params = [])
{
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        return $result ? $stmt : false;
    } catch (PDOException $e) {
        error_log('Query execution failed: ' . $e->getMessage() . ' SQL: ' . $sql);
        return false;
    }
}

/**
 * トランザクション処理のヘルパー関数
 */
function executeTransaction($pdo, $callback)
{
    try {
        $pdo->beginTransaction();
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        error_log('Transaction failed: ' . $e->getMessage());
        throw $e;
    }
}

// グローバル変数として利用できるようにエクスポート
$GLOBALS['pdo'] = $pdo;

// データベース接続の成功ログ（開発環境のみ）
if (($_ENV['ENVIRONMENT'] ?? 'development') === 'development') {
    error_log('Database connection established successfully to: ' . $host . ':' . $port . '/' . $db);
}