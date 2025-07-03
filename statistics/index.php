<?php
// 最初にオートローダーを読み込み
require_once(__DIR__ . '/../component/autoloader.php');

// その後にヘッダーを読み込み
include(__DIR__ . '/../component/header.php');

// セッション開始
SessionManager::start();
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報 - 受注管理システム</title>
    <link rel="stylesheet" href="../style.css">
    <!-- SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="with-header statistics-tab-page">
    <!-- タブナビゲーション -->
    <div class="tab-navigation">
        <div class="tab-container">
            <button class="tab-btn active" data-tab="dashboard">
                <span class="tab-icon">📊</span>
                <span>ダッシュボード</span>
            </button>
            <button class="tab-btn" data-tab="customers">
                <span class="tab-icon">👥</span>
                <span>顧客一覧</span>
            </button>
            <button class="tab-btn" data-tab="charts">
                <span class="tab-icon">📈</span>
                <span>グラフ分析</span>
            </button>
            <button class="tab-btn" data-tab="export">
                <span class="tab-icon">📁</span>
                <span>データ出力</span>
            </button>
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="main-content">
        <!-- ダッシュボードタブ -->
        <div id="dashboard" class="tab-content active">
            <div class="dashboard-overview">
                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">👥</div>
                        <div class="metric-title">総顧客数</div>
                    </div>
                    <div class="metric-value">1,234</div>
                    <div class="metric-trend trend-up">
                        <span>↗</span>
                        <span>+12% 前月比</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">💰</div>
                        <div class="metric-title">月間売上</div>
                    </div>
                    <div class="metric-value">¥2.5M</div>
                    <div class="metric-trend trend-up">
                        <span>↗</span>
                        <span>+8% 前月比</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">🚚</div>
                        <div class="metric-title">配達回数</div>
                    </div>
                    <div class="metric-value">456</div>
                    <div class="metric-trend trend-down">
                        <span>↘</span>
                        <span>-3% 前月比</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-header">
                        <div class="metric-icon">⏱️</div>
                        <div class="metric-title">平均リードタイム</div>
                    </div>
                    <div class="metric-value">2.3日</div>
                    <div class="metric-trend trend-up">
                        <span>↗</span>
                        <span>改善中</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 顧客一覧タブ -->
        <div id="customers" class="tab-content">
            <div class="customer-search">
                <div class="search-header">
                    <h2 class="search-title">
                        <span>🔍</span>
                        顧客検索
                    </h2>
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="table">
                            <span>📋</span>
                            テーブル
                        </button>
                        <button class="view-btn" data-view="card">
                            <span>📱</span>
                            カード
                        </button>
                    </div>
                </div>
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" class="search-input" placeholder="顧客名で検索...">
                </div>
            </div>

            <!-- テーブルビュー -->
            <div class="table-view">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                顧客名
                                <button class="sort-btn" data-column="name">▲▼</button>
                            </th>
                            <th>
                                売上（円）
                                <button class="sort-btn" data-column="sales">▲▼</button>
                            </th>
                            <th>
                                リードタイム
                                <button class="sort-btn" data-column="leadtime">▲▼</button>
                            </th>
                            <th>
                                配達回数
                                <button class="sort-btn" data-column="delivery">▲▼</button>
                            </th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>大阪商事株式会社</td>
                            <td>¥1,250,000</td>
                            <td>2日 4時間</td>
                            <td>45</td>
                            <td>
                                <button class="action-btn" onclick="showDetails('大阪商事株式会社')">
                                    詳細
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>スーパーマーケット田中</td>
                            <td>¥890,000</td>
                            <td>1日 12時間</td>
                            <td>32</td>
                            <td>
                                <button class="action-btn" onclick="showDetails('スーパーマーケット田中')">
                                    詳細
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>食品卸売り鈴木</td>
                            <td>¥1,560,000</td>
                            <td>3日 8時間</td>
                            <td>67</td>
                            <td>
                                <button class="action-btn" onclick="showDetails('食品卸売り鈴木')">
                                    詳細
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>飲食店チェーン佐藤</td>
                            <td>¥780,000</td>
                            <td>1日 6時間</td>
                            <td>28</td>
                            <td>
                                <button class="action-btn" onclick="showDetails('飲食店チェーン佐藤')">
                                    詳細
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- カードビュー -->
            <div class="card-view">
                <div class="customer-card">
                    <div class="card-header">
                        <div>
                            <div class="customer-name">大阪商事株式会社</div>
                            <div class="customer-id">ID: 001</div>
                        </div>
                    </div>
                    <div class="card-stats">
                        <div class="card-stat">
                            <div class="card-stat-label">売上</div>
                            <div class="card-stat-value">¥1.25M</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">配達回数</div>
                            <div class="card-stat-value">45</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">リードタイム</div>
                            <div class="card-stat-value">2日4時間</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">最終注文</div>
                            <div class="card-stat-value">3日前</div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn secondary" onclick="showDetails('大阪商事株式会社')">詳細</button>
                        <button class="card-btn primary" onclick="showGraph('大阪商事株式会社')">グラフ</button>
                    </div>
                </div>

                <div class="customer-card">
                    <div class="card-header">
                        <div>
                            <div class="customer-name">スーパーマーケット田中</div>
                            <div class="customer-id">ID: 002</div>
                        </div>
                    </div>
                    <div class="card-stats">
                        <div class="card-stat">
                            <div class="card-stat-label">売上</div>
                            <div class="card-stat-value">¥890K</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">配達回数</div>
                            <div class="card-stat-value">32</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">リードタイム</div>
                            <div class="card-stat-value">1日12時間</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">最終注文</div>
                            <div class="card-stat-value">1日前</div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn secondary" onclick="showDetails('スーパーマーケット田中')">詳細</button>
                        <button class="card-btn primary" onclick="showGraph('スーパーマーケット田中')">グラフ</button>
                    </div>
                </div>

                <div class="customer-card">
                    <div class="card-header">
                        <div>
                            <div class="customer-name">食品卸売り鈴木</div>
                            <div class="customer-id">ID: 003</div>
                        </div>
                    </div>
                    <div class="card-stats">
                        <div class="card-stat">
                            <div class="card-stat-label">売上</div>
                            <div class="card-stat-value">¥1.56M</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">配達回数</div>
                            <div class="card-stat-value">67</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">リードタイム</div>
                            <div class="card-stat-value">3日8時間</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">最終注文</div>
                            <div class="card-stat-value">5日前</div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn secondary" onclick="showDetails('食品卸売り鈴木')">詳細</button>
                        <button class="card-btn primary" onclick="showGraph('食品卸売り鈴木')">グラフ</button>
                    </div>
                </div>

                <div class="customer-card">
                    <div class="card-header">
                        <div>
                            <div class="customer-name">飲食店チェーン佐藤</div>
                            <div class="customer-id">ID: 004</div>
                        </div>
                    </div>
                    <div class="card-stats">
                        <div class="card-stat">
                            <div class="card-stat-label">売上</div>
                            <div class="card-stat-value">¥780K</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">配達回数</div>
                            <div class="card-stat-value">28</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">リードタイム</div>
                            <div class="card-stat-value">1日6時間</div>
                        </div>
                        <div class="card-stat">
                            <div class="card-stat-label">最終注文</div>
                            <div class="card-stat-value">2日前</div>
                        </div>
                    </div>
                    <div class="card-actions">
                        <button class="card-btn secondary" onclick="showDetails('飲食店チェーン佐藤')">詳細</button>
                        <button class="card-btn primary" onclick="showGraph('飲食店チェーン佐藤')">グラフ</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- グラフ分析タブ -->
        <div id="charts" class="tab-content">
            <div class="chart-selector">
                <h2 class="search-title">
                    <span>📊</span>
                    分析グラフを選択
                </h2>
                <div class="chart-options">
                    <div class="chart-option active" data-chart="sales">
                        <span class="chart-option-icon">💰</span>
                        <div class="chart-option-title">売上分析</div>
                    </div>
                    <div class="chart-option" data-chart="delivery">
                        <span class="chart-option-icon">🚚</span>
                        <div class="chart-option-title">配達実績</div>
                    </div>
                    <div class="chart-option" data-chart="leadtime">
                        <span class="chart-option-icon">⏱️</span>
                        <div class="chart-option-title">リードタイム</div>
                    </div>
                    <div class="chart-option" data-chart="trend">
                        <span class="chart-option-icon">📈</span>
                        <div class="chart-option-title">トレンド分析</div>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <div style="text-align: center;">
                    <span style="font-size: 48px; display: block; margin-bottom: 16px;">📊</span>
                    <h3 style="color: var(--main-green); margin-bottom: 8px;">グラフを選択してください</h3>
                    <p>上記のオプションから表示したいグラフを選択してください</p>
                </div>
            </div>
        </div>

        <!-- データ出力タブ -->
        <div id="export" class="tab-content">
            <div class="export-options">
                <div class="export-card">
                    <div class="export-icon">📋</div>
                    <div class="export-title">CSV出力</div>
                    <div class="export-description">
                        顧客データをCSV形式でダウンロード。<br>
                        Excel等で編集・分析が可能です。
                    </div>
                    <button class="export-btn" onclick="exportCSV()">
                        CSVダウンロード
                    </button>
                </div>

                <div class="export-card">
                    <div class="export-icon">📊</div>
                    <div class="export-title">PDF レポート</div>
                    <div class="export-description">
                        統計情報とグラフを含むレポート。<br>
                        会議資料や報告書として利用可能。
                    </div>
                    <button class="export-btn" onclick="exportPDF()">
                        PDFダウンロード
                    </button>
                </div>

                <div class="export-card">
                    <div class="export-icon">📈</div>
                    <div class="export-title">Excel ファイル</div>
                    <div class="export-description">
                        データと分析用のグラフを含むExcelファイル。<br>
                        詳細な分析に最適です。
                    </div>
                    <button class="export-btn" onclick="exportExcel()">
                        Excelダウンロード
                    </button>
                </div>

                <div class="export-card">
                    <div class="export-icon">📤</div>
                    <div class="export-title">メール送信</div>
                    <div class="export-description">
                        統計レポートを指定したメールアドレスに送信。<br>
                        定期報告に便利です。
                    </div>
                    <button class="export-btn" onclick="sendEmail()">
                        メール送信
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 詳細モーダル -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="detailTitle">顧客詳細情報</h2>
                <button class="close" onclick="closeModal('detailModal')">&times;</button>
            </div>
            <div id="detailContent">
                <!-- 詳細情報がここに表示されます -->
            </div>
        </div>
    </div>

    <!-- グラフモーダル -->
    <div id="graphModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="graphTitle">売上推移グラフ</h2>
                <button class="close" onclick="closeModal('graphModal')">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="modalCanvas"></canvas>
            </div>
        </div>
    </div>

    <script src="../script.js"></script>
</body>

</html>