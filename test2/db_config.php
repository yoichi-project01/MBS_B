<?php
// db_config.php

define('DB_HOST', 'localhost'); // データベースホスト名
define('DB_NAME', 'mbs');      // データベース名
define('DB_USER', 'root');     // データベースユーザー名
define('DB_PASS', '');         // データベースパスワード

// DSN (Data Source Name)
define('DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

try {
    $pdo = new PDO(DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // エラー発生時はログに記録し、安全なメッセージを表示
    error_log("Database connection failed: " . $e->getMessage());
    die("データベース接続エラーが発生しました。しばらくしてから再度お試しください。");
}
?>