<?php
$host = 'localhost';
$db   = 'mbs';  // データベース名
$user = 'root';
$pass = '';               // パスワードがある場合は記述
$charset = 'utf8mb4';

// DB接続
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('DB接続失敗: ' . $e->getMessage());
}

// アップロードされたCSVファイル処理
if (isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $filename = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($filename, 'r');

    $rowCount = 0;
    $isFirstRow = true;

    while (($line = fgets($handle)) !== false) {
        $line = mb_convert_encoding($line, 'UTF-8', 'SJIS-win');

        if ($isFirstRow) {
            $isFirstRow = false;
            continue;
        }

        $data = str_getcsv($line);
        if (count($data) < 9) continue;

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

        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_no, customer_name, store_name, manager_name, 
                address, telephone_number, delivery_conditions, 
                registration_date, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                customer_name = VALUES(customer_name),
                store_name = VALUES(store_name),
                manager_name = VALUES(manager_name),
                address = VALUES(address),
                telephone_number = VALUES(telephone_number),
                delivery_conditions = VALUES(delivery_conditions),
                registration_date = VALUES(registration_date),
                remarks = VALUES(remarks)
        ");

        $stmt->execute([
            (int)$customer_no,
            $customer_name,
            $store_name,
            $manager_name ?: null,
            $address,
            $telephone_number,
            $delivery_conditions ?: null,
            $registration_date,
            $remarks ?: null
        ]);

        $rowCount++;
    }

    fclose($handle);
    echo "{$rowCount} 件の顧客データを挿入または更新しました。";
} else {
    echo "ファイルが選択されていません。";
}