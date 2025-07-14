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

    <!-- 印刷用スタイル -->
    <style media="print">
    .site-header,
    .top-nav,
    .modal,
    .action-buttons,
    .pagination-controls,
    .bulk-actions,
    .quick-actions {
        display: none !important;
    }

    .content-scroll-area {
        padding: 0 !important;
    }

    .data-table {
        font-size: 12px;
    }

    .data-table th,
    .data-table td {
        padding: 8px 4px;
    }

    body {
        background: white !important;
    }

    .customer-card,
    .metric-card {
        break-inside: avoid;
    }
    </style>

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

<style>
/* エラーバナーのスタイル */
.error-banner {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    padding: 15px 20px;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.error-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.error-close {
    margin-left: auto;
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.error-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* 空の状態のスタイル */
.empty-state-main {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 249, 0.9) 100%);
    border-radius: 20px;
    margin: 40px auto;
    max-width: 600px;
    box-shadow: 0 12px 40px rgba(47, 93, 63, 0.1);
    border: 1px solid rgba(126, 217, 87, 0.2);
}

.empty-state-main .empty-icon {
    font-size: 80px;
    color: var(--sub-green);
    margin-bottom: 30px;
    opacity: 0.6;
}

.empty-state-main h2 {
    font-size: 32px;
    color: var(--main-green);
    margin-bottom: 15px;
    font-weight: 800;
}

.empty-state-main p {
    color: var(--sub-green);
    margin-bottom: 30px;
    line-height: 1.6;
    font-size: 16px;
}

.empty-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.btn.btn-primary {
    background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    color: white;
    box-shadow: 0 4px 12px rgba(126, 217, 87, 0.3);
}

.btn.btn-secondary {
    background: rgba(126, 217, 87, 0.1);
    color: var(--main-green);
    border: 1px solid rgba(126, 217, 87, 0.3);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(126, 217, 87, 0.4);
}

/* パフォーマンス最適化 */
.customer-overview-card,
.metric-card,
.top-customer-card {
    will-change: transform;
}

/* ローディングスピナーのスタイル */
.loading-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px;
    color: var(--sub-green);
}

.loading-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid rgba(126, 217, 87, 0.3);
    border-top: 3px solid var(--accent-green);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* レスポンシブ調整 */
@media (max-width: 768px) {
    .error-banner {
        position: relative;
        margin-bottom: 20px;
    }

    .empty-state-main {
        margin: 20px 10px;
        padding: 40px 20px;
    }

    .empty-state-main .empty-icon {
        font-size: 60px;
    }

    .empty-state-main h2 {
        font-size: 24px;
    }

    .empty-actions {
        flex-direction: column;
        align-items: center;
    }

    .btn {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
}

/* アクセシビリティ改善 */
@media (prefers-reduced-motion: reduce) {
    .loading-spinner {
        animation: none;
    }

    .metric-card,
    .customer-overview-card,
    .btn {
        transition: none;
    }
}

/* 高コントラストモード対応 */
@media (prefers-contrast: high) {
    .error-banner {
        border: 3px solid white;
    }

    .empty-state-main {
        border: 2px solid var(--main-green);
    }

    .btn {
        border: 2px solid currentColor;
    }
}
</style>