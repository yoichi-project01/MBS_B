<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>改善されたヘッダーデザイン</title>
    <style>
    /* CSS変数定義 */
    :root {
        --main-green: #2f5d3f;
        --sub-green: #4b7a5c;
        --accent-green: #7ed957;
        --bg-light: #f8faf9;
        --font-color: #2f5d3f;
        --font-family: 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
        --radius: 12px;
        --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --shadow: 0 4px 24px rgba(47, 93, 63, 0.10);
        --header-height: 68px;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 0;
        background: linear-gradient(120deg, #e3efe6 0%, #f8faf9 100%);
        font-family: var(--font-family);
        color: var(--font-color);
        min-height: 100vh;
        padding-top: var(--header-height);
    }

    /* ヘッダーメインスタイル */
    .site-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: linear-gradient(135deg, var(--main-green) 0%, var(--sub-green) 100%);
        backdrop-filter: blur(10px);
        border-bottom: 2px solid var(--accent-green);
        box-shadow: 0 8px 32px rgba(47, 93, 63, 0.15);
        z-index: 1000;
        transition: all var(--transition);
    }

    .site-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg,
                rgba(126, 217, 87, 0.1) 0%,
                rgba(47, 93, 63, 0.05) 100%);
        pointer-events: none;
    }

    .header-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 32px;
        height: var(--header-height);
        position: relative;
        z-index: 10;
    }

    /* ストアタイトル */
    .store-title {
        font-weight: 800;
        font-size: 24px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 12px;
        letter-spacing: 1px;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        transition: all var(--transition);
        white-space: nowrap;
    }

    .store-title::before {
        content: '📋';
        font-size: 28px;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    }

    .store-title:hover {
        transform: translateX(2px);
        text-shadow: 0 4px 12px rgba(126, 217, 87, 0.3);
    }

    /* ナビゲーション */
    .nav {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .nav-item {
        position: relative;
        font-weight: 600;
        font-size: 16px;
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px;
        transition: all var(--transition);
        text-decoration: none;
        letter-spacing: 0.5px;
        overflow: hidden;
    }

    .nav-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
                transparent,
                var(--accent-green),
                transparent);
        transition: left var(--transition);
    }

    .nav-item:hover::before {
        left: 100%;
    }

    .nav-item:hover {
        background: rgba(126, 217, 87, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(126, 217, 87, 0.3);
    }

    .nav-item:focus-visible {
        outline: 2px solid var(--accent-green);
        outline-offset: 2px;
    }

    /* ハンバーガーメニュー */
    .menu-toggle {
        display: none;
        flex-direction: column;
        cursor: pointer;
        padding: 8px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        transition: all var(--transition);
        border: none;
        position: relative;
        z-index: 1001;
    }

    .menu-toggle:hover {
        background: rgba(126, 217, 87, 0.2);
        transform: scale(1.05);
    }

    .hamburger-line {
        width: 25px;
        height: 3px;
        background: #fff;
        margin: 3px 0;
        border-radius: 2px;
        transition: all var(--transition);
        transform-origin: center;
    }

    .menu-toggle.active .hamburger-line:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }

    .menu-toggle.active .hamburger-line:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }

    .menu-toggle.active .hamburger-line:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }

    /* モバイルメニューオーバーレイ */
    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transition: all var(--transition);
        z-index: 999;
        backdrop-filter: blur(4px);
    }

    .menu-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* レスポンシブデザイン */
    @media (max-width: 768px) {
        .header-inner {
            padding: 0 16px;
        }

        .store-title {
            font-size: 20px;
        }

        .menu-toggle {
            display: flex;
        }

        .nav {
            position: fixed;
            top: var(--header-height);
            right: -300px;
            width: 280px;
            height: calc(100vh - var(--header-height));
            background: linear-gradient(180deg, var(--main-green) 0%, var(--sub-green) 100%);
            flex-direction: column;
            padding: 32px 0;
            gap: 0;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.3);
            transition: right var(--transition);
            overflow-y: auto;
        }

        .nav.active {
            right: 0;
        }

        .nav-item {
            width: 100%;
            padding: 20px 32px;
            border-radius: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 18px;
        }

        .nav-item:hover {
            background: rgba(126, 217, 87, 0.15);
            transform: translateX(8px);
            box-shadow: none;
        }
    }

    @media (max-width: 480px) {
        .header-inner {
            padding: 0 12px;
        }

        .store-title {
            font-size: 18px;
        }

        .store-title::before {
            font-size: 24px;
        }
    }

    /* アニメーション */
    @keyframes slideInFromRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .nav.active {
        animation: slideInFromRight 0.3s ease-out;
    }

    /* ダークモード対応 */
    @media (prefers-color-scheme: dark) {
        .site-header {
            background: linear-gradient(135deg, #1a2e1f 0%, #2d4a35 100%);
            border-bottom-color: #5fa36a;
        }
    }

    /* デモ用コンテンツ */
    .demo-content {
        max-width: 800px;
        margin: 60px auto;
        padding: 40px 20px;
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        text-align: center;
    }

    .demo-content h2 {
        color: var(--main-green);
        font-size: 28px;
        margin-bottom: 20px;
    }

    .demo-content p {
        line-height: 1.6;
        color: #666;
        font-size: 16px;
    }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="#" class="store-title">サンプル店舗 受注管理</a>

            <nav class="nav" id="nav">
                <a href="#" class="nav-item">顧客情報</a>
                <a href="#" class="nav-item">統計情報</a>
                <a href="#" class="nav-item">注文書</a>
                <a href="#" class="nav-item">納品書</a>
            </nav>

            <button class="menu-toggle" id="menuToggle" aria-label="メニューを開く">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        </div>
        <div class="menu-overlay" id="menuOverlay"></div>
    </header>

    <main class="demo-content">
        <h2>改善されたヘッダーデザイン</h2>
        <p>このヘッダーは以下の改善が施されています：</p>
        <ul style="text-align: left; max-width: 600px; margin: 20px auto;">
            <li><strong>モダンなグラデーション効果</strong> - より洗練された背景デザイン</li>
            <li><strong>スムーズなアニメーション</strong> - ホバー効果とトランジション</li>
            <li><strong>改善されたモバイル対応</strong> - スライドインメニューとオーバーレイ</li>
            <li><strong>アクセシビリティ向上</strong> - フォーカス表示とARIAラベル</li>
            <li><strong>アイコンの追加</strong> - 視覚的な改善</li>
            <li><strong>レスポンシブデザイン</strong> - 全デバイス対応</li>
        </ul>
        <p>画面サイズを変更してモバイル表示も確認してみてください！</p>
    </main>

    <script>
    // ハンバーガーメニューの制御
    const menuToggle = document.getElementById('menuToggle');
    const nav = document.getElementById('nav');
    const menuOverlay = document.getElementById('menuOverlay');

    function toggleMenu() {
        menuToggle.classList.toggle('active');
        nav.classList.toggle('active');
        menuOverlay.classList.toggle('active');

        // アクセシビリティ
        const isExpanded = nav.classList.contains('active');
        menuToggle.setAttribute('aria-expanded', isExpanded);
        menuToggle.setAttribute('aria-label', isExpanded ? 'メニューを閉じる' : 'メニューを開く');

        // ボディのスクロールを制御
        document.body.style.overflow = isExpanded ? 'hidden' : '';
    }

    function closeMenu() {
        menuToggle.classList.remove('active');
        nav.classList.remove('active');
        menuOverlay.classList.remove('active');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'メニューを開く');
        document.body.style.overflow = '';
    }

    // イベントリスナー
    menuToggle.addEventListener('click', toggleMenu);
    menuOverlay.addEventListener('click', closeMenu);

    // キーボードナビゲーション
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('active')) {
            closeMenu();
        }
    });

    // ナビリンクをクリックしたらメニューを閉じる（モバイル）
    document.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                closeMenu();
            }
        });
    });

    // リサイズ時にメニューを閉じる
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });

    // スクロール時のヘッダー効果
    let lastScrollY = window.scrollY;
    const header = document.querySelector('.site-header');

    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;

        if (currentScrollY > 100) {
            header.style.boxShadow = '0 12px 40px rgba(47, 93, 63, 0.25)';
        } else {
            header.style.boxShadow = '0 8px 32px rgba(47, 93, 63, 0.15)';
        }

        lastScrollY = currentScrollY;
    });
    </script>
</body>

</html>