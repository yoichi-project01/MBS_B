/**
 * MBS_B システム用統合JavaScript
 * 統計情報機能、ヘッダー管理機能、店舗選択機能を含む
 */

(function() {
    'use strict';
    
    // ========== グローバル変数 ==========
    let currentChart = null;
    let sampleDataGenerated = false;
    let customerData = [];
    
    // ページ設定（PHP側と同期）
    const PAGE_CONFIG = {
        '/customer_information/': { name: '顧客情報', icon: '👥' },
        '/statistics/': { name: '統計情報', icon: '📊' },
        '/order_list/': { name: '注文書', icon: '📋' },
        '/delivery_list/': { name: '納品書', icon: '🚚' },
        // ファイル名ベース
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
                confirmButtonText: 'OK',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
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
                confirmButtonText: 'OK'
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
    
    // ========== ヘッダー管理機能 ==========
    
    /**
     * 現在のページ情報を取得
     */
    function getCurrentPageInfo() {
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
    
    /**
     * ヘッダータイトルを更新
     */
    function updateHeaderTitle(customPageInfo = null) {
        const titleElement = document.querySelector('.site-header .store-title .page-text');
        const iconElement = document.querySelector('.site-header .store-title .page-icon');
        
        if (!titleElement || !iconElement) return;
    
        const urlParams = new URLSearchParams(window.location.search);
        const storeName = urlParams.get('store') || 
                         document.documentElement.getAttribute('data-store-name') || '';
        
        const pageInfo = customPageInfo || getCurrentPageInfo();
        
        // アイコンを更新
        iconElement.textContent = pageInfo.icon;
        
        // タイトルを更新
        if (storeName) {
            titleElement.textContent = `${storeName} - ${pageInfo.name}`;
            document.title = `${pageInfo.name} - ${storeName} - 受注管理システム`;
        } else {
            titleElement.textContent = pageInfo.name;
            document.title = `${pageInfo.name} - 受注管理システム`;
        }
    
        // アクティブなナビゲーションアイテムを更新
        updateActiveNavItem();
        
        // カスタムイベントを発火
        window.dispatchEvent(new CustomEvent('headerTitleUpdated', {
            detail: { pageInfo, storeName }
        }));
    }
    
    /**
     * アクティブなナビゲーションアイテムを更新
     */
    function updateActiveNavItem() {
        const currentPath = window.location.pathname;
        
        // 全てのナビアイテムからactiveクラスを削除
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
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
                    break;
                }
            }
        }
    }
    
    /**
     * ページ遷移時のアニメーション効果
     */
    function addPageTransitionEffect() {
        const titleElement = document.querySelector('.site-header .store-title');
        if (!titleElement) return;
    
        titleElement.style.opacity = '0';
        titleElement.style.transform = 'translateY(-10px)';
        
        requestAnimationFrame(() => {
            titleElement.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            titleElement.style.opacity = '1';
            titleElement.style.transform = 'translateY(0)';
        });
    }
    
    /**
     * 店舗名を動的に設定
     */
    function setStoreName(storeName) {
        if (!storeName) return;
        
        // URLパラメータを更新（履歴は変更しない）
        const url = new URL(window.location);
        url.searchParams.set('store', storeName);
        window.history.replaceState({}, '', url);
        
        // データ属性を更新
        document.documentElement.setAttribute('data-store-name', storeName);
        
        // ヘッダータイトルを更新
        updateHeaderTitle();
    }
    
    /**
     * カスタムページ情報を設定
     */
    function setCustomPageInfo(name, icon) {
        const customPageInfo = { name, icon };
        updateHeaderTitle(customPageInfo);
    }
    
    /**
     * ブレッドクラム風の表示を追加（オプション）
     */
    function createBreadcrumb() {
        const urlParams = new URLSearchParams(window.location.search);
        const storeName = urlParams.get('store');
        
        if (!storeName) return;
    
        const breadcrumbContainer = document.createElement('div');
        breadcrumbContainer.className = 'breadcrumb-nav';
        breadcrumbContainer.innerHTML = `
            <span class="breadcrumb-item">
                <a href="/MBS_B/menu.php?store=${encodeURIComponent(storeName)}" class="breadcrumb-link">
                    🏠 ${escapeHtml(storeName)}
                </a>
            </span>
            <span class="breadcrumb-separator">›</span>
            <span class="breadcrumb-current"></span>
        `;
    
        // 現在のページ名を設定
        const currentPageSpan = breadcrumbContainer.querySelector('.breadcrumb-current');
        const pageInfo = getCurrentPageInfo();
        
        if (currentPageSpan) {
            currentPageSpan.textContent = `${pageInfo.icon} ${pageInfo.name}`;
        }
    
        // ヘッダーの下に挿入
        const header = document.querySelector('.site-header');
        if (header && !document.querySelector('.breadcrumb-nav')) {
            header.parentNode.insertBefore(breadcrumbContainer, header.nextSibling);
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
    
        // メニューボタンの機能を初期化
        initializeMenuButtons();
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
    
    // ========== 統計情報ページ機能 ==========

    /**
     * 統計情報ページの初期化
     */
    function initializeStatisticsPage() {
        // ページ識別のためのクラス追加
        if (window.location.pathname.includes('/statistics/')) {
            document.body.classList.add('statistics-tab-page');
        }

        // 顧客データをグローバル変数にロード
        loadCustomerDataFromDOM();

        // イベントリスナーを設定
        setupTabNavigation();
        setupViewToggle();
        setupCustomerSearch();
        setupTableSorting();
        setupActionButtons();
        setupChartSelectors();
        setupModalInteractions();
    }

    /**
     * 顧客データをDOMから読み込む
     */
    function loadCustomerDataFromDOM() {
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        customerData = Array.from(tableRows).map(row => {
            const cells = row.cells;
            if (!cells || cells.length < 4) return null;
            
            // `addslashes` でエスケープされたシングルクォートを元に戻す
            const rawOnClick = row.querySelector('button[onclick*="showDetails"]')
                                ?.getAttribute('onclick') || '';
            const nameMatch = rawOnClick.match(/showDetails\('(.+?)'\)/);
            const customerName = nameMatch ? nameMatch[1].replace(/'/g, "'") : cells[0].textContent.trim();

            return {
                customer_name: customerName,
                total_sales_text: cells[1].textContent.trim(),
                avg_lead_time: cells[2].textContent.trim(),
                delivery_count: parseInt(cells[3].textContent.replace(/,/g, '')),
                customer_no: row.dataset.customerNo || null
            };
        }).filter(Boolean);
    }

    /**
     * タブナビゲーションの設定
     */
    function setupTabNavigation() {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                const activeContent = document.getElementById(tabId);
                if (activeContent) {
                    activeContent.classList.add('active');
                }
            });
        });
    }

    /**
     * 表示切り替え（テーブル/カード）の設定
     */
    function setupViewToggle() {
        const viewButtons = document.querySelectorAll('.view-btn');
        const tableView = document.querySelector('.table-view');
        const cardView = document.querySelector('.card-view');

        if (!tableView || !cardView) return;

        viewButtons.forEach(button => {
            button.addEventListener('click', () => {
                viewButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');

                const view = button.getAttribute('data-view');
                if (view === 'table') {
                    tableView.style.display = '';
                    cardView.style.display = 'none';
                } else {
                    tableView.style.display = 'none';
                    cardView.style.display = '';
                }
            });
        });
    }

    /**
     * 顧客検索機能の設定
     */
    function setupCustomerSearch() {
        const searchInput = document.querySelector('.search-input');
        if (!searchInput) return;

        searchInput.addEventListener('input', debounce(e => {
            const searchTerm = e.target.value.toLowerCase();
            filterCustomers(searchTerm);
        }, 300));
    }

    /**
     * 顧客リストのフィルタリング
     */
    function filterCustomers(searchTerm) {
        const tableRows = document.querySelectorAll('.data-table tbody tr');
        const customerCards = document.querySelectorAll('.customer-card');

        tableRows.forEach(row => {
            const customerName = row.cells[0].textContent.toLowerCase();
            row.style.display = customerName.includes(searchTerm) ? '' : 'none';
        });

        customerCards.forEach(card => {
            const customerName = card.querySelector('.customer-name').textContent.toLowerCase();
            card.style.display = customerName.includes(searchTerm) ? '' : 'none';
        });
    }

    /**
     * テーブルソート機能の設定
     */
    function setupTableSorting() {
        const sortButtons = document.querySelectorAll('.sort-btn');
        sortButtons.forEach(button => {
            button.setAttribute('data-order', 'desc');
            button.addEventListener('click', () => {
                const column = button.getAttribute('data-column');
                const currentOrder = button.getAttribute('data-order');
                const newOrder = currentOrder === 'desc' ? 'asc' : 'desc';

                sortButtons.forEach(btn => {
                    btn.innerHTML = '▲▼';
                    if (btn !== button) {
                        btn.setAttribute('data-order', 'desc');
                    }
                });

                button.setAttribute('data-order', newOrder);
                button.innerHTML = newOrder === 'asc' ? '▲' : '▼';
                sortTable(column, newOrder);
            });
        });
    }
    
    /**
     * 数値変換
     */
    function parseSalesValue(text) {
        const value = parseFloat(text.replace(/[¥,]/g, ''));
        if (text.includes('M')) return value * 1000000;
        if (text.includes('K')) return value * 1000;
        return value;
    }

    /**
     * テーブルのソート処理
     */
    function sortTable(column, order) {
        const tbody = document.querySelector('.data-table tbody');
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let valA, valB;
            const getCellText = (row, index) => row.cells[index].textContent.trim();

            switch (column) {
                case 'name':
                    valA = getCellText(a, 0);
                    valB = getCellText(b, 0);
                    return order === 'asc' ? valA.localeCompare(valB, 'ja') : valB.localeCompare(valA, 'ja');
                case 'sales':
                    valA = parseSalesValue(getCellText(a, 1));
                    valB = parseSalesValue(getCellText(b, 1));
                    break;
                case 'leadtime':
                    valA = parseFloat(getCellText(a, 2));
                    valB = parseFloat(getCellText(b, 2));
                    break;
                case 'delivery':
                    valA = parseInt(getCellText(a, 3).replace(/,/g, ''));
                    valB = parseInt(getCellText(b, 3).replace(/,/g, ''));
                    break;
                default:
                    return 0;
            }
            return order === 'asc' ? valA - valB : valB - valA;
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    /**
     * 詳細・グラフボタンの設定
     */
    function setupActionButtons() {
        document.body.addEventListener('click', e => {
            const button = e.target.closest('button[onclick]');
            if (!button) return;

            const onclickAttr = button.getAttribute('onclick');
            
            if (onclickAttr.startsWith('showDetails')) {
                e.preventDefault();
                const customerName = onclickAttr.match(/'(.*?)'/)[1].replace(/'/g, "'");
                showDetails(customerName);
            } else if (onclickAttr.startsWith('showGraph')) {
                e.preventDefault();
                const customerName = onclickAttr.match(/'(.*?)'/)[1].replace(/'/g, "'");
                showGraph(customerName);
            }
        });
    }

    /**
     * グラフ分析タブのグラフ選択機能
     */
    function setupChartSelectors() {
        const chartOptions = document.querySelectorAll('.chart-option');
        const chartContainer = document.querySelector('#charts .chart-container');

        chartOptions.forEach(option => {
            option.addEventListener('click', () => {
                chartOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                const chartType = option.getAttribute('data-chart');
                renderMainChart(chartType, chartContainer);
            });
        });
    }

    /**
     * メインのグラフを描画
     */
    function renderMainChart(chartType, container) {
        container.innerHTML = ''; // Clear previous chart
        const canvas = document.createElement('canvas');
        container.appendChild(canvas);
        
        let chartConfig;
        if (chartType === 'sales') {
            const salesData = prepareChartData('total_sales_text', 'desc');
            chartConfig = createMainChartConfig('売上分析', 'bar', salesData, '売上', val => format_yen(val));
        } else if (chartType === 'delivery') {
            const deliveryData = prepareChartData('delivery_count', 'desc');
            chartConfig = createMainChartConfig('配達実績', 'doughnut', deliveryData, '配達回数', val => `${val} 回`);
        } else if (chartType === 'leadtime') {
            const leadTimeData = prepareChartData('avg_lead_time', 'asc');
            chartConfig = createMainChartConfig('リードタイム分析', 'line', leadTimeData, '平均リードタイム', val => `${val} 日`);
        } else {
             container.innerHTML = `<div style="text-align: center;">
                <span style="font-size: 48px; display: block; margin-bottom: 16px;">📈</span>
                <h3 style="color: var(--main-green); margin-bottom: 8px;">トレンド分析</h3>
                <p>この機能は現在開発中です。</p>
            </div>`;
            return;
        }

        if (currentChart) currentChart.destroy();
        currentChart = new Chart(canvas.getContext('2d'), chartConfig);
    }

    /**
     * チャート用のデータ準備
     */
    function prepareChartData(dataKey, sortOrder) {
        const sortedData = [...customerData].sort((a, b) => {
            const valA = (dataKey === 'total_sales_text') ? parseSalesValue(a[dataKey]) : parseFloat(a[dataKey]);
            const valB = (dataKey === 'total_sales_text') ? parseSalesValue(b[dataKey]) : parseFloat(b[dataKey]);
            return sortOrder === 'desc' ? valB - valA : valA - valB;
        }).slice(0, 10);

        return {
            labels: sortedData.map(c => c.customer_name),
            values: sortedData.map(c => (dataKey === 'total_sales_text') ? parseSalesValue(c[dataKey]) : parseFloat(c[dataKey]))
        };
    }

    /**
     * メインチャートの設定を生成
     */
    function createMainChartConfig(title, type, data, label, tooltipCallback) {
        const isDoughnut = type === 'doughnut';
        const backgroundColors = [
            '#2f5d3f', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b',
            '#ffc107', '#ff9800', '#ff5722', '#795548', '#9e9e9e'
        ];

        return {
            type: type,
            data: {
                labels: data.labels,
                datasets: [{
                    label: label,
                    data: data.values,
                    backgroundColor: isDoughnut ? backgroundColors : 'rgba(47, 93, 63, 0.8)',
                    borderColor: isDoughnut ? '#fff' : '#2f5d3f',
                    borderWidth: 2,
                    fill: type === 'line',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: { display: true, text: title, font: { size: 18 }, padding: 20 },
                    legend: { display: isDoughnut, position: 'right' },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.label}: ${tooltipCallback(context.parsed.y || context.parsed)}`
                        }
                    }
                },
                scales: isDoughnut ? {} : {
                    y: { beginAtZero: true, ticks: { callback: value => tooltipCallback(value) } },
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } }
                }
            }
        };
    }


    /**
     * モーダル関連の操作設定
     */
    function setupModalInteractions() {
        document.body.addEventListener('click', e => {
            if (e.target.matches('.modal .close')) {
                const modalId = e.target.closest('.modal').id;
                closeModal(modalId);
            }
            if (e.target.matches('.modal')) {
                closeModal(e.target.id);
            }
        });
         document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal[style*="display: block"]');
                if (openModal) {
                    closeModal(openModal.id);
                }
            }
        });
    }

    /**
     * モーダルを閉じる
     */
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
        if (modalId === 'graphModal' && currentChart) {
            currentChart.destroy();
            currentChart = null;
        }
    }
    
    /**
     * PHPのフォーマット関数をJSで再現
     */
    function format_yen(amount) {
        if (amount >= 1000000) {
            return `¥${(amount / 1000000).toFixed(2)}M`;
        } else if (amount >= 1000) {
            return `¥${(amount / 1000).toFixed(1)}K`;
        }
        return `¥${amount.toLocaleString()}`;
    }

    /**
     * 詳細モーダル表示
     */
    function showDetails(customerName) {
        const modal = document.getElementById('detailModal');
        const title = document.getElementById('detailTitle');
        const content = document.getElementById('detailContent');
        if (!modal || !title || !content) return;

        const customer = customerData.find(c => c.customer_name === customerName);
        title.textContent = `${customerName} の詳細情報`;
        
        if (customer) {
            content.innerHTML = `
                <p><strong>顧客名:</strong> ${escapeHtml(customer.customer_name)}</p>
                <p><strong>総売上:</strong> ${customer.total_sales_text}</p>
                <p><strong>平均リードタイム:</strong> ${customer.avg_lead_time}</p>
                <p><strong>配達回数:</strong> ${customer.delivery_count.toLocaleString()} 回</p>
            `;
        } else {
            content.innerHTML = '<p>詳細情報が見つかりませんでした。</p>';
        }
        modal.style.display = 'block';
    }

    /**
     * グラフモーダル表示
     */
    function showGraph(customerName) {
        const customer = customerData.find(c => c.customer_name === customerName);
        const customerNo = customer ? customer.customer_no : Math.floor(Math.random() * 1000);
        showSalesGraph(customerNo, customerName);
    }

    /**
     * 売上グラフ表示（モーダル）
     */
    function showSalesGraph(customerNo, customerName) {
        const modal = document.getElementById('graphModal');
        const modalTitle = document.getElementById('graphTitle');
        const canvas = document.getElementById('modalCanvas');
        if (!modal || !modalTitle || !canvas) return;

        modalTitle.textContent = `${escapeHtml(customerName)} - 売上推移グラフ`;
        
        // サンプルデータでグラフ描画
        const salesHistory = generateSalesHistory();
        const chartCtx = canvas.getContext('2d');
        if (currentChart) {
            currentChart.destroy();
        }
        currentChart = new Chart(chartCtx, createIndividualSalesChartConfig(salesHistory));
        
        modal.style.display = 'block';
    }

    /**
     * 個人売上グラフの設定を生成
     */
    function createIndividualSalesChartConfig(salesHistory) {
        const labels = salesHistory.map(item => item.month);
        const data = salesHistory.map(item => item.sales);

        return {
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
                    pointRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(47, 93, 63, 0.9)',
                        callbacks: {
                            label: context => `売上: ${format_yen(context.parsed.y)}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => format_yen(value) }
                    }
                }
            }
        };
    }

    /**
     * 売上履歴データ生成（サンプル）
     */
    function generateSalesHistory() {
        const months = ['1月', '2月', '3月', '4月', '5月', '6月'];
        return months.map(month => ({
            month: month,
            sales: Math.floor(Math.random() * 800000) + 50000
        }));
    }
    
    // ========== セキュリティ機能 ==========
    
    /**
     * 外部スクリプトの制限
     */
    function restrictExternalScripts() {
        const allowedDomains = ['cdnjs.cloudflare.com', 'cdn.jsdelivr.net'];
        const scripts = document.querySelectorAll('script[src]');
        
        scripts.forEach(function(script) {
            const src = script.getAttribute('src');
            if (src && !allowedDomains.some(domain => src.includes(domain))) {
                script.remove();
                console.warn('Blocked potentially unsafe script:', src);
            }
        });
    }
    
    /**
     * CSP違反の監視
     */
    function setupSecurityMonitoring() {
        document.addEventListener('securitypolicyviolation', function(e) {
            console.warn('CSP Violation:', {
                directive: e.violatedDirective,
                blockedURI: e.blockedURI,
                lineNumber: e.lineNumber,
                columnNumber: e.columnNumber
            });
        });
    }
    
    /**
     * グローバルエラーハンドラー
     */
    function setupErrorHandling() {
        window.addEventListener('error', function(event) {
            console.error('JavaScript Error:', event.error);
            
            // ユーザーに表示するかどうかは環境に応じて判断
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.warn('Development mode: Error details logged to console');
            }
        });
    
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled Promise Rejection:', event.reason);
            
            // 重要なエラーの場合はユーザーに通知
            if (event.reason && event.reason.message && event.reason.message.includes('fetch')) {
                showErrorMessage('ネットワークエラーが発生しました。しばらく経ってから再度お試しください。');
            }
        });
    }
    
    // ========== ヘッダー管理機能の初期化 ==========
    
    /**
     * ヘッダー管理機能の初期化
     */
    function initializeHeaderManager() {
        // 初期タイトル設定
        updateHeaderTitle();
        addPageTransitionEffect();
        
        // popstate イベント（ブラウザの戻る/進むボタン）に対応
        window.addEventListener('popstate', function() {
            setTimeout(() => {
                updateHeaderTitle();
                addPageTransitionEffect();
            }, 50);
        });
    
        // ページ読み込み完了後の処理
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateHeaderTitle);
        }
    
        // ブレッドクラムの作成（オプション）
        createBreadcrumb();
    }
    
    // ========== パフォーマンス最適化 ==========
    
    /**
     * 画像の遅延読み込み
     */
    function initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });
    
            document.querySelectorAll('img[data-src]').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }
    
    /**
     * CSSアニメーションの最適化
     */
    function optimizeAnimations() {
        // アニメーションの preference を確認
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('reduced-motion');
        }
    
        // パフォーマンス監視
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(function() {
                // アイドル時間に非重要なアニメーションを設定
                document.querySelectorAll('.animate-on-idle').forEach(function(element) {
                    element.classList.add('animate');
                });
            });
        }
    }
    
    // ========== メイン初期化関数 ==========
    
    /**
     * アプリケーション全体の初期化
     */
    function initializeApp() {
        try {
            // セキュリティ監視の設定
            setupSecurityMonitoring();
            restrictExternalScripts();
    
            // エラーハンドリングの設定
            setupErrorHandling();
    
            // ヘッダー管理機能の初期化
            initializeHeaderManager();
    
            // メニューの初期化
            initializeMenu();
    
            // スクロール効果の初期化
            initializeScrollEffects();
    
            // 顧客アップロード機能の初期化（該当ページのみ）
            if (document.getElementById('fileUploadArea')) {
                initializeCustomerUpload();
            }
    
            // 統計情報ページの初期化（該当ページのみ）
            if (document.querySelector('.statistics-table, .enhanced-statistics-table') || 
                window.location.pathname.includes('/statistics/')) {
                initializeStatisticsPage();
            }
    
            // メニューボタンの効果（メニューページのみ）
            if (document.querySelector('.menu-button')) {
                enhanceMenuButtons();
            }
    
            // パフォーマンス最適化
            initializeLazyLoading();
            optimizeAnimations();
    
            // 初期化完了の通知
            console.log('MBS_B System: All modules initialized successfully');
    
            // カスタムイベントの発火
            window.dispatchEvent(new CustomEvent('appInitialized', {
                detail: { timestamp: new Date().toISOString() }
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
            updateHeaderTitle();
            updateActiveNavItem();
        }
    });
    
    // ========== 公開API ==========
    
    // グローバル関数として公開（後方互換性のため）
    window.selectedStore = selectedStore;
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.showSalesGraph = showSalesGraph;
    
    // HeaderManager API
    window.HeaderManager = {
        updateTitle: updateHeaderTitle,
        updateActiveNav: updateActiveNavItem,
        addTransitionEffect: addPageTransitionEffect,
        setStoreName: setStoreName,
        setCustomPageInfo: setCustomPageInfo,
        getCurrentPageInfo: getCurrentPageInfo,
        
        // ユーティリティ
        getStoreName: () => {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('store') || 
                   document.documentElement.getAttribute('data-store-name') || '';
        },
        
        // イベントリスナー
        onTitleUpdate: (callback) => {
            window.addEventListener('headerTitleUpdated', callback);
        }
    };
    
    // ========== 下位互換性のためのレガシー関数 ==========
    
    /**
     * レガシーサポート：古いコードとの互換性を保持
     */
    
    // 古いsortTable関数のエイリアス
    window.sortTable = sortTable;
    
    // 古いhandleSort関数のエイリアス
    window.handleSort = function(event) {
        const button = event.target;
        const column = button.getAttribute('data-column');
        const order = button.getAttribute('data-order');
        if (column && order) {
            sortTable(column, order, button);
        }
    };
    
    // 古いgenerateSampleData関数のエイリアス
    window.generateSampleData = generateSampleData;
    
    // フィルターテーブル機能（検索機能の別名）
    window.filterTable = function(searchTerm) {
        const tbody = document.getElementById('customerTableBody') || 
                     document.querySelector('.enhanced-statistics-table tbody') ||
                     document.querySelector('.statistics-table tbody');
    
        if (!tbody) return;
    
        const rows = tbody.querySelectorAll('.enhanced-table-row, .table-row, tr');
        let visibleCount = 0;
    
        rows.forEach(function(row) {
            const customerNameCell = row.querySelector('[data-column="customer_name"]');
            if (!customerNameCell) return;
    
            const customerName = customerNameCell.textContent.toLowerCase();
            const isVisible = searchTerm === '' || customerName.includes(searchTerm.toLowerCase());
    
            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    
        return visibleCount;
    };
    
    // ========== デバッグ用機能（開発環境のみ） ==========
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        // 開発者向けのヘルプメッセージ
        console.log('%cMBS_B Development Mode', 'color: #2f5d3f; font-size: 16px; font-weight: bold;');
        console.log('Available functions:');
        console.log('- HeaderManager.updateTitle() - ヘッダータイトルを更新');
        console.log('- HeaderManager.setStoreName("店舗名") - 店舗名を設定');
        console.log('- HeaderManager.setCustomPageInfo("ページ名", "🔧") - カスタムページ情報を設定');
        console.log('- selectedStore("店舗名") - 店舗を選択');
        console.log('- openModal("sales|delivery|leadtime") - 統計グラフモーダルを開く');
        console.log('- closeModal() - モーダルを閉じる');
        console.log('- showSalesGraph(customerNo, "顧客名") - 売上グラフを表示');
        console.log('- sortTable("column", "order", button) - テーブルソート');
        console.log('- filterTable("検索語") - テーブルフィルター');
        console.log('- generateSampleData() - サンプルデータ生成');
    
        // パフォーマンス監視
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData) {
                        console.log('Page load performance:', {
                            'DOM Content Loaded': Math.round(perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart) + 'ms',
                            'Load Complete': Math.round(perfData.loadEventEnd - perfData.loadEventStart) + 'ms',
                            'Total Load Time': Math.round(perfData.loadEventEnd - perfData.fetchStart) + 'ms'
                        });
                    }
                }, 1000);
            });
        }
    
        // デバッグ情報の表示
        window.MBS_DEBUG = {
            customerData: function() { return customerData; },
            currentChart: function() { return currentChart; },
            sampleDataGenerated: function() { return sampleDataGenerated; },
            validateInput: validateInput,
            sanitizeInput: sanitizeInput,
            escapeHtml: escapeHtml,
            parseLeadTimeToSeconds: parseLeadTimeToSeconds,
            formatFileSize: function(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
        };
    
        console.log('Debug tools available in window.MBS_DEBUG');
    }
    
    // ========== CSVアップロード機能の追加サポート ==========
    
    /**
     * CSV形式の検証（追加機能）
     */
    function validateCSVFormat(fileContent) {
        // 基本的なCSV形式チェック
        const lines = fileContent.split('\n');
        if (lines.length < 2) {
            return { valid: false, error: 'CSVファイルにデータが含まれていません。' };
        }
    
        // ヘッダー行の確認
        const headerLine = lines[0].trim();
        if (!headerLine) {
            return { valid: false, error: 'ヘッダー行が見つかりません。' };
        }
    
        const headers = headerLine.split(',');
        if (headers.length < 9) {
            return { valid: false, error: 'CSVファイルの列数が不足しています。最低9列必要です。' };
        }
    
        return { valid: true };
    }
    
    /**
     * CSVプレビュー機能
     */
    function previewCSV(fileContent, maxRows = 5) {
        const lines = fileContent.split('\n').slice(0, maxRows + 1); // ヘッダー + データ行
        const preview = [];
    
        lines.forEach(function(line, index) {
            if (line.trim()) {
                const columns = line.split(',').map(col => col.trim());
                preview.push({
                    rowNumber: index,
                    isHeader: index === 0,
                    columns: columns
                });
            }
        });
    
        return preview;
    }
    
    // CSVサポート関数をグローバルに公開（開発環境のみ）
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        window.MBS_CSV = {
            validateFormat: validateCSVFormat,
            preview: previewCSV
        };
    }
    
    // ========== アクセシビリティサポート機能 ==========
    
    /**
     * キーボードナビゲーションの強化
     */
    function enhanceKeyboardNavigation() {
        // テーブル内のキーボードナビゲーション
        const tables = document.querySelectorAll('.enhanced-statistics-table, .statistics-table');
        tables.forEach(function(table) {
            table.addEventListener('keydown', function(e) {
                if (e.target.matches('.sort-btn, .graph-btn')) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        e.target.click();
                    }
                }
            });
        });
    
        // フォーカス管理の改善
        document.addEventListener('focusin', function(e) {
            if (e.target.matches('.sort-btn, .graph-btn')) {
                e.target.setAttribute('tabindex', '0');
            }
        });
    }
    
    /**
     * スクリーンリーダー用の追加情報
     */
    function enhanceScreenReaderSupport() {
        // テーブルの説明を追加
        const tables = document.querySelectorAll('.enhanced-statistics-table, .statistics-table');
        tables.forEach(function(table) {
            if (!table.getAttribute('aria-describedby')) {
                const description = document.createElement('div');
                description.id = 'table-description-' + Date.now();
                description.className = 'sr-only';
                description.textContent = 'このテーブルは顧客の統計情報を表示します。列見出しのソートボタンで並び替えができます。';
                table.parentNode.insertBefore(description, table);
                table.setAttribute('aria-describedby', description.id);
            }
        });
    }
    
    // アクセシビリティ機能の初期化を追加
    document.addEventListener('DOMContentLoaded', function() {
        enhanceKeyboardNavigation();
        enhanceScreenReaderSupport();
    });
    
    // ========== 最終的な初期化確認 ==========
    
    // すべての機能が正常に読み込まれたことを確認
    setTimeout(function() {
        if (typeof window.selectedStore === 'function' &&
            typeof window.openModal === 'function' &&
            typeof window.closeModal === 'function' &&
            typeof window.showSalesGraph === 'function') {
            
            console.log('✅ MBS_B System: All functions loaded successfully');
            
            // 初期化完了イベントを発火
            window.dispatchEvent(new CustomEvent('mbsSystemReady', {
                detail: {
                    version: '2.0.0',
                    modules: [
                        'HeaderManager',
                        'MenuSystem',
                        'FileUpload',
                        'Statistics',
                        'Security',
                        'Accessibility'
                    ]
                }
            }));
        } else {
            console.warn('⚠️ MBS_B System: Some functions may not be loaded correctly');
        }
    }, 100);
    
    })();