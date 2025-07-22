<?php
/**
 * 共通データテーブルコンポーネント
 * 注文書・納品書共通のテーブル表示
 */

function renderDataTable($config) {
    $storeName = $config['storeName'];
    $pageType = $config['pageType']; // 'order' or 'delivery'
    $columns = $config['columns']; // テーブルのカラム定義
    $data = $config['data']; // 表示データ
    $sortParams = $config['sortParams'] ?? [];
    $emptyMessage = $config['emptyMessage'] ?? '該当するデータはありません。';
    
    echo '<div class="table-view-container">
        <table class="data-table">
            <thead>
                <tr>';
    
    // ヘッダーの生成
    foreach ($columns as $column) {
        $sortUrl = '';
        if (isset($column['sortable']) && $column['sortable']) {
            $currentSort = $sortParams['column'] ?? '';
            $currentOrder = $sortParams['order'] ?? 'ASC';
            $newOrder = ($currentSort === $column['key'] && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
            $sortUrl = "?store=" . urlencode($storeName) . "&sort=" . $column['key'] . "&order=" . $newOrder;
            if (isset($sortParams['search'])) {
                $sortUrl .= "&search_customer=" . urlencode($sortParams['search']);
            }
        }
        
        if ($sortUrl) {
            echo '<th><a href="' . htmlspecialchars($sortUrl) . '">' . htmlspecialchars($column['label']) . '</a></th>';
        } else {
            echo '<th>' . htmlspecialchars($column['label']) . '</th>';
        }
    }
    
    echo '    </tr>
            </thead>
            <tbody>';
    
    // データ行の生成
    if (empty($data)) {
        $colspan = count($columns);
        echo '<tr><td colspan="' . $colspan . '" class="text-center">' . htmlspecialchars($emptyMessage) . '</td></tr>';
    } else {
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($columns as $column) {
                $key = $column['key'];
                $value = isset($row[$key]) ? $row[$key] : '';
                
                // カスタムレンダラーがある場合
                if (isset($column['renderer']) && is_callable($column['renderer'])) {
                    $value = call_user_func($column['renderer'], $value, $row, $storeName);
                }
                
                echo '<td>' . $value . '</td>';
            }
            echo '</tr>';
        }
    }
    
    echo '    </tbody>
        </table>
    </div>';
}

/**
 * 注文書用のカラム定義
 */
function getOrderColumns() {
    return [
        [
            'key' => 'order_no',
            'label' => '注文番号',
            'sortable' => true
        ],
        [
            'key' => 'customer_name',
            'label' => '顧客名',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return '<span class="customer-name-clickable" data-customer-name="' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</span>';
            }
        ],
        [
            'key' => 'registration_date',
            'label' => '注文日',
            'sortable' => true
        ],
        [
            'key' => 'total_amount',
            'label' => '合計金額',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return '¥' . number_format($value);
            }
        ],
        [
            'key' => 'status',
            'label' => 'ステータス',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                $statusText = translate_status($value);
                return '<span class="status-' . htmlspecialchars($value) . '">' . htmlspecialchars($statusText) . '</span>';
            }
        ],
        [
            'key' => 'actions',
            'label' => '操作',
            'renderer' => function($value, $row, $storeName) {
                $orderNo = htmlspecialchars($row['order_no']);
                $storeParam = htmlspecialchars($storeName);
                return '<a href="detail.php?order_no=' . $orderNo . '&store=' . $storeParam . '" class="btn-detail">詳細</a>
                        <a href="../delivery_list/index.php?order_id=' . $orderNo . '" class="btn-delivery" target="_blank">納品書</a>';
            }
        ]
    ];
}

/**
 * 納品書用のカラム定義
 */
function getDeliveryColumns() {
    return [
        [
            'key' => 'delivery_no',
            'label' => '納品書番号',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return sprintf('D%04d', $row['id']);
            }
        ],
        [
            'key' => 'customer_name',
            'label' => '顧客名',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return '<span class="customer-name-clickable" data-customer-name="' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</span>';
            }
        ],
        [
            'key' => 'delivery_date',
            'label' => '納品日',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return date('Y-m-d');
            }
        ],
        [
            'key' => 'total_amount',
            'label' => '合計金額',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return '¥' . number_format(rand(10000, 50000));
            }
        ],
        [
            'key' => 'status',
            'label' => 'ステータス',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                $statusText = '';
                if ($value === 'completed') {
                    $statusText = '納品済み';
                } else if ($value === 'partial') {
                    $statusText = '一部納品';
                } else {
                    $statusText = '未納品';
                }
                return '<span class="status-badge status-' . htmlspecialchars($value) . '">' . htmlspecialchars($statusText) . '</span>';
            }
        ],
        [
            'key' => 'actions',
            'label' => '操作',
            'renderer' => function($value, $row, $storeName) {
                $deliveryNo = sprintf('D%04d', $row['id']);
                $storeParam = htmlspecialchars($storeName);
                return '<a href="detail.php?delivery_no=' . $deliveryNo . '&store=' . $storeParam . '" class="btn-detail">詳細</a>
                        <a href="../order_list/index.php?delivery_id=' . $deliveryNo . '" class="btn-delivery" target="_blank">注文書</a>';
            }
        ]
    ];
}

/**
 * ステータス翻訳関数（注文書用）
 */
function translate_status($status) {
    switch ($status) {
        case 'pending': return '保留中';
        case 'processing': return '処理中';
        case 'completed': return '完了';
        case 'cancelled': return 'キャンセル';
        default: return $status;
    }
}
?>