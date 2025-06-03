<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer = htmlspecialchars($_POST['customer_name'] ?? '');
    $item = htmlspecialchars($_POST['item'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $remarks = htmlspecialchars($_POST['remarks'] ?? '');

    // ここでDB保存やPDF生成などの処理を追加できます
    // 今回は簡単な確認画面を表示
    echo "<h1>注文書作成完了</h1>";
    echo "<p>顧客名: {$customer}</p>";
    echo "<p>商品名: {$item}</p>";
    echo "<p>数量: {$quantity}</p>";
    echo "<p>備考: {$remarks}</p>";
    echo '<a href="index.html">戻る</a>';
} else {
    header('Location: index.html');
    exit;
}