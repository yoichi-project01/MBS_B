/**
 * 統計情報ページのスマホ時のJavaScript
 * 注文書と同じ動作を実装
 */

document.addEventListener('DOMContentLoaded', function() {
    let currentIsMobile = null;
    
    // スマホ時のみ顧客名クリックイベントを有効化
    function setupCustomerClickEvents() {
        const isMobile = window.innerWidth <= 768;
        
        // 状態が変わった時のみ処理
        if (currentIsMobile === isMobile) {
            return;
        }
        currentIsMobile = isMobile;
        
        // 統計情報の顧客名要素を取得（customer-name-statisticsクラス）
        const customerElements = document.querySelectorAll('.customer-name-statistics');
        
        customerElements.forEach(element => {
            // 既存の要素をクローンして置き換え（全イベントリスナーを削除）
            const newElement = element.cloneNode(true);
            element.parentNode.replaceChild(newElement, element);
            
            if (isMobile) {
                // スマホ時：クリック可能にする
                newElement.style.cursor = 'pointer';
                newElement.style.color = 'var(--main-green)';
                newElement.style.fontWeight = '600';
                newElement.style.borderBottom = '1px dotted var(--main-green)';
                newElement.style.transition = 'all 0.3s ease';
                
                // クリックイベント - 顧客詳細ページに遷移
                newElement.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 行から顧客番号を取得（1列目のtd）
                    const row = this.closest('tr');
                    const customerNoCell = row.querySelector('td:first-child');
                    const customerNo = customerNoCell ? customerNoCell.textContent.trim() : '';
                    
                    // 店舗名を取得
                    const storeElement = document.querySelector('[data-store-name]');
                    const storeName = storeElement ? storeElement.dataset.storeName : 
                                    new URLSearchParams(window.location.search).get('store') || '';
                    
                    if (customerNo && storeName) {
                        // 顧客詳細ページに遷移
                        window.location.href = `/MBS_B/statistics/customer_detail.php?customer_no=${encodeURIComponent(customerNo)}&store=${encodeURIComponent(storeName)}`;
                    } else {
                        console.error('Customer number or store name not found');
                    }
                });
                
                // ホバーエフェクト
                newElement.addEventListener('mouseover', function() {
                    this.style.color = 'var(--accent-green)';
                    this.style.borderBottomColor = 'var(--accent-green)';
                    this.style.transform = 'translateY(-1px)';
                });
                
                newElement.addEventListener('mouseout', function() {
                    this.style.color = 'var(--main-green)';
                    this.style.borderBottomColor = 'var(--main-green)';
                    this.style.transform = 'translateY(0)';
                });
            } else {
                // デスクトップ時：通常の表示
                newElement.style.cursor = 'default';
                newElement.style.color = 'var(--font-color)';
                newElement.style.fontWeight = 'normal';
                newElement.style.borderBottom = 'none';
                newElement.style.transform = 'translateY(0)';
                newElement.style.transition = 'none';
                
                // クリックを無効化
                newElement.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
            }
        });
    }
    
    // 初回実行
    setupCustomerClickEvents();
    
    // リサイズ時に再実行（デバウンス付き）
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(setupCustomerClickEvents, 100);
    });
    
    // パフォーマンス測定
    if (window.performance && window.performance.mark) {
        window.performance.mark('statistics-mobile-loaded');
    }
});