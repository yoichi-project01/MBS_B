<?php
// デバッグ用のシンプルなAPI
require_once(__DIR__ . '/../component/autoloader.php');

header('Content-Type: application/json');

// 認証チェック
if (!SessionManager::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// エラー表示を有効にしてデバッグ
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 基本的なレスポンス
    $response = [
        'success' => true,
        'message' => 'デバッグAPI正常動作',
        'data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'post_data' => $_POST,
            'get_data' => $_GET
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE);
}
?>