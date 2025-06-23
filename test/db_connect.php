<?php
// データベース接続情報
$host = 'localhost'; // または、データベースサーバーのIPアドレスやホスト名
$db   = 'mbs';      // ご自身のデータベース名に置き換えてください
$user = 'root';    // ご自身のデータベースユーザー名に置き換えてください
$pass = '';        // ご自身のデータベースパスワードに置き換えてください (XAMPPのデフォルトは空)
$charset = 'utf8mb4';

// DSN (Data Source Name) の構築
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDOオプションの設定
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // エラー発生時に例外をスロー
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 結果を連想配列で取得
    PDO::ATTR_EMULATE_PREPARES   => false,                  // プリペアドステートメントのエミュレーションを無効にする (セキュリティとパフォーマンスのため)
];

try {
    // PDOインスタンスを作成し、データベースに接続
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // データベース接続エラーが発生した場合の処理
    error_log("データベース接続エラー: " . $e->getMessage()); // エラーログに詳細を記録
    die("データベース接続エラーが発生しました。システム管理者に連絡してください。"); // ユーザー向けのメッセージを表示
}