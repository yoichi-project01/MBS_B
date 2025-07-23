<?php
/**
 * 統一されたステータスバッジコンポーネント
 * 注文・納品ステータスの表示統一
 */

/**
 * ステータスバッジを描画する
 * 
 * @param array $config ステータス設定配列
 * @return string HTML文字列
 */
function renderStatusBadge($config) {
    // デフォルト設定
    $defaults = [
        'status' => 'unknown',
        'type' => 'order', // 'order' or 'delivery'
        'size' => 'sm',
        'icon' => true,
        'class' => '',
        'customText' => null
    ];
    
    $config = array_merge($defaults, $config);
    
    // ステータステキストの取得
    $statusText = $config['customText'] ?? getStatusText($config['status'], $config['type']);
    
    // ステータス設定の取得
    $statusConfig = getStatusConfig($config['status'], $config['type']);
    
    // サイズクラス
    $sizeClasses = [
        'xs' => 'status-badge-xs',
        'sm' => 'status-badge-sm',
        'md' => 'status-badge-md', 
        'lg' => 'status-badge-lg'
    ];
    
    // CSSクラスの構築
    $classes = ['status-badge'];
    $classes[] = 'status-badge-' . $config['status'];
    $classes[] = 'status-badge-' . $config['type'];
    
    if (isset($sizeClasses[$config['size']])) {
        $classes[] = $sizeClasses[$config['size']];
    }
    
    if (!empty($config['class'])) {
        $classes[] = $config['class'];
    }
    
    $classStr = implode(' ', $classes);
    
    // アイコンの構築
    $iconHtml = '';
    if ($config['icon'] && !empty($statusConfig['icon'])) {
        $iconHtml = '<i class="' . htmlspecialchars($statusConfig['icon']) . '"></i>';
    }
    
    // 最終HTML
    return '<span class="' . htmlspecialchars($classStr) . '">' . $iconHtml . htmlspecialchars($statusText) . '</span>';
}

/**
 * ステータステキストを取得する
 */
function getStatusText($status, $type) {
    if ($type === 'order') {
        return getOrderStatusText($status);
    } elseif ($type === 'delivery') {
        return getDeliveryStatusText($status);
    }
    
    return $status;
}

/**
 * 注文ステータスのテキストを取得
 */
function getOrderStatusText($status) {
    $statusTexts = [
        'pending' => '保留中',
        'processing' => '処理中',
        'completed' => '完了',
        'cancelled' => 'キャンセル',
        'draft' => '下書き',
        'confirmed' => '確認済み',
        'shipped' => '出荷済み',
        'delivered' => '配送完了'
    ];
    
    return $statusTexts[$status] ?? $status;
}

/**
 * 納品ステータスのテキストを取得
 */
function getDeliveryStatusText($status) {
    $statusTexts = [
        'pending' => '未納品',
        'partial' => '一部納品',
        'completed' => '納品済み',
        'cancelled' => 'キャンセル',
        'preparing' => '準備中',
        'shipped' => '出荷済み',
        'delivered' => '配送完了'
    ];
    
    return $statusTexts[$status] ?? $status;
}

/**
 * ステータス設定を取得
 */
function getStatusConfig($status, $type) {
    $configs = [
        'order' => [
            'pending' => [
                'color' => 'warning',
                'icon' => 'fas fa-clock'
            ],
            'processing' => [
                'color' => 'info',
                'icon' => 'fas fa-cog fa-spin'
            ],
            'completed' => [
                'color' => 'success',
                'icon' => 'fas fa-check-circle'
            ],
            'cancelled' => [
                'color' => 'danger',
                'icon' => 'fas fa-times-circle'
            ],
            'draft' => [
                'color' => 'secondary',
                'icon' => 'fas fa-edit'
            ],
            'confirmed' => [
                'color' => 'primary',
                'icon' => 'fas fa-check'
            ],
            'shipped' => [
                'color' => 'info',
                'icon' => 'fas fa-truck'
            ],
            'delivered' => [
                'color' => 'success',
                'icon' => 'fas fa-box'
            ]
        ],
        'delivery' => [
            'pending' => [
                'color' => 'warning',
                'icon' => 'fas fa-hourglass-half'
            ],
            'partial' => [
                'color' => 'warning',
                'icon' => 'fas fa-exclamation-triangle'
            ],
            'completed' => [
                'color' => 'success',
                'icon' => 'fas fa-check-circle'
            ],
            'cancelled' => [
                'color' => 'danger',
                'icon' => 'fas fa-ban'
            ],
            'preparing' => [
                'color' => 'info',
                'icon' => 'fas fa-boxes'
            ],
            'shipped' => [
                'color' => 'primary',
                'icon' => 'fas fa-shipping-fast'
            ],
            'delivered' => [
                'color' => 'success',
                'icon' => 'fas fa-home'
            ]
        ]
    ];
    
    return $configs[$type][$status] ?? [
        'color' => 'secondary',
        'icon' => 'fas fa-question-circle'
    ];
}

/**
 * 注文ステータスバッジ（ショートカット関数）
 */
function renderOrderStatusBadge($status, $size = 'sm', $customText = null) {
    return renderStatusBadge([
        'status' => $status,
        'type' => 'order',
        'size' => $size,
        'customText' => $customText
    ]);
}

/**
 * 納品ステータスバッジ（ショートカット関数）
 */
function renderDeliveryStatusBadge($status, $size = 'sm', $customText = null) {
    return renderStatusBadge([
        'status' => $status,
        'type' => 'delivery',
        'size' => $size,
        'customText' => $customText
    ]);
}

/**
 * 既存のtranslate_status関数との互換性維持
 */
if (!function_exists('translate_status')) {
    function translate_status($status) {
        return getOrderStatusText($status);
    }
}
?>