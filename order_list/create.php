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
        $registration_date = $_POST['registration_date'] ?? '';
        $status = 'pending'; // 新規注文は常に保留状態
        $order_items = $_POST['order_items'] ?? [];
        
        // バリデーション
        if (empty($customer_no)) {
            throw new Exception('顧客を選択してください。');
        }
        
        if (empty($registration_date)) {
            throw new Exception('注文日を入力してください。');
        }
        
        if (empty($order_items) || !is_array($order_items)) {
            throw new Exception('注文商品を追加してください。');
        }
        
        // 有効な注文商品をフィルタリング
        $valid_items = [];
        foreach ($order_items as $item) {
            if (!empty($item['books']) && !empty($item['order_volume']) && !empty($item['price'])) {
                $valid_items[] = [
                    'books' => trim($item['books']),
                    'order_volume' => (int)$item['order_volume'],
                    'price' => (float)$item['price'],
                    'abstract' => trim($item['abstract'] ?? ''),
                    'order_remarks' => trim($item['order_remarks'] ?? '')
                ];
            }
        }
        
        if (empty($valid_items)) {
            throw new Exception('有効な注文商品がありません。');
        }
        
        // トランザクション開始
        $pdo->beginTransaction();
        
        // 注文を挿入
        $order_sql = "INSERT INTO orders (customer_no, registration_date, status) VALUES (:customer_no, :registration_date, :status)";
        $order_stmt = $pdo->prepare($order_sql);
        $order_stmt->bindValue(':customer_no', $customer_no, PDO::PARAM_INT);
        $order_stmt->bindValue(':registration_date', $registration_date, PDO::PARAM_STR);
        $order_stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $order_stmt->execute();
        
        $order_no = $pdo->lastInsertId();
        
        // 注文商品を挿入
        $item_sql = "INSERT INTO order_items (order_no, books, order_volume, price, abstract, order_remarks) VALUES (:order_no, :books, :order_volume, :price, :abstract, :order_remarks)";
        $item_stmt = $pdo->prepare($item_sql);
        
        foreach ($valid_items as $item) {
            $item_stmt->bindValue(':order_no', $order_no, PDO::PARAM_INT);
            $item_stmt->bindValue(':books', $item['books'], PDO::PARAM_STR);
            $item_stmt->bindValue(':order_volume', $item['order_volume'], PDO::PARAM_INT);
            $item_stmt->bindValue(':price', $item['price'], PDO::PARAM_STR);
            $item_stmt->bindValue(':abstract', $item['abstract'], PDO::PARAM_STR);
            $item_stmt->bindValue(':order_remarks', $item['order_remarks'], PDO::PARAM_STR);
            $item_stmt->execute();
        }
        
        $pdo->commit();
        $success_message = "注文書No.{$order_no}を正常に作成しました。";
        
        // 3秒後にリダイレクト
        header("refresh:3;url=index.php?store=" . urlencode($storeName));
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
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
    <title>新規注文書作成 - <?php echo htmlspecialchars($storeName); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($storeName); ?>で新しい注文書を作成します。顧客選択、商品追加、注文詳細の入力が可能です。">
    
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
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body class="with-header order-create-page">
    <div class="dashboard-container">
        <div class="main-content">
            <div class="content-scroll-area">
                <div class="order-create-container">
                    
                    <!-- ヘッダー -->
                    <div class="create-header">
                        <h1 class="page-title">
                            <i class="fas fa-plus-circle"></i> 新規注文書作成
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
                    
                    <!-- 注文作成フォーム -->
                    <form method="POST" id="orderCreateForm" class="order-form">
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
                                    <label for="registration_date" class="form-label">注文日 <span class="required">*</span></label>
                                    <input type="date" name="registration_date" id="registration_date" class="form-input" 
                                           value="<?= $_POST['registration_date'] ?? date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 注文商品セクション -->
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="fas fa-shopping-cart"></i> 注文商品
                            </h2>
                            
                            <div class="order-items-container">
                                <div class="items-header">
                                    <button type="button" id="addItemBtn" class="btn-add-item">
                                        <i class="fas fa-plus"></i> 商品を追加
                                    </button>
                                </div>
                                
                                <div id="orderItemsList" class="items-list">
                                    <!-- 初期アイテム -->
                                    <div class="order-item-row">
                                        <div class="item-number">1</div>
                                        <div class="item-fields">
                                            <div class="field-group">
                                                <label class="field-label">書籍名 <span class="required">*</span></label>
                                                <input type="text" name="order_items[0][books]" class="form-input" placeholder="書籍名を入力" required>
                                            </div>
                                            <div class="field-group">
                                                <label class="field-label">概要</label>
                                                <input type="text" name="order_items[0][abstract]" class="form-input" placeholder="書籍の概要">
                                            </div>
                                            <div class="field-group">
                                                <label class="field-label">数量 <span class="required">*</span></label>
                                                <input type="number" name="order_items[0][order_volume]" class="form-input" min="1" value="1" required>
                                            </div>
                                            <div class="field-group">
                                                <label class="field-label">単価 <span class="required">*</span></label>
                                                <input type="number" name="order_items[0][price]" class="form-input" min="0" step="0.01" placeholder="0.00" required>
                                            </div>
                                            <div class="field-group full-width">
                                                <label class="field-label">備考</label>
                                                <input type="text" name="order_items[0][order_remarks]" class="form-input" placeholder="備考（任意）">
                                            </div>
                                        </div>
                                        <button type="button" class="btn-remove-item" onclick="removeOrderItem(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
                                <i class="fas fa-save"></i> 注文書を作成
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
    
    <script nonce="<?= SessionManager::get('csp_nonce') ?>">
    let itemCounter = 1;
    
    // 注文作成ページ固有の初期化
    document.addEventListener('DOMContentLoaded', function() {
        updateSummary();
        
        // 商品追加ボタン
        document.getElementById('addItemBtn').addEventListener('click', addOrderItem);
        
        // フォーム送信時の確認
        document.getElementById('orderCreateForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }
            
            const submitBtn = this.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 作成中...';
        });
        
        // 数量・単価変更時のサマリー更新
        document.addEventListener('input', function(e) {
            if (e.target.name && (e.target.name.includes('[order_volume]') || e.target.name.includes('[price]'))) {
                updateSummary();
            }
        });
        
        // パフォーマンス測定
        if (window.performance && window.performance.mark) {
            window.performance.mark('order-create-page-loaded');
        }
    });
    
    // 商品行を追加
    function addOrderItem() {
        const itemsList = document.getElementById('orderItemsList');
        const newRow = document.createElement('div');
        newRow.className = 'order-item-row';
        newRow.innerHTML = `
            <div class="item-number">${itemCounter + 1}</div>
            <div class="item-fields">
                <div class="field-group">
                    <label class="field-label">書籍名 <span class="required">*</span></label>
                    <input type="text" name="order_items[${itemCounter}][books]" class="form-input" placeholder="書籍名を入力" required>
                </div>
                <div class="field-group">
                    <label class="field-label">概要</label>
                    <input type="text" name="order_items[${itemCounter}][abstract]" class="form-input" placeholder="書籍の概要">
                </div>
                <div class="field-group">
                    <label class="field-label">数量 <span class="required">*</span></label>
                    <input type="number" name="order_items[${itemCounter}][order_volume]" class="form-input" min="1" value="1" required>
                </div>
                <div class="field-group">
                    <label class="field-label">単価 <span class="required">*</span></label>
                    <input type="number" name="order_items[${itemCounter}][price]" class="form-input" min="0" step="0.01" placeholder="0.00" required>
                </div>
                <div class="field-group full-width">
                    <label class="field-label">備考</label>
                    <input type="text" name="order_items[${itemCounter}][order_remarks]" class="form-input" placeholder="備考（任意）">
                </div>
            </div>
            <button type="button" class="btn-remove-item" onclick="removeOrderItem(this)">
                <i class="fas fa-trash"></i>
            </button>
        `;
        
        itemsList.appendChild(newRow);
        itemCounter++;
        updateItemNumbers();
        updateSummary();
        
        // 新しい行にアニメーション
        newRow.style.opacity = '0';
        newRow.style.transform = 'translateY(20px)';
        setTimeout(() => {
            newRow.style.transition = 'all 0.3s ease';
            newRow.style.opacity = '1';
            newRow.style.transform = 'translateY(0)';
        }, 10);
    }
    
    // 商品行を削除
    function removeOrderItem(button) {
        const row = button.closest('.order-item-row');
        const itemsList = document.getElementById('orderItemsList');
        
        if (itemsList.children.length > 1) {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-100%)';
            setTimeout(() => {
                row.remove();
                updateItemNumbers();
                updateSummary();
            }, 300);
        } else {
            alert('最低1つの商品が必要です。');
        }
    }
    
    // アイテム番号を更新
    function updateItemNumbers() {
        const rows = document.querySelectorAll('.order-item-row');
        rows.forEach((row, index) => {
            const numberDiv = row.querySelector('.item-number');
            numberDiv.textContent = index + 1;
        });
    }
    
    // サマリーを更新
    function updateSummary() {
        const rows = document.querySelectorAll('.order-item-row');
        let totalItems = 0;
        let totalAmount = 0;
        
        rows.forEach(row => {
            const volumeInput = row.querySelector('input[name*="[order_volume]"]');
            const priceInput = row.querySelector('input[name*="[price]"]');
            
            const volume = parseInt(volumeInput?.value || 0);
            const price = parseFloat(priceInput?.value || 0);
            
            if (volume > 0 && price > 0) {
                totalItems += volume;
                totalAmount += volume * price;
            }
        });
        
        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('totalAmount').textContent = '¥' + totalAmount.toLocaleString();
    }
    
    // フォームバリデーション
    function validateForm() {
        const customerNo = document.getElementById('customer_no').value;
        const registrationDate = document.getElementById('registration_date').value;
        const rows = document.querySelectorAll('.order-item-row');
        
        if (!customerNo) {
            alert('顧客を選択してください。');
            return false;
        }
        
        if (!registrationDate) {
            alert('注文日を入力してください。');
            return false;
        }
        
        let hasValidItem = false;
        for (let row of rows) {
            const booksInput = row.querySelector('input[name*="[books]"]');
            const volumeInput = row.querySelector('input[name*="[order_volume]"]');
            const priceInput = row.querySelector('input[name*="[price]"]');
            
            if (booksInput?.value && volumeInput?.value && priceInput?.value) {
                hasValidItem = true;
                break;
            }
        }
        
        if (!hasValidItem) {
            alert('少なくとも1つの有効な商品を追加してください。');
            return false;
        }
        
        return true;
    }
    </script>
</body>
</html>