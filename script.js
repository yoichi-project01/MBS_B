// ========== 統合されたJavaScriptファイル ==========
// MBS_B システム用統合JavaScript

(function() {
    'use strict';

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

    function sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }

    function showErrorMessage(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'エラー',
                text: message,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'OK'
            });
        } else {
            alert('エラー: ' + message);
        }
    }

    function showSuccessMessage(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: title,
                html: message,
                confirmButtonColor: '#2f5d3f',
                confirmButtonText: 'OK'
            });
        } else {
            alert(title + ': ' + message);
        }
    }

    // ========== ハンバーガーメニューの制御 ==========
    let menuToggle, nav, menuOverlay;

    function initializeMenu() {
        menuToggle = document.getElementById('menuToggle');
        nav = document.getElementById('nav');
        menuOverlay = document.getElementById('menuOverlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleMenu();
            });
        }

        if (menuOverlay) {
            menuOverlay.addEventListener('click', function(e) {
                e.preventDefault();
                closeMenu();
            });
        }

        // ナビリンクをクリックしたらメニューを閉じる（モバイル）
        document.querySelectorAll('.nav-item').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(function() {
                        closeMenu();
                    }, 100);
                }
            });
        });

        // キーボードナビゲーション
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && nav && nav.classList.contains('active')) {
                closeMenu();
            }
        });

        // リサイズ時にメニューを閉じる
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMenu();
            }
        });
    }

    function toggleMenu() {
        if (menuToggle && nav && menuOverlay) {
            const isActive = nav.classList.contains('active');
            
            if (isActive) {
                closeMenu();
            } else {
                openMenu();
            }
        }
    }

    function openMenu() {
        if (menuToggle && nav && menuOverlay) {
            menuToggle.classList.add('active');
            nav.classList.add('active');
            menuOverlay.classList.add('active');
            menuToggle.setAttribute('aria-expanded', 'true');
            menuToggle.setAttribute('aria-label', 'メニューを閉じる');
            document.body.style.overflow = 'hidden';
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

    // ========== スクロール効果 ==========
    function initializeScrollEffects() {
        let lastScrollY = window.scrollY;
        const header = document.querySelector('.site-header');

        const debouncedScrollHandler = debounce(function() {
            const currentScrollY = window.scrollY;

            if (header) {
                if (currentScrollY > 100) {
                    header.style.boxShadow = '0 12px 40px rgba(47, 93, 63, 0.25)';
                } else {
                    header.style.boxShadow = '0 8px 32px rgba(47, 93, 63, 0.15)';
                }
            }

            lastScrollY = currentScrollY;
        }, 10);

        window.addEventListener('scroll', debouncedScrollHandler);
    }

    // ========== 店舗選択機能 ==========
    let selectedStoreData = '';

    function selectedStore(storeName) {
        if (!storeName || typeof storeName !== 'string') {
            showErrorMessage('無効な店舗名です。');
            return;
        }

        // 入力値のサニタイズ
        const sanitizedStoreName = sanitizeInput(storeName.trim());
        
        // 許可された店舗名のチェック
        const allowedStores = ['緑橋本店', '今里店', '深江橋店'];
        if (!allowedStores.includes(sanitizedStoreName)) {
            showErrorMessage('許可されていない店舗名です。');
            return;
        }

        // ローディング表示
        showLoadingAnimation();
        
        // セッション変数に保存
        selectedStoreData = sanitizedStoreName;
        
        // ページ遷移
        setTimeout(function() {
            window.location.href = '/MBS_B/menu.php?store=' + encodeURIComponent(sanitizedStoreName);
        }, 500);
    }

    // グローバルに公開（後方互換性のため）
    window.selectedStore = selectedStore;

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

        // アクセシビリティ対応
        loadingOverlay.setAttribute('role', 'dialog');
        loadingOverlay.setAttribute('aria-label', '店舗選択中');
        loadingOverlay.setAttribute('aria-live', 'polite');

        document.body.appendChild(loadingOverlay);
    }

    // ========== メニューボタンの動的効果 ==========
    function enhanceMenuButtons() {
        const menuButtons = document.querySelectorAll('.menu-button');
        
        menuButtons.forEach(function(button, index) {
            // ホバー効果
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });

            // クリック時のリップル効果
            button.addEventListener('click', function(e) {
                createRippleEffect(this, e);
            });

            // アニメーションの遅延設定
            button.style.animationDelay = (index * 0.1) + 's';
        });
    }

    function createRippleEffect(element, event) {
        const ripple = document.createElement('div');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
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
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(function() {
            if (ripple.parentNode) {
                ripple.remove();
            }
        }, 600);
    }

    // ========== 顧客情報CSVアップロード機能 ==========
    function initializeCustomerUpload() {
        const fileUploadArea = document.getElementById('fileUploadArea');
        const csvFile = document.getElementById('csvFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadButton = document.getElementById('uploadButton');

        // 要素が存在しない場合は初期化をスキップ
        if (!fileUploadArea || !csvFile) {
            return;
        }

        // ファイル選択エリアのクリックイベント
        fileUploadArea.addEventListener('click', function(e) {
            if (e.target !== csvFile) {
                csvFile.click();
            }
        });

        // ドラッグ&ドロップ機能
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });

        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        // ファイル選択イベント
        csvFile.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });

        // ファイル選択処理
        function handleFileSelect(file) {
            // ファイル形式チェック
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showErrorMessage('CSVファイルを選択してください。');
                resetFileInput();
                return;
            }

            // ファイルサイズチェック (5MB制限)
            if (file.size > 5 * 1024 * 1024) {
                showErrorMessage('ファイルサイズは5MB以下にしてください。');
                resetFileInput();
                return;
            }

            // ファイルが空でないかチェック
            if (file.size === 0) {
                showErrorMessage('空のファイルはアップロードできません。');
                resetFileInput();
                return;
            }

            // ファイル情報を表示
            if (fileName) fileName.textContent = file.name;
            if (fileSize) fileSize.textContent = formatFileSize(file.size);
            if (fileInfo) fileInfo.style.display = 'flex';
            fileUploadArea.classList.add('file-selected');
            if (uploadButton) uploadButton.disabled = false;
        }

        function resetFileInput() {
            csvFile.value = '';
            if (fileInfo) fileInfo.style.display = 'none';
            fileUploadArea.classList.remove('file-selected');
            if (uploadButton) uploadButton.disabled = true;
        }

        // ファイルサイズのフォーマット
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // フォーム送信時の処理
        const form = document.querySelector('.upload-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (uploadButton) {
                    uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> アップロード中...';
                    uploadButton.disabled = true;
                }
            });
        }
    }

    // ========== フォーカス管理 ==========
    function setupFocusManagement() {
        const focusableElements = document.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        focusableElements.forEach(function(element) {
            element.addEventListener('focus', function() {
                this.style.outline = '2px solid var(--accent-green)';
                this.style.outlineOffset = '2px';
            });
            
            element.addEventListener('blur', function() {
                this.style.outline = '';
                this.style.outlineOffset = '';
            });
        });
    }

    // ========== 店舗情報の初期化 ==========
    function initializeStoreSelection() {
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
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                const parts = cookie.split('=');
                if (parts[0] === 'selectedStore' && parts[1]) {
                    try {
                        storedStore = decodeURIComponent(parts[1]);
                        selectedStoreData = storedStore;
                        break;
                    } catch (e) {
                        console.warn('Cookie decode error:', e);
                    }
                }
            }
        }
        
        if (storedStore) {
            const titleElement = document.querySelector('.store-title');
            if (titleElement) {
                titleElement.innerHTML = sanitizeInput(storedStore) + '<br>受注管理システム';
            }
            
            // ページタイトルも更新
            document.title = sanitizeInput(storedStore) + ' - 受注管理システム';
        }

        // メニューボタンの設定
        const menuButtons = document.querySelectorAll('.menu-button');
        if (menuButtons.length && storedStore) {
            menuButtons.forEach(function(button) {
                const path = button.dataset.path;
                if (path) {
                    button.addEventListener('click', function() {
                        showLoadingAnimation();
                        setTimeout(function() {
                            window.location.href = path + '?store=' + encodeURIComponent(storedStore);
                        }, 500);
                    });
                }
            });
        }
    }

    // ========== アニメーション用のIntersection Observer ==========
    function initializeObservers() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // 要素が後から追加される場合のために、MutationObserverでも監視
        const mutationObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && node.classList && node.classList.contains('menu-button')) {
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
    }

    // ========== スタイル動的追加 ==========
    function addDynamicStyles() {
        const style = document.createElement('style');
        style.textContent = `
            /* ローディングアニメーション */
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

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* リップル効果 */
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }

            /* ファイル要件表示 */
            .file-requirements {
                margin-top: 8px;
                color: var(--sub-green);
                font-size: 12px;
                line-height: 1.4;
            }

            /* パルス効果 */
            .pulse-effect {
                animation: pulse 2s ease-in-out;
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }

            /* アクセシビリティ改善 */
            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            /* フォーカス表示の改善 */
            *:focus-visible {
                outline: 2px solid var(--accent-green);
                outline-offset: 2px;
            }

            .menu-button:focus-visible,
            .upload-button:focus-visible {
                outline: 3px solid var(--accent-green);
                outline-offset: 3px;
            }

            /* エラートースト */
            .error-toast {
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
            }

            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    }

    // ========== エラーハンドリング ==========
    function initializeErrorHandling() {
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
            
            // ユーザーに優しいエラーメッセージを表示
            const errorToast = document.createElement('div');
            errorToast.className = 'error-toast';
            errorToast.textContent = 'エラーが発生しました。ページを再読み込みしてください。';
            errorToast.setAttribute('role', 'alert');
            errorToast.setAttribute('aria-live', 'assertive');
            
            document.body.appendChild(errorToast);
            
            setTimeout(function() {
                if (errorToast.parentNode) {
                    errorToast.remove();
                }
            }, 5000);
        });

        // Promise rejection handling
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Unhandled Promise Rejection:', e.reason);
            e.preventDefault(); // Prevent default browser behavior
        });
    }

    // ========== パフォーマンス監視 ==========
    function initializePerformanceMonitoring() {
        window.addEventListener('load', function() {
            // ページ読み込み時間を測定
            if (window.performance && window.performance.now) {
                const loadTime = performance.now();
                if (loadTime > 3000) {
                    console.warn('ページの読み込みが遅い可能性があります:', loadTime + 'ms');
                }
            }
        });
    }

    // ========== 初期化処理 ==========
    function initializeApp() {
        // 動的スタイルの追加
        addDynamicStyles();
        
        // 各種機能の初期化
        initializeMenu();
        initializeScrollEffects();
        initializeStoreSelection();
        enhanceMenuButtons();
        setupFocusManagement();
        initializeCustomerUpload();
        initializeObservers();
        initializeErrorHandling();
        initializePerformanceMonitoring();
        
        // ページ読み込み完了のアニメーション
        document.body.style.opacity = '0';
        setTimeout(function() {
            document.body.style.transition = 'opacity 0.5s ease-in-out';
            document.body.style.opacity = '1';
        }, 100);
    }

    // ========== DOMContentLoaded後の初期化 ==========
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApp);
    } else {
        // Already loaded
        initializeApp();
    }

    // ========== 公開API（後方互換性のため） ==========
    window.MBS = {
        selectedStore: selectedStore,
        showErrorMessage: showErrorMessage,
        showSuccessMessage: showSuccessMessage,
        showLoadingAnimation: showLoadingAnimation,
        toggleMenu: toggleMenu,
        closeMenu: closeMenu
    };

})();