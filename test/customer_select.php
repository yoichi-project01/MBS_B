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
    <link rel="stylesheet" href="../style.css"> <!-- パスは環境に合わせて調整 -->
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <a id="store-title">緑橋書店 受注管理システム</a>
            <nav class="nav">
                <a href="#">顧客情報</a>
                <a href="#">統計情報</a>
                <a href="order_history.php">注文書</a>
                <a href="#">納品書</a>
            </nav>
        </div>
    </header>
    <main class="main-content" style="margin-top: 90px;">
        <h1 class="section-title" style="margin-bottom: 18px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users"
                style="vertical-align: middle;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            顧客選択
        </h1>
        <form class="search-form" style="margin-bottom: 18px;">
            <input type="text" id="customerSearch" placeholder="顧客名で検索..." autocomplete="off">
        </form>
        <div class="table-container" style="max-width: 500px;">
            <ul id="customerList" class="customer-list" style="list-style:none; margin:0; padding:0;">
                <?php if (empty($customers)): ?>
                <li>顧客データがありません。</li>
                <?php endif; ?>
            </ul>
        </div>
        <div id="currentSelection" class="current-selection" style="display: none;">
            現在選択中の顧客: <span id="selectedCustomerDisplay"></span>
        </div>
        <div class="menu" style="margin-top: 18px;">
            <button type="button" class="back-button" onclick="location.href='order_history.php'">戻る</button>
            <button type="button" class="select-button" id="selectCustomerButton" disabled>この顧客を選択</button>
        </div>
    </main>
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