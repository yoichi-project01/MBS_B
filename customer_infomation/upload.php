<?php
$host = 'localhost';
$db   = 'mbs';      // ← あなたのDB名に変更
$user = 'root';          // ← あなたのユーザー名に変更
$pass = '';      // ← あなたのパスワードに変更
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("DB接続失敗: " . $e->getMessage());
}

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

        if (count($data) < 7) continue; // データ不完全行をスキップ

        list(
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
        customer_name, manager_name, address,
        telephone_number, delivery_conditions, registration_date, remarks
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

        try {
            $stmt->execute([
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