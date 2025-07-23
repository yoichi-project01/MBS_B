<?php
/**
 * 統一されたアクションボタンコンポーネント
 * 詳細ボタン、操作ボタンなどの共通化
 */

/**
 * アクションボタンを描画する
 * 
 * @param array $config ボタン設定配列
 * @return string HTML文字列
 */
function renderActionButton($config) {
    // デフォルト設定
    $defaults = [
        'type' => 'primary',
        'size' => 'sm',
        'url' => '#',
        'label' => 'ボタン',
        'icon' => null,
        'target' => '_self',
        'disabled' => false,
        'confirm' => null,
        'class' => '',
        'data' => []
    ];
    
    $config = array_merge($defaults, $config);
    
    // ボタンタイプによるクラス設定
    $typeClasses = [
        'detail' => 'action-btn-detail',
        'delivery' => 'action-btn-delivery', 
        'create' => 'action-btn-create',
        'edit' => 'action-btn-edit',
        'delete' => 'action-btn-delete',
        'primary' => 'action-btn-primary',
        'secondary' => 'action-btn-secondary',
        'success' => 'action-btn-success',
        'warning' => 'action-btn-warning',
        'danger' => 'action-btn-danger'
    ];
    
    // サイズクラス
    $sizeClasses = [
        'xs' => 'action-btn-xs',
        'sm' => 'action-btn-sm', 
        'md' => 'action-btn-md',
        'lg' => 'action-btn-lg'
    ];
    
    // CSSクラスの構築
    $classes = ['action-btn'];
    
    if (isset($typeClasses[$config['type']])) {
        $classes[] = $typeClasses[$config['type']];
    }
    
    if (isset($sizeClasses[$config['size']])) {
        $classes[] = $sizeClasses[$config['size']];
    }
    
    if ($config['disabled']) {
        $classes[] = 'action-btn-disabled';
    }
    
    if (!empty($config['class'])) {
        $classes[] = $config['class'];
    }
    
    $classStr = implode(' ', $classes);
    
    // 属性の構築
    $attributes = [];
    $attributes[] = 'class="' . htmlspecialchars($classStr) . '"';
    $attributes[] = 'href="' . htmlspecialchars($config['url']) . '"';
    
    if ($config['target'] !== '_self') {
        $attributes[] = 'target="' . htmlspecialchars($config['target']) . '"';
    }
    
    if ($config['disabled']) {
        $attributes[] = 'aria-disabled="true"';
        $attributes[] = 'onclick="event.preventDefault(); return false;"';
    }
    
    if (!empty($config['confirm'])) {
        $confirmMsg = htmlspecialchars($config['confirm'], ENT_QUOTES);
        $attributes[] = 'onclick="return confirm(\'' . $confirmMsg . '\')"';
    }
    
    // data属性の追加
    foreach ($config['data'] as $key => $value) {
        $attributes[] = 'data-' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    $attributeStr = implode(' ', $attributes);
    
    // アイコンの構築
    $iconHtml = '';
    if (!empty($config['icon'])) {
        $iconHtml = '<i class="' . htmlspecialchars($config['icon']) . '"></i>';
    }
    
    // ラベルの構築
    $labelHtml = htmlspecialchars($config['label']);
    
    // 最終HTML
    return '<a ' . $attributeStr . '>' . $iconHtml . $labelHtml . '</a>';
}

/**
 * 詳細ボタンを描画する（ショートカット関数）
 */
function renderDetailButton($url, $label = '詳細', $storeName = '') {
    return renderActionButton([
        'type' => 'detail',
        'url' => $url,
        'label' => $label,
        'icon' => 'fas fa-eye'
    ]);
}

/**
 * 編集ボタンを描画する（ショートカット関数）
 */
function renderEditButton($url, $label = '編集', $storeName = '') {
    return renderActionButton([
        'type' => 'edit',
        'url' => $url, 
        'label' => $label,
        'icon' => 'fas fa-edit'
    ]);
}

/**
 * 削除ボタンを描画する（ショートカット関数）
 */
function renderDeleteButton($url, $label = '削除', $confirmMessage = '本当に削除しますか？') {
    return renderActionButton([
        'type' => 'delete',
        'url' => $url,
        'label' => $label,
        'icon' => 'fas fa-trash',
        'confirm' => $confirmMessage
    ]);
}

/**
 * 作成ボタンを描画する（ショートカット関数）
 */
function renderCreateButton($url, $label = '新規作成', $storeName = '') {
    return renderActionButton([
        'type' => 'create',
        'url' => $url,
        'label' => $label,
        'icon' => 'fas fa-plus'
    ]);
}
?>