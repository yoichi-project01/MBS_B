<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>緑橋書店 受注管理システム</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300;400;500;700;900&display=swap');

    :root {
        --primary-green: #2d5a3d;
        --secondary-green: #4a7c59;
        --accent-green: #7ed957;
        --light-green: #e8f5e8;
        --dark-green: #1a3d2b;
        --bg-gradient: linear-gradient(135deg, #f0f9f0 0%, #e8f5e8 50%, #d4edda 100%);
        --card-bg: rgba(255, 255, 255, 0.95);
        --text-primary: #2d5a3d;
        --text-secondary: #5a7063;
        --shadow-light: 0 4px 20px rgba(45, 90, 61, 0.08);
        --shadow-medium: 0 8px 30px rgba(45, 90, 61, 0.12);
        --shadow-heavy: 0 15px 50px rgba(45, 90, 61, 0.15);
        --border-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: var(--bg-gradient);
        color: var(--text-primary);
        min-height: 100vh;
        line-height: 1.6;
        overflow-x: hidden;
    }

    /* 背景装飾 */
    body::before {
        content: '';
        position: fixed;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background:
            radial-gradient(circle at 25% 25%, rgba(126, 217, 87, 0.05) 0%, transparent 50%),
            radial-gradient(circle at 75% 75%, rgba(45, 90, 61, 0.05) 0%, transparent 50%);
        animation: float 20s ease-in-out infinite;
        z-index: -1;
    }

    @keyframes float {

        0%,
        100% {
            transform: rotate(0deg) scale(1);
        }

        50% {
            transform: rotate(180deg) scale(1.1);
        }
    }

    /* コンテナ */
    .container {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 2rem;
        position: relative;
    }

    /* メインタイトル */
    .main-title {
        text-align: center;
        margin-bottom: 3rem;
        position: relative;
    }

    .store-name {
        font-size: clamp(2.5rem, 5vw, 4rem);
        font-weight: 900;
        color: var(--primary-green);
        margin-bottom: 0.5rem;
        position: relative;
        display: inline-block;
    }

    .store-name::before {
        content: '📚';
        position: absolute;
        left: -60px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 0.8em;
        opacity: 0.8;
    }

    .store-name::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-green), var(--secondary-green));
        border-radius: 2px;
    }

    .system-name {
        font-size: clamp(1.2rem, 2.5vw, 1.8rem);
        font-weight: 500;
        color: var(--text-secondary);
        letter-spacing: 0.05em;
    }

    /* メニューカード */
    .menu-container {
        background: var(--card-bg);
        backdrop-filter: blur(20px);
        border-radius: var(--border-radius);
        padding: 3rem;
        box-shadow: var(--shadow-heavy);
        border: 1px solid rgba(255, 255, 255, 0.3);
        width: 100%;
        max-width: 500px;
        position: relative;
        overflow: hidden;
    }

    .menu-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--accent-green), var(--secondary-green), var(--primary-green));
    }

    .menu-title {
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-green);
        margin-bottom: 2rem;
        position: relative;
    }

    /* メニューボタン */
    .menu-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .menu-button {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
        border: none;
        border-radius: 12px;
        padding: 1.2rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        color: white;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        font-family: inherit;
        box-shadow: var(--shadow-light);
    }

    .menu-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.6s;
    }

    .menu-button:hover::before {
        left: 100%;
    }

    .menu-button:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-medium);
        background: linear-gradient(135deg, var(--secondary-green) 0%, var(--accent-green) 100%);
    }

    .menu-button:active {
        transform: translateY(0);
        box-shadow: var(--shadow-light);
    }

    /* 店舗選択モード */
    .store-selection .menu-button {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        color: var(--primary-green);
        border: 2px solid var(--light-green);
    }

    .store-selection .menu-button:hover {
        background: linear-gradient(135deg, var(--light-green) 0%, #ffffff 100%);
        border-color: var(--accent-green);
        color: var(--dark-green);
    }

    /* 機能メニューモード */
    .function-menu .menu-button {
        position: relative;
        padding-left: 3.5rem;
    }

    .function-menu .menu-button::after {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.2em;
    }

    .menu-button[data-function="customer"]::after {
        content: '👥';
    }

    .menu-button[data-function="statistics"]::after {
        content: '📊';
    }

    .menu-button[data-function="orders"]::after {
        content: '📋';
    }

    .menu-button[data-function="delivery"]::after {
        content: '🚚';
    }

    /* 戻るボタン */
    .back-button {
        position: absolute;
        top: 2rem;
        left: 2rem;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid var(--light-green);
        border-radius: 50px;
        padding: 0.8rem 1.5rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--primary-green);
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .back-button:hover {
        background: var(--light-green);
        transform: translateX(-4px);
    }

    .back-button::before {
        content: '←';
        font-size: 1.1em;
    }

    /* ステータス表示 */
    .status-bar {
        position: absolute;
        top: 2rem;
        right: 2rem;
        background: rgba(126, 217, 87, 0.1);
        border: 1px solid var(--accent-green);
        border-radius: 20px;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--primary-green);
    }

    /* アニメーション */
    .fade-in {
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .slide-in {
        animation: slideIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* レスポンシブデザイン */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }

        .menu-container {
            padding: 2rem 1.5rem;
        }

        .store-name::before {
            left: -40px;
            font-size: 0.7em;
        }

        .back-button {
            top: 1rem;
            left: 1rem;
            padding: 0.6rem 1rem;
        }

        .status-bar {
            top: 1rem;
            right: 1rem;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
    }

    @media (max-width: 480px) {
        .main-title {
            margin-bottom: 2rem;
        }

        .menu-container {
            padding: 1.5rem 1rem;
        }

        .store-name::before {
            display: none;
        }

        .function-menu .menu-button {
            padding-left: 1rem;
            text-align: center;
        }

        .function-menu .menu-button::after {
            display: none;
        }
    }

    /* ローディングアニメーション */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
        margin-left: 10px;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* ホバーエフェクト強化 */
    .menu-button {
        position: relative;
        overflow: hidden;
    }

    .menu-button::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        transition: width 0.4s, height 0.4s;
    }

    .menu-button:active::after {
        width: 300px;
        height: 300px;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- 店舗選択画面 -->
        <div id="store-selection" class="store-selection fade-in">
            <div class="main-title">
                <h1 class="store-name">緑橋書店</h1>
                <p class="system-name">受注管理システム</p>
            </div>

            <div class="menu-container">
                <h2 class="menu-title">店舗を選択してください</h2>
                <div class="menu-grid">
                    <button class="menu-button" onclick="selectStore('緑橋本店')">緑橋本店</button>
                    <button class="menu-button" onclick="selectStore('今里店')">今里店</button>
                    <button class="menu-button" onclick="selectStore('深江橋店')">深江橋店</button>
                </div>
            </div>
        </div>

        <!-- 機能メニュー画面 -->
        <div id="function-menu" class="function-menu" style="display: none;">
            <button class="back-button slide-in" onclick="goBack()">店舗選択に戻る</button>
            <div class="status-bar slide-in" id="selected-store"></div>

            <div class="main-title fade-in">
                <h1 class="store-name" id="current-store-name">緑橋本店</h1>
                <p class="system-name">受注管理システム</p>
            </div>

            <div class="menu-container fade-in">
                <h2 class="menu-title">機能を選択してください</h2>
                <div class="menu-grid">
                    <button class="menu-button" data-function="customer"
                        onclick="navigateToFunction('customer_information/index.php')">
                        顧客情報管理
                    </button>
                    <button class="menu-button" data-function="statistics"
                        onclick="navigateToFunction('statistics/index.php')">
                        統計情報表示
                    </button>
                    <button class="menu-button" data-function="orders"
                        onclick="navigateToFunction('order_list/index.php')">
                        注文書管理
                    </button>
                    <button class="menu-button" data-function="delivery"
                        onclick="navigateToFunction('delivery_list/index.php')">
                        納品書管理
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let selectedStoreName = '';

    // 店舗選択
    function selectStore(storeName) {
        selectedStoreName = storeName;

        // ボタンにローディング表示
        const buttons = document.querySelectorAll('#store-selection .menu-button');
        buttons.forEach(btn => {
            if (btn.textContent === storeName) {
                btn.innerHTML = storeName + '<span class="loading"></span>';
                btn.style.pointerEvents = 'none';
            }
        });

        // 少し遅延を入れてスムーズな遷移を演出
        setTimeout(() => {
            document.getElementById('store-selection').style.display = 'none';
            document.getElementById('function-menu').style.display = 'block';
            document.getElementById('current-store-name').textContent = storeName;
            document.getElementById('selected-store').textContent = `選択中: ${storeName}`;

            // アニメーションクラスを再適用
            const elements = document.querySelectorAll('#function-menu .fade-in, #function-menu .slide-in');
            elements.forEach(el => {
                el.style.animation = 'none';
                el.offsetHeight; // リフロー
                el.style.animation = null;
            });
        }, 800);
    }

    // 戻る
    function goBack() {
        document.getElementById('function-menu').style.display = 'none';
        document.getElementById('store-selection').style.display = 'block';

        // ボタンをリセット
        const buttons = document.querySelectorAll('#store-selection .menu-button');
        buttons.forEach(btn => {
            btn.innerHTML = btn.textContent.replace(/選択中.*/, '').trim();
            btn.style.pointerEvents = 'auto';
        });
    }

    // 機能画面への遷移
    function navigateToFunction(path) {
        const button = event.target;
        button.style.pointerEvents = 'none';
        button.innerHTML += '<span class="loading"></span>';

        // 実際のアプリケーションでは、ここでページ遷移や Ajax 呼び出しを行う
        setTimeout(() => {
            // デモ用のアラート
            alert(
                `${selectedStoreName}の${button.textContent.replace('管理', '').replace('表示', '')}機能に遷移します。\n実際のシステムでは ${path} にアクセスします。`);

            // ボタンをリセット
            button.style.pointerEvents = 'auto';
            button.innerHTML = button.innerHTML.replace('<span class="loading"></span>', '');
        }, 1000);
    }

    // キーボードナビゲーション
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const functionMenu = document.getElementById('function-menu');
            if (functionMenu.style.display !== 'none') {
                goBack();
            }
        }
    });

    // 初期アニメーション
    document.addEventListener('DOMContentLoaded', function() {
        // ページ読み込み完了後に要素を順次表示
        const elements = document.querySelectorAll('.fade-in');
        elements.forEach((el, index) => {
            el.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>
</body>

</html>