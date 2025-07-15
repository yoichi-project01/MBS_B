<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');

SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

// 店舗が選択されていない場合のエラーハンドリング
if (empty($storeName)) {
    $_SESSION['error_message'] = '店舗が選択されていません。店舗選択画面からアクセスしてください。';
    header('Location: /MBS_B/index.php');
    exit;
}

include(__DIR__ . '/../component/header.php');

// デバッグモードの設定
$debugMode = ($_ENV['ENVIRONMENT'] ?? 'development') !== 'production';





// セキュリティ関連の設定
$csrfToken = CSRFProtection::getToken();

// メタデータの設定
$pageTitle = "統計情報 - {$storeName}";
$pageDescription = "{$storeName}の売上統計、顧客分析、配達実績などの詳細な統計情報を表示します。";
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">

    <!-- CSP Meta Tag -->
    <meta http-equiv="Content-Security-Policy"
        content="default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' https://cdnjs.cloudflare.com;">

    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/statistics.css">

    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <!-- Preload key resources -->
    <link rel="preload" href="/MBS_B/assets/js/main.js" as="script">
    <link rel="preload" href="/MBS_B/assets/js/pages/statistics.js" as="script">
</head>

<body class="with-header statistics-page">
    <!-- エラーメッセージの表示 -->
    <?php if (!empty($errorMessage)): ?>
    <div class="error-banner" role="alert" aria-live="assertive">
        <div class="error-content">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo htmlspecialchars($errorMessage); ?></span>
            <button class="error-close" onclick="this.parentElement.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics content starts here -->
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <!-- 顧客一覧のみの表示 -->
                <?php include 'customer_list_content.php'; ?>
            </div>
        </div>
    </div>

    <!-- 詳細モーダル -->
    <div id="detailModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="detailTitle">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">顧客詳細</h2>
                <button class="close-modal" onclick="closeModal('detailModal')" aria-label="モーダルを閉じる">
                    &times;
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="loading-placeholder">
                    <div class="loading-spinner"></div>
                    <span>読み込み中...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- エクスポート用の隠しフォーム -->
    <form id="exportForm" method="POST" action="export.php" style="display: none;">
        <?php echo CSRFProtection::getTokenField(); ?>
        <input type="hidden" name="store" value="<?php echo htmlspecialchars($storeName); ?>">
        <input type="hidden" name="export_type" value="statistics">
        <input type="hidden" name="selected_customers" id="selectedCustomers">
        <input type="hidden" name="export_format" id="exportFormat" value="csv">
    </form>


    <!-- JavaScript Data -->
    <script>
    // グローバルデータの設定
    window.statisticsData = {
        storeName: <?php echo json_encode($storeName); ?>,
        csrfToken: <?php echo json_encode($csrfToken); ?>,
        debugMode: <?php echo json_encode($debugMode); ?>
    };

    // エラーハンドリング
    window.addEventListener('error', function(e) {
        if (window.statisticsData.debugMode) {
            console.error('Statistics page error:', e.error);
        }
    });

    // パフォーマンス測定
    if (window.performance && window.performance.mark) {
        window.performance.mark('statistics-data-loaded');
    }
    </script>

    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    <script src="/MBS_B/assets/js/pages/customer-statistics.js"></script>

    <!-- 個別の機能モジュール -->
    <script type="module">
    // 統計ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        // 統計ページが正常に読み込まれた後の処理
        if (window.performance && window.performance.mark) {
            window.performance.mark('statistics-js-loaded');
        }
    });
    </script>

    <!-- 分析・トラッキング（本番環境のみ） -->
    <?php if (($_ENV['ENVIRONMENT'] ?? 'development') === 'production'): ?>
    <script>
    // Google Analytics や他の分析ツールをここに追加
    // ユーザーのプライバシーを尊重し、必要最小限のデータのみ収集
    </script>
    <?php endif; ?>

</body>

</html>