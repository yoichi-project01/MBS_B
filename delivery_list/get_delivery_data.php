<?php
require_once '../component/db.php';

header('Content-Type: application/json');

$order_id = $_GET['order_id'] ?? null;

if ($order_id) {
    try {
        $pdo = db_connect();

        // 注文情報を取得
        $stmt = $pdo->prepare("SELECT o.order_no as order_id, o.registration_date as order_date, c.customer_name FROM orders o JOIN customers c ON o.customer_no = c.customer_no WHERE o.order_no = :order_id");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        $order_info = $stmt->fetch(PDO::FETCH_ASSOC);

        // 注文詳細を取得
        $stmt = $pdo->prepare("SELECT books as product_name, order_volume as quantity, price FROM order_items WHERE order_no = :order_id");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['order_info' => $order_info, 'order_details' => $order_details]);

    } catch (PDOException $e) {
        error_log('Database error in get_delivery_data.php: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error occurred']);
    }
} else {
    echo json_encode(['error' => 'Order ID is missing']);
}
?>