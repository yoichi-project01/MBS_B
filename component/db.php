<?php

/**
 * データベース接続コンポーネント
 * シングルトンパターン風にして、グローバル変数を排除
 */

/**
 * データベース接続を取得する関数
 * 既存の接続を再利用するか、新しい接続を作成する
 * @return PDO
 * @throws PDOException
 */
function db_connect()
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // 環境変数から設定を読み込む
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db   = $_ENV['DB_NAME'] ?? 'mbs';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';
    $charset = 'utf8mb4';
    $port = $_ENV['DB_PORT'] ?? 3306;

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::ATTR_TIMEOUT            => 30,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
        PDO::MYSQL_ATTR_FOUND_ROWS   => true,
    ];

    if (isset($_ENV['DB_SSL']) && $_ENV['DB_SSL'] === 'true') {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'] ?? '';
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        $pdo->exec("SET time_zone = '+09:00'");
        $pdo->exec("SET SESSION sql_safe_updates = 1");

        $environment = $_ENV['ENVIRONMENT'] ?? $_SERVER['ENVIRONMENT'] ?? 'development';
        if ($environment === 'development') {
            error_log('Database connection established successfully to: ' . $host . ':' . $port . '/' . $db);
        }
        
        return $pdo;

    } catch (PDOException $e) {
        $environment = $_ENV['ENVIRONMENT'] ?? $_SERVER['ENVIRONMENT'] ?? 'development';
        $errorMessage = 'Database connection failed';

        if ($environment === 'production') {
            error_log($errorMessage . ': [Error Code: ' . $e->getCode() . '] Host: ' . $host . ':' . $port);
            http_response_code(503);
            echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>システムメンテナンス中</title></head><body><h1>システムメンテナンス中</h1><p>しばらくしてから再度お試しください。</p></body></html>';
        } else {
            error_log($errorMessage . ': ' . $e->getMessage() . ' Host: ' . $host . ':' . $port);
            echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8"><title>データベース接続エラー</title></head><body><h1>データベース接続エラー</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre></body></html>';
        }
        exit;
    }
}

/**
 * データベース接続のヘルスチェック
 */
function checkDatabaseHealth()
{
    try {
        $pdo = db_connect();
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
function executeSafeQuery($sql, $params = [])
{
    try {
        $pdo = db_connect();
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
function executeTransaction($callback)
{
    $pdo = db_connect();
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
