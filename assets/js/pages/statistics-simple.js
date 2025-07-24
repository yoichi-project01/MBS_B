/**
 * 統計ページ用の簡単なJavaScript
 * PHP/HTMLベースの表示に合わせて最小限の機能のみ提供
 */

document.addEventListener('DOMContentLoaded', function() {
    // 顧客詳細ボタンのクリックイベント
    document.querySelectorAll('.view-detail-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerNo = this.dataset.customerNo;
            loadCustomerDetail(customerNo);
        });
    });
    
    // パフォーマンス測定
    if (window.performance && window.performance.mark) {
        window.performance.mark('statistics-page-loaded');
    }
});

// 顧客詳細の読み込み
async function loadCustomerDetail(customerNo) {
    try {
        const storeName = document.querySelector('[data-store-name]')?.dataset.storeName || 
                          new URLSearchParams(window.location.search).get('store') || '';
        
        const formData = new FormData();
        formData.append('customer_no', customerNo);
        formData.append('store', storeName);
        
        const response = await fetch('/MBS_B/statistics/get_customer_detail.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('customerOrdersContent').innerHTML = renderCustomerDetail(data.data);
            openModal('customerOrdersModal');
        } else {
            alert('顧客詳細の取得に失敗しました: ' + data.message);
        }
    } catch (error) {
        console.error('Error loading customer detail:', error);
        alert('顧客詳細の読み込みに失敗しました');
    }
}

// 顧客詳細のレンダリング
function renderCustomerDetail(data) {
    const { customer_info, statistics } = data;
    
    return `
        <div class="customer-detail-content">
            <div class="customer-basic-info">
                <h3>基本情報</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>顧客番号:</label>
                        <span>${customer_info.customer_no}</span>
                    </div>
                    <div class="info-item">
                        <label>顧客名:</label>
                        <span>${escapeHtml(customer_info.customer_name)}</span>
                    </div>
                    <div class="info-item">
                        <label>住所:</label>
                        <span>${escapeHtml(customer_info.address)}</span>
                    </div>
                    <div class="info-item">
                        <label>電話番号:</label>
                        <span>${escapeHtml(customer_info.telephone_number)}</span>
                    </div>
                    <div class="info-item">
                        <label>登録日:</label>
                        <span>${customer_info.registration_date}</span>
                    </div>
                </div>
            </div>
            ${statistics ? `
                <div class="customer-statistics">
                    <h3>統計情報</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <label>累計売上:</label>
                            <span>¥${formatNumber(statistics.total_sales)}</span>
                        </div>
                        <div class="stat-item">
                            <label>平均リードタイム:</label>
                            <span>${statistics.avg_lead_time}日</span>
                        </div>
                        <div class="stat-item">
                            <label>配達回数:</label>
                            <span>${statistics.delivery_count}回</span>
                        </div>
                        <div class="stat-item">
                            <label>最終注文日:</label>
                            <span>${statistics.last_order_date || '-'}</span>
                        </div>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

// ヘルパー関数
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function formatNumber(number) {
    if (number === null || number === undefined || isNaN(number)) return '0';
    return new Intl.NumberFormat('ja-JP').format(Number(number));
}