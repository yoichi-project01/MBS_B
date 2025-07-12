
/* ==================================
   Header Component
   ================================== */

export class HeaderManager {
    constructor() {
        this.menuToggle = null;
        this.nav = null;
        this.menuOverlay = null;
        this.isMenuOpen = false;
        this.isMobile = false;
        this.resizeDebounceTimer = null;
        this.focusableElements = [];
        this.originalFocus = null;

        this.init();
    }

    init() {
        this.bindElements();
        this.bindEvents();
        this.checkMobileView();
        this.updateHeaderTitle();
        this.setupAccessibility();
    }

    bindElements() {
        this.menuToggle = document.getElementById('menuToggle');
        this.nav = document.getElementById('nav');
        this.menuOverlay = document.getElementById('menuOverlay');
    }

    bindEvents() {
        if (this.menuToggle) {
            this.menuToggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleMenu();
            });
        }

        if (this.menuOverlay) {
            this.menuOverlay.addEventListener('click', (e) => {
                e.preventDefault();
                this.closeMenu();
            });
        }

        document.querySelectorAll('.nav-item').forEach(link => {
            link.addEventListener('click', (e) => {
                if (link.href && link.href.startsWith('http')) {
                    if (this.isMobile) {
                        setTimeout(() => {
                            this.closeMenu();
                        }, 100);
                    }
                    return;
                }

                if (this.isMobile) {
                    setTimeout(() => {
                        this.closeMenu();
                    }, 100);
                }
            });
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isMenuOpen) {
                this.closeMenu();
            } else if (e.key === 'Tab' && this.isMenuOpen) {
                this.handleTabKey(e);
            }
        });

        window.addEventListener('resize', () => {
            this.debounceResize();
        });

        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                this.updateHeaderTitle();
                this.updateActiveNavItem();
            }
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.updateHeaderTitle();
            });
        }
    }

    toggleMenu() {
        if (this.isMenuOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        if (!this.nav || !this.menuOverlay || !this.menuToggle) return;

        this.isMenuOpen = true;
        this.originalFocus = document.activeElement;

        this.menuToggle.classList.add('active');
        this.nav.classList.add('active');
        this.menuOverlay.classList.add('active');

        this.menuToggle.setAttribute('aria-expanded', 'true');
        this.menuToggle.setAttribute('aria-label', 'メニューを閉じる');
        this.nav.setAttribute('aria-hidden', 'false');

        document.body.style.overflow = 'hidden';

        this.updateFocusableElements();

        setTimeout(() => {
            this.focusFirstMenuItem();
        }, 300);
    }

    closeMenu() {
        if (!this.nav || !this.menuOverlay || !this.menuToggle) return;

        this.isMenuOpen = false;

        this.menuToggle.classList.remove('active');
        this.nav.classList.remove('active');
        this.menuOverlay.classList.remove('active');

        this.menuToggle.setAttribute('aria-expanded', 'false');
        this.menuToggle.setAttribute('aria-label', 'メニューを開く');
        this.nav.setAttribute('aria-hidden', 'true');

        document.body.style.overflow = '';

        if (this.originalFocus && typeof this.originalFocus.focus === 'function') {
            this.originalFocus.focus();
        } else {
            this.menuToggle.focus();
        }
    }

    handleTabKey(e) {
        if (!this.focusableElements.length) return;

        const firstElement = this.focusableElements[0];
        const lastElement = this.focusableElements[this.focusableElements.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    updateFocusableElements() {
        if (!this.nav) return;

        this.focusableElements = Array.from(this.nav.querySelectorAll(
            'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select, [tabindex]:not([tabindex="-1"])'
        )).filter(element => {
            return !element.disabled && element.offsetParent !== null;
        });
    }

    focusFirstMenuItem() {
        if (!this.focusableElements.length) return;

        const firstItem = this.focusableElements[0];
        if (firstItem) {
            firstItem.focus();
        }
    }

    debounceResize() {
        clearTimeout(this.resizeDebounceTimer);
        this.resizeDebounceTimer = setTimeout(() => {
            this.handleResize();
        }, 250);
    }

    handleResize() {
        const wasMobile = this.isMobile;
        this.checkMobileView();

        if (wasMobile && !this.isMobile && this.isMenuOpen) {
            this.closeMenu();
        }

        this.updateHeaderTitle();
    }

    checkMobileView() {
        this.isMobile = window.innerWidth <= 768;
    }

    getCurrentPageInfo() {
        const currentPath = window.location.pathname;
        const currentFile = currentPath.split('/').pop();

        const PAGE_CONFIG = {
            '/customer_information/': { name: '顧客情報', icon: '👥' },
            '/statistics/': { name: '統計情報', icon: '📊' },
            '/order_list/': { name: '注文書', icon: '📋' },
            '/delivery_list/': { name: '納品書', icon: '🚚' },
            'index.php': { name: '顧客情報CSVアップロード', icon: '👥' },
            'upload.php': { name: '顧客情報CSVアップロード', icon: '👥' }
        };

        for (const [path, config] of Object.entries(PAGE_CONFIG)) {
            if (path.startsWith('/') && currentPath.includes(path)) {
                return config;
            }
        }

        if (PAGE_CONFIG[currentFile]) {
            return PAGE_CONFIG[currentFile];
        }

        return { name: '受注管理', icon: '📋' };
    }

    updateHeaderTitle(customPageInfo = null) {
        const titleElement = document.querySelector('.site-header .store-title .page-text');
        const iconElement = document.querySelector('.site-header .store-title .page-icon');

        if (!titleElement || !iconElement) return;

        const urlParams = new URLSearchParams(window.location.search);
        const storeName = urlParams.get('store') ||
                         document.documentElement.getAttribute('data-store-name') || '';

        const pageInfo = customPageInfo || this.getCurrentPageInfo();

        iconElement.textContent = pageInfo.icon;

        let displayTitle;
        if (this.isMobile && storeName) {
            const shortStoreName = storeName.replace('店', '');
            displayTitle = `${shortStoreName} - ${pageInfo.name}`;
        } else if (storeName) {
            displayTitle = `${storeName} - ${pageInfo.name}`;
        } else {
            displayTitle = pageInfo.name;
        }

        titleElement.textContent = displayTitle;

        if (storeName) {
            document.title = `${pageInfo.name} - ${storeName} - 受注管理システム`;
        } else {
            document.title = `${pageInfo.name} - 受注管理システム`;
        }

        this.updateActiveNavItem();
        this.addPageTransitionEffect();

        window.dispatchEvent(new CustomEvent('headerTitleUpdated', {
            detail: { pageInfo, storeName }
        }));
    }

    updateActiveNavItem() {
        const currentPath = window.location.pathname;

        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
            item.setAttribute('aria-current', 'false');
        });

        const navMappings = {
            '/customer_information/': 0,
            '/statistics/': 1,
            '/order_list/': 2,
            '/delivery_list/': 3
        };

        for (const [path, index] of Object.entries(navMappings)) {
            if (currentPath.includes(path)) {
                const navItems = document.querySelectorAll('.nav-item');
                if (navItems[index]) {
                    navItems[index].classList.add('active');
                    navItems[index].setAttribute('aria-current', 'page');
                    break;
                }
            }
        }
    }

    addPageTransitionEffect() {
        const titleElement = document.querySelector('.site-header .store-title');
        if (!titleElement) return;

        titleElement.style.opacity = '0';
        titleElement.style.transform = 'translateY(-10px)';

        requestAnimationFrame(() => {
            titleElement.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            titleElement.style.opacity = '1';
            titleElement.style.transform = 'translateY(0)';

            setTimeout(() => {
                titleElement.style.transition = '';
            }, 400);
        });
    }

    setupAccessibility() {
        if (this.menuToggle) {
            this.menuToggle.setAttribute('aria-expanded', 'false');
            this.menuToggle.setAttribute('aria-label', 'メニューを開く');
            this.menuToggle.setAttribute('aria-controls', 'nav');
        }

        if (this.nav) {
            this.nav.setAttribute('aria-hidden', 'true');
            this.nav.setAttribute('role', 'navigation');
            this.nav.setAttribute('aria-label', 'メインナビゲーション');
        }

        document.querySelectorAll('.nav-item').forEach((item, index) => {
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'menuitem');
        });
    }

    setStoreName(storeName) {
        if (!storeName) return;

        const url = new URL(window.location);
        url.searchParams.set('store', storeName);
        window.history.replaceState({}, '', url);

        document.documentElement.setAttribute('data-store-name', storeName);

        this.updateHeaderTitle();
    }

    setCustomPageInfo(name, icon) {
        const customPageInfo = { name, icon };
        this.updateHeaderTitle(customPageInfo);
    }

    getStoreName() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('store') ||
               document.documentElement.getAttribute('data-store-name') || '';
    }
}
