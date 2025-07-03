<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>統計情報 - 受注管理システム</title>
    <style>
    :root {
        --main-green: #2f5d3f;
        --sub-green: #4b7a5c;
        --accent-green: #7ed957;
        --light-green: #e8f5e8;
        --bg-light: #f8faf9;
        --font-color: #2f5d3f;
        --font-family: 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
        --radius: 12px;
        --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow: 0 2px 12px rgba(47, 93, 63, 0.08);
        --shadow-hover: 0 4px 20px rgba(47, 93, 63, 0.12);
        --header-height: 68px;
        --tab-height: 56px;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: var(--font-family);
        color: var(--font-color);
        background: linear-gradient(135deg, #f8faf9 0%, #e8f5e8 100%);
        min-height: 100vh;
        padding-top: calc(var(--header-height) + var(--tab-height));
    }

    /* ヘッダー */
    .site-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
        box-shadow: 0 2px 12px rgba(47, 93, 63, 0.15);
        z-index: 1000;
        height: var(--header-height);
    }

    .header-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 24px;
        height: 100%;
    }

    .store-title {
        font-weight: 700;
        font-size: 20px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* タブナビゲーション */
    .tab-navigation {
        position: fixed;
        top: var(--header-height);
        left: 0;
        width: 100%;
        background: white;
        border-bottom: 1px solid rgba(47, 93, 63, 0.1);
        z-index: 999;
        height: var(--tab-height);
    }

    .tab-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        height: 100%;
    }

    .tab-btn {
        flex: 1;
        border: none;
        background: transparent;
        padding: 16px 20px;
        font-family: var(--font-family);
        font-size: 14px;
        font-weight: 600;
        color: var(--sub-green);
        cursor: pointer;
        transition: all var(--transition);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-bottom: 3px solid transparent;
    }

    .tab-btn.active {
        color: var(--main-green);
        border-bottom-color: var(--accent-green);
        background: linear-gradient(to bottom, rgba(126, 217, 87, 0.05), transparent);
    }

    .tab-btn:hover:not(.active) {
        color: var(--main-green);
        background: rgba(126, 217, 87, 0.03);
    }

    .tab-icon {
        font-size: 16px;
    }

    /* メインコンテンツ */
    .main-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 32px 24px;
        min-height: calc(100vh - var(--header-height) - var(--tab-height));
    }

    /* タブコンテンツ */
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* =============== ダッシュボードタブ =============== */
    .dashboard-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }

    .metric-card {
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
        transition: all var(--transition);
        position: relative;
        overflow: hidden;
    }

    .metric-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--accent-green), var(--main-green));
    }

    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .metric-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        background: linear-gradient(135deg, var(--accent-green), var(--main-green));
    }

    .metric-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--sub-green);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .metric-value {
        font-size: 28px;
        font-weight: 800;
        color: var(--main-green);
        margin-bottom: 8px;
    }

    .metric-trend {
        font-size: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .trend-up {
        color: #22c55e;
    }

    .trend-down {
        color: #ef4444;
    }

    /* =============== 顧客一覧タブ =============== */
    .customer-search {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
    }

    .search-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .search-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--main-green);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .view-toggle {
        display: flex;
        background: var(--light-green);
        border-radius: 8px;
        padding: 2px;
    }

    .view-btn {
        padding: 6px 12px;
        border: none;
        background: transparent;
        color: var(--sub-green);
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all var(--transition);
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .view-btn.active {
        background: white;
        color: var(--main-green);
        box-shadow: 0 1px 4px rgba(47, 93, 63, 0.1);
    }

    .search-box {
        position: relative;
        max-width: 400px;
    }

    .search-input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid rgba(126, 217, 87, 0.2);
        border-radius: 8px;
        font-size: 14px;
        font-family: var(--font-family);
        background: white;
        transition: all var(--transition);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--accent-green);
        box-shadow: 0 0 0 3px rgba(126, 217, 87, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--sub-green);
        font-size: 16px;
    }

    /* テーブルビュー */
    .table-view {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th {
        background: linear-gradient(135deg, var(--main-green), var(--sub-green));
        color: white;
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        position: sticky;
        top: 0;
    }

    .data-table td {
        padding: 16px;
        border-bottom: 1px solid rgba(47, 93, 63, 0.08);
        font-size: 14px;
    }

    .data-table tr:hover td {
        background: rgba(126, 217, 87, 0.03);
    }

    .sort-btn {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        margin-left: 8px;
        opacity: 0.7;
        transition: opacity var(--transition);
    }

    .sort-btn:hover {
        opacity: 1;
    }

    .action-btn {
        padding: 6px 12px;
        background: var(--accent-green);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition);
    }

    .action-btn:hover {
        background: var(--main-green);
        transform: translateY(-1px);
    }

    /* カードビュー */
    .card-view {
        display: none;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 16px;
    }

    .customer-card {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
        transition: all var(--transition);
    }

    .customer-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 16px;
    }

    .customer-name {
        font-size: 16px;
        font-weight: 700;
        color: var(--main-green);
        margin-bottom: 4px;
    }

    .customer-id {
        font-size: 11px;
        color: var(--sub-green);
        background: var(--light-green);
        padding: 2px 6px;
        border-radius: 4px;
    }

    .card-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }

    .card-stat {
        text-align: center;
        padding: 8px;
        background: rgba(126, 217, 87, 0.05);
        border-radius: 6px;
    }

    .card-stat-label {
        font-size: 11px;
        color: var(--sub-green);
        font-weight: 600;
        margin-bottom: 2px;
    }

    .card-stat-value {
        font-size: 14px;
        font-weight: 700;
        color: var(--main-green);
    }

    .card-actions {
        display: flex;
        gap: 8px;
    }

    .card-btn {
        flex: 1;
        padding: 8px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: all var(--transition);
    }

    .card-btn.primary {
        background: var(--accent-green);
        color: white;
    }

    .card-btn.secondary {
        background: var(--light-green);
        color: var(--main-green);
    }

    .card-btn:hover {
        transform: translateY(-1px);
    }

    /* =============== グラフタブ =============== */
    .chart-selector {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
    }

    .chart-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .chart-option {
        padding: 16px;
        border: 2px solid rgba(126, 217, 87, 0.2);
        border-radius: 8px;
        cursor: pointer;
        transition: all var(--transition);
        text-align: center;
        background: white;
    }

    .chart-option:hover,
    .chart-option.active {
        border-color: var(--accent-green);
        background: rgba(126, 217, 87, 0.05);
    }

    .chart-option-icon {
        font-size: 24px;
        margin-bottom: 8px;
        display: block;
    }

    .chart-option-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--main-green);
    }

    .chart-container {
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
        min-height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--sub-green);
    }

    /* =============== エクスポートタブ =============== */
    .export-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .export-card {
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(126, 217, 87, 0.1);
        text-align: center;
    }

    .export-icon {
        font-size: 48px;
        margin-bottom: 16px;
        color: var(--accent-green);
    }

    .export-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--main-green);
        margin-bottom: 8px;
    }

    .export-description {
        font-size: 14px;
        color: var(--sub-green);
        margin-bottom: 20px;
        line-height: 1.5;
    }

    .export-btn {
        width: 100%;
        padding: 12px 24px;
        background: var(--accent-green);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all var(--transition);
    }

    .export-btn:hover {
        background: var(--main-green);
        transform: translateY(-2px);
    }

    /* モーダル */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        z-index: 2000;
        animation: fadeIn 0.3s ease;
    }

    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: var(--radius);
        padding: 24px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(126, 217, 87, 0.2);
    }

    .modal-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--main-green);
    }

    .close-btn {
        background: none;
        border: none;
        font-size: 20px;
        color: var(--sub-green);
        cursor: pointer;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--transition);
    }

    .close-btn:hover {
        background: var(--light-green);
        color: var(--main-green);
    }

    /* レスポンシブ対応 */
    @media (max-width: 768px) {
        .header-inner {
            padding: 0 16px;
        }

        .store-title {
            font-size: 18px;
        }

        .tab-btn {
            padding: 12px 8px;
            font-size: 12px;
        }

        .tab-icon {
            font-size: 14px;
        }

        .main-content {
            padding: 20px 16px;
        }

        .dashboard-overview {
            grid-template-columns: 1fr;
            gap: 16px;
        }

        .search-header {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box {
            max-width: none;
        }

        .table-view {
            overflow-x: auto;
        }

        .data-table {
            min-width: 600px;
        }

        .card-view {
            grid-template-columns: 1fr;
        }

        .chart-options {
            grid-template-columns: 1fr;
        }

        .export-options {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        body {
            padding-top: calc(var(--header-height) + 100px);
        }

        .tab-navigation {
            height: auto;
        }

        .tab-container {
            flex-wrap: wrap;
        }

        .tab-btn {
            flex: 1 1 50%;
            min-width: 50%;
        }

        .metric-card {
            padding: 16px;
        }

        .metric-value {
            font-size: 24px;
        }
    }
    </style>
</head>

<body>
    <!-- ヘッダー -->
    <div class="site-header">
        <div class="header-inner">
            <div class="store-title">
                <span>📊</span>
                <span>緑橋本店 - 統計情報</span>
            </div>
        </div>
    </div>

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
                <button class="close-btn" onclick="closeModal('detailModal')">&times;</button>
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
                <button class="close-btn" onclick="closeModal('graphModal')">&times;</button>
            </div>
            <div class="chart-container">
                <canvas id="modalCanvas"></canvas>
            </div>
        </div>
    </div>

    <script>
    // タブ切り替え機能
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // アクティブタブの切り替え
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
        });
    });

    // ビュー切り替え機能（顧客一覧タブ）
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const view = this.dataset.view;
            const tableView = document.querySelector('.table-view');
            const cardView = document.querySelector('.card-view');

            if (view === 'table') {
                tableView.style.display = 'block';
                cardView.style.display = 'none';
            } else {
                tableView.style.display = 'none';
                cardView.style.display = 'grid';
            }
        });
    });

    // グラフオプション選択
    document.querySelectorAll('.chart-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.chart-option').forEach(o => o.classList.remove('active'));
            this.classList.add('active');

            const chartType = this.dataset.chart;
            showChart(chartType);
        });
    });

    // 検索機能
    document.querySelector('.search-input').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.data-table tbody tr');
        const cards = document.querySelectorAll('.customer-card');

        // テーブル行のフィルタリング
        rows.forEach(row => {
            const customerName = row.cells[0].textContent.toLowerCase();
            row.style.display = customerName.includes(searchTerm) ? '' : 'none';
        });

        // カードのフィルタリング
        cards.forEach(card => {
            const customerName = card.querySelector('.customer-name').textContent.toLowerCase();
            card.style.display = customerName.includes(searchTerm) ? '' : 'none';
        });
    });

    // ソート機能
    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const column = this.dataset.column;
            console.log('Sort by:', column);
            // ソート処理をここに実装
        });
    });

    // レスポンシブ対応：画面サイズに応じてビューを自動切り替え
    function handleResize() {
        const tableView = document.querySelector('.table-view');
        const cardView = document.querySelector('.card-view');

        if (window.innerWidth <= 768) {
            tableView.style.display = 'none';
            cardView.style.display = 'grid';
            document.querySelector('.view-btn[data-view="card"]').classList.add('active');
            document.querySelector('.view-btn[data-view="table"]').classList.remove('active');
        } else {
            const activeView = document.querySelector('.view-btn.active').dataset.view;
            if (activeView === 'table') {
                tableView.style.display = 'block';
                cardView.style.display = 'none';
            } else {
                tableView.style.display = 'none';
                cardView.style.display = 'grid';
            }
        }
    }

    window.addEventListener('resize', handleResize);
    document.addEventListener('DOMContentLoaded', handleResize);

    // 顧客詳細表示
    function showDetails(customerName) {
        const modal = document.getElementById('detailModal');
        const title = document.getElementById('detailTitle');
        const content = document.getElementById('detailContent');

        title.textContent = `${customerName} - 詳細情報`;
        content.innerHTML = `
                <div style="padding: 20px;">
                    <h3 style="color: var(--main-green); margin-bottom: 16px;">${customerName}</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div style="background: var(--light-green); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--sub-green); margin-bottom: 4px;">総売上</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--main-green);">¥1,250,000</div>
                        </div>
                        <div style="background: var(--light-green); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--sub-green); margin-bottom: 4px;">注文回数</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--main-green);">45回</div>
                        </div>
                        <div style="background: var(--light-green); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--sub-green); margin-bottom: 4px;">平均注文金額</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--main-green);">¥27,777</div>
                        </div>
                        <div style="background: var(--light-green); padding: 12px; border-radius: 8px;">
                            <div style="font-size: 12px; color: var(--sub-green); margin-bottom: 4px;">最終注文日</div>
                            <div style="font-size: 18px; font-weight: 700; color: var(--main-green);">2024/12/01</div>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <h4 style="color: var(--main-green); margin-bottom: 8px;">連絡先情報</h4>
                        <p style="margin-bottom: 4px;"><strong>住所:</strong> 大阪市東成区緑橋1-1-1</p>
                        <p style="margin-bottom: 4px;"><strong>電話:</strong> 06-1234-5678</p>
                        <p style="margin-bottom: 4px;"><strong>担当者:</strong> 田中太郎</p>
                    </div>
                </div>
            `;
        modal.style.display = 'block';
    }

    // グラフ表示
    function showGraph(customerName) {
        const modal = document.getElementById('graphModal');
        const title = document.getElementById('graphTitle');

        title.textContent = `${customerName} - 売上推移グラフ`;
        modal.style.display = 'block';

        console.log('Showing graph for:', customerName);
    }

    // チャート表示
    function showChart(chartType) {
        const container = document.querySelector('#charts .chart-container');

        let content = '';
        switch (chartType) {
            case 'sales':
                content = `
                        <div style="text-align: center;">
                            <span style="font-size: 48px; display: block; margin-bottom: 16px;">💰</span>
                            <h3 style="color: var(--main-green); margin-bottom: 8px;">売上分析グラフ</h3>
                            <p>月別売上推移と顧客別売上ランキングを表示します</p>
                        </div>
                    `;
                break;
            case 'delivery':
                content = `
                        <div style="text-align: center;">
                            <span style="font-size: 48px; display: block; margin-bottom: 16px;">🚚</span>
                            <h3 style="color: var(--main-green); margin-bottom: 8px;">配達実績グラフ</h3>
                            <p>配達回数の推移と地域別配達実績を表示します</p>
                        </div>
                    `;
                break;
            case 'leadtime':
                content = `
                        <div style="text-align: center;">
                            <span style="font-size: 48px; display: block; margin-bottom: 16px;">⏱️</span>
                            <h3 style="color: var(--main-green); margin-bottom: 8px;">リードタイム分析</h3>
                            <p>平均リードタイムの推移と顧客別比較を表示します</p>
                        </div>
                    `;
                break;
            case 'trend':
                content = `
                        <div style="text-align: center;">
                            <span style="font-size: 48px; display: block; margin-bottom: 16px;">📈</span>
                            <h3 style="color: var(--main-green); margin-bottom: 8px;">トレンド分析</h3>
                            <p>各種指標のトレンドと予測データを表示します</p>
                        </div>
                    `;
                break;
        }

        container.innerHTML = content;
    }

    // モーダルを閉じる
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // モーダル外クリックで閉じる
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.style.display = 'none';
            });
        }
    });

    // エクスポート機能
    function exportCSV() {
        alert('CSVファイルをダウンロードします');
        // 実際の実装では、サーバーサイドでCSV生成処理を呼び出し
    }

    function exportPDF() {
        alert('PDFレポートをダウンロードします');
        // 実際の実装では、サーバーサイドでPDF生成処理を呼び出し
    }

    function exportExcel() {
        alert('Excelファイルをダウンロードします');
        // 実際の実装では、サーバーサイドでExcel生成処理を呼び出し
    }

    function sendEmail() {
        const email = prompt('送信先メールアドレスを入力してください:');
        if (email) {
            alert(`${email} にレポートを送信します`);
            // 実際の実装では、サーバーサイドでメール送信処理を呼び出し
        }
    }

    // 統計カードの数値アニメーション
    function animateValue(element, start, end, duration) {
        const range = end - start;
        const stepTime = Math.abs(Math.floor(duration / range));
        const timer = setInterval(() => {
            start += Math.ceil(range / 50);
            if (start >= end) {
                start = end;
                clearInterval(timer);
            }
            element.textContent = start.toLocaleString();
        }, stepTime);
    }

    // 初期化
    document.addEventListener('DOMContentLoaded', function() {
        // 統計数値のアニメーション
        setTimeout(() => {
            const metricValues = document.querySelectorAll('.metric-value');
            metricValues.forEach((element, index) => {
                const finalValue = element.textContent;

                if (finalValue.includes('¥')) {
                    element.textContent = '¥0';
                    setTimeout(() => {
                        element.textContent = finalValue;
                    }, index * 200);
                } else if (finalValue.includes('日')) {
                    element.textContent = '0日';
                    setTimeout(() => {
                        element.textContent = finalValue;
                    }, index * 200);
                } else {
                    element.textContent = '0';
                    setTimeout(() => {
                        const numValue = parseInt(finalValue.replace(/,/g, ''));
                        animateValue(element, 0, numValue, 1000);
                    }, index * 200);
                }
            });
        }, 300);
    });
    </script>
</body>

</html>