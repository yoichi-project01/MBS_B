/* ==================================
   Customer Details Modal Component
   ================================== */

/**
 * 顧客名クリック時の詳細表示機能を初期化
 */
export function initializeCustomerNameClick() {
    // 顧客名クリックイベントの設定
    document.addEventListener('click', (e) => {
        // 注文書ページの顧客名クリックのみを処理
        if (e.target.classList.contains('customer-name-clickable') && window.location.pathname.includes('order_list')) {
            e.preventDefault();
            const customerName = e.target.getAttribute('data-customer');
            const orderNo = e.target.getAttribute('data-order');
            const storeName = e.target.getAttribute('data-store');
            
            showOrderDetails(customerName, orderNo, storeName);
        }
    });

    // モーダル閉じるイベント
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('order-details-modal') || 
            e.target.classList.contains('close-details')) {
            closeOrderDetails();
        }
    });

    // ESCキーでモーダルを閉じる
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeOrderDetails();
        }
    });
}

/**
 * 注文詳細モーダルを表示
 */
function showOrderDetails(customerName, orderNo, storeName) {
    // 既存のモーダルを削除
    const existingModal = document.querySelector('.order-details-modal');
    if (existingModal) {
        existingModal.remove();
    }

    // モーダルHTML生成
    const modal = createOrderDetailsModal(customerName, orderNo, storeName);
    document.body.appendChild(modal);
    
    // モーダル表示
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);

    // 注文データを取得して表示
    fetchOrderDetails(orderNo, storeName, modal);
}

/**
 * 注文詳細モーダルを閉じる
 */
function closeOrderDetails() {
    const modal = document.querySelector('.order-details-modal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => {
            modal.remove();
        }, 300);
    }
}

/**
 * モーダルHTML作成
 */
function createOrderDetailsModal(customerName, orderNo, storeName) {
    const modal = document.createElement('div');
    modal.className = 'order-details-modal';
    modal.innerHTML = `
        <div class="order-details-content">
            <div class="order-details-header">
                <h3>${customerName} さんの注文詳細</h3>
                <button class="close-details" type="button">×</button>
            </div>
            <div class="order-details-body">
                <div class="loading-state">
                    <p>データを読み込んでいます...</p>
                </div>
            </div>
        </div>
    `;
    return modal;
}

/**
 * 注文詳細データ取得
 */
async function fetchOrderDetails(orderNo, storeName, modal) {
    try {
        const response = await fetch(`/MBS_B/order_list/detail.php?order_no=${encodeURIComponent(orderNo)}&store=${encodeURIComponent(storeName)}`);
        
        if (!response.ok) {
            throw new Error('データの取得に失敗しました');
        }

        const html = await response.text();
        
        // レスポンスがJSONエラーの場合
        if (html.startsWith('{') && html.includes('error')) {
            const errorData = JSON.parse(html);
            throw new Error(errorData.error || 'データの取得に失敗しました');
        }

        // 詳細データを表示
        const bodyElement = modal.querySelector('.order-details-body');
        bodyElement.innerHTML = createOrderDetailsContent(orderNo, storeName, html);
        
    } catch (error) {
        console.error('注文詳細取得エラー:', error);
        const bodyElement = modal.querySelector('.order-details-body');
        bodyElement.innerHTML = `
            <div class="error-state">
                <p>注文詳細の取得に失敗しました。</p>
                <p class="error-message">${error.message}</p>
                <div class="order-basic-info">
                    <h4>基本情報</h4>
                    <p><strong>注文番号:</strong> ${orderNo}</p>
                    <p><strong>店舗:</strong> ${storeName}</p>
                    <a href="/MBS_B/order_list/detail.php?order_no=${encodeURIComponent(orderNo)}&store=${encodeURIComponent(storeName)}" 
                       target="_blank" class="btn-detail">詳細ページで確認</a>
                </div>
            </div>
        `;
    }
}

/**
 * 注文詳細コンテンツ作成
 */
function createOrderDetailsContent(orderNo, storeName, detailHtml) {
    // 簡単な情報表示（実際の詳細ページからデータを抽出するか、APIで取得）
    return `
        <div class="order-summary">
            <div class="order-info-grid">
                <div class="info-item">
                    <label>注文番号</label>
                    <span>${orderNo}</span>
                </div>
                <div class="info-item">
                    <label>店舗</label>
                    <span>${storeName}</span>
                </div>
            </div>
            
            <div class="order-actions">
                <a href="/MBS_B/order_list/detail.php?order_no=${encodeURIComponent(orderNo)}&store=${encodeURIComponent(storeName)}" 
                   target="_blank" class="table-action-btn">詳細ページで確認</a>
                <a href="/MBS_B/delivery_list/index.php?order_id=${encodeURIComponent(orderNo)}" 
                   target="_blank" class="table-action-btn btn-info">納品書を確認</a>
            </div>
        </div>
    `;
}