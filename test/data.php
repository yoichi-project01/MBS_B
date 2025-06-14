<?php
header('Content-Type: application/json');

try {
    $file = 'data.csv';
    if (!file_exists($file)) {
        echo json_encode(['status' => 'error', 'message' => 'データファイルが見つかりません。']);
        exit;
    }

    $data = [];
    if (($handle = fopen($file, 'r')) !== false) {
        fgetcsv($handle); // ヘッダー行をスキップ
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 5) {
                $data[] = [
                    'CustomerNo' => $row[0],
                    'CustomerName' => $row[1],
                    'OrderDate' => $row[2],
                    'DeliveryDate' => $row[3],
                    'SalesAmount' => floatval($row[4])
                ];
            }
        }
        fclose($handle);
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>