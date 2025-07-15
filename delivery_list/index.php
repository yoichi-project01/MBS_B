<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';

$sampleDeliveries = [
    ['id' => 1, 'customer_name' => '木村 紗希', 'status' => 'completed', 'completed' => 3, 'total' => 3],
    ['id' => 2, 'customer_name' => '桜井株式会社', 'status' => 'partial', 'completed' => 2, 'total' => 3],
    ['id' => 3, 'customer_name' => 'カフェ ドルチェビータ', 'status' => 'completed', 'completed' => 1, 'total' => 1],
    ['id' => 4, 'customer_name' => '喫茶店 フレーバー', 'status' => 'completed', 'completed' => 5, 'total' => 5],
    ['id' => 5, 'customer_name' => '木下萌', 'status' => 'partial', 'completed' => 3, 'total' => 4],
    ['id' => 6, 'customer_name' => 'コーヒーハウス レインボー', 'status' => 'completed', 'completed' => 3, 'total' => 3],
];

$sampleCustomers = [
    '木村紗希',
    'カフェ ドルチェビータ',
    '喫茶店 フレーバー',
    '木下萌',
    '川上里奈',
    'コーヒーハウス レインボー',
    '桜井株式会社'
];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>納品書管理 - <?php echo htmlspecialchars($storeName ?? 'MBS'); ?></title>
    <meta name="description" content="納品書管理システム。納品状況の確認、新規納品書の作成が可能です。">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/delivery.css">
    
    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="with-header delivery-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="delivery-list-container">
                    
                    <!-- 検索・フィルタリング -->
                    <div class="delivery-search-section">
                        <div class="search-container">
                            <input type="text" class="search-input" placeholder="顧客名で検索..." id="searchInput">
                            <button class="search-btn" data-action="searchDeliveries">
                                <i class="fas fa-search"></i> 検索
                            </button>
                        </div>
                        <div class="delivery-header-info">
                            <h2 class="delivery-title">
                                <i class="fas fa-truck"></i> 納品書管理
                            </h2>
                            <p class="delivery-subtitle">納品状況の管理と新規納品書の作成</p>
                        </div>
                        <div class="delivery-actions">
                            <button class="btn-create-delivery" data-action="showCustomerSelect">
                                <i class="fas fa-plus"></i> 新規納品書
                            </button>
                        </div>
                    </div>

                    <!-- 納品書一覧テーブル -->
                    <div class="table-view-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-col">選択</th>
                                    <th>顧客名</th>
                                    <th>ステータス</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="deliveryTableBody">
                                <?php foreach ($sampleDeliveries as $delivery): ?>
                                <tr data-customer-name="<?php echo htmlspecialchars($delivery['customer_name']); ?>">
                                    <td class="checkbox-col">
                                        <input type="checkbox" value="<?php echo $delivery['id']; ?>">
                                    </td>
                                    <td><?php echo htmlspecialchars($delivery['customer_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $delivery['status']; ?>">
                                            <?php
                                                if ($delivery['status'] === 'completed') {
                                                    echo "納品済み {$delivery['completed']}/{$delivery['total']}";
                                                } else {
                                                    echo "納品未完了 {$delivery['completed']}/{$delivery['total']}";
                                                }
                                                ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline" data-action="showDeliveryDetail" data-customer-name="<?php echo htmlspecialchars($delivery['customer_name']); ?>">
                                            <i class="fas fa-eye"></i> 詳細
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="pagination-controls">
                            <button class="pagination-btn" id="prevBtn"><i class="fas fa-chevron-left"></i> 前へ</button>
                            <span class="page-info">1/4</span>
                            <button class="pagination-btn" id="nextBtn">次へ <i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay customer-select" id="customerSelect">
        <div class="modal-content customer-modal">
            <button class="close-modal" data-action="hideCustomerSelect">&times;</button>
            <h2><i class="fas fa-user-plus"></i> 顧客名を選択してください</h2>
            <input type="text" class="search-input" placeholder="検索..." id="customerSearchInput">

            <div class="customer-list" id="customerList">
                <?php foreach ($sampleCustomers as $customer): ?>
                <div class="customer-item" data-customer-name="<?php echo htmlspecialchars($customer); ?>">
                    <?php echo htmlspecialchars($customer); ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="modal-actions">
                <button class="btn btn-primary" data-action="confirmCustomerSelection">
                    <i class="fas fa-check"></i> 顧客選択決定
                </button>
            </div>
        </div>
    </div>

    <!-- 書籍選択モーダル -->
    <div class="modal-overlay book-selection" id="bookSelection">
        <div class="modal-content book-modal">
            <button class="close-modal" data-action="hideBookSelection">&times;</button>
            <h2><i class="fas fa-book"></i> 納品書籍選択</h2>
            <p class="selected-customer">顧客: <span id="selectedCustomerName"></span></p>
            
            <div class="book-search-section">
                <input type="text" class="search-input" placeholder="書籍名で検索..." id="bookSearchInput">
                <button class="search-btn" onclick="filterBookList()">
                    <i class="fas fa-search"></i> 検索
                </button>
            </div>

            <div class="book-list-container">
                <h3>注文済み書籍から選択</h3>
                <div class="book-list" id="bookList">
                    <!-- 動的に生成される書籍リスト -->
                </div>
            </div>

            <div class="selected-books-section">
                <h3>選択済み書籍 (<span id="selectedBookCount">0</span>件)</h3>
                <div class="selected-books-list" id="selectedBooksList">
                    <!-- 選択された書籍が表示される -->
                </div>
                <div class="delivery-total">
                    <strong>納品予定合計: <span id="deliveryTotal">¥0</span></strong>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary" data-action="hideBookSelection">
                    <i class="fas fa-times"></i> キャンセル
                </button>
                <button class="btn btn-primary" data-action="confirmBookSelection" id="confirmBooksBtn" disabled>
                    <i class="fas fa-check"></i> 納品書作成
                </button>
            </div>
        </div>
    </div>

    <div class="modal-overlay delivery-detail" id="deliveryDetail">
        <div class="modal-content delivery-modal">
            <button class="close-modal" data-action="hideDeliveryDetail">&times;</button>

            <div class="delivery-header">
                <h2><i class="fas fa-file-invoice"></i> 納品書 No. <span id="deliveryNo">1</span></h2>
                <div class="delivery-info-grid">
                    <div class="info-item">
                        <span class="info-label">登録日:</span>
                        <span id="registrationDate">2022/11/25</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">顧客名:</span>
                        <span id="detailCustomerName">木村 紗希</span>
                    </div>
                </div>
            </div>

            <div class="detail-table-container">
                <table class="table detail-table">
                    <thead>
                        <tr>
                            <th class="checkbox-col">選択</th>
                            <th>品名</th>
                            <th>数量</th>
                            <th>単価</th>
                            <th>金額</th>
                        </tr>
                    </thead>
                    <tbody id="deliveryDetailBody">
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" checked></td>
                            <td>週間BCN　10月号</td>
                            <td>1</td>
                            <td class="text-right">¥1,100</td>
                            <td class="text-right">¥1,210</td>
                        </tr>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox" checked></td>
                            <td>日経コンピューター　10月号</td>
                            <td>2</td>
                            <td class="text-right">¥1,000</td>
                            <td class="text-right">¥2,200</td>
                        </tr>
                        <tr>
                            <td class="checkbox-col"><input type="checkbox"></td>
                            <td>週間マガジン　10月号</td>
                            <td>1</td>
                            <td class="text-right">¥800</td>
                            <td class="text-right">¥880</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="total-section card">
                <div class="total-row">
                    <span class="total-label">合計金額:</span>
                    <span class="total-amount" id="totalAmount">¥4,290</span>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary" data-action="saveDelivery">
                    <i class="fas fa-save"></i> 保存
                </button>
                <button class="btn btn-primary" data-action="printDelivery">
                    <i class="fas fa-print"></i> 印刷
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    
    <script>
    // 納品書ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        // 検索機能
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.querySelector('[data-action="searchDeliveries"]');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', performSearch);
        }
        
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
        }
        
        // 新規納品書作成ボタン
        const createBtn = document.querySelector('[data-action="showCustomerSelect"]');
        if (createBtn) {
            createBtn.addEventListener('click', showCustomerSelect);
        }
        
        // 詳細ボタン
        document.querySelectorAll('[data-action="showDeliveryDetail"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const customerName = this.dataset.customerName;
                showDeliveryDetail(customerName);
            });
        });
        
        // モーダル閉じるボタン
        document.querySelectorAll('[data-action="hideCustomerSelect"]').forEach(btn => {
            btn.addEventListener('click', hideCustomerSelect);
        });
        
        document.querySelectorAll('[data-action="hideDeliveryDetail"]').forEach(btn => {
            btn.addEventListener('click', hideDeliveryDetail);
        });
        
        // 顧客選択確定ボタン
        const confirmBtn = document.querySelector('[data-action="confirmCustomerSelection"]');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', confirmCustomerSelection);
        }
        
        // 保存・印刷ボタン
        const saveBtn = document.querySelector('[data-action="saveDelivery"]');
        const printBtn = document.querySelector('[data-action="printDelivery"]');
        
        if (saveBtn) {
            saveBtn.addEventListener('click', saveDelivery);
        }
        
        if (printBtn) {
            printBtn.addEventListener('click', printDelivery);
        }
        
        // 書籍選択モーダル関連のイベントリスナー
        const confirmBooksBtn = document.querySelector('[data-action=\"confirmBookSelection\"]');
        if (confirmBooksBtn) {
            confirmBooksBtn.addEventListener('click', confirmBookSelection);
        }
        
        document.querySelectorAll('[data-action=\"hideBookSelection\"]').forEach(btn => {
            btn.addEventListener('click', hideBookSelection);
        });
        
        // 顧客選択機能
        initializeCustomerSelection();
        
        // 顧客検索機能
        const customerSearchInput = document.getElementById('customerSearchInput');
        if (customerSearchInput) {
            customerSearchInput.addEventListener('input', function() {
                filterCustomerList(this.value);
            });
        }
        
        // ページネーション
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                navigateToPage('prev');
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                navigateToPage('next');
            });
        }
        
        // 初期ページネーション状態を設定
        updatePaginationState();
        
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('delivery-page-loaded');
        }
    });
    
    // 顧客選択機能の初期化
    function initializeCustomerSelection() {
        document.querySelectorAll('.customer-item').forEach(item => {
            item.addEventListener('click', function() {
                // 既存の選択を解除
                document.querySelectorAll('.customer-item').forEach(i => i.classList.remove('selected'));
                // 新しい選択を追加
                this.classList.add('selected');
                
                // 視覚的フィードバック
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        });
    }
    
    // 顧客リストフィルタリング
    function filterCustomerList(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const customerItems = document.querySelectorAll('.customer-item');
        let visibleCount = 0;
        
        customerItems.forEach(item => {
            const customerName = item.textContent.toLowerCase();
            if (customerName.includes(term) || term === '') {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
                // 非表示になる項目が選択されていた場合、選択を解除
                if (item.classList.contains('selected')) {
                    item.classList.remove('selected');
                }
            }
        });
        
        // 検索結果がない場合のメッセージ
        updateCustomerSearchMessage(visibleCount);
    }
    
    // 顧客検索結果メッセージの更新
    function updateCustomerSearchMessage(visibleCount) {
        const customerList = document.getElementById('customerList');
        let noResultsMsg = document.getElementById('customerNoResults');
        
        if (visibleCount === 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'customerNoResults';
                noResultsMsg.style.cssText = 'text-align: center; padding: 20px; color: #666; font-style: italic;';
                noResultsMsg.textContent = '該当する顧客が見つかりません';
                customerList.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = 'block';
        } else {
            if (noResultsMsg) {
                noResultsMsg.style.display = 'none';
            }
        }
    }
    
    // 検索機能
    function performSearch() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;
        
        const searchTerm = searchInput.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#deliveryTableBody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const customerName = row.dataset.customerName ? row.dataset.customerName.toLowerCase() : '';
            const statusText = row.querySelector('.status-badge') ? row.querySelector('.status-badge').textContent.toLowerCase() : '';
            
            if (customerName.includes(searchTerm) || statusText.includes(searchTerm) || searchTerm === '') {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // 検索結果が0件の場合の表示
        updateNoResultsMessage(visibleCount);
        
        // 検索後はページを1に戻す
        currentPage = 1;
        updatePaginationState();
    }
    
    // 検索結果なしメッセージの更新
    function updateNoResultsMessage(visibleCount) {
        let noResultsRow = document.getElementById('noResultsRow');
        const tableBody = document.getElementById('deliveryTableBody');
        
        if (visibleCount === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = '<td colspan="4" style="text-align: center; padding: 40px; color: #666;">検索結果が見つかりませんでした。</td>';
                tableBody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else {
            if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }
    }
    
    // 顧客選択モーダル表示
    function showCustomerSelect() {
        const modal = document.getElementById('customerSelect');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.classList.remove('hide');
            document.body.style.overflow = 'hidden';
            
            // アニメーション用のクラス追加
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }
    
    // 顧客選択モーダル非表示
    function hideCustomerSelect() {
        const modal = document.getElementById('customerSelect');
        if (modal) {
            modal.classList.remove('active');
            modal.classList.remove('show');
            modal.classList.add('hide');
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
    }
    
    // 納品書詳細モーダル表示
    function showDeliveryDetail(customerName) {
        const modal = document.getElementById('deliveryDetail');
        const customerNameSpan = document.getElementById('detailCustomerName');
        const deliveryNo = document.getElementById('deliveryNo');
        const registrationDate = document.getElementById('registrationDate');
        const totalAmount = document.getElementById('totalAmount');
        
        if (modal) {
            // 顧客名を設定
            if (customerNameSpan) {
                customerNameSpan.textContent = customerName;
            }
            
            // 納品書番号を設定（仮の値）
            if (deliveryNo) {
                const fakeDeliveryNo = Math.floor(Math.random() * 1000) + 1;
                deliveryNo.textContent = fakeDeliveryNo;
            }
            
            // 登録日を設定
            if (registrationDate) {
                registrationDate.textContent = new Date().toLocaleDateString('ja-JP');
            }
            
            // 合計金額を設定（サンプルデータ）
            if (totalAmount) {
                totalAmount.textContent = '¥4,290';
            }
            
            // 詳細テーブルのサンプルデータを更新
            updateDeliveryDetailTable(customerName);
            
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.classList.remove('hide');
            document.body.style.overflow = 'hidden';
            
            // アニメーション用のクラス追加
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }
    
    // 納品書詳細テーブルの更新
    function updateDeliveryDetailTable(customerName) {
        const detailTableBody = document.getElementById('deliveryDetailBody');
        if (detailTableBody) {
            // サンプルデータで更新
            detailTableBody.innerHTML = `
                <tr>
                    <td class="checkbox-col"><input type="checkbox" checked></td>
                    <td>週間BCN　10月号</td>
                    <td>1</td>
                    <td class="text-right">¥1,100</td>
                    <td class="text-right">¥1,210</td>
                </tr>
                <tr>
                    <td class="checkbox-col"><input type="checkbox" checked></td>
                    <td>日経コンピューター　10月号</td>
                    <td>2</td>
                    <td class="text-right">¥1,000</td>
                    <td class="text-right">¥2,200</td>
                </tr>
                <tr>
                    <td class="checkbox-col"><input type="checkbox"></td>
                    <td>週間マガジン　10月号</td>
                    <td>1</td>
                    <td class="text-right">¥800</td>
                    <td class="text-right">¥880</td>
                </tr>
            `;
        }
    }
    
    // 納品書詳細モーダル非表示
    function hideDeliveryDetail() {
        const modal = document.getElementById('deliveryDetail');
        if (modal) {
            modal.classList.remove('active');
            modal.classList.remove('show');
            modal.classList.add('hide');
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
    }
    
    // 顧客選択確定
    function confirmCustomerSelection() {
        const selectedCustomer = document.querySelector('.customer-item.selected');
        if (selectedCustomer) {
            const customerName = selectedCustomer.textContent.trim();
            console.log('選択された顧客:', customerName);
            hideCustomerSelect();
            
            // 新規納品書作成処理
            createNewDelivery(customerName);
        } else {
            Swal.fire({
                title: 'エラー',
                text: '顧客を選択してください。',
                icon: 'warning',
                confirmButtonColor: '#7ed957',
                confirmButtonText: 'OK'
            });
        }
    }
    
    // 新規納品書作成
    function createNewDelivery(customerName) {
        // 書籍選択モーダルを表示
        showBookSelection(customerName);
    }
    
    // 書籍選択モーダル表示
    function showBookSelection(customerName) {
        const modal = document.getElementById('bookSelection');
        const customerNameSpan = document.getElementById('selectedCustomerName');
        
        if (modal && customerNameSpan) {
            customerNameSpan.textContent = customerName;
            
            // 顧客の注文データを取得して書籍リストを生成
            loadCustomerBooks(customerName);
            
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.classList.remove('hide');
            document.body.style.overflow = 'hidden';
            
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
    }
    
    // 書籍選択モーダル非表示
    function hideBookSelection() {
        const modal = document.getElementById('bookSelection');
        if (modal) {
            modal.classList.remove('active');
            modal.classList.remove('show');
            modal.classList.add('hide');
            
            // 選択をリセット
            resetBookSelection();
            
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
    }
    
    // 顧客の書籍データをロード
    function loadCustomerBooks(customerName) {
        const bookList = document.getElementById('bookList');
        
        // サンプルデータ（実際の実装では顧客の注文データを取得）
        const sampleBooks = [
            {
                id: 1,
                title: '週間BCN 10月号',
                price: 1100,
                ordered: 3,
                delivered: 1,
                remaining: 2
            },
            {
                id: 2,
                title: '日経コンピューター 10月号',
                price: 1000,
                ordered: 5,
                delivered: 2,
                remaining: 3
            },
            {
                id: 3,
                title: '週間マガジン 10月号',
                price: 800,
                ordered: 2,
                delivered: 0,
                remaining: 2
            },
            {
                id: 4,
                title: 'IT雑誌 November Edition',
                price: 1200,
                ordered: 1,
                delivered: 0,
                remaining: 1
            },
            {
                id: 5,
                title: 'Business Weekly',
                price: 950,
                ordered: 4,
                delivered: 1,
                remaining: 3
            }
        ];
        
        // 残りがある書籍のみ表示
        const availableBooks = sampleBooks.filter(book => book.remaining > 0);
        
        if (availableBooks.length === 0) {
            bookList.innerHTML = '<div class="empty-selection">納品可能な書籍がありません</div>';
            return;
        }
        
        bookList.innerHTML = availableBooks.map(book => `
            <div class="book-item" data-book-id="${book.id}">
                <div class="book-info">
                    <div class="book-title">${book.title}</div>
                    <div class="book-details">注文: ${book.ordered}冊 | 既納品: ${book.delivered}冊 | 残り: ${book.remaining}冊</div>
                </div>
                <div class="book-quantity">
                    <span>数量:</span>
                    <input type="number" class="quantity-input" min="1" max="${book.remaining}" value="1" id="qty-${book.id}">
                </div>
                <div class="book-price">¥${book.price.toLocaleString()}</div>
                <button class="add-book-btn" onclick="addBookToDelivery(${book.id}, '${book.title}', ${book.price}, ${book.remaining})">
                    <i class="fas fa-plus"></i> 追加
                </button>
            </div>
        `).join('');
    }
    
    // 選択された書籍の管理
    let selectedBooks = [];
    
    // 書籍を納品リストに追加
    function addBookToDelivery(bookId, title, price, maxQuantity) {
        const quantityInput = document.getElementById(`qty-${bookId}`);
        const quantity = parseInt(quantityInput.value);
        
        if (quantity < 1 || quantity > maxQuantity) {
            Swal.fire({
                title: 'エラー',
                text: `数量は1から${maxQuantity}の間で入力してください。`,
                icon: 'warning',
                confirmButtonColor: '#7ed957'
            });
            return;
        }
        
        // 既に選択されている場合は数量を更新
        const existingIndex = selectedBooks.findIndex(book => book.id === bookId);
        if (existingIndex !== -1) {
            selectedBooks[existingIndex].quantity = quantity;
            selectedBooks[existingIndex].total = quantity * price;
        } else {
            selectedBooks.push({
                id: bookId,
                title: title,
                price: price,
                quantity: quantity,
                total: quantity * price
            });
        }
        
        updateSelectedBooksList();
        updateDeliveryTotal();
        
        // 視覚的フィードバック
        const addBtn = event.target.closest('.add-book-btn');
        addBtn.style.background = '#28a745';
        addBtn.innerHTML = '<i class="fas fa-check"></i> 追加済み';
        setTimeout(() => {
            addBtn.style.background = '';
            addBtn.innerHTML = '<i class="fas fa-plus"></i> 追加';
        }, 1000);
    }
    
    // 選択済み書籍リストの更新
    function updateSelectedBooksList() {
        const selectedBooksList = document.getElementById('selectedBooksList');
        const selectedBookCount = document.getElementById('selectedBookCount');
        const confirmBtn = document.getElementById('confirmBooksBtn');
        
        selectedBookCount.textContent = selectedBooks.length;
        
        if (selectedBooks.length === 0) {
            selectedBooksList.innerHTML = '<div class="empty-selection">書籍を選択してください</div>';
            confirmBtn.disabled = true;
        } else {
            selectedBooksList.innerHTML = selectedBooks.map(book => `
                <div class="selected-book-item">
                    <div class="selected-book-info">
                        <div class="selected-book-title">${book.title}</div>
                        <div class="selected-book-quantity">数量: ${book.quantity}冊 × ¥${book.price.toLocaleString()}</div>
                    </div>
                    <div class="selected-book-total">¥${book.total.toLocaleString()}</div>
                    <button class="remove-book-btn" onclick="removeBookFromDelivery(${book.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
            confirmBtn.disabled = false;
        }
    }
    
    // 書籍を選択から削除
    function removeBookFromDelivery(bookId) {
        selectedBooks = selectedBooks.filter(book => book.id !== bookId);
        updateSelectedBooksList();
        updateDeliveryTotal();
    }
    
    // 納品合計の更新
    function updateDeliveryTotal() {
        const deliveryTotal = document.getElementById('deliveryTotal');
        const total = selectedBooks.reduce((sum, book) => sum + book.total, 0);
        deliveryTotal.textContent = '¥' + total.toLocaleString();
    }
    
    // 書籍選択のリセット
    function resetBookSelection() {
        selectedBooks = [];
        updateSelectedBooksList();
        updateDeliveryTotal();
    }
    
    // 書籍検索フィルタ
    function filterBookList() {
        const searchInput = document.getElementById('bookSearchInput');
        const searchTerm = searchInput.value.toLowerCase().trim();
        const bookItems = document.querySelectorAll('.book-item');
        
        bookItems.forEach(item => {
            const title = item.querySelector('.book-title').textContent.toLowerCase();
            if (title.includes(searchTerm) || searchTerm === '') {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    // 書籍選択確定
    function confirmBookSelection() {
        if (selectedBooks.length === 0) {
            Swal.fire({
                title: 'エラー',
                text: '納品する書籍を選択してください。',
                icon: 'warning',
                confirmButtonColor: '#7ed957'
            });
            return;
        }
        
        const customerName = document.getElementById('selectedCustomerName').textContent;
        const totalAmount = selectedBooks.reduce((sum, book) => sum + book.total, 0);
        
        hideBookSelection();
        
        // 選択された書籍で納品書を作成
        processDeliveryCreationWithBooks(customerName, selectedBooks, totalAmount);
    }
    
    // 書籍選択付き納品書作成処理
    function processDeliveryCreationWithBooks(customerName, selectedBooks, totalAmount) {
        // ローディング表示
        Swal.fire({
            title: '納品書作成中...',
            text: '書籍を処理しています',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        // 新しい納品書IDを生成
        const newDeliveryId = Date.now();
        const currentDate = new Date().toLocaleDateString('ja-JP');
        
        // 成功メッセージを表示
        setTimeout(() => {
            Swal.fire({
                title: '納品書作成完了',
                html: `
                    <p><strong>納品書No.</strong> ${newDeliveryId}</p>
                    <p><strong>顧客名:</strong> ${customerName}</p>
                    <p><strong>納品書籍数:</strong> ${selectedBooks.length}種類</p>
                    <p><strong>合計金額:</strong> ¥${totalAmount.toLocaleString()}</p>
                    <p><strong>作成日:</strong> ${currentDate}</p>
                `,
                icon: 'success',
                confirmButtonColor: '#7ed957',
                confirmButtonText: 'OK'
            }).then(() => {
                // テーブルに新しい行を追加
                addNewDeliveryToTable({
                    id: newDeliveryId,
                    customer_name: customerName,
                    status: 'partial',
                    completed: selectedBooks.length,
                    total: selectedBooks.length,
                    books: selectedBooks
                });
            });
        }, 1500);
    }
    
    // 旧納品書作成処理（互換性のため残す）
    function processDeliveryCreation(customerName) {
        // ローディング表示
        Swal.fire({
            title: '納品書作成中...',
            text: '少々お待ちください',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });
        
        // 新しい納品書IDを生成（実際の実装では次のIDを取得）
        const newDeliveryId = Date.now(); // 仮のID
        const currentDate = new Date().toLocaleDateString('ja-JP');
        
        // 成功メッセージを表示
        setTimeout(() => {
            Swal.fire({
                title: '納品書作成完了',
                html: `
                    <p><strong>納品書No.</strong> ${newDeliveryId}</p>
                    <p><strong>顧客名:</strong> ${customerName}</p>
                    <p><strong>作成日:</strong> ${currentDate}</p>
                `,
                icon: 'success',
                confirmButtonColor: '#7ed957',
                confirmButtonText: 'OK'
            }).then(() => {
                // テーブルに新しい行を追加
                addNewDeliveryToTable({
                    id: newDeliveryId,
                    customer_name: customerName,
                    status: 'partial',
                    completed: 0,
                    total: 1
                });
            });
        }, 1500);
    }
    
    // 新しい納品書をテーブルに追加
    function addNewDeliveryToTable(delivery) {
        const tableBody = document.getElementById('deliveryTableBody');
        const newRow = document.createElement('tr');
        newRow.dataset.customerName = delivery.customer_name;
        
        newRow.innerHTML = `
            <td class="checkbox-col">
                <input type="checkbox" value="${delivery.id}">
            </td>
            <td>${delivery.customer_name}</td>
            <td>
                <span class="status-badge status-${delivery.status}">
                    納品未完了 ${delivery.completed}/${delivery.total}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-outline" data-action="showDeliveryDetail" data-customer-name="${delivery.customer_name}">
                    <i class="fas fa-eye"></i> 詳細
                </button>
            </td>
        `;
        
        // 新しい行にイベントリスナーを追加
        const detailBtn = newRow.querySelector('[data-action="showDeliveryDetail"]');
        if (detailBtn) {
            detailBtn.addEventListener('click', function() {
                const customerName = this.dataset.customerName;
                showDeliveryDetail(customerName);
            });
        }
        
        // テーブルの先頭に追加
        tableBody.insertBefore(newRow, tableBody.firstChild);
        
        // ページネーションを更新
        updatePaginationState();
        
        // 新しい行をハイライト
        newRow.style.backgroundColor = 'rgba(126, 217, 87, 0.2)';
        setTimeout(() => {
            newRow.style.backgroundColor = '';
            newRow.style.transition = 'background-color 1s ease';
        }, 2000);
    }
    
    // 納品書保存
    function saveDelivery() {
        // 保存処理を実装
        console.log('納品書を保存します');
        alert('納品書を保存しました。');
    }
    
    // 納品書印刷
    function printDelivery() {
        // 印刷処理を実装
        console.log('納品書を印刷します');
        window.print();
    }
    
    // ページネーション変数
    let currentPage = 1;
    const itemsPerPage = 5; // 1ページあたりの表示件数
    
    // ページネーション処理
    function navigateToPage(direction) {
        const allRows = Array.from(document.querySelectorAll('#deliveryTableBody tr:not(#noResultsRow)'));
        const totalPages = Math.ceil(allRows.length / itemsPerPage);
        
        if (direction === 'prev' && currentPage > 1) {
            currentPage--;
        } else if (direction === 'next' && currentPage < totalPages) {
            currentPage++;
        }
        
        displayCurrentPage(allRows);
        updatePaginationState();
    }
    
    // 現在のページを表示
    function displayCurrentPage(allRows) {
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        allRows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // ページネーション状態を更新
    function updatePaginationState() {
        const allRows = Array.from(document.querySelectorAll('#deliveryTableBody tr:not(#noResultsRow)'));
        const totalPages = Math.ceil(allRows.length / itemsPerPage);
        
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const pageInfo = document.querySelector('.page-info');
        
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }
        
        if (pageInfo) {
            pageInfo.textContent = `${currentPage}/${totalPages || 1}`;
        }
        
        // 初期表示
        if (allRows.length > 0) {
            displayCurrentPage(allRows);
        }
    }
    
    // モーダル外クリックで閉じる
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
    </script>
</body>
</html>