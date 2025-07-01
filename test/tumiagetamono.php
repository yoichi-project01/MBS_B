<?php
require_once(__DIR__ . '/../component/autoloader.php');
include(__DIR__ . '/../component/header.php');

$perPage = 4;

function formatLeadTime($secondsFloat)
{
    $totalSeconds = (int) round($secondsFloat);
    $days = floor($totalSeconds / 86400);
    $hours = floor(($totalSeconds % 86400) / 3600);
    $minutes = floor(($totalSeconds % 3600) / 60);
    $seconds = $totalSeconds % 60;

    $result = "";
    if ($days > 0) $result .= "{$days}日 ";
    if ($hours > 0) $result .= "{$hours}時間 ";
    if ($minutes > 0) $result .= "{$minutes}分 ";
    $result .= "{$seconds}秒";
    return trim($result);
}

$validator = new Validator();
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int) $_GET['page'] : 1;
$selectedStore = isset($_GET['store']) ? trim($_GET['store']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$allowedStores = ['緑橋本店', '今里店', '深江橋店'];
if ($selectedStore && !$validator->inArray($selectedStore, $allowedStores, '店舗名')) {
    $selectedStore = '';
}

if (!$validator->maxLength($search, 100, '検索文字列')) {
    $search = substr($search, 0, 100);
}

$escapedSelectedStore = htmlspecialchars($selectedStore);
$escapedSearch = htmlspecialchars($search);

try {
    $rows = [];
    $totalCount = 0;

    if (!empty($selectedStore)) {
        $whereConditions = "c.store_name = :store";
        $params = [':store' => $selectedStore];

        if ($search !== '') {
            $whereConditions .= " AND c.customer_name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }

        $countSql = "SELECT COUNT(*) FROM statistics_information s JOIN customers c ON s.customer_no = c.customer_no WHERE $whereConditions";
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalCount = (int) $countStmt->fetchColumn();

        if ($totalCount > 0) {
            $queryParams = array_filter(['store' => $selectedStore, 'search' => $search]);
            $pagination = new Pagination($page, $totalCount, $perPage, '', $queryParams);

            if ($pagination->needsRedirect()) {
                header("Location: " . $pagination->getRedirectUrl());
                exit;
            }

            $offset = $pagination->getOffset($perPage);

            $sql = "SELECT c.customer_no, c.customer_name, c.store_name, s.sales_by_customer, s.lead_time, s.delivery_amount 
                    FROM statistics_information s JOIN customers c ON s.customer_no = c.customer_no 
                    WHERE $whereConditions ORDER BY c.customer_no ASC LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();
        }
    }
} catch (PDOException $e) {
    error_log('Statistics page database error: ' . $e->getMessage());
    $environment = $_ENV['ENVIRONMENT'] ?? 'development';
    $errorMessage = $environment === 'production'
        ? "データベースエラーが発生しました。管理者にお問い合わせください。"
        : "DBエラー: " . htmlspecialchars($e->getMessage());
    echo '<div style="text-align: center; padding: 50px; color: #dc3545;">' . $errorMessage . '</div>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報 - 在庫管理システム</title>
    <link rel="stylesheet" href="../style.css">
    <!-- Chart.js CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
    .enhanced-controls {
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.15);
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: center;
        border: 2px solid rgba(126, 217, 87, 0.2);
    }

    .enhanced-table-container {
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.15);
        overflow: hidden;
        margin-bottom: 24px;
        border: 2px solid rgba(126, 217, 87, 0.2);
    }

    .enhanced-statistics-table {
        width: 100%;
        border-collapse: collapse;
        font-family: var(--font-family);
    }

    .enhanced-statistics-table thead {
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
    }

    .enhanced-statistics-table th {
        padding: 18px 15px;
        text-align: left;
        color: white;
        font-weight: 700;
        font-size: 14px;
        position: relative;
    }

    .enhanced-statistics-table td {
        padding: 16px 15px;
        border-bottom: 1px solid rgba(47, 93, 63, 0.1);
        color: var(--font-color);
        transition: background-color 0.2s ease;
    }

    .enhanced-table-row:hover {
        background: rgba(126, 217, 87, 0.08);
    }

    .enhanced-table-row:nth-child(even) {
        background-color: rgba(248, 250, 249, 0.5);
    }

    .enhanced-table-row:nth-child(even):hover {
        background: rgba(126, 217, 87, 0.08);
    }

    .graph-btn {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
    }

    .graph-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(76, 175, 80, 0.4);
        background: linear-gradient(135deg, #45a049, #4CAF50);
    }

    .graph-btn:focus-visible {
        outline: 2px solid var(--accent-green);
        outline-offset: 2px;
    }

    .sample-data-btn {
        background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    }

    .sample-data-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        background: linear-gradient(135deg, #ee5a52, #ff6b6b);
    }

    /* モーダルスタイル */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        animation: modalFadeIn 0.3s ease;
    }

    @keyframes modalFadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .modal-content {
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        margin: 3% auto;
        padding: 30px;
        border-radius: 16px;
        width: 90%;
        max-width: 900px;
        position: relative;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: modalSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid rgba(126, 217, 87, 0.2);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px) scale(0.9);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(126, 217, 87, 0.2);
    }

    .modal-title {
        color: var(--main-green);
        font-size: 20px;
        font-weight: 700;
        margin: 0;
    }

    .close {
        color: var(--sub-green);
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        background: none;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close:hover {
        color: var(--main-green);
        background: rgba(126, 217, 87, 0.1);
        transform: scale(1.1);
    }

    .chart-container {
        margin: 20px 0;
        height: 400px;
        position: relative;
    }

    .enhanced-no-data {
        text-align: center;
        padding: 80px 20px;
        background: linear-gradient(135deg, #ffffff 0%, #f8faf9 100%);
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.15);
        border: 2px dashed var(--sub-green);
    }

    .enhanced-no-data .icon {
        font-size: 64px;
        margin-bottom: 20px;
        display: block;
        filter: grayscale(0.3);
    }

    .enhanced-no-data p {
        font-size: 18px;
        color: var(--main-green);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .enhanced-no-data .sub-message {
        font-size: 14px !important;
        color: var(--sub-green) !important;
        font-weight: 400 !important;
    }

    @media (max-width: 768px) {
        .enhanced-controls {
            flex-direction: column;
            align-items: stretch;
            padding: 20px;
        }

        .enhanced-controls select,
        .enhanced-controls input[type="text"],
        .enhanced-controls button {
            width: 100%;
            margin-bottom: 10px;
        }

        .enhanced-statistics-table {
            font-size: 14px;
        }

        .enhanced-statistics-table th,
        .enhanced-statistics-table td {
            padding: 12px 8px;
        }

        .modal-content {
            width: 95%;
            margin: 5% auto;
            padding: 20px;
        }

        .chart-container {
            height: 300px;
        }
    }
    </style>
</head>

<body class="with-header">
    <div class="container">
        <h1 class="page-title">
            📊 統計情報
        </h1>

        <!-- 拡張コントロールパネル -->
        <div class="enhanced-controls">
            <form method="GET" action="" style="display: contents;">
                <input type="hidden" name="store" value="<?= $escapedSelectedStore ?>">
                <input type="text" name="search" class="search-input" value="<?= $escapedSearch ?>"
                    placeholder="顧客名で検索..." maxlength="100" autocomplete="off" style="flex: 1; min-width: 200px;">
                <button type="submit" class="search-button">
                    🔍 検索
                </button>
            </form>
            <button class="sample-data-btn" onclick="generateSampleData()">
                📊 サンプルデータ生成
            </button>
        </div>

        <!-- 統計情報テーブル -->
        <div class="statistics-container">
            <?php if (empty($rows)): ?>
            <div class="enhanced-no-data">
                <span class="icon">📋</span>
                <?php if (empty($selectedStore)): ?>
                <p>店舗を選択してください。</p>
                <p class="sub-message">ヘッダーのナビゲーションから店舗を選択してデータを表示してください。</p>
                <?php else: ?>
                <p>該当するデータがありません。</p>
                <p class="sub-message">別の条件で検索してみるか、「サンプルデータ生成」をお試しください。</p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="enhanced-table-container">
                <table class="enhanced-statistics-table" id="customerTable">
                    <thead>
                        <tr>
                            <th style="width: 25%;">
                                <span>顧客名</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" onclick="sortTable('customer_name', 'asc')"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" onclick="sortTable('customer_name', 'desc')"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 20%;">
                                <span>売上（円）</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" onclick="sortTable('sales_by_customer', 'asc')"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" onclick="sortTable('sales_by_customer', 'desc')"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 25%;">
                                <span>リードタイム</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" onclick="sortTable('lead_time', 'asc')"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" onclick="sortTable('lead_time', 'desc')"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 15%;">
                                <span>配達回数</span>
                                <div class="sort-buttons">
                                    <button class="sort-btn" onclick="sortTable('delivery_amount', 'asc')"
                                        title="昇順">▲</button>
                                    <button class="sort-btn" onclick="sortTable('delivery_amount', 'desc')"
                                        title="降順">▼</button>
                                </div>
                            </th>
                            <th style="width: 15%;">グラフ</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <?php foreach ($rows as $row): ?>
                        <tr class="enhanced-table-row" data-customer-no="<?= htmlspecialchars($row['customer_no']) ?>">
                            <td data-column="customer_name">
                                <span><?= htmlspecialchars($row['customer_name']) ?></span>
                            </td>
                            <td data-column="sales_by_customer">
                                <span class="amount-value"><?= number_format($row['sales_by_customer']) ?></span>
                            </td>
                            <td data-column="lead_time">
                                <span class="time-value"><?= formatLeadTime($row['lead_time']) ?></span>
                            </td>
                            <td data-column="delivery_amount">
                                <span class="count-value"><?= htmlspecialchars($row['delivery_amount']) ?></span>
                            </td>
                            <td>
                                <button class="graph-btn"
                                    onclick="showSalesGraph(<?= $row['customer_no'] ?>, '<?= htmlspecialchars($row['customer_name']) ?>')">
                                    📊 グラフ
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ページネーション -->
            <?php if (isset($pagination)): ?>
            <?= $pagination->render() ?>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- グラフモーダル -->
    <div id="graphModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle" class="modal-title">売上推移グラフ</h2>
                <button class="close" onclick="closeModal()" aria-label="モーダルを閉じる">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    (function() {
        'use strict';

        let currentChart = null;
        let sampleDataGenerated = false;

        // テーブルソート機能
        function sortTable(column, order) {
            const tbody = document.getElementById('customerTableBody');
            const rows = Array.from(tbody.querySelectorAll('.enhanced-table-row'));

            rows.sort(function(a, b) {
                const aCell = a.querySelector('[data-column="' + column + '"]');
                const bCell = b.querySelector('[data-column="' + column + '"]');

                if (!aCell || !bCell) return 0;

                let aValue = aCell.textContent.trim();
                let bValue = bCell.textContent.trim();

                // 数値の処理
                if (column === 'sales_by_customer' || column === 'delivery_amount') {
                    aValue = parseFloat(aValue.replace(/[,円]/g, '')) || 0;
                    bValue = parseFloat(bValue.replace(/[,円]/g, '')) || 0;
                } else if (column === 'lead_time') {
                    aValue = parseLeadTime(aValue);
                    bValue = parseLeadTime(bValue);
                }

                if (order === 'asc') {
                    return aValue > bValue ? 1 : -1;
                } else {
                    return aValue < bValue ? 1 : -1;
                }
            });

            // ソートボタンの状態更新
            document.querySelectorAll('.sort-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // 行を再配置
            tbody.innerHTML = '';
            rows.forEach(function(row) {
                tbody.appendChild(row);
            });
        }

        // リードタイムをパースして秒数に変換
        function parseLeadTime(timeStr) {
            let totalSeconds = 0;
            const dayMatch = timeStr.match(/(\d+)日/);
            const hourMatch = timeStr.match(/(\d+)時間/);
            const minuteMatch = timeStr.match(/(\d+)分/);
            const secondMatch = timeStr.match(/(\d+)秒/);

            if (dayMatch) totalSeconds += parseInt(dayMatch[1], 10) * 86400;
            if (hourMatch) totalSeconds += parseInt(hourMatch[1], 10) * 3600;
            if (minuteMatch) totalSeconds += parseInt(minuteMatch[1], 10) * 60;
            if (secondMatch) totalSeconds += parseInt(secondMatch[1], 10);

            return totalSeconds;
        }

        // サンプルデータ生成
        function generateSampleData() {
            if (sampleDataGenerated) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'info',
                        title: 'サンプルデータについて',
                        text: 'サンプルデータは既に生成されています。リアルなデータとして売上推移グラフをご確認ください。',
                        confirmButtonColor: '#2f5d3f'
                    });
                } else {
                    alert('サンプルデータは既に生成されています。');
                }
                return;
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'サンプルデータ生成完了',
                    html: `
                        <p>サンプルデータを生成しました！</p>
                        <p>各顧客の「📊 グラフ」ボタンをクリックして、過去6ヶ月の売上推移をご確認ください。</p>
                        <br>
                        <small style="color: #666;">※ 実際のデータではなく、デモンストレーション用のサンプルデータです。</small>
                    `,
                    confirmButtonColor: '#2f5d3f',
                    confirmButtonText: 'OK'
                });
            } else {
                alert('サンプルデータを生成しました！各顧客のグラフボタンをクリックして売上推移を確認してください。');
            }

            sampleDataGenerated = true;
        }

        // グラフ表示
        function showSalesGraph(customerNo, customerName) {
            // サンプルデータ生成
            const salesHistory = generateSalesHistory();

            document.getElementById('modalTitle').textContent = `${customerName} - 売上推移グラフ（過去6ヶ月）`;
            createChart(salesHistory);
            document.getElementById('graphModal').style.display = 'block';

            // フォーカス管理（アクセシビリティ）
            document.querySelector('.close').focus();
        }

        // 売上履歴データ生成（サンプル）
        function generateSalesHistory() {
            const months = ['7月', '8月', '9月', '10月', '11月', '12月'];
            const history = [];

            months.forEach(function(month) {
                // ランダムな売上データを生成（0〜800,000円）
                const sales = Math.floor(Math.random() * 800000) + 50000;
                history.push({
                    month: month,
                    sales: sales
                });
            });

            return history;
        }

        // チャート作成
        function createChart(salesHistory) {
            const ctx = document.getElementById('salesChart').getContext('2d');

            if (currentChart) {
                currentChart.destroy();
            }

            const labels = salesHistory.map(item => item.month);
            const data = salesHistory.map(item => item.sales);

            currentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '売上（円）',
                        data: data,
                        borderColor: '#2f5d3f',
                        backgroundColor: 'rgba(47, 93, 63, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2f5d3f',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: '#7ed957',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: {
                                    size: 14,
                                    family: "'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif",
                                    weight: '600'
                                },
                                color: '#2f5d3f',
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(47, 93, 63, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#7ed957',
                            borderWidth: 2,
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                title: function(context) {
                                    return context[0].label + 'の売上';
                                },
                                label: function(context) {
                                    return '¥' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '¥' + value.toLocaleString();
                                },
                                font: {
                                    size: 12,
                                    family: "'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif"
                                },
                                color: '#4b7a5c'
                            },
                            grid: {
                                color: 'rgba(75, 122, 92, 0.1)',
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: '売上金額（円）',
                                color: '#2f5d3f',
                                font: {
                                    size: 14,
                                    weight: '600'
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 12,
                                    family: "'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif"
                                },
                                color: '#4b7a5c'
                            },
                            grid: {
                                color: 'rgba(75, 122, 92, 0.1)',
                                drawBorder: false
                            },
                            title: {
                                display: true,
                                text: '月',
                                color: '#2f5d3f',
                                font: {
                                    size: 14,
                                    weight: '600'
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        // モーダル閉じる
        function closeModal() {
            document.getElementById('graphModal').style.display = 'none';
            if (currentChart) {
                currentChart.destroy();
                currentChart = null;
            }
        }

        // イベントリスナーの設定
        document.addEventListener('DOMContentLoaded', function() {
            // ソートボタンのイベントリスナー
            document.querySelectorAll('.sort-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    const column = this.getAttribute('data-column');
                    const order = this.getAttribute('data-order');

                    if (column && order) {
                        sortTable(column, order);
                    }
                });
            });

            // モーダル外クリックで閉じる
            document.getElementById('graphModal').addEventListener('click', function(event) {
                if (event.target === this) {
                    closeModal();
                }
            });

            // ESCキーでモーダルを閉じる
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const modal = document.getElementById('graphModal');
                    if (modal.style.display === 'block') {
                        closeModal();
                    }
                }
            });
        });

        // グローバル関数として公開
        window.sortTable = sortTable;
        window.showSalesGraph = showSalesGraph;
        window.closeModal = closeModal;
        window.generateSampleData = generateSampleData;

    })();
    </script>
</body>

</html>