<?php
session_start();
require_once 'db_connect.php';

$order_no = $_GET['order_no'] ?? null;

if (!$order_no) {
    $_SESSION['error_message'] = "削除する注文書が指定されていません。";
    header('Location: order_history.php');
    exit();
}

try {
    $pdo->beginTransaction();

    // 関連する注文アイテムを先に削除
    $delete_items_sql = "DELETE FROM order_items WHERE order_no = ?";
    $stmt_items = $pdo->prepare($delete_items_sql);
    $stmt_items->execute([$order_no]);

    // その後、注文書を削除
    $delete_order_sql = "DELETE FROM orders WHERE order_no = ?";
    $stmt_order = $pdo->prepare($delete_order_sql);
    $stmt_order->execute([$order_no]);

    $pdo->commit();
    $_SESSION['message'] = "注文書No " . htmlspecialchars($order_no, ENT_QUOTES) . " が正常に削除されました。";
    header('Location: order_history.php');
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("データベースエラー (order_delete.php): " . $e->getMessage());
    $_SESSION['error_message'] = "注文書No " . htmlspecialchars($order_no, ENT_QUOTES) . " の削除中にエラーが発生しました。システム管理者に連絡してください。<br>エラー詳細: " . $e->getMessage();
    header('Location: order_detail.php?order_no=' . urlencode($order_no)); // 削除失敗時は詳細画面に戻す
    exit();
}