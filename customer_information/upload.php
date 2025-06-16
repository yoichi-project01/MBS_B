<?php
require_once(__DIR__ . '/../component/db.php');

// アップロードされたCSVファイル処理
if (isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $filename = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($filename, 'r');

    session_start();

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
            $store_name,
            $customer_name,
            $manager_name,
            $address,
            $telephone_number,
            $delivery_conditions,
            $registration_date,
            $remarks
        ] = $data;
        $stmt = $pdo->prepare("
        INSERT INTO customers (
        customer_no, store_name, customer_name, manager_name,
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
            $store_name,
            $customer_name,
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
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    print("ファイルが選択されていません。");
}