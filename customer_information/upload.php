<?php
require_once(__DIR__ . '/../component/db.php');
session_start();

if (isset($_FILES['csv_file']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $filename = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($filename, 'r');

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
    }

    fclose($handle);

    // ✅ 成功フラグをセッションに保存
    $_SESSION['upload_status'] = 'success';

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // ❌ 失敗フラグをセッションに保存
    $_SESSION['upload_status'] = 'error';

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}