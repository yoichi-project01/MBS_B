document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const navLinks = document.querySelectorAll('.nav-link');
    const tabContents = document.querySelectorAll('.tab-content');
    const mainTitle = document.getElementById('main-title');

    // --- Sidebar Toggle for Mobile --- //
    const toggleSidebar = () => {
        sidebar.classList.toggle('active');
        if (sidebar.classList.contains('active')) {
            // Create and show overlay
            const overlay = document.createElement('div');
            overlay.classList.add('overlay');
            document.body.appendChild(overlay);
            overlay.addEventListener('click', toggleSidebar);
        } else {
            // Remove overlay
            const overlay = document.querySelector('.overlay');
            if (overlay) {
                overlay.remove();
            }
        }
    };

    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    // --- Navigation functionality --- //
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Close sidebar if open in mobile view
            if (sidebar.classList.contains('active')) {
                toggleSidebar();
            }

            // Tab switching logic
            const tab = link.dataset.tab;
            const activeNavLink = document.querySelector('.nav-link.active');
            const activeTabContent = document.querySelector('.tab-content.active');
            
            if (activeNavLink) {
                activeNavLink.classList.remove('active');
            }
            link.classList.add('active');
            
            if (activeTabContent) {
                activeTabContent.classList.remove('active');
            }
            const targetTab = document.getElementById(tab);
            if (targetTab) {
                targetTab.classList.add('active');
            }

            // Update title
            const titleSpan = link.querySelector('span');
            if (titleSpan) {
                mainTitle.textContent = titleSpan.textContent;
            }
        });
    });

    // --- View Toggle (Table/Card) --- //
    const viewBtns = document.querySelectorAll('.view-btn');
    const tableView = document.querySelector('.table-view-container');
    const cardView = document.querySelector('.card-view-container');

    if (viewBtns.length > 0) {
        viewBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                viewBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (btn.dataset.view === 'table') {
                    if (tableView) tableView.style.display = 'block';
                    if (cardView) cardView.style.display = 'none';
                } else {
                    if (tableView) tableView.style.display = 'none';
                    if (cardView) cardView.style.display = 'grid';
                }
            });
        });
    }

    // --- Customer Search Functionality (in customers tab) --- //
    const customerSearchInput = document.getElementById('customerSearchInput');
    if (customerSearchInput) {
        customerSearchInput.addEventListener('keyup', () => {
            const filter = customerSearchInput.value.toLowerCase();
            
            // Search in table view
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                const name = row.cells[0]?.textContent.toLowerCase() || '';
                row.style.display = name.includes(filter) ? '' : 'none';
            });

            // Search in card view
            const cards = document.querySelectorAll('.card-view-container .customer-card');
            cards.forEach(card => {
                const name = card.querySelector('.customer-name')?.textContent.toLowerCase() || '';
                card.style.display = name.includes(filter) ? 'flex' : 'none';
            });
        });
    }

    // --- Table Sorting Functionality --- //
    const sortHeaders = document.querySelectorAll('[data-sort]');
    sortHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const sortType = header.dataset.sort;
            const table = header.closest('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Determine sort direction
            const isAscending = header.classList.contains('sort-asc');
            
            // Remove all sort classes
            sortHeaders.forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Add appropriate sort class
            header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
            
            // Sort rows
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch(sortType) {
                    case 'name':
                        aValue = a.cells[0].textContent.trim();
                        bValue = b.cells[0].textContent.trim();
                        break;
                    case 'sales':
                        aValue = parseFloat(a.cells[1].textContent.replace(/[,¥]/g, '')) || 0;
                        bValue = parseFloat(b.cells[1].textContent.replace(/[,¥]/g, '')) || 0;
                        break;
                    case 'leadtime':
                        aValue = parseFloat(a.cells[2].textContent.replace(/[日]/g, '')) || 0;
                        bValue = parseFloat(b.cells[2].textContent.replace(/[日]/g, '')) || 0;
                        break;
                    case 'deliveries':
                        aValue = parseInt(a.cells[3].textContent.replace(/[,]/g, '')) || 0;
                        bValue = parseInt(b.cells[3].textContent.replace(/[,]/g, '')) || 0;
                        break;
                    default:
                        return 0;
                }
                
                if (typeof aValue === 'string') {
                    return isAscending ? bValue.localeCompare(aValue) : aValue.localeCompare(bValue);
                } else {
                    return isAscending ? bValue - aValue : aValue - bValue;
                }
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        });
    });

    // --- Add hover effects for better UX --- //
    const metricCards = document.querySelectorAll('.metric-card');
    metricCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
    });

    // --- Top Customer Cards Animation --- //
    const customerCards = document.querySelectorAll('.top-customer-card');
    customerCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in-up');
    });

    // --- Responsive handling --- //
    function handleResize() {
        if (window.innerWidth > 768) {
            // Close mobile sidebar on desktop
            if (sidebar.classList.contains('active')) {
                toggleSidebar();
            }
        }
    }

    window.addEventListener('resize', handleResize);
});

// --- Customer Details Modal --- //
function showDetails(customerName) {
    const modal = document.getElementById('detailModal');
    const title = document.getElementById('detailTitle');
    const content = document.getElementById('detailContent');

    if (modal && title && content) {
        title.textContent = customerName + ' の詳細';
        content.innerHTML = `
            <div class="customer-detail-info">
                <h4>顧客情報</h4>
                <p><strong>顧客名:</strong> ${customerName}</p>
                <p><strong>登録日:</strong> 詳細情報を取得中...</p>
                <p><strong>連絡先:</strong> 詳細情報を取得中...</p>
                <p><strong>住所:</strong> 詳細情報を取得中...</p>
                
                <h4>取引履歴</h4>
                <p>過去の注文履歴や配達記録をここに表示します。</p>
                
                <h4>備考</h4>
                <p>特記事項があればここに表示されます。</p>
            </div>
        `;
        modal.style.display = 'block';
        
        // Focus management for accessibility
        modal.focus();
    }
}

// --- Modal Close Function --- //
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// --- Close modal when clicking outside --- //
window.addEventListener('click', function(event) {
    const detailModal = document.getElementById('detailModal');
    if (event.target === detailModal) {
        closeModal('detailModal');
    }
});

// --- Keyboard navigation for modals --- //
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const openModal = document.querySelector('.modal[style*="block"]');
        if (openModal) {
            closeModal(openModal.id);
        }
    }
});

// --- Initialize animations --- //
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to elements
    const animatedElements = document.querySelectorAll('.metric-card, .top-customer-card');
    animatedElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.6s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// --- Export functions for global access --- //
window.showDetails = showDetails;
window.closeModal = closeModal;