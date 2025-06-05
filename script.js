function selectedStore(storeName) {
  localStorage.setItem('selectedStore', storeName);
  window.location.href = `http://localhost/MBS_B/menu.php?store=${encodeURIComponent(storeName)}`;
}

document.addEventListener('DOMContentLoaded', () => {
  const params = new URLSearchParams(window.location.search);
  const store = params.get('store');

  if (store) {
      localStorage.setItem('selectedStore', store);
  }

  // localStorage から取得して h1 を更新
  const storedStore = localStorage.getItem('selectedStore');
  if (storedStore) {
      const titleElement = document.getElementById('store-title');
      if (titleElement) {
          titleElement.innerHTML = `${storedStore}<br>受注管理システム`;
      }
  }
});
