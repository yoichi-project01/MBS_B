<?php
// get_orders.php

require_once 'db_config.php'; // データベース接続設定を読み込み

header('Content-Type: application/json'); // JSON形式でレスポンスを返すことを指定

$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$orders = [];

if ($customerId > 0) {
    try {
        // 顧客IDに基づいて注文番号を取得するSQLクエリ
        // orders テーブルには order_no と customer_no があります。
        $stmt = $pdo->prepare("SELECT order_no FROM orders WHERE customer_no = :customerId");
        $stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();
        
        while ($row = $stmt->fetch()) {
            $orders[] = '注文No.' . $row['order_no'];
        }
        echo json_encode($orders);

    } catch (PDOException $e) {
        error_log("Error fetching orders: " . $e->getMessage());
        http_response_code(500); // サーバーエラー
        echo json_encode(['error' => '注文データの取得に失敗しました。']);
    }
} else {
    http_response_code(400); // 不正なリクエスト
    echo json_encode(['error' => '有効な顧客IDが指定されていません。']);
}
?>