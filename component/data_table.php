<?php
/**
 * 共通データテーブルコンポーネント
 * 注文書・納品書共通のテーブル表示
 */

require_once(__DIR__ . '/status_badge.php');
require_once(__DIR__ . '/table_actions.php');

function renderDataTable($config) {
    $storeName = $config['storeName'];
    $pageType = $config['pageType']; // 'order' or 'delivery'
    $columns = $config['columns']; // テーブルのカラム定義
    $data = $config['data']; // 表示データ
    $sortParams = $config['sortParams'] ?? [];
    $emptyMessage = $config['emptyMessage'] ?? '該当するデータはありません。';
    $mobileMode = $config['mobileMode'] ?? 'full'; // 'full' or 'customer-only'
    
    // モバイル用の特別なCSSクラスを追加
    $tableClass = 'data-table';
    if ($pageType === 'order' && $mobileMode === 'customer-only') {
        $tableClass .= ' mobile-customer-only';
    }
    
    echo '<div class="table-view-container">
        <table class="' . $tableClass . '">
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
                $value = isset($row[$key]) ? $row[$key] : null;
                
                // カスタムレンダラーがある場合
                if (isset($column['renderer']) && is_callable($column['renderer'])) {
                    $value = call_user_func($column['renderer'], $value, $row, $storeName);
                } else {
                    // レンダラーがない場合のデフォルト処理
                    $value = $value !== null ? htmlspecialchars($value) : '';
                }
                
                // モバイル用のdata-label属性を追加
                $dataLabel = isset($column['label']) ? $column['label'] : '';
                echo '<td data-label="' . htmlspecialchars($dataLabel) . '">' . $value . '</td>';
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
                $customerName = htmlspecialchars($value);
                $orderNo = htmlspecialchars($row['order_no']);
                return '<span class="customer-name-clickable" data-customer="' . $customerName . '" data-order="' . $orderNo . '" data-store="' . htmlspecialchars($storeName) . '">' . $customerName . '</span>';
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
                $amount = is_numeric($value) ? floatval($value) : 0;
                return '¥' . number_format($amount);
            }
        ],
        [
            'key' => 'status',
            'label' => 'ステータス',
            'sortable' => true,
            'renderer' => function($value, $row, $storeName) {
                return renderOrderStatusBadge($value);
            }
        ],
        [
            'key' => 'actions',
            'label' => '操作',
            'renderer' => function($value, $row, $storeName) {
                return renderOrderTableActions($row['order_no'], $storeName, false);
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
                return htmlspecialchars($value);
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
                return renderDeliveryStatusBadge($value);
            }
        ],
        [
            'key' => 'actions',
            'label' => '操作',
            'renderer' => function($value, $row, $storeName) {
                $deliveryNo = sprintf('D%04d', $row['id']);
                return renderDeliveryTableActions($deliveryNo, $storeName, true);
            }
        ]
    ];
}

/**
 * ステータス翻訳関数（注文書用）
 */
if (!function_exists('translate_status')) {
    function translate_status($status) {
        switch ($status) {
            case 'pending': return '保留中';
            case 'processing': return '処理中';
            case 'completed': return '完了';
            case 'cancelled': return 'キャンセル';
            default: return $status;
        }
    }
}
?>