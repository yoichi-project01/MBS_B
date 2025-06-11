<?php
$host = 'localhost';
$db   = 'mbs';  // データベース名を適宜変更
$user = 'root';
$pass = '';  // パスワードを設定している場合は記述
$charset = 'utf8mb4';

// PDO接続設定
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('データベース接続失敗: ' . $e->getMessage());
}

// CSVファイル処理
if (isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $filename = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($filename, 'r');

    $rowCount = 0;
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        // 空行をスキップ
        if (count($data) < 9) continue;

        // CSV列の順番に合わせて取得
        [
            $customer_no,
            $customer_name,
            $store_name,
            $manager_name,
            $address,
            $telephone_number,
            $delivery_conditions,
            $registration_date,
            $remarks
        ] = $data;

        // データベースに挿入
        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_no, customer_name, store_name, manager_name, 
                address, telephone_number, delivery_conditions, 
                registration_date, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            (int)$customer_no,
            $customer_name,
            $store_name,
            $manager_name ?: null,
            $address,
            (int)$telephone_number,
            $delivery_conditions ?: null,
            $registration_date,
            $remarks ?: null
        ]);

        $rowCount++;
    }

    fclose($handle);
    echo "{$rowCount} 件の顧客データを登録しました。";
} else {
    echo "ファイルが選択されていません。";
}