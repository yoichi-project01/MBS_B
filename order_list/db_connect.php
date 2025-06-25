<?php
// データベース接続はダミーモードのため無効化
// $host = 'localhost';
// $db   = 'mbs';
// $user = 'root';
// $pass = '';
// $charset = 'utf8mb4';
// $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
// $options = [
//     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//     PDO::ATTR_EMULATE_PREPARES   => false,
// ];
// try {
//     $pdo = new PDO($dsn, $user, $pass, $options);
// } catch (\PDOException $e) {
//     error_log("データベース接続エラー: " . $e->getMessage());
//     die("データベース接続エラーが発生しました。システム管理者に連絡してください。");
// }

// ダミーモード用のダミー変数
$pdo = null;
