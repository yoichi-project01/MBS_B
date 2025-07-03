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
    const searchTerm = event.target.value.toLowerCase().trim();
    
    // 入力値の検証
    if (!validateInput(searchTerm, 'text', 100)) {
        event.target.value = '';
        showErrorMessage('無効な文字が含まれています。');
        return;
    }

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
 * グラフ描画機能
 */
function openModal(graphType) {
    const modal = document.getElementById('graphModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalCanvas = document.getElementById('modalCanvas');

    if (!modal || !modalTitle || !modalCanvas) return;

    const graphTitles = {
        'sales': '顧客別売上グラフ',
        'delivery': '顧客別配達回数グラフ',
        'leadtime': '顧客別リードタイムグラフ'
    };

    modalTitle.textContent = graphTitles[graphType] || 'グラフ';
    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');

    // モーダルにフォーカスを移動
    modal.focus();

    // 現在のチャートを破棄
    if (currentChart) {
        currentChart.destroy();
        currentChart = null;
    }

    // キャンバスのコンテキストを取得
    const ctx = modalCanvas.getContext('2d');

    // データの準備
    let data, config;
    
    switch(graphType) {
        case 'sales':
            data = prepareSalesData();
            config = createSalesChartConfig(data);
            break;
        case 'delivery':
            data = prepareDeliveryData();
            config = createDeliveryChartConfig(data);
            break;
        case 'leadtime':
            data = prepareLeadTimeData();
            config = createLeadTimeChartConfig(data);
            break;
        default:
            console.error('Unknown graph type:', graphType);
            return;
    }

    // Chart.jsでグラフを描画
    if (typeof Chart !== 'undefined') {
        currentChart = new Chart(ctx, config);
    } else {
        // Chart.jsが利用できない場合の代替処理
        modalCanvas.style.display = 'none';
        const errorMessage = document.createElement('div');
        errorMessage.className = 'chart-error';
        errorMessage.innerHTML = `
            <p>グラフライブラリが読み込まれていません。</p>
            <p>Chart.jsが必要です。</p>
        `;
        modalCanvas.parentNode.appendChild(errorMessage);
    }
}

/**
 * モーダルを閉じる
 */
function closeModal() {
    const modal = document.getElementById('graphModal');
    if (modal) {
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');

        // チャートを破棄
        if (currentChart) {
            currentChart.destroy();
            currentChart = null;
        }

        // エラーメッセージを削除
        const errorMessage = modal.querySelector('.chart-error');
        if (errorMessage) {
            errorMessage.remove();
        }

        // キャンバスを再表示
        const modalCanvas = document.getElementById('modalCanvas');
        if (modalCanvas) {
            modalCanvas.style.display = 'block';
        }
    }
}

/**
 * 売上グラフ表示（セキュリティ強化版）
 */
function showSalesGraph(customerNo, customerName) {
    // 入力値の検証
    if (!Number.isInteger(customerNo) || customerNo <= 0) {
        console.error('Invalid customer number');
        return;
    }

    if (!customerName || typeof customerName !== 'string') {
        console.error('Invalid customer name');
        return;
    }

    // XSS対策
    const sanitizedCustomerName = escapeHtml(customerName);
    
    // サンプルデータ生成
    const salesHistory = generateSalesHistory();

    // モーダルタイトルの設定
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) {
        modalTitle.textContent = `${sanitizedCustomerName} - 売上推移グラフ（過去6ヶ月）`;
    }

    // グラフの作成と表示
    createChart(salesHistory);
    
    const modal = document.getElementById('graphModal');
    if (modal) {
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden', 'false');
        
        // フォーカス管理（アクセシビリティ）
        const closeButton = modal.querySelector('.close');
        if (closeButton) {
            closeButton.focus();
        }
    }
}

/**
 * 売上履歴データ生成（サンプル）
 */
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

/**
 * チャート作成
 */
function createChart(salesHistory) {
    const ctx = document.getElementById('modalCanvas');
    if (!ctx) return;

    const chartCtx = ctx.getContext('2d');

    if (currentChart) {
        currentChart.destroy();
    }

    const labels = salesHistory.map(item => item.month);
    const data = salesHistory.map(item => item.sales);

    if (typeof Chart !== 'undefined') {
        currentChart = new Chart(chartCtx, {
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
}

// グローバル関数として公開
window.openModal = openModal;
window.closeModal = closeModal;
window.showSalesGraph = showSalesGraph;

/**
 * 売上データの準備
 */
function prepareSalesData() {
    if (customerData.length === 0) {
        generateSampleData();
    }

    const sortedData = customerData
        .filter(function(customer) {
            return customer.sales_by_customer > 0;
        })
        .sort(function(a, b) {
            return b.sales_by_customer - a.sales_by_customer;
        })
        .slice(0, 10); // 上位10件

    return {
        labels: sortedData.map(function(customer) {
            return customer.customer_name;
        }),
        values: sortedData.map(function(customer) {
            return customer.sales_by_customer;
        })
    };
}

/**
 * 配達回数データの準備
 */
function prepareDeliveryData() {
    if (customerData.length === 0) {
        generateSampleData();
    }

    const sortedData = customerData
        .filter(function(customer) {
            return customer.delivery_amount > 0;
        })
        .sort(function(a, b) {
            return b.delivery_amount - a.delivery_amount;
        })
        .slice(0, 10); // 上位10件

    return {
        labels: sortedData.map(function(customer) {
            return customer.customer_name;
        }),
        values: sortedData.map(function(customer) {
            return customer.delivery_amount;
        })
    };
}

/**
 * リードタイムデータの準備
 */
function prepareLeadTimeData() {
    if (customerData.length === 0) {
        generateSampleData();
    }

    // リードタイムを秒数に変換してソート
    const dataWithSeconds = customerData.map(function(customer) {
        return {
            ...customer,
            lead_time_seconds: parseLeadTimeToSeconds(customer.lead_time)
        };
    });

    const sortedData = dataWithSeconds
        .filter(function(customer) {
            return customer.lead_time_seconds > 0;
        })
        .sort(function(a, b) {
            return b.lead_time_seconds - a.lead_time_seconds;
        })
        .slice(0, 10); // 上位10件

    return {
        labels: sortedData.map(function(customer) {
            return customer.customer_name;
        }),
        values: sortedData.map(function(customer) {
            return customer.lead_time_seconds / 3600; // 時間単位に変換
        }),
        originalValues: sortedData.map(function(customer) {
            return customer.lead_time;
        })
    };
}

/**
 * 売上チャート設定
 */
function createSalesChartConfig(data) {
    return {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: '売上（円）',
                data: data.values,
                backgroundColor: 'rgba(47, 93, 63, 0.8)',
                borderColor: 'rgba(47, 93, 63, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '顧客別売上ランキング（上位10位）',
                    font: { size: 16, weight: 'bold' },
                    color: '#2f5d3f'
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '売上: ¥' + context.parsed.y.toLocaleString();
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
                        }
                    },
                    title: {
                        display: true,
                        text: '売上（円）'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '顧客名'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    };
}

/**
 * 配達回数チャート設定
 */
function createDeliveryChartConfig(data) {
    return {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                label: '配達回数',
                data: data.values,
                backgroundColor: [
                    'rgba(47, 93, 63, 0.8)',
                    'rgba(126, 217, 87, 0.8)',
                    'rgba(76, 175, 80, 0.8)',
                    'rgba(139, 195, 74, 0.8)',
                    'rgba(156, 204, 101, 0.8)',
                    'rgba(174, 213, 129, 0.8)',
                    'rgba(191, 223, 156, 0.8)',
                    'rgba(209, 233, 184, 0.8)',
                    'rgba(226, 242, 211, 0.8)',
                    'rgba(244, 252, 239, 0.8)'
                ],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '顧客別配達回数（上位10位）',
                    font: { size: 16, weight: 'bold' },
                    color: '#2f5d3f'
                },
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + '回 (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    };
}

/**
 * リードタイムチャート設定
 */
function createLeadTimeChartConfig(data) {
    return {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'リードタイム（時間）',
                data: data.values,
                borderColor: 'rgba(47, 93, 63, 1)',
                backgroundColor: 'rgba(47, 93, 63, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgba(47, 93, 63, 1)',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: '顧客別リードタイム（上位10位）',
                    font: { size: 16, weight: 'bold' },
                    color: '#2f5d3f'
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const index = context.dataIndex;
                            return data.originalValues[index];
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'リードタイム（時間）'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + 'h';
                        }
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '顧客名'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    };
}

/**
 * サンプルデータの生成（デモ用）
 */
function generateSampleData() {
    if (sampleDataGenerated) return;

    const sampleCustomers = [
        '大阪商事株式会社', 'スーパーマーケット田中', '食品卸売り鈴木',
        '飲食店チェーン佐藤', 'コンビニエンスストア高橋', '百貨店伊藤',
        'レストラン山田', 'カフェ渡辺', 'ファミリーレストラン中村',
        '居酒屋小林', '弁当屋加藤', 'パン屋吉田', '肉屋山本', '魚屋松本',
        '八百屋井上', 'ケーキ屋木村', 'アイスクリーム店林', '和菓子店清水',
        'ピザ店森', 'ラーメン店池田'
    ];

    customerData = sampleCustomers.map(function(name, index) {
        return {
            customer_no: index + 1,
            customer_name: name,
            sales_by_customer: Math.floor(Math.random() * 2000000) + 100000,
            lead_time: generateRandomLeadTime(),
            delivery_amount: Math.floor(Math.random() * 50) + 1
        };
    });

    sampleDataGenerated = true;
}

/**
 * ランダムなリードタイムの生成
 */
function generateRandomLeadTime() {
    const types = [
        function() { return Math.floor(Math.random() * 10) + 1 + '日'; },
        function() { return Math.floor(Math.random() * 23) + 1 + '時間'; },
        function() { return Math.floor(Math.random() * 59) + 1 + '分'; },
        function() { 
            const days = Math.floor(Math.random() * 3) + 1;
            const hours = Math.floor(Math.random() * 23) + 1;
            return days + '日' + hours + '時間';
        }
    ];

    const randomType = types[Math.floor(Math.random() * types.length)];
    return randomType();
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

// ========== 公開API（HeaderManager） ==========
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

// ========== エラーハンドリング ==========

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

// ========== セキュリティ機能 ==========

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
 // ========== 統合されたJavaScriptファイル ==========
// MBS_B システム用統合JavaScript（統計情報機能、ヘッダー管理機能を含む）

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
    // ページ識別のためのクラス追加
    if (window.location.pathname.includes('/statistics/')) {
        document.body.classList.add('statistics-page');
    }

    setupStatisticsEventListeners();
    loadExistingData();
    setupStatisticsAccessibility();
    setupGraphButtons();
    setupSortButtons();
}

/**
 * 統計情報ページのイベントリスナー設定
 */
function setupStatisticsEventListeners() {
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
 * グラフボタンのセットアップ
 */
function setupGraphButtons() {
    const graphButtons = document.querySelectorAll('.graph-btn');
    graphButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            
            const customerNo = this.getAttribute('data-customer-no');
            const customerName = this.getAttribute('data-customer-name');
            
            if (customerNo && customerName) {
                // 入力値の検証
                const validatedCustomerNo = parseInt(customerNo);
                if (!Number.isInteger(validatedCustomerNo) || validatedCustomerNo <= 0) {
                    showErrorMessage('無効な顧客番号です。');
                    return;
                }

                if (!validateInput(customerName, 'text', 255)) {
                    showErrorMessage('無効な顧客名です。');
                    return;
                }

                showSalesGraph(validatedCustomerNo, customerName);
            }
        });
    });
}

/**
 * ソートボタンのセットアップ
 */
function setupSortButtons() {
    const sortButtons = document.querySelectorAll('.sort-btn');
    sortButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            
            const column = this.getAttribute('data-column');
            const order = this.getAttribute('data-order');
            
            if (column && order) {
                sortTable(column, order, this);
            }
        });
    });
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
 * テーブルソート機能
 */
function sortTable(column, order, activeButton) {
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
    updateSortButtonState(activeButton);

    // 行の再配置（アニメーション付き）
    animateTableSort(tbody, rows);

    // アクセシビリティ通知
    announceSort(column, order);
}

/**
 * リードタイム文字列を秒数に変換
 */