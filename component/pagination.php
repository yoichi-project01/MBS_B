<?php
/**
 * 共通ページネーションコンポーネント
 * 注文書・納品書共通のページネーション表示
 */

function renderPagination($config) {
    $storeName = $config["storeName"];
    $currentPage = $config["currentPage"] ?? 1;
    $totalPages = $config["totalPages"] ?? 1;
    $searchValue = $config["searchValue"] ?? "";
    $sortColumn = $config["sortColumn"] ?? "";
    $sortOrder = $config["sortOrder"] ?? "ASC";
    $totalItems = $config["totalItems"] ?? 0;
    $itemsPerPage = $config["itemsPerPage"] ?? 10;
    
    // 5件以上かつ複数ページがある場合のみ表示
    if ($totalItems < 5 || $totalPages <= 1) {
        return;
    }
    
    echo "<div class=\"pagination\">";
    
    // 前へリンク
    if ($currentPage > 1) {
        $prevUrl = buildPaginationUrl($storeName, $currentPage - 1, $searchValue, $sortColumn, $sortOrder);
        echo "<a href=\"" . htmlspecialchars($prevUrl) . "\">前へ</a>";
    }
    
    // ページ情報
    echo "<span>ページ " . htmlspecialchars(intval($currentPage)) . " / " . htmlspecialchars(intval($totalPages)) . "</span>";
    
    // 次へリンク
    if ($currentPage < $totalPages) {
        $nextUrl = buildPaginationUrl($storeName, $currentPage + 1, $searchValue, $sortColumn, $sortOrder);
        echo "<a href=\"" . htmlspecialchars($nextUrl) . "\">次へ</a>";
    }
    
    echo "</div>";
}

/**
 * ページネーション用URLを構築
 */
function buildPaginationUrl($storeName, $page, $searchValue = "", $sortColumn = "", $sortOrder = "ASC") {
    $params = [];
    $params["store"] = $storeName;
    $params["page"] = $page;
    
    if (!empty($searchValue)) {
        $params["search_customer"] = $searchValue;
    }
    
    if (!empty($sortColumn)) {
        $params["sort"] = $sortColumn;
        $params["order"] = $sortOrder;
    }
    
    return "?" . http_build_query($params);
}

/**
 * ページネーション設定を計算
 */
function calculatePagination($totalItems, $itemsPerPage = 10, $currentPage = 1) {
    $totalPages = max(1, ceil($totalItems / $itemsPerPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        "totalPages" => $totalPages,
        "currentPage" => $currentPage,
        "itemsPerPage" => $itemsPerPage,
        "offset" => $offset,
        "totalItems" => $totalItems
    ];
}
?>
