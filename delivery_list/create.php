<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();

$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';
$success_message = '';
$error_message = '';

// 店舗が選択されていない場合のエラーハンドリング
if (empty($storeName)) {
    $_SESSION['error_message'] = '店舗が選択されていません。店舗選択画面からアクセスしてください。';
    header('Location: /MBS_B/index.php');
    exit;
}

// CSRFトークンの生成
$csrfToken = CSRFProtection::getToken();

// CSPノンスの生成
if (!SessionManager::get('csp_nonce')) {
    SessionManager::set('csp_nonce', bin2hex(random_bytes(16)));
}

try {
    $pdo = db_connect();
    
    // 顧客リストを取得
    $customers_sql = "SELECT customer_no, customer_name FROM customers WHERE store_name = :store_name ORDER BY customer_name";
    $customers_stmt = $pdo->prepare($customers_sql);
    $customers_stmt->bindValue(':store_name', $storeName, PDO::PARAM_STR);
    $customers_stmt->execute();
    $customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "データベースエラー: " . $e->getMessage();
}

// フォーム送信処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRFトークンの検証
        if (!CSRFProtection::validateToken()) {
            throw new Exception('セキュリティトークンが無効です。');
        }
        
        $customer_no = $_POST['customer_no'] ?? '';
        $delivery_date = $_POST['delivery_date'] ?? '';
        $status = 'pending'; // 新規納品書は常に未納品状態
        $delivery_items = $_POST['delivery_items'] ?? [];
        
        // バリデーション
        if (empty($customer_no)) {
            throw new Exception('顧客を選択してください。');
        }
        
        if (empty($delivery_date)) {
            throw new Exception('納品日を入力してください。');
        }
        
        if (empty($delivery_items) || !is_array($delivery_items)) {
            throw new Exception('納品商品を追加してください。');
        }
        
        // 選択された納品商品をフィルタリング
        $valid_items = [];
        foreach ($delivery_items as $item) {
            // 選択されたもので、かつ必要な情報が揃っているもののみ
            if (!empty($item['selected']) && !empty($item['product_name']) && !empty($item['quantity']) && !empty($item['unit_price'])) {
                $valid_items[] = [
                    'product_name' => trim($item['product_name']),
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'description' => trim($item['description'] ?? ''),
                    'remarks' => trim($item['remarks'] ?? ''),
                    'order_item_no' => (int)($item['order_item_no'] ?? 0)
                ];
            }
        }
        
        if (empty($valid_items)) {
            throw new Exception('有効な納品商品がありません。');
        }
        
        // サンプルデータとして処理（実際のDB保存はコメントアウト）
        /*
        // トランザクション開始
        $pdo->beginTransaction();
        
        // 納品書を挿入
        $delivery_sql = "INSERT INTO deliveries (customer_no, delivery_date, status) VALUES (:customer_no, :delivery_date, :status)";
        $delivery_stmt = $pdo->prepare($delivery_sql);
        $delivery_stmt->bindValue(':customer_no', $customer_no, PDO::PARAM_INT);
        $delivery_stmt->bindValue(':delivery_date', $delivery_date, PDO::PARAM_STR);
        $delivery_stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $delivery_stmt->execute();
        
        $delivery_no = $pdo->lastInsertId();
        
        // 納品商品を挿入
        $item_sql = "INSERT INTO delivery_items (delivery_no, product_name, quantity, unit_price, description, remarks) VALUES (:delivery_no, :product_name, :quantity, :unit_price, :description, :remarks)";
        $item_stmt = $pdo->prepare($item_sql);
        
        foreach ($valid_items as $item) {
            $item_stmt->bindValue(':delivery_no', $delivery_no, PDO::PARAM_INT);
            $item_stmt->bindValue(':product_name', $item['product_name'], PDO::PARAM_STR);
            $item_stmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
            $item_stmt->bindValue(':unit_price', $item['unit_price'], PDO::PARAM_STR);
            $item_stmt->bindValue(':description', $item['description'], PDO::PARAM_STR);
            $item_stmt->bindValue(':remarks', $item['remarks'], PDO::PARAM_STR);
            $item_stmt->execute();
        }
        
        $pdo->commit();
        */
        
        // サンプル用の納品書番号生成
        $delivery_no = 'D' . sprintf('%04d', rand(1000, 9999));
        $success_message = "納品書{$delivery_no}を正常に作成しました。";
        
        // 3秒後にリダイレクト
        header("refresh:3;url=index.php?store=" . urlencode($storeName));
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規納品書作成 - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($storeName); ?>で新しい納品書を作成します。顧客選択、商品追加、納品詳細の入力が可能です。">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/MBS_B/assets/css/base.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/header.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/button.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/modal.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/form.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/components/table.css">
    <link rel="stylesheet" href="/MBS_B/assets/css/pages/order.css">
    
    <!-- External Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</head>
<body class="with-header order-create-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="order-create-container">
                    
                    <!-- ヘッダー -->
                    <div class="create-header">
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle"></i> 新規納品書作成
                        </h1>
                        <a href="index.php?store=<?= htmlspecialchars($storeName) ?>" class="back-button">
                            <i class="fas fa-arrow-left"></i> 一覧へ戻る
                        </a>
                    </div>
                    
                    <!-- 成功・エラーメッセージ -->
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- 納品書作成フォーム -->
                    <form method="POST" id="deliveryCreateForm" class="order-form">
                        <?php echo CSRFProtection::getTokenField(); ?>
                        
                        <!-- 基本情報セクション -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-info-circle"></i> 基本情報
                            </h2>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="customer_no" class="form-label">顧客名 <span class="required">*</span></label>
                                    <select name="customer_no" id="customer_no" class="form-select" required>
                                        <option value="">顧客を選択してください</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['customer_no'] ?>" <?= (isset($_POST['customer_no']) && $_POST['customer_no'] == $customer['customer_no']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="delivery_date" class="form-label">納品日 <span class="required">*</span></label>
                                    <input type="date" name="delivery_date" id="delivery_date" class="form-input" 
                                           value="<?= $_POST['delivery_date'] ?? date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 納品商品セクション -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-truck"></i> 納品商品
                            </h2>
                            
                            <div class="order-items-container">
                                <div class="items-header">
                                    <button type="button" id="addItemBtn" class="btn-add-item">
                                        <i class="fas fa-plus"></i> 商品を追加
                                    </button>
                                </div>
                                
                                <div class="customer-products-notice" id="customerProductsNotice" style="display: none;">
                                    <p><i class="fas fa-info-circle"></i> 顧客を選択すると、その顧客の注文済み商品から選択できます。</p>
                                </div>
                                
                                <div id="deliveryItemsList" class="items-list">
                                    <!-- 商品選択アイテムは顧客選択後に表示 -->
                                </div>
                                
                                <div class="order-summary">
                                    <div class="summary-item">
                                        <span class="summary-label">合計商品数:</span>
                                        <span id="totalItems" class="summary-value">1</span>
                                    </div>
                                    <div class="summary-item">
                                        <span class="summary-label">概算合計:</span>
                                        <span id="totalAmount" class="summary-value">¥0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- アクションボタン -->
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i> 納品書を作成
                            </button>
                            <button type="reset" class="btn-reset">
                                <i class="fas fa-undo"></i> リセット
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Files -->
    <script src="/MBS_B/assets/js/main.js" type="module"></script>
    
    <script nonce="<?= htmlspecialchars(SessionManager::get('csp_nonce', '')) ?>">
    let itemCounter = 0;
    let customerProducts = [];
    
    // 納品書作成ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        updateSummary();
        
        // 顧客選択時の処理
        document.getElementById('customer_no').addEventListener('change', function() {
            const customerNo = this.value;
            if (customerNo) {
                loadCustomerProducts(customerNo);
            } else {
                clearProductsList();
                document.getElementById('customerProductsNotice').style.display = 'none';
            }
        });
        
        // 商品追加ボタン（初期状態では非表示）
        document.getElementById('addItemBtn').addEventListener('click', addDeliveryItem);
        document.getElementById('addItemBtn').style.display = 'none';
        
        // フォーム送信時の確認
        document.getElementById('deliveryCreateForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 作成中...';
        });
        
        // 数量変更時のサマリー更新
        document.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('[quantity]')) {
                updateSummary();
            }
        });
        
        // 初期メッセージ表示
        document.getElementById('customerProductsNotice').style.display = 'block';
        
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('delivery-create-page-loaded');
        }
    });
    
    // 顧客の注文商品を取得
    async function loadCustomerProducts(customerNo) {
        try {
            const formData = new FormData();
            formData.append('customer_no', customerNo);
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            const response = await fetch('get_customer_products.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                customerProducts = data.products;
                displayProductSelection();
            } else {
                throw new Error(data.error || '商品情報の取得に失敗しました。');
            }
        } catch (error) {
            alert('エラー: ' + error.message);
            clearProductsList();
        }
    }
    
    // 商品選択画面を表示
    function displayProductSelection() {
        const itemsList = document.getElementById('deliveryItemsList');
        itemsList.innerHTML = '';
        
        if (customerProducts.length === 0) {
            itemsList.innerHTML = '<div class="no-products-message"><p><i class="fas fa-info-circle"></i> この顧客には未納品の注文商品がありません。</p></div>';
            document.getElementById('addItemBtn').style.display = 'none';
            return;
        }
        
        customerProducts.forEach((product, index) => {
            const row = createProductSelectionRow(product, index);
            itemsList.appendChild(row);
        });
        
        document.getElementById('addItemBtn').style.display = 'none'; // 商品は選択式なので追加ボタンは不要
        updateSummary();
    }
    
    // 商品選択行を作成
    function createProductSelectionRow(product, index) {
        const row = document.createElement('div');
        row.className = 'order-item-row';
        row.innerHTML = `
            <div class="item-number">${index + 1}</div>
            <div class="item-fields">
                <div class="field-group">
                    <label class="field-label">
                        <input type="checkbox" name="delivery_items[${index}][selected]" value="1" onchange="toggleProductRow(this)">
                        商品名
                    </label>
                    <div class="product-name">${escapeHtml(product.product_name)}</div>
                    <input type="hidden" name="delivery_items[${index}][product_name]" value="${escapeHtml(product.product_name)}">
                    <input type="hidden" name="delivery_items[${index}][order_item_no]" value="${product.order_item_no}">
                </div>
                <div class="field-group">
                    <label class="field-label">商品説明</label>
                    <div class="product-description">${escapeHtml(product.description || '')}</div>
                    <input type="hidden" name="delivery_items[${index}][description]" value="${escapeHtml(product.description || '')}">
                </div>
                <div class="field-group">
                    <label class="field-label">納品数量 <span class="required">*</span></label>
                    <input type="number" name="delivery_items[${index}][quantity]" class="form-input" 
                           min="1" max="${product.remaining_quantity}" value="1" disabled>
                    <small class="field-help">残り: ${product.remaining_quantity}個</small>
                </div>
                <div class="field-group">
                    <label class="field-label">単価</label>
                    <div class="product-price">¥${product.unit_price.toLocaleString()}</div>
                    <input type="hidden" name="delivery_items[${index}][unit_price]" value="${product.unit_price}">
                </div>
                <div class="field-group full-width">
                    <label class="field-label">備考</label>
                    <input type="text" name="delivery_items[${index}][remarks]" class="form-input" 
                           placeholder="備考（任意）" value="${escapeHtml(product.remarks || '')}" disabled>
                </div>
            </div>
            <div class="order-info">
                <small>注文No: ${product.order_no} | 注文日: ${product.order_date}</small>
            </div>
        `;
        
        return row;
    }
    
    // 商品行の有効/無効を切り替え
    function toggleProductRow(checkbox) {
        const row = checkbox.closest('.order-item-row');
        const inputs = row.querySelectorAll('input:not([type="checkbox"]):not([type="hidden"])');
        
        inputs.forEach(input => {
            input.disabled = !checkbox.checked;
        });
        
        if (!checkbox.checked) {
            // チェックを外した場合は数量を1にリセット
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            if (quantityInput) {
                quantityInput.value = 1;
            }
        }
        
        updateSummary();
    }
    
    // 商品リストをクリア
    function clearProductsList() {
        document.getElementById('deliveryItemsList').innerHTML = '';
        customerProducts = [];
        updateSummary();
    }
    
    // サマリーを更新
    function updateSummary() {
        const checkedRows = document.querySelectorAll('input[name*="[selected]"]:checked');
        let totalItems = 0;
        let totalAmount = 0;
        
        checkedRows.forEach(checkbox => {
            const row = checkbox.closest('.order-item-row');
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            const priceInput = row.querySelector('input[name*="[unit_price]"]');
            
            const quantity = parseInt(quantityInput?.value || 0);
            const price = parseFloat(priceInput?.value || 0);
            
            if (quantity > 0 && price > 0) {
                totalItems += quantity;
                totalAmount += quantity * price;
            }
        });
        
        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('totalAmount').textContent = '¥' + totalAmount.toLocaleString();
    }
    
    // フォームバリデーション
    function validateForm() {
        const customerNo = document.getElementById('customer_no').value;
        const deliveryDate = document.getElementById('delivery_date').value;
        const checkedRows = document.querySelectorAll('input[name*="[selected]"]:checked');
        
        if (!customerNo) {
            alert('顧客を選択してください。');
            return false;
        }
        
        if (!deliveryDate) {
            alert('納品日を入力してください。');
            return false;
        }
        
        if (checkedRows.length === 0) {
            alert('少なくとも1つの商品を選択してください。');
            return false;
        }
        
        // 選択された商品の数量チェック
        for (let checkbox of checkedRows) {
            const row = checkbox.closest('.order-item-row');
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            const quantity = parseInt(quantityInput?.value || 0);
            
            if (quantity <= 0) {
                alert('選択した商品の数量を正しく入力してください。');
                return false;
            }
        }
        
        return true;
    }
    
    // HTMLエスケープ関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // 使用しない関数（削除予定）
    function addDeliveryItem() {
        // この関数は使用されません
    }
    
    function removeDeliveryItem(button) {
        // この関数は使用されません
    }
    
    function updateItemNumbers() {
        // この関数は使用されません
    }
    </script>
</body>
</html>