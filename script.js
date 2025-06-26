// ========== ハンバーガーメニューの制御 ==========
const menuToggle = document.getElementById('menuToggle');
const nav = document.getElementById('nav');
const menuOverlay = document.getElementById('menuOverlay');

function toggleMenu() {
    if (menuToggle && nav && menuOverlay) {
        const isActive = nav.classList.contains('active');
        
        if (isActive) {
            // メニューを閉じる
            menuToggle.classList.remove('active');
            nav.classList.remove('active');
            menuOverlay.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
            menuToggle.setAttribute('aria-label', 'メニューを開く');
            document.body.style.overflow = '';
        } else {
            // メニューを開く
            menuToggle.classList.add('active');
            nav.classList.add('active');
            menuOverlay.classList.add('active');
            menuToggle.setAttribute('aria-expanded', 'true');
            menuToggle.setAttribute('aria-label', 'メニューを閉じる');
            document.body.style.overflow = 'hidden';
        }
    }
}

function closeMenu() {
    if (menuToggle && nav && menuOverlay) {
        menuToggle.classList.remove('active');
        nav.classList.remove('active');
        menuOverlay.classList.remove('active');
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.setAttribute('aria-label', 'メニューを開く');
        document.body.style.overflow = '';
    }
}

// DOMContentLoaded後にイベントリスナーを設定
document.addEventListener('DOMContentLoaded', function() {
    // ハンバーガーメニューのイベントリスナー
    const menuToggleBtn = document.getElementById('menuToggle');
    const navMenu = document.getElementById('nav');
    const overlay = document.getElementById('menuOverlay');
    
    if (menuToggleBtn) {
        menuToggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMenu();
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            e.preventDefault();
            closeMenu();
        });
    }

    // ナビリンクをクリックしたらメニューを閉じる（モバイル）
    document.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    closeMenu();
                }, 100);
            }
        });
    });

    // キーボードナビゲーション
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && navMenu && navMenu.classList.contains('active')) {
            closeMenu();
        }
    });

    // リサイズ時にメニューを閉じる
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    });
});

// ========== スクロール効果 ==========
let lastScrollY = window.scrollY;
const header = document.querySelector('.site-header');

window.addEventListener('scroll', () => {
    const currentScrollY = window.scrollY;

    if (header) {
        if (currentScrollY > 100) {
            header.style.boxShadow = '0 12px 40px rgba(47, 93, 63, 0.25)';
        } else {
            header.style.boxShadow = '0 8px 32px rgba(47, 93, 63, 0.15)';
        }
    }

    lastScrollY = currentScrollY;
});

// ========== 店舗選択機能 ==========
// セッション内での店舗情報を保持する変数
let selectedStoreData = '';

function selectedStore(storeName) {
    // ローディング表示
    showLoadingAnimation();
    
    // セッション変数に保存
    selectedStoreData = storeName;
    
    // 少し遅延を入れてからページ遷移（アニメーション効果のため）
    setTimeout(() => {
        window.location.href = `/MBS_B/menu.php?store=${encodeURIComponent(storeName)}`;
    }, 500);
}

// ========== ローディングアニメーション ==========
function showLoadingAnimation() {
    // 既存のローディング要素があれば削除
    const existingLoading = document.querySelector('.loading-overlay');
    if (existingLoading) {
        existingLoading.remove();
    }

    // ローディングオーバーレイを作成
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>店舗を選択中...</p>
        </div>
    `;

    // スタイルを動的に追加
    const style = document.createElement('style');
    style.textContent = `
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(47, 93, 63, 0.9);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.3s ease-out;
        }
        
        .loading-spinner {
            text-align: center;
            color: white;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(126, 217, 87, 0.3);
            border-top: 4px solid #7ed957;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(loadingOverlay);
}

// ========== メニューボタンの動的効果 ==========
function enhanceMenuButtons() {
    const menuButtons = document.querySelectorAll('.menu-button');
    
    menuButtons.forEach((button, index) => {
        // ホバー時のサウンド効果（実際のサウンドは省略）
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = 'translateY(0) scale(1)';
        });

        // クリック時のリップル効果
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('div');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(126, 217, 87, 0.4);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
                z-index: 1;
            `;
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });

        // パルス効果をランダムなタイミングで追加
        setTimeout(() => {
            button.classList.add('pulse-effect');
            setTimeout(() => {
                button.classList.remove('pulse-effect');
            }, 2000);
        }, Math.random() * 3000 + 1000);
    });
}

// リップルアニメーションのCSSを動的に追加
function addRippleStyles() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// ========== フォーカス管理 ==========
function setupFocusManagement() {
    const focusableElements = document.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    focusableElements.forEach(element => {
        element.addEventListener('focus', () => {
            element.style.outline = '2px solid var(--accent-green)';
            element.style.outlineOffset = '2px';
        });
        
        element.addEventListener('blur', () => {
            element.style.outline = '';
            element.style.outlineOffset = '';
        });
    });
}

// ========== ページ読み込み時の処理 ==========
document.addEventListener('DOMContentLoaded', () => {
    // URLパラメータから店舗情報を取得
    const params = new URLSearchParams(window.location.search);
    const store = params.get('store');

    // URLにstoreパラメータがあればセッション変数に保存
    if (store) {
        selectedStoreData = store;
    }

    // セッション変数またはCookieから取得してタイトルを変更
    let storedStore = selectedStoreData;
    
    // Cookieからも取得を試行
    if (!storedStore) {
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'selectedStore') {
                storedStore = decodeURIComponent(value);
                selectedStoreData = storedStore;
                break;
            }
        }
    }
    
    if (storedStore) {
        const titleElement = document.querySelector('.store-title');
        if (titleElement) {
            titleElement.innerHTML = `${storedStore}<br>受注管理システム`;
        }
        
        // ページタイトルも更新
        document.title = `${storedStore} - 受注管理システム`;
    }

    // メニューボタンの設定
    const menuButtons = document.querySelectorAll('.menu-button');
    if (menuButtons.length && storedStore) {
        menuButtons.forEach(button => {
            const path = button.dataset.path;
            if (path) {
                button.addEventListener('click', () => {
                    showLoadingAnimation();
                    setTimeout(() => {
                        window.location.href = `${path}?store=${encodeURIComponent(storedStore)}`;
                    }, 500);
                });
            }
        });
    }

    // 各種機能を初期化
    enhanceMenuButtons();
    addRippleStyles();
    setupFocusManagement();
    
    // ページ読み込み完了のアニメーション
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease-in-out';
        document.body.style.opacity = '1';
    }, 100);
});

// ========== エラーハンドリング ==========
window.addEventListener('error', (e) => {
    console.error('JavaScript Error:', e.error);
    
    // ユーザーに優しいエラーメッセージを表示
    const errorToast = document.createElement('div');
    errorToast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #ff6b6b;
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000;
        animation: slideInUp 0.3s ease-out;
    `;
    errorToast.textContent = 'エラーが発生しました。ページを再読み込みしてください。';
    document.body.appendChild(errorToast);
    
    setTimeout(() => {
        errorToast.remove();
    }, 5000);
});

// ========== パフォーマンス監視 ==========
window.addEventListener('load', () => {
    // ページ読み込み時間を測定
    const loadTime = performance.now();
    if (loadTime > 3000) {
        console.warn('ページの読み込みが遅い可能性があります:', loadTime + 'ms');
    }
});

// ========== ユーティリティ関数 ==========
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// スクロールイベントをデバウンス
const debouncedScrollHandler = debounce(() => {
    const currentScrollY = window.scrollY;
    if (header) {
        if (currentScrollY > 100) {
            header.style.boxShadow = '0 12px 40px rgba(47, 93, 63, 0.25)';
        } else {
            header.style.boxShadow = '0 8px 32px rgba(47, 93, 63, 0.15)';
        }
    }
}, 10);

window.addEventListener('scroll', debouncedScrollHandler);

// ========== アニメーション用のIntersection Observer ==========
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// 要素が後から追加される場合のために、MutationObserverでも監視
const mutationObserver = new MutationObserver((mutations) => {
    mutations.forEach(mutation => {
        mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1 && node.classList.contains('menu-button')) {
                observer.observe(node);
            }
        });
    });
});

// 監視開始
mutationObserver.observe(document.body, {
    childList: true,
    subtree: true
});