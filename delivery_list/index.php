<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="container">
        <h1>納品書</h1>
        <div class="details">
            <p><strong>注文番号:</strong> <span id="order-number"></span></p>
            <p><strong>注文日:</strong> <span id="order-date"></span></p>
            <p><strong>お客様名:</strong> <span id="customer-name"></span></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>商品名</th>
                    <th>数量</th>
                    <th>単価</th>
                    <th>金額</th>
                </tr>
            </thead>
            <tbody id="order-items">
            </tbody>
        </table>
        <div class="total">
            <p><strong>合計:</strong> <span id="total-amount"></span></p>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>