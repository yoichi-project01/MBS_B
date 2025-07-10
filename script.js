/**
 * MBS_B システム用統合JavaScript - 完全版
 * ヘッダー管理、ナビゲーション、アップロード、統計機能、納品書機能を統合
 */

(function() {
    'use strict';
    
    // ========== グローバル変数 ==========
    let customerData = [];
    let headerManager;
    let scrollEffects;
    
    // ページ設定（PHP側と同期）
    const PAGE_CONFIG = {
        '/customer_information/': { name: '顧客情報', icon: '👥' },
        '/statistics/': { name: '統計情報', icon: '📊' },
        '/order_list/': { name: '注文書', icon: '📋' },
        '/delivery_list/': { name: '納品書', icon: '🚚' },
        'index.php': { name: '顧客情報CSVアップロード', icon: '👥' },
        'upload.php': { name: '顧客情報CSVアップロード', icon: '👥' }
    };
    
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
    
    function throttle(func, wait) {
        let lastTime = 0;
        return function executedFunction(...args) {
            const now = Date.now();
            if (now - lastTime >= wait) {
                func(...args);
                lastTime = now;
            }
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
                confirmButtonText: 'OK',
                customClass: {
                    container: 'swal-z-index'
                }
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
                confirmButtonText: 'OK',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                },
                customClass: {
                    container: 'swal-z-index'
                }
            });
        } else {
            alert(title + ': ' + message);
        }
    }
    
    function showInfoMessage(title, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: title,
                text: message,
                confirmButtonColor: '#2f5d3f',
                confirmButtonText: 'OK',
                customClass: {
                    container: 'swal-z-index'
                }
            });
        } else {
            alert(title + ': ' + message);
        }
    }
    
    /**
     * HTML エスケープ
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * セキュアな入力値検証
     */
    function validateInput(input, type = 'text', maxLength = 100) {
        if (!input || typeof input !== 'string') {
            return false;
        }
    
        // 長さチェック
        if (input.length > maxLength) {
            return false;
        }
    
        // XSS攻撃パターンのチェック
        const xssPatterns = [
            /<script[^>]*>.*?<\/script>/gi,
            /javascript:/gi,
            /on\w+\s*=/gi,
            /<iframe[^>]*>/gi,
            /<object[^>]*>/gi,
            /<embed[^>]*>/gi
        ];
    
        for (const pattern of xssPatterns) {
            if (pattern.test(input)) {
                return false;
            }
        }
    
        // SQLインジェクション攻撃パターンのチェック
        const sqlPatterns = [
            /(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b|\btruncate\b)/gi,
            /(\-\-|\#|\/\*|\*\/)/gi,
            /(\bor\b\s+\d+\s*=\s*\d+|\band\b\s+\d+\s*=\s*\d+)/gi
        ];
    
        for (const pattern of sqlPatterns) {
            if (pattern.test(input)) {
                return false;
            }
        }
    
        return true;
    }

    // ========== 改善されたヘッダー管理クラス ==========
    class HeaderManager {
        constructor() {
            this.menuToggle = null;
            this.nav = null;
            this.menuOverlay = null;
            this.isMenuOpen = false;
            this.isMobile = false;
            this.resizeDebounceTimer = null;
            this.focusableElements = [];
            this.originalFocus = null;
            
            this.init();
        }
        
        init() {
            this.bindElements();
            this.bindEvents();
            this.checkMobileView();
            this.updateHeaderTitle();
            this.setupAccessibility();
        }
        
        bindElements() {
            this.menuToggle = document.getElementById('menuToggle');
            this.nav = document.getElementById('nav');
            this.menuOverlay = document.getElementById('menuOverlay');
        }
        
        bindEvents() {
            // ハンバーガーメニュークリック
            if (this.menuToggle) {
                this.menuToggle.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggleMenu();
                });
            }
            
            // オーバーレイクリック
            if (this.menuOverlay) {
                this.menuOverlay.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.closeMenu();
                });
            }
            
            // ナビリンククリック
            document.querySelectorAll('.nav-item').forEach(link => {
                link.addEventListener('click', (e) => {
                    // 外部リンクの場合はページ遷移を許可
                    if (link.href && link.href.startsWith('http')) {
                        if (this.isMobile) {
                            setTimeout(() => {
                                this.closeMenu();
                            }, 100);
                        }
                        return;
                    }
                    
                    // 内部リンクの場合の処理
                    if (this.isMobile) {
                        setTimeout(() => {
                            this.closeMenu();
                        }, 100);
                    }
                });
            });
            
            // キーボードイベント
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isMenuOpen) {
                    this.closeMenu();
                } else if (e.key === 'Tab' && this.isMenuOpen) {
                    this.handleTabKey(e);
                }
            });
            
            // リサイズイベント
            window.addEventListener('resize', () => {
                this.debounceResize();
            });
            
            // ページ表示時の処理（Back Forward Cache対応）
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.updateHeaderTitle();
                    this.updateActiveNavItem();
                }
            });
            
            // DOMContentLoaded時の処理
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    this.updateHeaderTitle();
                });
            }
        }
        
        toggleMenu() {
            if (this.isMenuOpen) {
                this.closeMenu();
            } else {
                this.openMenu();
            }
        }
        
        openMenu() {
            if (!this.nav || !this.menuOverlay || !this.menuToggle) return;
            
            this.isMenuOpen = true;
            this.originalFocus = document.activeElement;
            
            // クラスの追加
            this.menuToggle.classList.add('active');
            this.nav.classList.add('active');
            this.menuOverlay.classList.add('active');
            
            // ARIA属性の更新
            this.menuToggle.setAttribute('aria-expanded', 'true');
            this.menuToggle.setAttribute('aria-label', 'メニューを閉じる');
            this.nav.setAttribute('aria-hidden', 'false');
            
            // スクロールを無効化
            document.body.style.overflow = 'hidden';
            
            // フォーカス管理
            this.updateFocusableElements();
            
            // アニメーション後の処理
            setTimeout(() => {
                this.focusFirstMenuItem();
            }, 300);
        }
        
        closeMenu() {
            if (!this.nav || !this.menuOverlay || !this.menuToggle) return;
            
            this.isMenuOpen = false;
            
            // クラスの削除
            this.menuToggle.classList.remove('active');
            this.nav.classList.remove('active');
            this.menuOverlay.classList.remove('active');
            
            // ARIA属性の更新
            this.menuToggle.setAttribute('aria-expanded', 'false');
            this.menuToggle.setAttribute('aria-label', 'メニューを開く');
            this.nav.setAttribute('aria-hidden', 'true');
            
            // スクロールを有効化
            document.body.style.overflow = '';
            
            // フォーカスを元の要素に戻す
            if (this.originalFocus && typeof this.originalFocus.focus === 'function') {
                this.originalFocus.focus();
            } else {
                this.menuToggle.focus();
            }
        }
        
        handleTabKey(e) {
            if (!this.focusableElements.length) return;
            
            const firstElement = this.focusableElements[0];
            const lastElement = this.focusableElements[this.focusableElements.length - 1];
            
            if (e.shiftKey) {
                if (document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                }
            } else {
                if (document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
        
        updateFocusableElements() {
            if (!this.nav) return;
            
            this.focusableElements = Array.from(this.nav.querySelectorAll(
                'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select, [tabindex]:not([tabindex="-1"])'
            )).filter(element => {
                return !element.disabled && element.offsetParent !== null;
            });
        }
        
        focusFirstMenuItem() {
            if (!this.focusableElements.length) return;
            
            const firstItem = this.focusableElements[0];
            if (firstItem) {
                firstItem.focus();
            }
        }
        
        debounceResize() {
            clearTimeout(this.resizeDebounceTimer);
            this.resizeDebounceTimer = setTimeout(() => {
                this.handleResize();
            }, 250);
        }
        
        handleResize() {
            const wasMobile = this.isMobile;
            this.checkMobileView();
            
            // モバイルからデスクトップに切り替わった場合
            if (wasMobile && !this.isMobile && this.isMenuOpen) {
                this.closeMenu();
            }
            
            this.updateHeaderTitle();
        }
        
        checkMobileView() {
            this.isMobile = window.innerWidth <= 768;
        }
        
        // ページ情報の管理
        getCurrentPageInfo() {
            const currentPath = window.location.pathname;
            const currentFile = currentPath.split('/').pop();
            
            // ディレクトリベースでの判定（優先）
            for (const [path, config] of Object.entries(PAGE_CONFIG)) {
                if (path.startsWith('/') && currentPath.includes(path)) {
                    return config;
                }
            }
            
            // ファイル名ベースでの判定
            if (PAGE_CONFIG[currentFile]) {
                return PAGE_CONFIG[currentFile];
            }
            
            // デフォルト
            return { name: '受注管理', icon: '📋' };
        }
        
        updateHeaderTitle(customPageInfo = null) {
            const titleElement = document.querySelector('.site-header .store-title .page-text');
            const iconElement = document.querySelector('.site-header .store-title .page-icon');
            
            if (!titleElement || !iconElement) return;
            
            const urlParams = new URLSearchParams(window.location.search);
            const storeName = urlParams.get('store') || 
                             document.documentElement.getAttribute('data-store-name') || '';
            
            const pageInfo = customPageInfo || this.getCurrentPageInfo();
            
            // アイコンを更新
            iconElement.textContent = pageInfo.icon;
            
            // タイトルを更新（スマホでは短縮表示）
            let displayTitle;
            if (this.isMobile && storeName) {
                // スマホでは店舗名を短縮
                const shortStoreName = storeName.replace('店', '');
                displayTitle = `${shortStoreName} - ${pageInfo.name}`;
            } else if (storeName) {
                displayTitle = `${storeName} - ${pageInfo.name}`;
            } else {
                displayTitle = pageInfo.name;
            }
            
            titleElement.textContent = displayTitle;
            
            // ページタイトルの更新
            if (storeName) {
                document.title = `${pageInfo.name} - ${storeName} - 受注管理システム`;
            } else {
                document.title = `${pageInfo.name} - 受注管理システム`;
            }
            
            // アクティブなナビゲーションアイテムを更新
            this.updateActiveNavItem();
            
            // ページ遷移アニメーション
            this.addPageTransitionEffect();
            
            // カスタムイベントを発火
            window.dispatchEvent(new CustomEvent('headerTitleUpdated', {
                detail: { pageInfo, storeName }
            }));
        }
        
        updateActiveNavItem() {
            const currentPath = window.location.pathname;
            
            // 全てのナビアイテムからactiveクラスを削除
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                item.setAttribute('aria-current', 'false');
            });
            
            // 現在のページに対応するナビアイテムにactiveクラスを追加
            const navMappings = {
                '/customer_information/': 0,
                '/statistics/': 1,
                '/order_list/': 2,
                '/delivery_list/': 3
            };
            
            for (const [path, index] of Object.entries(navMappings)) {
                if (currentPath.includes(path)) {
                    const navItems = document.querySelectorAll('.nav-item');
                    if (navItems[index]) {
                        navItems[index].classList.add('active');
                        navItems[index].setAttribute('aria-current', 'page');
                        break;
                    }
                }
            }
        }
        
        addPageTransitionEffect() {
            const titleElement = document.querySelector('.site-header .store-title');
            if (!titleElement) return;
            
            titleElement.style.opacity = '0';
            titleElement.style.transform = 'translateY(-10px)';
            
            requestAnimationFrame(() => {
                titleElement.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                titleElement.style.opacity = '1';
                titleElement.style.transform = 'translateY(0)';
                
                // トランジション完了後にスタイルをリセット
                setTimeout(() => {
                    titleElement.style.transition = '';
                }, 400);
            });
        }
        
        // アクセシビリティ機能
        setupAccessibility() {
            // ARIA属性の初期設定
            if (this.menuToggle) {
                this.menuToggle.setAttribute('aria-expanded', 'false');
                this.menuToggle.setAttribute('aria-label', 'メニューを開く');
                this.menuToggle.setAttribute('aria-controls', 'nav');
            }
            
            if (this.nav) {
                this.nav.setAttribute('aria-hidden', 'true');
                this.nav.setAttribute('role', 'navigation');
                this.nav.setAttribute('aria-label', 'メインナビゲーション');
            }
            
            // ナビゲーションアイテムの設定
            document.querySelectorAll('.nav-item').forEach((item, index) => {
                item.setAttribute('tabindex', '0');
                item.setAttribute('role', 'menuitem');
            });
        }
        
        // 外部API
        setStoreName(storeName) {
            if (!storeName) return;
            
            // URLパラメータを更新（履歴は変更しない）
            const url = new URL(window.location);
            url.searchParams.set('store', storeName);
            window.history.replaceState({}, '', url);
            
            // データ属性を更新
            document.documentElement.setAttribute('data-store-name', storeName);
            
            // ヘッダータイトルを更新
            this.updateHeaderTitle();
        }
        
        setCustomPageInfo(name, icon) {
            const customPageInfo = { name, icon };
            this.updateHeaderTitle(customPageInfo);
        }
        
        getStoreName() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('store') || 
                   document.documentElement.getAttribute('data-store-name') || '';
        }
    }
    
    // ========== スクロール効果クラス ==========
    class ScrollEffects {
        constructor() {
            this.lastScrollY = window.scrollY;
            this.header = document.querySelector('.site-header');
            this.ticking = false;
            this.init();
        }
        
        init() {
            this.bindEvents();
        }
        
        bindEvents() {
            const scrollHandler = () => {
                if (!this.ticking) {
                    requestAnimationFrame(() => {
                        this.handleScroll();
                        this.ticking = false;
                    });
                    this.ticking = true;
                }
            };
            
            window.addEventListener('scroll', scrollHandler, { passive: true });
        }
        
        handleScroll() {
            const currentScrollY = window.scrollY;
            
            if (this.header) {
                if (currentScrollY > 100) {
                    this.header.style.boxShadow = '0 12px 40px rgba(47, 93, 63, 0.25)';
                } else {
                    this.header.style.boxShadow = '0 8px 32px rgba(47, 93, 63, 0.15)';
                }
                
                // ヘッダーの表示/非表示制御（オプション）
                if (currentScrollY > this.lastScrollY && currentScrollY > 200) {
                    // 下スクロール時は隠す
                    this.header.style.transform = 'translateY(-100%)';
                } else {
                    // 上スクロール時は表示
                    this.header.style.transform = 'translateY(0)';
                }
            }
            
            this.lastScrollY = currentScrollY;
        }
    }
    
    // ========== 店舗選択機能 ==========
    let selectedStoreData = '';
    
    function selectedStore(storeName) {
        if (!storeName || typeof storeName !== 'string') {
            showErrorMessage('無効な店舗名です。');
            return;
        }
    
        // 入力値のサニタイズと検証
        const sanitizedStoreName = sanitizeInput(storeName.trim());
        
        if (!validateInput(sanitizedStoreName, 'text', 50)) {
            showErrorMessage('無効な文字が含まれています。');
            return;
        }
        
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
                if (!window.matchMedia('(hover: none)').matches) {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                }
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
    
    // ========== 統計情報ページ機能（統合版） ==========
    
    /**
     * 統計情報ページの初期化
     */
    function initializeStatisticsPage() {
        // ページ識別のためのクラス追加
        if (window.location.pathname.includes('/statistics/')) {
            document.body.classList.add('statistics-page');
        }
    
        setupStatisticsEventListeners();
        loadExistingData();
        setupStatisticsAccessibility();
        setupSortButtons();
        initializeTabNavigation();
        initializeSidebarToggle();
        initializeStatisticsViewToggle();
        initializeStatisticsSearch();
        setupStatisticsModalHandlers();
        enhanceStatisticsAnimations();
    }
    
    /**
     * サイドバー切り替え機能の初期化
     */
    function initializeSidebarToggle() {
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (!menuToggle || !sidebar) return;
        
        const toggleSidebar = () => {
            sidebar.classList.toggle('active');
            if (sidebar.classList.contains('active')) {
                // オーバーレイの作成と表示
                const overlay = document.createElement('div');
                overlay.classList.add('overlay');
                document.body.appendChild(overlay);
                overlay.addEventListener('click', toggleSidebar);
            } else {
                // オーバーレイの削除
                const overlay = document.querySelector('.overlay');
                if (overlay) {
                    overlay.remove();
                }
            }
        };
        
        menuToggle.addEventListener('click', toggleSidebar);
        
        // ESCキーでサイドバーを閉じる
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        });
    }
    
    /**
     * 統計ページ専用のビュー切り替え機能
     */
    function initializeStatisticsViewToggle() {
        const viewBtns = document.querySelectorAll('.view-btn');
        const tableView = document.querySelector('.table-view-container');
        const cardView = document.querySelector('.card-view-container');
        
        if (viewBtns.length === 0) return;
        
        viewBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // 全てのボタンからactiveクラスを削除
                viewBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const viewType = btn.dataset.view;
                
                if (viewType === 'table') {
                    if (tableView) tableView.style.display = 'block';
                    if (cardView) cardView.style.display = 'none';
                } else if (viewType === 'card') {
                    if (tableView) tableView.style.display = 'none';
                    if (cardView) cardView.style.display = 'grid';
                }
                
                // ビュー切り替えのアニメーション
                animateViewSwitch(viewType);
            });
        });
    }
    
    /**
     * ビュー切り替えアニメーション
     */
    function animateViewSwitch(viewType) {
        const container = viewType === 'table' ? 
            document.querySelector('.table-view-container') : 
            document.querySelector('.card-view-container');
            
        if (!container) return;
        
        container.style.opacity = '0';
        container.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            container.style.transition = 'all 0.3s ease';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
            
            setTimeout(() => {
                container.style.transition = '';
            }, 300);
        }, 50);
    }
    
    /**
     * 統計ページ専用の検索機能
     */
    function initializeStatisticsSearch() {
        const customerSearchInput = document.getElementById('customerSearchInput');
        
        if (!customerSearchInput) return;
        
        customerSearchInput.addEventListener('keyup', () => {
            const filter = customerSearchInput.value.toLowerCase().trim();
            
            // 入力値の検証
            if (!validateInput(filter, 'text', 100)) {
                customerSearchInput.value = '';
                showErrorMessage('無効な文字が含まれています。');
                return;
            }
            
            // テーブルビューでの検索
            const tableRows = document.querySelectorAll('.data-table tbody tr');
            let tableVisibleCount = 0;
            
            tableRows.forEach(row => {
                const nameCell = row.cells[0];
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    const isVisible = name.includes(filter);
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible) tableVisibleCount++;
                }
            });
            
            // カードビューでの検索
            const customerCards = document.querySelectorAll('.card-view-container .customer-card');
            let cardVisibleCount = 0;
            
            customerCards.forEach(card => {
                const nameElement = card.querySelector('.customer-name');
                if (nameElement) {
                    const name = nameElement.textContent.toLowerCase();
                    const isVisible = name.includes(filter);
                    card.style.display = isVisible ? 'flex' : 'none';
                    if (isVisible) cardVisibleCount++;
                }
            });
            
            // 検索結果の通知
            const totalVisible = Math.max(tableVisibleCount, cardVisibleCount);
            const message = filter ? 
                `${totalVisible}件の顧客が見つかりました` : 
                '全ての顧客を表示しています';
            announceToScreenReader(message);
        });
    }
    
    /**
     * 統計ページのモーダルハンドラー設定
     */
    function setupStatisticsModalHandlers() {
        // モーダル外クリックで閉じる
        window.addEventListener('click', function(event) {
            const detailModal = document.getElementById('detailModal');
            if (event.target === detailModal) {
                closeModal('detailModal');
            }
        });
        
        // キーボードナビゲーション
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const openModal = document.querySelector('.modal[style*="block"]');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        });
    }
    
    /**
     * 統計ページのアニメーション強化
     */
    function enhanceStatisticsAnimations() {
        // メトリックカードのアニメーション
        const metricCards = document.querySelectorAll('.metric-card');
        metricCards.forEach((card, index) => {
            card.addEventListener('mouseenter', () => {
                if (!window.matchMedia('(hover: none)').matches) {
                    card.style.transform = 'translateY(-5px)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
            
            // 初期アニメーション
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // トップ顧客カードのアニメーション
        const topCustomerCards = document.querySelectorAll('.top-customer-card');
        topCustomerCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in-up');
            
            card.addEventListener('mouseenter', () => {
                if (!window.matchMedia('(hover: none)').matches) {
                    card.style.transform = 'translateY(-2px)';
                }
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }
        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('detailModal');
                if (modal && modal.style.display === 'block') {
                    closeModal('detailModal');
                }
            }
        });
    
    /**
     * 統計情報ページのイベントリスナー設定
     */
    function setupStatisticsEventListeners() {
        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('detailModal');
                if (modal && modal.style.display === 'block') {
                    closeModal('detailModal');
                }
            }
        });
    
        // 検索フォームの改善
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearchInput, 300));
        }
        
        // レスポンシブ処理
        function handleResize() {
            if (window.innerWidth > 768) {
                // デスクトップサイズになったらサイドバーを閉じる
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.overlay');
                if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    if (overlay) overlay.remove();
                }
            }
        }
        
        window.addEventListener('resize', debounce(handleResize, 250));
    }
    
    /**
     * タブナビゲーションの初期化（統合版）
     */
    function initializeTabNavigation() {
        // メインナビゲーションのタブ機能
        const navLinks = document.querySelectorAll('.nav-link');
        const tabContents = document.querySelectorAll('.tab-content');
        const mainTitle = document.getElementById('main-title');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // 外部リンクの場合は通常の遷移を許可
                if (link.href && link.href.startsWith('http') && !link.dataset.tab) {
                    return;
                }
                
                e.preventDefault();
                
                // サイドバーを閉じる（モバイル表示時）
                const sidebar = document.querySelector('.sidebar');
                const overlay = document.querySelector('.overlay');
                if (sidebar && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    if (overlay) overlay.remove();
                }
                
                // タブ切り替え処理
                const tab = link.dataset.tab;
                if (tab) {
                    switchTab(tab, link, navLinks, tabContents, mainTitle);
                }
            });
        });
        
        // 初期タブの設定
        const initialActiveLink = document.querySelector('.nav-link.active');
        if (initialActiveLink && initialActiveLink.dataset.tab) {
            const initialTab = initialActiveLink.dataset.tab;
            switchTab(initialTab, initialActiveLink, navLinks, tabContents, mainTitle);
        }
    }
    
    /**
     * タブ切り替え処理
     */
    function switchTab(targetTab, activeLink, navLinks, tabContents, mainTitle) {
        // 全てのナビリンクからactiveクラスを削除
        navLinks.forEach(link => {
            link.classList.remove('active');
            link.setAttribute('aria-current', 'false');
        });
        
        // 全てのタブコンテンツを非表示
        tabContents.forEach(content => {
            content.classList.remove('active');
            content.setAttribute('aria-hidden', 'true');
        });
        
        // アクティブなタブを設定
        activeLink.classList.add('active');
        activeLink.setAttribute('aria-current', 'page');
        
        // 対応するコンテンツを表示
        const targetContent = document.getElementById(targetTab);
        if (targetContent) {
            targetContent.classList.add('active');
            targetContent.setAttribute('aria-hidden', 'false');
            
            // タブ切り替えアニメーション
            animateTabSwitch(targetContent);
        }
        
        // タイトルを更新
        const titleSpan = activeLink.querySelector('span');
        if (titleSpan && mainTitle) {
            mainTitle.textContent = titleSpan.textContent;
        }
        
        // タブ切り替え完了イベント
        window.dispatchEvent(new CustomEvent('tabSwitched', {
            detail: { 
                tab: targetTab, 
                title: titleSpan ? titleSpan.textContent : targetTab 
            }
        }));
    }
    
    /**
     * タブ切り替えアニメーション
     */
    function animateTabSwitch(targetContent) {
        targetContent.style.opacity = '0';
        targetContent.style.transform = 'translateX(20px)';
        
        requestAnimationFrame(() => {
            targetContent.style.transition = 'all 0.3s ease';
            targetContent.style.opacity = '1';
            targetContent.style.transform = 'translateX(0)';
            
            setTimeout(() => {
                targetContent.style.transition = '';
            }, 300);
        });
    }
    
    /**
     * ソートボタンのセットアップ（統合版）
     */
    function setupSortButtons() {
        const sortButtons = document.querySelectorAll('.sort-btn, [data-sort]');
        sortButtons.forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                
                const column = this.getAttribute('data-column') || this.getAttribute('data-sort');
                const currentOrder = this.classList.contains('sort-asc') ? 'desc' : 'asc';
                
                if (column) {
                    sortTable(column, currentOrder, this);
                }
            });
            
            // ツールチップの設定
            const column = button.getAttribute('data-column') || button.getAttribute('data-sort');
            if (column && !button.getAttribute('data-tooltip')) {
                const columnNames = {
                    'customer_name': '顧客名',
                    'name': '顧客名',
                    'sales_by_customer': '売上',
                    'sales': '売上',
                    'lead_time': 'リードタイム',
                    'leadtime': 'リードタイム',
                    'delivery_amount': '配達回数',
                    'deliveries': '配達回数'
                };
                
                if (columnNames[column]) {
                    button.setAttribute('data-tooltip', `${columnNames[column]}でソート`);
                    button.classList.add('tooltip');
                }
            }
        });
        
        // ヘッダークリックでソート（統計ページ用）
        const sortableHeaders = document.querySelectorAll('th[data-sort]');
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const sortType = this.dataset.sort;
                const isAscending = this.classList.contains('sort-asc');
                
                // 全てのヘッダーからソートクラスを削除
                sortableHeaders.forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                });
                
                // 新しいソート方向を設定
                const newOrder = isAscending ? 'desc' : 'asc';
                this.classList.add(`sort-${newOrder}`);
                
                // テーブルをソート
                sortTableByHeader(sortType, newOrder, this);
            });
        });
    }
    
    /**
     * ヘッダークリックによるテーブルソート
     */
    function sortTableByHeader(sortType, order, header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.sort((a, b) => {
            let aValue, bValue;
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            
            aValue = a.cells[columnIndex]?.textContent.trim() || '';
            bValue = b.cells[columnIndex]?.textContent.trim() || '';
            
            // データ型に応じた処理
            if (sortType === 'sales' || sortType === 'deliveries') {
                aValue = parseFloat(aValue.replace(/[,¥]/g, '')) || 0;
                bValue = parseFloat(bValue.replace(/[,¥]/g, '')) || 0;
            } else if (sortType === 'leadtime') {
                aValue = parseLeadTimeToSeconds(aValue);
                bValue = parseLeadTimeToSeconds(bValue);
            } else {
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }
            
            if (order === 'asc') {
                return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
            } else {
                return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
            }
        });
        
        // 行の再配置
        animateTableSort(tbody, rows);
        
        // ソート完了の通知
        announceSort(sortType, order);
    }
    
    /**
     * 統計ページ専用の顧客詳細表示機能
     */
    function showStatisticsDetails(customerName) {
        const modal = document.getElementById('detailModal');
        const title = document.getElementById('detailTitle');
        const content = document.getElementById('detailContent');
    
        if (!modal || !title || !content) {
            // モーダルが存在しない場合は動的に作成
            createDetailModal();
            return showStatisticsDetails(customerName);
        }
        
        title.textContent = `${customerName} の詳細情報`;
        
        // 顧客データを検索
        const customerInfo = customerData.find(customer => 
            customer.customer_name === customerName
        );
        
        const detailHtml = `
            <div class="customer-detail-info">
                <div class="detail-section">
                    <h4><i class="fas fa-user"></i> 基本情報</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>顧客名:</label>
                            <span>${escapeHtml(customerName)}</span>
                        </div>
                        <div class="detail-item">
                            <label>顧客ID:</label>
                            <span>${customerInfo ? customerInfo.customer_no : 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>登録日:</label>
                            <span>データ取得中...</span>
                        </div>
                        <div class="detail-item">
                            <label>ステータス:</label>
                            <span class="badge success">アクティブ</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4><i class="fas fa-chart-line"></i> 売上統計</h4>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value">${customerInfo ? `¥${customerInfo.sales_by_customer.toLocaleString()}` : 'N/A'}</div>
                            <div class="stat-label">総売上</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${customerInfo ? customerInfo.delivery_amount : 'N/A'}</div>
                            <div class="stat-label">配達回数</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${customerInfo ? customerInfo.lead_time : 'N/A'}</div>
                            <div class="stat-label">平均リードタイム</div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4><i class="fas fa-history"></i> 取引履歴</h4>
                    <p class="loading-text">取引履歴を読み込み中...</p>
                </div>
                
                <div class="detail-section">
                    <h4><i class="fas fa-sticky-note"></i> 備考・特記事項</h4>
                    <p>特記事項はありません。</p>
                </div>
            </div>
        `;
        
        content.innerHTML = detailHtml;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        
        // フォーカス管理
        modal.focus();
        
        // モーダル表示アニメーション
        modal.style.opacity = '0';
        modal.style.transform = 'scale(0.9)';
        
        requestAnimationFrame(() => {
            modal.style.transition = 'all 0.3s ease';
            modal.style.opacity = '1';
            modal.style.transform = 'scale(1)';
        });
        
        // 詳細データの非同期読み込み（実際のAPIコールに置き換え可能）
        setTimeout(() => {
            loadCustomerDetailData(customerName);
        }, 500);
    }
    
    /**
     * 詳細モーダルの動的作成
     */
    function createDetailModal() {
        const modalHtml = `
            <div id="detailModal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="detailTitle">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 id="detailTitle">顧客詳細</h2>
                        <button class="close-modal" onclick="closeModal('detailModal')" aria-label="モーダルを閉じる">
                            &times;
                        </button>
                    </div>
                    <div class="modal-body" id="detailContent">
                        <!-- ここに詳細コンテンツが挿入されます -->
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    /**
     * 顧客詳細データの読み込み
     */
    function loadCustomerDetailData(customerName) {
        // 実際のプロジェクトではここでAPIコールを行う
        setTimeout(() => {
            const registrationDate = document.querySelector('.detail-item:nth-child(3) span');
            const historySection = document.querySelector('.detail-section:nth-child(3) p');
            
            if (registrationDate) {
                registrationDate.textContent = '2023-01-15';
            }
            
            if (historySection) {
                historySection.innerHTML = `
                    <div class="history-timeline">
                        <div class="history-item">
                            <div class="history-date">2024-12-15</div>
                            <div class="history-content">商品購入: ¥25,000</div>
                        </div>
                        <div class="history-item">
                            <div class="history-date">2024-11-28</div>
                            <div class="history-content">商品購入: ¥18,500</div>
                        </div>
                        <div class="history-item">
                            <div class="history-date">2024-10-10</div>
                            <div class="history-content">初回購入: ¥12,000</div>
                        </div>
                    </div>
                `;
            }
        }, 1000);
    }
    
    /**
     * 既存データの読み込み
     */
    function loadExistingData() {
        const tableRows = document.querySelectorAll('.enhanced-table-row, .table-row, .data-table tbody tr');
        customerData = Array.from(tableRows).map(function(row) {
            const customerNo = row.getAttribute('data-customer-no') || Math.floor(Math.random() * 1000);
            const cells = row.querySelectorAll('td');
            
            if (cells.length < 4) return null;
            
            const customerName = cells[0] ? cells[0].textContent.trim() : '';
            const sales = cells[1] ? cells[1].textContent.replace(/[,¥]/g, '') : '0';
            const leadTime = cells[2] ? cells[2].textContent.trim() : '0日';
            const deliveryAmount = cells[3] ? cells[3].textContent.trim() : '0';
    
            return {
                customer_no: parseInt(customerNo),
                customer_name: customerName,
                sales_by_customer: parseInt(sales) || 0,
                lead_time: leadTime,
                delivery_amount: parseInt(deliveryAmount) || 0
            };
        }).filter(Boolean);
    }
    
    /**
     * 統計情報ページのアクセシビリティ設定
     */
    function setupStatisticsAccessibility() {
        // テーブルにaria-labelを追加
        const tables = document.querySelectorAll('.enhanced-statistics-table, .statistics-table, .data-table');
        tables.forEach(function(table) {
            table.setAttribute('aria-label', '顧客統計情報テーブル');
            table.setAttribute('role', 'table');
        });
    
        // ソートボタンにaria-labelを追加
        document.querySelectorAll('.sort-btn, [data-sort]').forEach(function(button) {
            const column = button.getAttribute('data-column') || button.getAttribute('data-sort');
            const columnNames = {
                'customer_name': '顧客名',
                'name': '顧客名',
                'sales_by_customer': '売上',
                'sales': '売上',
                'lead_time': 'リードタイム',
                'leadtime': 'リードタイム',
                'delivery_amount': '配達回数',
                'deliveries': '配達回数'
            };
            
            if (column && columnNames[column]) {
                button.setAttribute('aria-label', `${columnNames[column]}でソート`);
            }
        });
    }
    
    /**
     * テーブルソート機能
     */
    function sortTable(column, order, activeButton) {
        const tbody = document.getElementById('customerTableBody') || 
                     document.querySelector('.enhanced-statistics-table tbody') ||
                     document.querySelector('.statistics-table tbody') ||
                     document.querySelector('.data-table tbody');
    
        if (!tbody) return;
    
        const rows = Array.from(tbody.querySelectorAll('tr'));
    
        rows.sort(function(a, b) {
            const aCell = a.querySelector(`[data-column="${column}"]`) || a.cells[getColumnIndex(column)];
            const bCell = b.querySelector(`[data-column="${column}"]`) || b.cells[getColumnIndex(column)];
    
            if (!aCell || !bCell) return 0;
    
            let aValue = aCell.textContent.trim();
            let bValue = bCell.textContent.trim();
    
            // データ型に応じた処理
            if (column.includes('sales') || column.includes('amount') || column.includes('deliveries')) {
                aValue = parseFloat(aValue.replace(/[,円¥]/g, '')) || 0;
                bValue = parseFloat(bValue.replace(/[,円¥]/g, '')) || 0;
            } else if (column.includes('lead') || column.includes('time')) {
                aValue = parseLeadTimeToSeconds(aValue);
                bValue = parseLeadTimeToSeconds(bValue);
            } else {
                // 文字列の場合
                aValue = aValue.toLowerCase();
                bValue = bValue.toLowerCase();
            }
    
            if (order === 'asc') {
                return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
            } else {
                return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
            }
        });
    
        // アクティブボタンの状態更新
        updateSortButtonState(activeButton, order);
    
        // 行の再配置（アニメーション付き）
        animateTableSort(tbody, rows);
    
        // アクセシビリティ通知
        announceSort(column, order);
    }
    
    /**
     * 列のインデックスを取得
     */
    function getColumnIndex(column) {
        const columnMappings = {
            'customer_name': 0,
            'name': 0,
            'sales_by_customer': 1,
            'sales': 1,
            'lead_time': 2,
            'leadtime': 2,
            'delivery_amount': 3,
            'deliveries': 3
        };
        
        return columnMappings[column] || 0;
    }
    
    /**
     * リードタイム文字列を秒数に変換
     */
    function parseLeadTimeToSeconds(timeStr) {
        let totalSeconds = 0;
        const patterns = [
            { regex: /(\d+)日/, multiplier: 86400 },
            { regex: /(\d+)時間/, multiplier: 3600 },
            { regex: /(\d+)分/, multiplier: 60 },
            { regex: /(\d+)秒/, multiplier: 1 }
        ];
    
        patterns.forEach(function(pattern) {
            const match = timeStr.match(pattern.regex);
            if (match) {
                totalSeconds += parseInt(match[1], 10) * pattern.multiplier;
            }
        });
    
        return totalSeconds;
    }
    
    /**
     * ソートボタンの状態更新
     */
    function updateSortButtonState(activeButton, order) {
        // 全てのボタンからソートクラスを削除
        document.querySelectorAll('.sort-btn, [data-sort]').forEach(function(btn) {
            btn.classList.remove('active', 'sort-asc', 'sort-desc');
            btn.setAttribute('aria-pressed', 'false');
        });
    
        // アクティブボタンにクラスを追加
        if (activeButton) {
            activeButton.classList.add('active', order === 'asc' ? 'sort-asc' : 'sort-desc');
            activeButton.setAttribute('aria-pressed', 'true');
        }
    }
    
    /**
     * テーブルソートのアニメーション
     */
    function animateTableSort(tbody, sortedRows) {
        // フェードアウト
        tbody.style.opacity = '0.6';
        tbody.style.transform = 'translateY(10px)';
    
        setTimeout(function() {
            // 行を再配置
            tbody.innerHTML = '';
            sortedRows.forEach(function(row) {
                tbody.appendChild(row);
            });
    
            // フェードイン
            tbody.style.transition = 'all 0.3s ease';
            tbody.style.opacity = '1';
            tbody.style.transform = 'translateY(0)';
    
            // トランジション完了後にスタイルをリセット
            setTimeout(function() {
                tbody.style.transition = '';
            }, 300);
        }, 150);
    }
    
    /**
     * ソート完了の音声通知
     */
    function announceSort(column, order) {
        const columnNames = {
            'customer_name': '顧客名',
            'name': '顧客名',
            'sales_by_customer': '売上',
            'sales': '売上',
            'lead_time': 'リードタイム',
            'leadtime': 'リードタイム',
            'delivery_amount': '配達回数',
            'deliveries': '配達回数'
        };
        const orderText = order === 'asc' ? '昇順' : '降順';
        const message = `${columnNames[column] || column}を${orderText}でソートしました`;
    
        // スクリーンリーダー用の通知
        announceToScreenReader(message);
    }
    
    /**
     * スクリーンリーダーへの通知
     */
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
    
        document.body.appendChild(announcement);
    
        setTimeout(function() {
            if (announcement.parentNode) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }
    
    /**
     * 検索入力の処理
     */
    function handleSearchInput(event) {
        const searchTerm = event.target.value.toLowerCase().trim();
        
        // 入力値の検証
        if (!validateInput(searchTerm, 'text', 100)) {
            event.target.value = '';
            showErrorMessage('無効な文字が含まれています。');
            return;
        }
    
        const tbody = document.getElementById('customerTableBody') || 
                     document.querySelector('.enhanced-statistics-table tbody') ||
                     document.querySelector('.statistics-table tbody') ||
                     document.querySelector('.data-table tbody');
    
        if (!tbody) return;
    
        const rows = tbody.querySelectorAll('tr');
        let visibleCount = 0;
    
        rows.forEach(function(row) {
            const customerNameCell = row.querySelector('[data-column="customer_name"]') || 
                                   row.cells[0];
            if (!customerNameCell) return;
    
            const customerName = customerNameCell.textContent.toLowerCase();
            const isVisible = searchTerm === '' || customerName.includes(searchTerm);
    
            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    
        // 検索結果の通知
        const message = searchTerm ? `${visibleCount}件の顧客が見つかりました` : '全ての顧客を表示しています';
        announceToScreenReader(message);
    }
    
    /**
     * モーダルを閉じる（統計情報ページ用）
     */
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
        }
    }
    
    // ========== 顧客詳細表示機能（統合版） ==========
    function showDetails(customerName) {
        // 統計ページ用の詳細表示を使用
        showStatisticsDetails(customerName);
    }
    
    /**
     * モーダルを閉じる（統合版）
     */
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        // 閉じるアニメーション
        modal.style.transition = 'all 0.3s ease';
        modal.style.opacity = '0';
        modal.style.transform = 'scale(0.9)';
        
        setTimeout(() => {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.style.transition = '';
            modal.style.transform = '';
            modal.style.opacity = '';
        }, 300);
        
        // フォーカスを元の位置に戻す
        const triggerElement = document.querySelector(`[onclick*="${modalId}"]`) ||
                              document.querySelector('.table-action-btn:focus') ||
                              document.activeElement;
        
        if (triggerElement && typeof triggerElement.focus === 'function') {
            setTimeout(() => {
                triggerElement.focus();
            }, 350);
        }
    }
    
    // ========== メニューボタンの機能初期化 ==========
    function initializeMenuButtons() {
        // menu.phpページのメニューボタン
        const menuButtons = document.querySelectorAll('.menu-button[data-path]');
        menuButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const path = this.getAttribute('data-path');
                const urlParams = new URLSearchParams(window.location.search);
                const store = urlParams.get('store');
                
                if (path && store) {
                    const fullPath = `/MBS_B/${path}?store=${encodeURIComponent(store)}`;
                    window.location.href = fullPath;
                }
            });
        });
    
        // index.htmlページの店舗選択ボタン
        const storeButtons = document.querySelectorAll('.menu-button[onclick]');
        storeButtons.forEach(function(button) {
            // onclick属性を削除して、新しいイベントリスナーを追加
            const onclickValue = button.getAttribute('onclick');
            if (onclickValue && onclickValue.includes('selectedStore')) {
                button.removeAttribute('onclick');
                
                // 店舗名を抽出
                const match = onclickValue.match(/selectedStore\(['"]([^'"]+)['"]\)/);
                if (match && match[1]) {
                    const storeName = match[1];
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        selectedStore(storeName);
                    });
                }
            }
        });
    }

    // ========== 納品書ページ専用JavaScript ==========

    /**
     * 納品書管理システム
     */
    class DeliverySystem {
        constructor() {
            this.selectedCustomer = '';
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupAccessibility();
        }

        bindEvents() {
            // 納品書ページでない場合は初期化をスキップ
            if (!document.querySelector('.delivery-container')) {
                return;
            }

            // テーブル行クリックイベント
            const rows = document.querySelectorAll('#deliveryTableBody tr');
            rows.forEach((row) => {
                row.addEventListener('click', (e) => {
                    if (e.target.type !== 'checkbox') {
                        const customerName = row.cells[1].textContent;
                        this.showDeliveryDetail(customerName);
                    }
                });
            });

            // 検索機能
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', debounce(() => {
                    this.searchDeliveries();
                }, 300));
            }

            const customerSearchInput = document.getElementById('customerSearchInput');
            if (customerSearchInput) {
                customerSearchInput.addEventListener('input', debounce(() => {
                    this.searchCustomers();
                }, 300));
            }

            // ESCキーでモーダルを閉じる
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.hideCustomerSelect();
                    this.hideDeliveryDetail();
                }
            });

            // モーダル外クリックで閉じる
            const customerSelect = document.getElementById('customerSelect');
            if (customerSelect) {
                customerSelect.addEventListener('click', (e) => {
                    if (e.target === customerSelect) {
                        this.hideCustomerSelect();
                    }
                });
            }

            const deliveryDetail = document.getElementById('deliveryDetail');
            if (deliveryDetail) {
                deliveryDetail.addEventListener('click', (e) => {
                    if (e.target === deliveryDetail) {
                        this.hideDeliveryDetail();
                    }
                });
            }

            // チェックボックスの動的更新
            this.setupCheckboxHandlers();
        }

        setupAccessibility() {
            // ARIA属性の設定
            const customerModal = document.querySelector('.customer-modal');
            if (customerModal) {
                customerModal.setAttribute('role', 'dialog');
                customerModal.setAttribute('aria-modal', 'true');
                customerModal.setAttribute('aria-labelledby', 'customer-modal-title');
            }

            const deliveryModal = document.querySelector('.delivery-modal');
            if (deliveryModal) {
                deliveryModal.setAttribute('role', 'dialog');
                deliveryModal.setAttribute('aria-modal', 'true');
                deliveryModal.setAttribute('aria-labelledby', 'delivery-modal-title');
            }
        }

        setupCheckboxHandlers() {
            // 詳細テーブルのチェックボックス変更時の処理
            const detailTable = document.getElementById('deliveryDetailBody');
            if (detailTable) {
                detailTable.addEventListener('change', (e) => {
                    if (e.target.type === 'checkbox') {
                        this.updateTotalAmount();
                    }
                });
            }
        }

        // 顧客選択モーダル表示
        showCustomerSelect() {
            const modal = document.getElementById('customerSelect');
            if (modal) {
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                
                // フォーカス管理
                const firstItem = modal.querySelector('.customer-item');
                if (firstItem) {
                    firstItem.focus();
                }

                // スクロールを無効化
                document.body.style.overflow = 'hidden';
            }
        }

        // 顧客選択モーダル非表示
        hideCustomerSelect() {
            const modal = document.getElementById('customerSelect');
            if (modal) {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                
                // スクロールを有効化
                document.body.style.overflow = '';
                
                // 選択状態をリセット
                this.selectedCustomer = '';
                document.querySelectorAll('.customer-item').forEach(item => {
                    item.classList.remove('selected');
                    item.style.background = '';
                });
            }
        }

        // 顧客選択
        selectCustomer(customerName) {
            if (!validateInput(customerName, 'text', 100)) {
                showErrorMessage('無効な顧客名です。');
                return;
            }

            this.selectedCustomer = customerName;
            
            // 全ての顧客アイテムから選択状態を削除
            document.querySelectorAll('.customer-item').forEach(item => {
                item.classList.remove('selected');
                item.style.background = '';
            });
            
            // クリックされたアイテムを選択状態にする
            const clickedItem = event.target.closest('.customer-item');
            if (clickedItem) {
                clickedItem.classList.add('selected');
                clickedItem.style.background = '#e8f5e8';
            }

            // スクリーンリーダー用の通知
            announceToScreenReader(`${customerName}が選択されました`);
        }

        // 顧客選択決定
        confirmCustomerSelection() {
            if (this.selectedCustomer) {
                this.hideCustomerSelect();
                this.showDeliveryDetail(this.selectedCustomer);
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: '顧客を選択してください',
                        text: '顧客リストから顧客を選択してから決定ボタンを押してください。',
                        confirmButtonColor: '#2f5d3f'
                    });
                } else {
                    showErrorMessage('顧客を選択してください。');
                }
            }
        }

        // 納品書詳細表示
        showDeliveryDetail(customerName = '木村 紗希') {
            const modal = document.getElementById('deliveryDetail');
            const customerNameElement = document.getElementById('customerName');
            
            if (modal && customerNameElement) {
                customerNameElement.textContent = customerName;
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                
                // フォーカス管理
                const closeBtn = modal.querySelector('.close-btn');
                if (closeBtn) {
                    closeBtn.focus();
                }

                // スクロールを無効化
                document.body.style.overflow = 'hidden';

                // 合計金額を更新
                this.updateTotalAmount();
            }
        }

        // 納品書詳細非表示
        hideDeliveryDetail() {
            const modal = document.getElementById('deliveryDetail');
            if (modal) {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                
                // スクロールを有効化
                document.body.style.overflow = '';
            }
        }

        // 検索機能
        searchDeliveries() {
            const searchInput = document.getElementById('searchInput');
            if (!searchInput) return;

            const searchTerm = searchInput.value.toLowerCase().trim();
            
            // 入力値の検証
            if (!validateInput(searchTerm, 'text', 100)) {
                searchInput.value = '';
                showErrorMessage('無効な文字が含まれています。');
                return;
            }

            const rows = document.querySelectorAll('#deliveryTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const customerName = row.cells[1].textContent.toLowerCase();
                const isVisible = searchTerm === '' || customerName.includes(searchTerm);
                
                if (isVisible) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // 検索結果の通知
            const message = searchTerm ? 
                `${visibleCount}件の納品書が見つかりました` : 
                '全ての納品書を表示しています';
            announceToScreenReader(message);
        }

        // 顧客検索機能
        searchCustomers() {
            const searchInput = document.getElementById('customerSearchInput');
            if (!searchInput) return;

            const searchTerm = searchInput.value.toLowerCase().trim();
            
            // 入力値の検証
            if (!validateInput(searchTerm, 'text', 100)) {
                searchInput.value = '';
                showErrorMessage('無効な文字が含まれています。');
                return;
            }

            const items = document.querySelectorAll('.customer-item');
            let visibleCount = 0;
            
            items.forEach(item => {
                const customerName = item.textContent.toLowerCase();
                const isVisible = searchTerm === '' || customerName.includes(searchTerm);
                
                if (isVisible) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // 検索結果の通知
            const message = searchTerm ? 
                `${visibleCount}人の顧客が見つかりました` : 
                '全ての顧客を表示しています';
            announceToScreenReader(message);
        }

        // 合計金額更新
        updateTotalAmount() {
            const checkboxes = document.querySelectorAll('#deliveryDetailBody input[type="checkbox"]:checked');
            let totalQuantity = 0;
            let totalAmount = 0;

            checkboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (row && !row.querySelector('td[colspan]')) { // 合計行を除外
                    const quantityCell = row.cells[2];
                    const amountCell = row.cells[4];
                    
                    if (quantityCell && amountCell) {
                        const quantity = parseInt(quantityCell.textContent) || 0;
                        const amount = parseFloat(amountCell.textContent.replace(/[¥,]/g, '')) || 0;
                        
                        totalQuantity += quantity;
                        totalAmount += amount;
                    }
                }
            });

            // 合計行を更新
            const totalRow = document.querySelector('#deliveryDetailBody tr:last-child');
            if (totalRow) {
                const quantityCell = totalRow.cells[2];
                const amountCell = totalRow.cells[4];
                
                if (quantityCell) quantityCell.textContent = totalQuantity;
                if (amountCell) amountCell.textContent = `¥${totalAmount.toLocaleString()}`;
            }

            // 合計金額セクションを更新
            const totalAmountElement = document.querySelector('.total-amount');
            if (totalAmountElement) {
                totalAmountElement.textContent = `¥${totalAmount.toLocaleString()}`;
            }
        }

        // 保存機能
        saveDelivery() {
            const customerName = document.getElementById('customerName')?.textContent || '';
            const deliveryNo = document.getElementById('deliveryNo')?.textContent || '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: '保存完了',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>納品書No:</strong> ${escapeHtml(deliveryNo)}</p>
                            <p><strong>顧客名:</strong> ${escapeHtml(customerName)}</p>
                            <p>納品書が正常に保存されました。</p>
                        </div>
                    `,
                    confirmButtonColor: '#2f5d3f',
                    timer: 3000,
                    timerProgressBar: true
                });
            } else {
                showSuccessMessage('保存完了', '納品書が保存されました。');
            }

            this.hideDeliveryDetail();
        }

        // 印刷機能
        printDelivery() {
            // 印刷前の準備
            const originalTitle = document.title;
            const customerName = document.getElementById('customerName')?.textContent || '';
            const deliveryNo = document.getElementById('deliveryNo')?.textContent || '';
            
            document.title = `納品書No.${deliveryNo} - ${customerName}`;

            // 印刷実行
            window.print();

            // 印刷後の処理
            setTimeout(() => {
                document.title = originalTitle;
            }, 1000);
        }
    }

    // ========== グローバル関数（後方互換性のため） ==========

    let deliverySystemInstance = null;

    // 顧客選択モーダル表示
    function showCustomerSelect() {
        if (!deliverySystemInstance) {
            deliverySystemInstance = new DeliverySystem();
        }
        deliverySystemInstance.showCustomerSelect();
    }

    // 顧客選択モーダル非表示
    function hideCustomerSelect() {
        if (deliverySystemInstance) {
            deliverySystemInstance.hideCustomerSelect();
        }
    }

    // 顧客選択
    function selectCustomer(customerName) {
        if (!deliverySystemInstance) {
            deliverySystemInstance = new DeliverySystem();
        }
        deliverySystemInstance.selectCustomer(customerName);
    }

    // 顧客選択決定
    function confirmCustomerSelection() {
        if (deliverySystemInstance) {
            deliverySystemInstance.confirmCustomerSelection();
        }
    }

    // 納品書詳細表示
    function showDeliveryDetail(customerName) {
        if (!deliverySystemInstance) {
            deliverySystemInstance = new DeliverySystem();
        }
        deliverySystemInstance.showDeliveryDetail(customerName);
    }

    // 納品書詳細非表示
    function hideDeliveryDetail() {
        if (deliverySystemInstance) {
            deliverySystemInstance.hideDeliveryDetail();
        }
    }

    // 検索機能
    function searchDeliveries() {
        if (deliverySystemInstance) {
            deliverySystemInstance.searchDeliveries();
        }
    }

    // 顧客検索機能
    function searchCustomers() {
        if (deliverySystemInstance) {
            deliverySystemInstance.searchCustomers();
        }
    }

    // 保存機能
    function saveDelivery() {
        if (deliverySystemInstance) {
            deliverySystemInstance.saveDelivery();
        }
    }

    // 印刷機能
    function printDelivery() {
        if (deliverySystemInstance) {
            deliverySystemInstance.printDelivery();
        }
    }

    // ========== 初期化処理 ==========

    // 納品書システムの初期化
    function initializeDeliverySystem() {
        // 納品書ページでのみ初期化
        if (document.querySelector('.delivery-container')) {
            deliverySystemInstance = new DeliverySystem();
            console.log('Delivery System: Initialized successfully');
        }
    }
    
    // ========== メイン初期化関数 ==========
    
    /**
     * アプリケーション全体の初期化
     */
    function initializeApp() {
        try {
            // ヘッダー管理機能の初期化
            headerManager = new HeaderManager();
            scrollEffects = new ScrollEffects();
    
            // メニューボタンの初期化
            initializeMenuButtons();
    
            // 顧客アップロード機能の初期化（該当ページのみ）
            if (document.getElementById('fileUploadArea')) {
                initializeCustomerUpload();
            }
    
            // 統計情報ページの初期化（該当ページのみ）
            if (document.querySelector('.statistics-table, .enhanced-statistics-table, .data-table') || 
                window.location.pathname.includes('/statistics/')) {
                initializeStatisticsPage();
            }

            // 納品書システムの初期化
            initializeDeliverySystem();
    
            // メニューボタンの効果（メニューページのみ）
            if (document.querySelector('.menu-button')) {
                enhanceMenuButtons();
            }
    
            // 初期化完了の通知
            console.log('MBS_B System: All modules initialized successfully');
    
            // カスタムイベントの発火
            window.dispatchEvent(new CustomEvent('appInitialized', {
                detail: { 
                    timestamp: new Date().toISOString(),
                    version: '3.1.0',
                    modules: [
                        'HeaderManager',
                        'ScrollEffects', 
                        'MenuSystem',
                        'FileUpload',
                        'Statistics',
                        'DeliverySystem'
                    ]
                }
            }));
    
        } catch (error) {
            console.error('Initialization error:', error);
            showErrorMessage('アプリケーションの初期化中にエラーが発生しました。ページを再読み込みしてください。');
        }
    }
    
    // ========== イベントリスナーの設定 ==========
    
    // DOM読み込み完了時の初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApp);
    } else {
        // 既に読み込み完了している場合は即座に実行
        initializeApp();
    }
    
    // ページ表示時の処理（Back Forward Cache対応）
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // キャッシュから復元された場合の処理
            if (headerManager) {
                headerManager.updateHeaderTitle();
                headerManager.updateActiveNavItem();
            }
            // 納品書システムの再初期化
            if (document.querySelector('.delivery-container')) {
                initializeDeliverySystem();
            }
        }
    });
    
    // ページ非表示時のクリーンアップ
    window.addEventListener('pagehide', function(event) {
        // メニューが開いている場合は閉じる
        if (headerManager && headerManager.isMenuOpen) {
            headerManager.closeMenu();
        }
    });
    
    // モーダル外クリックで閉じる機能
    window.addEventListener('click', function(event) {
        const detailModal = document.getElementById('detailModal');
        if (event.target === detailModal) {
            closeModal('detailModal');
        }
    });
    
    // ========== 公開API ==========
    
    // グローバル関数として公開（後方互換性のため）
    window.selectedStore = selectedStore;
    window.closeModal = closeModal;
    window.showDetails = showDetails;
    window.sortTable = sortTable;

    // 納品書システム関数
    window.showCustomerSelect = showCustomerSelect;
    window.hideCustomerSelect = hideCustomerSelect;
    window.selectCustomer = selectCustomer;
    window.confirmCustomerSelection = confirmCustomerSelection;
    window.showDeliveryDetail = showDeliveryDetail;
    window.hideDeliveryDetail = hideDeliveryDetail;
    window.searchDeliveries = searchDeliveries;
    window.searchCustomers = searchCustomers;
    window.saveDelivery = saveDelivery;
    window.printDelivery = printDelivery;
    
    // HeaderManager API
    window.HeaderManager = {
        updateTitle: () => headerManager?.updateHeaderTitle(),
        updateActiveNav: () => headerManager?.updateActiveNavItem(),
        addTransitionEffect: () => headerManager?.addPageTransitionEffect(),
        setStoreName: (name) => headerManager?.setStoreName(name),
        setCustomPageInfo: (name, icon) => headerManager?.setCustomPageInfo(name, icon),
        getCurrentPageInfo: () => headerManager?.getCurrentPageInfo(),
        closeMenu: () => headerManager?.closeMenu(),
        getStoreName: () => headerManager?.getStoreName(),
        
        // イベントリスナー
        onTitleUpdate: (callback) => {
            window.addEventListener('headerTitleUpdated', callback);
        },
        
        // 状態取得
        isMenuOpen: () => headerManager?.isMenuOpen || false,
        isMobile: () => headerManager?.isMobile || false
    };

    // 納品書システムAPI
    window.DeliverySystem = {
        showCustomerSelect,
        hideCustomerSelect,
        selectCustomer,
        confirmCustomerSelection,
        showDeliveryDetail,
        hideDeliveryDetail,
        searchDeliveries,
        searchCustomers,
        saveDelivery,
        printDelivery,
        
        // インスタンス取得
        getInstance: () => deliverySystemInstance,
        
        // 強制初期化
        forceInit: () => {
            deliverySystemInstance = new DeliverySystem();
            return deliverySystemInstance;
        }
    };
    
    // ユーティリティAPI
    window.MBSUtils = {
        debounce,
        throttle,
        validateInput,
        sanitizeInput,
        escapeHtml,
        showErrorMessage,
        showSuccessMessage,
        showInfoMessage,
        announceToScreenReader,
        createRippleEffect
    };
    
})();