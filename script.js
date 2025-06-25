// --- ハンバーガーメニュー(jQuery)対応 ---
$(function() {
  // メニュー開閉
  $('#menu-btn').on('click', function(e) {
    e.stopPropagation();
    $('.nav').toggleClass('open');
    $('.menu-overlay').toggleClass('active');
    // メニューが開いている間はスクロール禁止
    if ($('.nav').hasClass('open')) {
      $('body').css('overflow', 'hidden');
    } else {
      $('body').css('overflow', '');
    }
  });
  // オーバーレイまたはメニュー内リンククリックで閉じる
  $('.menu-overlay, .nav a').on('click', function() {
    $('.nav').removeClass('open');
    $('.menu-overlay').removeClass('active');
    $('body').css('overflow', '');
  });
});

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
