<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
require_once(__DIR__ . '/../component/search_section.php');
require_once(__DIR__ . '/../component/data_table.php');
require_once(__DIR__ . '/../component/pagination.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

$sampleDeliveries = [
    ['id' => 1, 'customer_name' => '木村 紗希', 'status' => 'completed', 'completed' => 3, 'total' => 3],
    ['id' => 2, 'customer_name' => '桜井株式会社', 'status' => 'partial', 'completed' => 2, 'total' => 3],
    ['id' => 3, 'customer_name' => 'カフェ ドルチェビータ', 'status' => 'completed', 'completed' => 1, 'total' => 1],
    ['id' => 4, 'customer_name' => '喫茶店 フレーバー', 'status' => 'completed', 'completed' => 5, 'total' => 5],
    ['id' => 5, 'customer_name' => '木下萌', 'status' => 'partial', 'completed' => 3, 'total' => 4],
    ['id' => 6, 'customer_name' => 'コーヒーハウス レインボー', 'status' => 'completed', 'completed' => 3, 'total' => 3],
];

$sampleCustomers = [
    '木村紗希',
    'カフェ ドルチェビータ',
    '喫茶店 フレーバー',
    '木下萌',
    '川上里奈',
    'コーヒーハウス レインボー',
    '桜井株式会社'
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書一覧 - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($storeName); ?>の納品書一覧を表示します。納品書の検索、並び替え、詳細確認が可能です。">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/action_button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/status_badge.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table_actions.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/order.css">
    
    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="with-header delivery-list-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="delivery-list-container">
                    
                    <?php
                    // 検索セクションの設定
                    renderSearchSection([
                        'storeName' => $storeName,
                        'pageType' => 'delivery',
                        'icon' => 'fas fa-truck',
                        'title' => '納品書一覧',
                        'totalCount' => count($sampleDeliveries),
                        'itemName' => '納品書',
                        'searchValue' => '',
                        'createUrl' => "create.php?store=" . urlencode($storeName),
                        'createButtonText' => '新規納品書作成'
                    ]);
                    ?>

                    <?php
                    // テーブルの設定
                    renderDataTable([
                        'storeName' => $storeName,
                        'pageType' => 'delivery',
                        'columns' => getDeliveryColumns(),
                        'data' => $sampleDeliveries,
                        'sortParams' => [
                            'column' => 'delivery_date',
                            'order' => 'DESC',
                            'search' => ''
                        ],
                        'emptyMessage' => '該当する納品書はありません。'
                    ]);
                    ?>

                    <?php
                    // ページネーションの設定（サンプルデータなので1ページのみ）
                    $page = 1;
                    $total_pages = 1;
                    renderPagination([
                        'storeName' => $storeName,
                        'currentPage' => $page,
                        'totalPages' => $total_pages,
                        'searchValue' => '',
                        'sortColumn' => 'delivery_date',
                        'sortOrder' => 'DESC',
                        'totalItems' => count($sampleDeliveries),
                        'itemsPerPage' => 10
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
</body>

</html>