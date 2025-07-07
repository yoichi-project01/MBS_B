
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

    // --- Navigation & other functionalities --- //
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Close sidebar if open in mobile view
            if (sidebar.classList.contains('active')) {
                toggleSidebar();
            }

            // Tab switching logic
            const tab = link.dataset.tab;
            document.querySelector('.nav-link.active').classList.remove('active');
            link.classList.add('active');
            
            document.querySelector('.tab-content.active').classList.remove('active');
            document.getElementById(tab).classList.add('active');

            // Update title
            mainTitle.textContent = link.querySelector('span').textContent;
        });
    });

    // ... (The rest of the chart, search, and modal JS remains the same)
    const viewBtns = document.querySelectorAll('.view-btn');
    const tableView = document.querySelector('.table-view-container');
    const cardView = document.querySelector('.card-view-container');

    if (viewBtns.length > 0) {
        viewBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                viewBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                if (btn.dataset.view === 'table') {
                    tableView.style.display = 'block';
                    cardView.style.display = 'none';
                } else {
                    tableView.style.display = 'none';
                    cardView.style.display = 'grid';
                }
            });
        });
    }

    const searchInput = document.getElementById('customerSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', () => {
            const filter = searchInput.value.toLowerCase();
            
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                row.style.display = name.includes(filter) ? '' : 'table-row';
            });

            const cards = document.querySelectorAll('.card-view-container .customer-card');
            cards.forEach(card => {
                const name = card.querySelector('.customer-name').textContent.toLowerCase();
                card.style.display = name.includes(filter) ? 'flex' : 'none';
            });
        });
    }

    const mainSalesChartCtx = document.getElementById('mainSalesChart')?.getContext('2d');
    if (mainSalesChartCtx) {
        new Chart(mainSalesChartCtx, {
            type: 'line',
            data: {
                labels: ['1月', '2月', '3月', '4月', '5月', '6月', '7月'],
                datasets: [{
                    label: '売上',
                    data: [65000, 59000, 80000, 81000, 56000, 55000, 40000],
                    borderColor: '#2c5e42',
                    backgroundColor: 'rgba(44, 94, 66, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    const salesChartCtx = document.getElementById('salesChart')?.getContext('2d');
    if (salesChartCtx) {
        new Chart(salesChartCtx, {
            type: 'bar',
            data: {
                labels: ['商品A', '商品B', '商品C', '商品D', '商品E'],
                datasets: [{
                    label: '販売数',
                    data: [120, 190, 150, 210, 130],
                    backgroundColor: '#6ac083',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});

function showDetails(customerName) {
    const modal = document.getElementById('detailModal');
    const title = document.getElementById('detailTitle');
    const content = document.getElementById('detailContent');

    title.textContent = customerName + ' の詳細';
    content.innerHTML = `<p>ここに ${customerName} の詳細な顧客情報、注文履歴、連絡先などを表示します。</p>`;
    modal.style.display = 'block';
}

function showGraph(customerName) {
    const modal = document.getElementById('graphModal');
    const title = document.getElementById('graphTitle');
    
    title.textContent = customerName + ' の売上推移';
    modal.style.display = 'block';

    const modalChartCtx = document.getElementById('modalChart').getContext('2d');
    new Chart(modalChartCtx, {
        type: 'line',
        data: {
            labels: ['4月', '5月', '6月', '7月'],
            datasets: [{
                label: '月間売上',
                data: [12000, 19000, 13000, 17000],
                borderColor: '#2c5e42',
                tension: 0.1
            }]
        },
        options: { responsive: true }
    });
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.addEventListener('click', function(event) {
    const detailModal = document.getElementById('detailModal');
    const graphModal = document.getElementById('graphModal');
    if (event.target == detailModal) {
        closeModal('detailModal');
    }
    if (event.target == graphModal) {
        closeModal('graphModal');
    }
});
