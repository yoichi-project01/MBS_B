// 書店名をローカルストレージに保存し、GETで次のページへ遷移
function selectedStore(storeName) {
  localStorage.setItem('selectedStore', storeName);
  window.location.href = `/MBS_B/menu.php?store=${encodeURIComponent(storeName)}`;
}

// DOM読み込み後の処理
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const store = params.get('store');

  // URLにパラメータがあればローカルストレージにも保存
  if (store) {
    localStorage.setItem('selectedStore', store);
  }

  // ローカルストレージから取得してタイトルを変更
  const storedStore = localStorage.getItem('selectedStore');
  if (storedStore) {
    const titleElement = document.getElementById('store-title');
    if (titleElement) {
      titleElement.innerHTML = `${storedStore}<br>受注管理システム`;
    }
  }
});
