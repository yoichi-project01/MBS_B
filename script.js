document.body.style.overflow = 'hidden';

// 書店名をローカルストレージに保存し、GETで次のページへ遷移
function selectedStore(storeName) {
  localStorage.setItem('selectedStore', storeName);
  window.location.href = `/MBS_B/menu.php?store=${encodeURIComponent(storeName)}`;
}

// DOM読み込み後の処理
document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const store = params.get('store');

  // URLにstoreパラメータがあればローカルストレージに保存
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

  // メニューボタンがある場合は、クリック時にstore付きで遷移
  const menuButtons = document.querySelectorAll('.menu-button');
  if (menuButtons.length && storedStore) {
    menuButtons.forEach(button => {
      const path = button.dataset.path;
      if (path) {
        button.addEventListener('click', () => {
          window.location.href = `${path}?store=${encodeURIComponent(storedStore)}`;
        });
      }
    });
  }
});
