<?php
session_start();

require_once 'db_connect.php';

$search_keyword = $_GET['search'] ?? '';
$selected_customer_id = $_GET['customer_id'] ?? null;
$selected_customer_name = ''; // 選択された顧客名を保持するための変数

$customers = [];

try {
    $stmt = $pdo->query("SELECT customer_no, customer_name FROM customers ORDER BY customer_no ASC");
    $db_raw_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_customers = [];
    foreach ($db_raw_customers as $row) {
        $formatted_customers[] = [
            'id' => $row['customer_no'],
            'name' => $row['customer_name'] ?? '不明'
        ];
    }
    $customers = $formatted_customers;

} catch (PDOException $e) {
    error_log("データベースエラー (customer_select.php): " . $e->getMessage());
    $customers = [];
}

// URLにcustomer_idが指定されている場合、セッションに保存
if ($selected_customer_id !== null) {
    // 選択された顧客情報を検索
    $found_customer = array_filter($customers, function($customer) use ($selected_customer_id) {
        return $customer['id'] == $selected_customer_id;
    });

    if (!empty($found_customer)) {
        $first_customer = reset($found_customer); // 最初の要素を取得
        $_SESSION['temp_order_customer_no'] = $first_customer['id'];
        $_SESSION['temp_order_customer_name'] = $first_customer['name'];
        $selected_customer_name = $first_customer['name']; // 表示用
        
        // 顧客を選択したら、order_create.php へリダイレクト
        header('Location: order_create.php');
        exit;
    } else {
        // 無効なcustomer_idが指定された場合
        // 必要に応じてエラーメッセージを表示するか、顧客選択画面に留まる
        error_log("無効な顧客IDがcustomer_select.phpで指定されました: " . $selected_customer_id);
    }
}

// CSRFトークンを生成 (order_create.php に渡すため)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>顧客選択</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 600px;
            margin-top: 20px;
            overflow: hidden;
        }
        .header {
            background-color: #28a745;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.2em;
        }
        .nav-buttons {
            display: flex;
        }
        .nav-buttons button {
            background-color: #218838;
            color: white;
            border: none;
            padding: 10px 15px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s ease;
        }
        .nav-buttons button:hover {
            background-color: #1e7e34;
        }
        .content {
            padding: 20px;
        }
        .section-title {
            font-size: 1.5em;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            color: #333;
        }
        .section-title svg {
            margin-right: 10px;
        }
        .search-area {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }
        .search-area input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .customer-list-wrapper {
            max-height: 300px; /* スクロール可能にする */
            overflow-y: auto; /* 縦方向のスクロールバー */
            border: 1px solid #eee;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .customer-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .customer-list li {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .customer-list li:last-child {
            border-bottom: none;
        }
        .customer-list li:hover {
            background-color: #e9e9e9;
        }
        .customer-list li.selected {
            background-color: #d1e7dd; /* Light green for selected */
            font-weight: bold;
            color: #155724;
        }
        .customer-id {
            font-size: 0.8em;
            color: #666;
            margin-left: 10px;
        }
        .action-buttons {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
            flex-grow: 1;
        }
        .action-buttons .back-button {
            background-color: #6c757d;
            color: white;
        }
        .action-buttons .back-button:hover {
            background-color: #5a6268;
        }
        .action-buttons .select-button {
            background-color: #007bff;
            color: white;
        }
        .action-buttons .select-button:hover {
            background-color: #0056b3;
        }
        .action-buttons .select-button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .current-selection {
            margin-top: 10px;
            padding: 10px;
            background-color: #e2f0cb; /* 薄い緑色 */
            border: 1px solid #c8e6c9;
            border-radius: 5px;
            color: #216f44;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>緑橋書店 受注管理システム</h1>
            <div class="nav-buttons">
                <button onclick="location.href='#'">顧客情報</button>
                <button onclick="location.href='#'">統計情報</button>
                <button onclick="location.href='order_history.php'">注文書</button>
                <button onclick="location.href='#'">納品書</button>
            </div>
        </div>

        <div class="content">
            <div class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                顧客選択
            </div>

            <div class="search-area">
                <input type="text" id="customerSearch" placeholder="顧客名で検索..." autocomplete="off">
            </div>

            <div class="customer-list-wrapper">
                <ul id="customerList" class="customer-list">
                    <?php if (empty($customers)): ?>
                        <li>顧客データがありません。</li>
                    <?php else: ?>
                        <?php endif; ?>
                </ul>
            </div>
            
            <div id="currentSelection" class="current-selection" style="display: none;">
                現在選択中の顧客: <span id="selectedCustomerDisplay"></span>
            </div>

            <div class="action-buttons">
                <button type="button" class="back-button" onclick="location.href='order_history.php'">戻る</button>
                <button type="button" class="select-button" id="selectCustomerButton" disabled>この顧客を選択</button>
            </div>
        </div>
    </div>

    <script>
        const allCustomers = <?= json_encode($customers) ?>; // PHPから顧客データをJSに渡す
        const customerSearchInput = document.getElementById('customerSearch');
        const customerListUl = document.getElementById('customerList');
        const selectCustomerButton = document.getElementById('selectCustomerButton');
        const currentSelectionDiv = document.getElementById('currentSelection');
        const selectedCustomerDisplaySpan = document.getElementById('selectedCustomerDisplay');

        let selectedCustomerId = null;
        let selectedCustomerName = ''; // 選択された顧客の名前を保持

        // 顧客リストをフィルタリングしてレンダリングする関数
        function filterAndRenderCustomers() {
            const searchTerm = customerSearchInput.value.toLowerCase();
            const fragment = document.createDocumentFragment();
            let hasResults = false;

            allCustomers.forEach(customer => {
                // 顧客名に検索キーワードが含まれるかチェック
                if (customer.name.toLowerCase().includes(searchTerm)) {
                    hasResults = true;
                    const li = document.createElement('li');
                    li.textContent = customer.name;
                    li.setAttribute('data-customer-id', customer.id);
                    li.setAttribute('data-customer-name', customer.name);

                    const idSpan = document.createElement('span');
                    idSpan.className = 'customer-id';
                    idSpan.textContent = `(ID: ${customer.id})`;
                    li.appendChild(idSpan);

                    if (customer.id == selectedCustomerId) {
                        li.classList.add('selected');
                        selectedCustomerName = customer.name; // 選択中の顧客名を更新
                    }

                    li.addEventListener('click', () => {
                        // 既に選択されている要素の 'selected' クラスを削除
                        const currentlySelected = customerListUl.querySelector('.selected');
                        if (currentlySelected) {
                            currentlySelected.classList.remove('selected');
                        }
                        // クリックされた要素に 'selected' クラスを追加
                        li.classList.add('selected');
                        selectedCustomerId = customer.id;
                        selectedCustomerName = customer.name; // 選択された顧客名を更新
                        selectCustomerButton.disabled = false; // ボタンを有効化
                        updateCurrentSelectionDisplay(); // 表示を更新
                    });
                    fragment.appendChild(li);
                }
            });

            // リストをクリアしてから新しい要素を追加
            customerListUl.innerHTML = '';
            if (hasResults) {
                customerListUl.appendChild(fragment);
            } else {
                const noResultLi = document.createElement('li');
                noResultLi.textContent = '該当する顧客が見つかりません。';
                customerListUl.appendChild(noResultLi);
            }

            updateCurrentSelectionDisplay(); // フィルタリング後に表示を更新
        }

        // 現在選択中の顧客を表示する関数
        function updateCurrentSelectionDisplay() {
            if (selectedCustomerId && selectedCustomerName) {
                selectedCustomerDisplaySpan.textContent = `${selectedCustomerName} (ID: ${selectedCustomerId})`;
                currentSelectionDiv.style.display = 'block';
            } else {
                currentSelectionDiv.style.display = 'none';
                selectedCustomerDisplaySpan.textContent = '';
            }
        }

        // 「この顧客を選択」ボタンのクリックイベント
        selectCustomerButton.addEventListener('click', () => {
            if (selectedCustomerId) {
                // 選択された顧客IDをURLパラメータとして渡し、ページをリダイレクト（PHP側で処理）
                // PHP側でセッションに保存し、order_create.phpへリダイレクトされる
                window.location.href = `customer_select.php?customer_id=${selectedCustomerId}`;
            }
        });

        // 検索入力フィールドの 'input' イベントでリアルタイムフィルタリング
        customerSearchInput.addEventListener('input', filterAndRenderCustomers);

        // ページロード時の初期処理
        document.addEventListener('DOMContentLoaded', () => {
            // URLパラメータから初期検索キーワードがあれば設定
            const urlParams = new URLSearchParams(window.location.search);
            const initialSearch = urlParams.get('search') || '';
            customerSearchInput.value = initialSearch;

            // 初期表示と、もしcustomer_idがURLにある場合の顧客選択
            // このページに直接customer_idでアクセスした場合、PHP側で処理されてリダイレクトされるため、
            // ここでselectedCustomerIdを直接セットする必要はないが、残しておく。
            const urlCustomerId = urlParams.get('customer_id');
            if (urlCustomerId) {
                // PHP側でリダイレクトされるため、通常このブロックは実行されない
                // もしリダイレクトさせたくない場合は、PHPのheader()を削除
                selectedCustomerId = urlCustomerId;
                const preSelectedCustomer = allCustomers.find(customer => customer.id == urlCustomerId);
                if (preSelectedCustomer) {
                    selectedCustomerName = preSelectedCustomer.name;
                }
            }
            
            filterAndRenderCustomers(); // 初期フィルタリングと描画

            // URLに customer_id がある場合（かつリダイレクトされなかった場合）
            // または、JavaScript内でselectedCustomerIdが設定された場合、ボタンを有効にする
            if (selectedCustomerId) {
                selectCustomerButton.disabled = false;
            }
        });
    </script>
</body>
</html>