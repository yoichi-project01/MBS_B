<?php
require_once(__DIR__ . '/../component/db.php');

if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($tmpName, 'r');

    if ($handle === false) {
        die("CSVファイルを開けませんでした。");
    }

    // ヘッダーをスキップ（必要ならコメントアウト）
    fgetcsv($handle);

    while (($data = fgetcsv($handle)) !== false) {
        // Shift_JIS → UTF-8 に変換
        $data = array_map(function ($value) {
            return mb_convert_encoding($value, 'UTF-8', 'SJIS-win');
        }, $data);

        if (count($data) < 9) continue; // 必須列が不足している場合はスキップ

        list(
            $customer_no,
            $store_name,
            $customer_name,
            $manager_name,
            $address,
            $telephone_number,
            $delivery_conditions,
            $registration_date,
            $remarks
        ) = $data;

        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_no,
                store_name,
                customer_name,
                manager_name,
                address,
                telephone_number,
                delivery_conditions,
                registration_date,
                remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        try {
            $stmt->execute([
                $customer_no,
                $store_name,
                $customer_name,
                $manager_name ?: null,
                $address,
                $telephone_number,
                $delivery_conditions ?: null,
                $registration_date,
                $remarks ?: null
            ]);
        } catch (PDOException $e) {
            echo "挿入エラー：" . $e->getMessage() . "<br>";
            continue;
        }
    }

    fclose($handle);
    header("Location: index.html?result=success");
    exit;
} else {
    header("Location: index.html?result=fail");
    exit;
}