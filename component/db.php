<?php
// 環境変数から設定を読み込む（本番環境では.envファイルまたは環境変数を使用）
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'mbs';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // 本番環境では詳細なエラー情報を表示しない
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';
    if ($environment === 'production') {
        error_log('Database connection failed: ' . $e->getMessage());
        exit('データベース接続エラーが発生しました。管理者にお問い合わせください。');
    } else {
        exit('DB接続失敗: ' . $e->getMessage());
    }
}