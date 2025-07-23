<?php
/**
 * テーブルアクションコンポーネント
 * テーブル行のアクション部分の統一化
 */

require_once(__DIR__ . '/action_button.php');

/**
 * テーブルアクションを描画する
 * 
 * @param array $config アクション設定配列
 * @return string HTML文字列
 */
function renderTableActions($config) {
    // デフォルト設定
    $defaults = [
        'actions' => [],
        'wrapper' => true,
        'layout' => 'horizontal', // 'horizontal' or 'vertical' or 'dropdown'
        'class' => '',
        'size' => 'sm'
    ];
    
    $config = array_merge($defaults, $config);
    
    if (empty($config['actions'])) {
        return '';
    }
    
    $actionsHtml = [];
    
    foreach ($config['actions'] as $action) {
        $actionConfig = array_merge([
            'size' => $config['size']
        ], $action);
        
        $actionsHtml[] = renderActionButton($actionConfig);
    }
    
    // レイアウトによる組み立て
    $actionsStr = '';
    if ($config['layout'] === 'dropdown') {
        $actionsStr = renderDropdownActions($actionsHtml, $config);
    } else {
        $separator = $config['layout'] === 'vertical' ? '' : ' ';
        $actionsStr = implode($separator, $actionsHtml);
    }
    
    // ラッパーが必要な場合
    if ($config['wrapper']) {
        $wrapperClass = 'table-actions';
        if ($config['layout'] === 'vertical') {
            $wrapperClass .= ' table-actions-vertical';
        } elseif ($config['layout'] === 'dropdown') {
            $wrapperClass .= ' table-actions-dropdown';
        }
        
        if (!empty($config['class'])) {
            $wrapperClass .= ' ' . $config['class'];
        }
        
        return '<div class="' . htmlspecialchars($wrapperClass) . '">' . $actionsStr . '</div>';
    }
    
    return $actionsStr;
}

/**
 * ドロップダウンスタイルのアクションを描画
 */
function renderDropdownActions($actionsHtml, $config) {
    if (empty($actionsHtml)) {
        return '';
    }
    
    // 最初のアクションを主要アクションとして表示
    $primaryAction = array_shift($actionsHtml);
    
    if (empty($actionsHtml)) {
        return $primaryAction;
    }
    
    $dropdownId = 'actions-' . uniqid();
    
    $html = $primaryAction;
    $html .= '<div class="dropdown">';
    $html .= '<button class="action-btn action-btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
    $html .= '<i class="fas fa-ellipsis-v"></i>';
    $html .= '</button>';
    $html .= '<div class="dropdown-menu" aria-labelledby="' . $dropdownId . '">';
    
    foreach ($actionsHtml as $actionHtml) {
        $html .= '<div class="dropdown-item">' . $actionHtml . '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * 注文書用のテーブルアクションを描画（ショートカット関数）
 */
function renderOrderTableActions($orderNo, $storeName, $includeDelivery = false) {
    $actions = [
        [
            'type' => 'detail',
            'url' => 'detail.php?order_no=' . urlencode($orderNo) . '&store=' . urlencode($storeName),
            'label' => '詳細',
            'icon' => 'fas fa-eye'
        ]
    ];
    
    if ($includeDelivery) {
        $actions[] = [
            'type' => 'delivery',
            'url' => '../delivery_list/index.php?order_id=' . urlencode($orderNo),
            'label' => '納品書',
            'icon' => 'fas fa-file-alt',
            'target' => '_blank'
        ];
    }
    
    return renderTableActions([
        'actions' => $actions
    ]);
}

/**
 * 納品書用のテーブルアクションを描画（ショートカット関数）
 */
function renderDeliveryTableActions($deliveryNo, $storeName, $includeOrder = false) {
    $actions = [
        [
            'type' => 'detail',
            'url' => 'detail.php?delivery_no=' . urlencode($deliveryNo) . '&store=' . urlencode($storeName),
            'label' => '詳細',
            'icon' => 'fas fa-eye'
        ]
    ];
    
    return renderTableActions([
        'actions' => $actions
    ]);
}

/**
 * 編集・削除アクションを描画（ショートカット関数）
 */
function renderEditDeleteActions($editUrl, $deleteUrl, $confirmMessage = '本当に削除しますか？') {
    return renderTableActions([
        'actions' => [
            [
                'type' => 'edit',
                'url' => $editUrl,
                'label' => '編集',
                'icon' => 'fas fa-edit'
            ],
            [
                'type' => 'delete',
                'url' => $deleteUrl,
                'label' => '削除',
                'icon' => 'fas fa-trash',
                'confirm' => $confirmMessage
            ]
        ]
    ]);
}

/**
 * カスタムアクション群を描画（ショートカット関数）
 */
function renderCustomTableActions($actions, $layout = 'horizontal', $size = 'sm') {
    return renderTableActions([
        'actions' => $actions,
        'layout' => $layout,
        'size' => $size
    ]);
}
?>