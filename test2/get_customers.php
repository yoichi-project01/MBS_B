<?php
// get_customers.php

require_once 'db_config.php'; // データベース接続設定を読み込み

header('Content-Type: application/json'); // JSON形式でレスポンスを返すことを指定

$searchTerm = isset($_GET['term']) ? $_GET['term'] : '';
$customers = [];

try {
    // 顧客名を検索するSQLクエリ
    // `customer_name` または `store_name` で検索できるようにします
    $stmt = $pdo->prepare("SELECT customer_no, customer_name, store_name FROM customers WHERE customer_name LIKE :searchTerm OR store_name LIKE :searchTerm");
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    $stmt->execute();

    while ($row = $stmt->fetch()) {
        // 表示名を customer_name に統一するか、store_name を含めるかはお好みで調整
        $displayName = $row['customer_name'];
        if ($row['store_name'] && $row['store_name'] !== $row['customer_name']) {
            $displayName = $row['store_name'] . ' (' . $row['customer_name'] . ')';
        }
        $customers[] = [
            'id' => $row['customer_no'],
            'name' => $displayName
        ];
    }
    echo json_encode($customers);

} catch (PDOException $e) {
    error_log("Error fetching customers: " . $e->getMessage());
    http_response_code(500); // サーバーエラー
    echo json_encode(['error' => '顧客データの取得に失敗しました。']);
}
?>