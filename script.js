// ハンバーガーメニューの制御
const menuToggle = document.getElementById('menuToggle');
const nav = document.getElementById('nav');
const menuOverlay = document.getElementById('menuOverlay');

function toggleMenu() {
    menuToggle.classList.toggle('active');
    nav.classList.toggle('active');
    menuOverlay.classList.toggle('active');

    // アクセシビリティ
    const isExpanded = nav.classList.contains('active');
    menuToggle.setAttribute('aria-expanded', isExpanded);
    menuToggle.setAttribute('aria-label', isExpanded ? 'メニューを閉じる' : 'メニューを開く');

    // ボディのスクロールを制御
    document.body.style.overflow = isExpanded ? 'hidden' : '';
}

function closeMenu() {
    menuToggle.classList.remove('active');
    nav.classList.remove('active');
    menuOverlay.classList.remove('active');
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'メニューを開く');
    document.body.style.overflow = '';
}

// イベントリスナー
menuToggle.addEventListener('click', toggleMenu);
menuOverlay.addEventListener('click', closeMenu);

// キーボードナビゲーション
document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('active')) {
            closeMenu();
        }
    }

);

// ナビリンクをクリックしたらメニューを閉じる（モバイル）
document.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeMenu();
                }
            }

        );
    }

);

// リサイズ時にメニューを閉じる
window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            closeMenu();
        }
    }

);

// スクロール時のヘッダー効果
let lastScrollY = window.scrollY;
const header = document.querySelector('.site-header');

window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;

        if (currentScrollY > 100) {
            header.style.boxShadow = '0 12px 40px rgba(47, 93, 63, 0.25)';
        } else {
            header.style.boxShadow = '0 8px 32px rgba(47, 93, 63, 0.15)';
        }

        lastScrollY = currentScrollY;
    }

);


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
  