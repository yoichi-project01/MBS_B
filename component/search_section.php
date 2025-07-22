<?php
/**
 * 共通検索セクションコンポーネント
 * 注文書・納品書共通のヘッダー検索エリア
 */

function renderSearchSection($config) {
    $storeName = $config['storeName'];
    $pageType = $config['pageType']; // 'order' or 'delivery'
    $icon = $config['icon'];
    $title = $config['title'];
    $totalCount = $config['totalCount'];
    $itemName = $config['itemName']; // '注文' or '納品書'
    $searchValue = $config['searchValue'] ?? '';
    $createUrl = $config['createUrl'];
    $createButtonText = $config['createButtonText'];
    
    echo '
    <!-- 検索・フィルタリング -->
    <div class="order-search-section">
        <div class="search-container">
            <form action="" method="GET">
                <input type="hidden" name="store" value="' . htmlspecialchars($storeName) . '">
                <input type="text" name="search_customer" class="search-input" placeholder="顧客名で検索..." value="' . htmlspecialchars($searchValue) . '">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> 検索
                </button>
            </form>
        </div>
        <div class="order-header-info">
            <h2 class="order-title">
                <i class="' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($title) . '
            </h2>
            <p class="order-subtitle">' . htmlspecialchars($storeName) . ' - 全 ' . intval($totalCount) . ' 件の' . htmlspecialchars($itemName) . '</p>
        </div>
        <div class="order-actions">
            <a href="' . htmlspecialchars($createUrl) . '" class="btn-create-order">
                <i class="fas fa-plus"></i> ' . htmlspecialchars($createButtonText) . '
            </a>
        </div>
    </div>';
}
?>