<?php
// 最もシンプルなテスト用API
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Simple test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => [
        'customers' => [
            [
                'customer_no' => 10001,
                'customer_name' => 'テスト顧客',
                'total_sales' => 100000,
                'delivery_count' => 5,
                'avg_lead_time' => 2.5
            ]
        ],
        'summary' => [
            'total_customers' => 1,
            'total_sales' => 100000,
            'avg_lead_time' => 2.5,
            'total_deliveries' => 5,
            'active_customers' => 1
        ],
        'pagination' => [
            'current_page' => 1,
            'total_pages' => 1,
            'total_records' => 1,
            'per_page' => 20
        ]
    ]
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>