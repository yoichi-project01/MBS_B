<?php
require_once(__DIR__ . '/component/autoloader.php');
SessionManager::start();

$allowedStores = ['緑橋本店', '今里店', '深江橋店'];
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

if ($storeName && in_array($storeName, $allowedStores, true)) {
    // クッキーのセキュリティ設定
    $cookieOptions = [
        'expires' => time() + 3600,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // HTTPSの場合のみsecure
        'httponly' => true, // JavaScriptからのアクセスを禁止
        'samesite' => 'Lax' // CSRF対策
    ];
    setcookie('selectedStore', $storeName, $cookieOptions);
    SessionManager::set('store_name', $storeName);
    SessionManager::regenerateId();
} else {
    // 不正な店舗名の場合はデフォルト値またはエラー処理
    $storeName = SessionManager::get('store_name', ''); // セッションから取得
    if (empty($storeName)) {
        // セッションにもない場合は、トップページにリダイレクトするか、エラーメッセージを表示
        header('Location: /MBS_B/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($storeName) ?> - 受注管理システム</title>
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/top.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="store-selection-page">
    <main class="container">
        <h1 class="main-page-title">
            <span class="icon">🏠</span>
            <?= htmlspecialchars($storeName) ?><br>受注管理システム
        </h1>

        <div class="menu">
            <button class="menu-button" data-path="customer_information/index.php">
                <span>
                    <i class="fas fa-users"></i>
                    顧客情報
                </span>
            </button>
            <button class="menu-button" data-path="statistics/index.php">
                <span>
                    <i class="fas fa-chart-bar"></i>
                    統計情報
                </span>
            </button>
            <button class="menu-button" data-path="order_list/index.php">
                <span>
                    <i class="fas fa-file-invoice"></i>
                    注文書
                </span>
            </button>
            <button class="menu-button" data-path="delivery_list/index.php">
                <span>
                    <i class="fas fa-truck"></i>
                    納品書
                </span>
            </button>
        </div>
    </main>
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
</body>

</html>