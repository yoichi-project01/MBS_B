// ========== 統合されたJavaScriptファイル ==========
// MBS_B システム用統合JavaScript（統計情報機能を含む）

(function() {
    'use strict';

    // ========== グローバル変数 ==========
    let currentChart = null;
    let sampleDataGenerated = false;
    let customerData = [];

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

    // ========== 統計情報ページ機能 ==========

    /**
     * 統計情報ページの初期化
     */
    function initializeStatisticsPage() {
        setupStatisticsEventListeners();
        loadExistingData();
        setupStatisticsAccessibility();
    }

    /**
     * 統計情報ページのイベントリスナー設定
     */
    function setupStatisticsEventListeners() {
        // ソートボタン
        document.querySelectorAll('.sort-btn').forEach(function(button) {
            button.addEventListener('click', handleSort);
        });

        // モーダル関連
        const modal = document.getElementById('graphModal');
        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === this) {
                    closeModal();
                }
            });
        }

        // ESCキーでモーダルを閉じる
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modal = document.getElementById('graphModal');
                if (modal && modal.style.display === 'block') {
                    closeModal();
                }
            }
        });

        // 検索フォームの改善
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearchInput, 300));
        }
    }

    /**
     * 既存データの読み込み
     */
    function loadExistingData() {
        const tableRows = document.querySelectorAll('.enhanced-table-row, .table-row');
        customerData = Array.from(tableRows).map(function(row) {
            const customerNo = row.getAttribute('data-customer-no') || Math.floor(Math.random() * 1000);
            const customerNameEl = row.querySelector('[data-column="customer_name"]');
            const salesEl = row.querySelector('[data-column="sales_by_customer"]');
            const leadTimeEl = row.querySelector('[data-column="lead_time"]');
            const deliveryAmountEl = row.querySelector('[data-column="delivery_amount"]');

            if (!customerNameEl) return null;

            const customerName = customerNameEl.textContent.trim();
            const sales = salesEl ? salesEl.textContent.replace(/[,¥]/g, '') : '0';
            const leadTime = leadTimeEl ? leadTimeEl.textContent.trim() : '0秒';
            const deliveryAmount = deliveryAmountEl ? deliveryAmountEl.textContent.trim() : '0';

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
        const tables = document.querySelectorAll('.enhanced-statistics-table, .statistics-table');
        tables.forEach(function(table) {
            table.setAttribute('aria-label', '顧客統計情報テーブル');
        });

        // ソートボタンにaria-labelを追加
        document.querySelectorAll('.sort-btn').forEach(function(button) {
            const column = button.getAttribute('data-column');
            const order = button.getAttribute('data-order');
            if (column && order) {
                const columnNames = {
                    'customer_name': '顧客名',
                    'sales_by_customer': '売上',
                    'lead_time': 'リードタイム',
                    'delivery_amount': '配達回数'
                };
                const orderText = order === 'asc' ? '昇順' : '降順';
                button.setAttribute('aria-label', `${columnNames[column]}を${orderText}でソート`);
            }
        });
    }

    /**
     * ソート処理
     */
    function handleSort(event) {
        const button = event.target;
        const column = button.getAttribute('data-column');
        const order = button.getAttribute('data-order');

        if (!column || !order) return;

        const tbody = document.getElementById('customerTableBody') || 
                     document.querySelector('.enhanced-statistics-table tbody') ||
                     document.querySelector('.statistics-table tbody');

        if (!tbody) return;

        const rows = Array.from(tbody.querySelectorAll('.enhanced-table-row, .table-row, tr'));

        rows.sort(function(a, b) {
            const aCell = a.querySelector('[data-column="' + column + '"]');
            const bCell = b.querySelector('[data-column="' + column + '"]');

            if (!aCell || !bCell) return 0;

            let aValue = aCell.textContent.trim();
            let bValue = bCell.textContent.trim();

            // データ型に応じた処理
            if (column === 'sales_by_customer' || column === 'delivery_amount') {
                aValue = parseFloat(aValue.replace(/[,円¥]/g, '')) || 0;
                bValue = parseFloat(bValue.replace(/[,円¥]/g, '')) || 0;
            } else if (column === 'lead_time') {
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
        updateSortButtonState(button);

        // 行の再配置（アニメーション付き）
        animateTableSort(tbody, rows);

        // アクセシビリティ通知
        announceSort(column, order);
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
    function updateSortButtonState(activeButton) {
        // 全てのボタンからactiveクラスを削除
        document.querySelectorAll('.sort-btn').forEach(function(btn) {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });

        // アクティブボタンにクラスを追加
        activeButton.classList.add('active');
        activeButton.setAttribute('aria-pressed', 'true');
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
            'sales_by_customer': '売上',
            'lead_time': 'リードタイム',
            'delivery_amount': '配達回数'
        };
        const orderText = order === 'asc' ? '昇順' : '降順';
        const message = `${columnNames[column]}を${orderText}でソートしました`;

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
        const searchTerm = event.target.value.toLowerCase();
        const rows = document.querySelectorAll('.enhanced-table-row, .table-row');

        let visibleCount = 0;

        rows.forEach(function(row) {
            const customerNameEl = row.querySelector('[data-column="customer_name"]');
            if (!customerNameEl) return;

            const customerName = customerNameEl.textContent.toLowerCase();
            const isVisible = customerName.includes(searchTerm);

            if (isVisible) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // 検索結果の通知
        if (searchTerm) {
            announceToScreenReader(`${visibleCount}件の顧客が見つかりました`);
        }
    }

    /**
     * サンプルデータ生成
     */
    function generateSampleData() {
        if (sampleDataGenerated) {
            showInfoMessage('サンプルデータについて', 'サンプルデータは既に生成されています。リアルなデータとして売上推移グラフをご確認ください。');
            return;
        }

        showSuccessMessage(
            'サンプルデータ生成完了',
            `<p>サンプルデータを生成しました！</p>
             <p>各顧客の「📊 グラフ」ボタンをクリックして、過去6ヶ月の売上推移をご確認ください。</p>
             <br>
             <small style="color: #666;">※ 実際のデータではなく、デモンストレーション用のサンプルデータです。</small>`
        );

        sampleDataGenerated = true;
    }

    /**
     * 売上グラフの表示
     */
    function showSalesGraph(customerNo, customerName) {
        const salesHistory = generateSalesHistory(customerNo);

        document.getElementById('modalTitle').textContent = `${customerName} - 売上推移グラフ（過去6ヶ月）`;
        createChart(salesHistory);
        
        const modal = document.getElementById('graphModal');
        if (modal) {
            modal.style.display = 'block';
            
            // フォーカス管理
            setTimeout(function() {
                const closeButton = modal.querySelector('.close');
                if (closeButton) {
                    closeButton.focus();
                }
            }, 100);
        }

        // アクセシビリティ通知
        announceToScreenReader(`${customerName}の売上推移グラフを表示しました`);
    }

    /**
     * 売上履歴データの生成（実際のデータに基づいてより現実的に）
     */
    function generateSalesHistory(customerNo) {
        const months = ['7月', '8月', '9月', '10月', '11月', '12月'];
        const history = [];

        // 顧客番号に基づいてシード値を設定（一貫性のあるデータ生成）
        const seed = customerNo || 1;
        
        months.forEach(function(month, index) {
            // より現実的な売上データを生成
            const baseAmount = 100000 + (seed * 1000);
            const seasonalFactor = 1 + Math.sin((index / 12) * Math.PI * 2) * 0.3;
            const randomFactor = 0.7 + (Math.sin(seed + index) + 1) * 0.3;
            
            const sales = Math.floor(baseAmount * seasonalFactor * randomFactor);
            
            history.push({
                month: month,
                sales: Math.max(sales, 50000) // 最低売上を保証
            });
        });

        return history;
    }

    /**
     * Chart.jsを使用したグラフ作成
     */
    function createChart(salesHistory) {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        const context = ctx.getContext('2d');

        // 既存のチャートがあれば破棄
        if (currentChart) {
            currentChart.destroy();
        }

        const labels = salesHistory.map(item => item.month);
        const data = salesHistory.map(item => item.sales);

        // Chart.jsが利用可能かチェック
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js が読み込まれていません');
            return;
        }

        currentChart = new Chart(context, {
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
                        backgroundColor: 'rgba(47, 93, 63, 0.95)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#7ed957',
                        borderWidth: 2,
                        cornerRadius: 8,
                        displayColors: false,
                        titleFont: {
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13
                        },
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

    /**
     * モーダルを閉じる
     */
    function closeModal() {
        const modal = document.getElementById('graphModal');
        if (modal) {
            modal.style.display = 'none';
        }

        if (currentChart) {
            currentChart.destroy();
            currentChart = null;
        }

        // フォーカスを元の場所に戻す
        announceToScreenReader('グラフを閉じました');
    }

    /**
     * テーブルデータのエクスポート機能（CSV）
     */
    function exportTableToCSV() {
        const table = document.querySelector('.enhanced-statistics-table, .statistics-table');
        if (!table) return;

        const rows = Array.from(table.querySelectorAll('tr'));
        const csvContent = rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            return cells.map(cell => {
                const text = cell.textContent.trim();
                // CSVエスケープ処理
                if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                    return '"' + text.replace(/"/g, '""') + '"';
                }
                return text;
            }).join(',');
        }).join('\n');

        // BOMを追加してExcelで正しく開けるようにする
        const BOM = '\uFEFF';
        const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' });

        // ダウンロード実行
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', '統計情報_' + new Date().toISOString().slice(0, 10) + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showSuccessMessage('エクスポート完了', 'CSVファイルのダウンロードが開始されました。');
    }

    /**
     * キーボードナビゲーションの改善
     */
    function enhanceKeyboardNavigation() {
        // テーブル内のキーボードナビゲーション
        const tables = document.querySelectorAll('.enhanced-statistics-table, .statistics-table');
        tables.forEach(function(table) {
            table.addEventListener('keydown', function(event) {
                const focusedElement = document.activeElement;
                
                if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    
                    const currentRow = focusedElement.closest('tr');
                    if (currentRow) {
                        const nextRow = event.key === 'ArrowDown' 
                            ? currentRow.nextElementSibling 
                            : currentRow.previousElementSibling;
                        
                        if (nextRow) {
                            const focusableElement = nextRow.querySelector('button, a, [tabindex]');
                            if (focusableElement) {
                                focusableElement.focus();
                            }
                        }
                    }
                }
            });
        });
    }

    /**
     * ローカルストレージを使用した設定の保存
     */
    function saveUserPreferences() {
        try {
            const preferences = {
                lastSortColumn: null,
                lastSortOrder: null,
                lastSearchTerm: ''
            };

            // ソート状態の保存
            const activeSort = document.querySelector('.sort-btn.active');
            if (activeSort) {
                preferences.lastSortColumn = activeSort.getAttribute('data-column');
                preferences.lastSortOrder = activeSort.getAttribute('data-order');
            }

            // 検索状態の保存
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                preferences.lastSearchTerm = searchInput.value;
            }

            localStorage.setItem('statistics-preferences', JSON.stringify(preferences));
        } catch (e) {
            // ローカルストレージが利用できない場合は何もしない
            console.info('ローカルストレージが利用できません');
        }
    }

    /**
     * 保存された設定の読み込み
     */
    function loadUserPreferences() {
        try {
            const saved = localStorage.getItem('statistics-preferences');
            if (saved) {
                const preferences = JSON.parse(saved);
                
                // ソート状態の復元
                if (preferences.lastSortColumn && preferences.lastSortOrder) {
                    const sortButton = document.querySelector(
                        `.sort-btn[data-column="${preferences.lastSortColumn}"][data-order="${preferences.lastSortOrder}"]`
                    );
                    if (sortButton) {
                        setTimeout(function() {
                            sortButton.click();
                        }, 100);
                    }
                }

                // 検索状態の復元
                if (preferences.lastSearchTerm) {
                    const searchInput = document.querySelector('input[name="search"]');
                    if (searchInput && !searchInput.value) {
                        searchInput.value = preferences.lastSearchTerm;
                        handleSearchInput({ target: searchInput });
                    }
                }
            }
        } catch (e) {
            console.info('保存された設定の読み込みに失敗しました');
        }
    }

    /**
     * ページ離脱時の処理
     */
    function handlePageUnload() {
        saveUserPreferences();
        
        if (currentChart) {
            currentChart.destroy();
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

        // 統計情報ページの機能を初期化（該当要素がある場合のみ）
        if (document.querySelector('.sort-btn') || document.querySelector('#graphModal')) {
            initializeStatisticsPage();
            enhanceKeyboardNavigation();
            loadUserPreferences();
            
            // ページ離脱時の処理
            window.addEventListener('beforeunload', handlePageUnload);
        }
        
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
        showInfoMessage: showInfoMessage,
        showLoadingAnimation: showLoadingAnimation,
        toggleMenu: toggleMenu,
        closeMenu: closeMenu
    };

    // ========== 統計情報ページ用のグローバル関数 ==========
    window.sortTable = handleSort;
    window.showSalesGraph = showSalesGraph;
    window.closeModal = closeModal;
    window.generateSampleData = generateSampleData;
    window.exportTableToCSV = exportTableToCSV;

    /**
     * 統計情報ページ用公開API
     */
    window.StatisticsPage = {
        // 主要機能
        showSalesGraph: showSalesGraph,
        generateSampleData: generateSampleData,
        exportTableToCSV: exportTableToCSV,
        closeModal: closeModal,
        
        // ユーティリティ
        showSuccessMessage: showSuccessMessage,
        showErrorMessage: showErrorMessage,
        showInfoMessage: showInfoMessage,
        
        // データ管理
        getCustomerData: function() { return customerData; },
        getCurrentChart: function() { return currentChart; },
        
        // 設定管理
        saveUserPreferences: saveUserPreferences,
        loadUserPreferences: loadUserPreferences
    };

})();