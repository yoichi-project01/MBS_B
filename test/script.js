// 顧客名でフィルター
function filterTable() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#customerTable tbody tr");

    rows.forEach(row => {
        const nameCell = row.querySelector('[data-column="customer_name"]');
        if (!nameCell) return;

        const name = nameCell.textContent.toLowerCase();
        row.style.display = name.includes(input) ? "" : "none";
    });
}

// ソート処理
function sortTableByColumn(column, order) {
    const tbody = document.querySelector("#customerTable tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));

    rows.sort((a, b) => {
        const aText = a.querySelector(`[data-column="${column}"]`).textContent.trim();
        const bText = b.querySelector(`[data-column="${column}"]`).textContent.trim();

        // 数値判定（カンマ付き数値やfloat対応）
        const aVal = parseFloat(aText.replace(/,/g, '')) || 0;
        const bVal = parseFloat(bText.replace(/,/g, '')) || 0;

        if (order === "asc") return aVal - bVal;
        else return bVal - aVal;
    });

    // 並べ替えた行を tbody に再追加
    rows.forEach(row => tbody.appendChild(row));
}

// ボタンにイベントを追加
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".sort-icon").forEach(button => {
        button.addEventListener("click", () => {
            const column = button.getAttribute("data-column");
            const order = button.getAttribute("data-order");
            sortTableByColumn(column, order);
        });
    });
});
