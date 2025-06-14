let customerData = [];

document.getElementById('loadData').addEventListener('click', async () => {
    const resultDiv = document.getElementById('result');
    const tableBody = document.querySelector('#customerTable tbody');

    try {
        const response = await fetch('data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const json = await response.json();

        if (json.status !== 'success') {
            resultDiv.textContent = 'エラー: ' + json.message;
            return;
        }

        customerData = processData(json.data);
        displayData(customerData);
        resultDiv.textContent = 'データが正常に読み込まれました。';
    } catch (error) {
        resultDiv.textContent = 'エラーが発生しました: ' + error.message;
    }
});

function processData(rawData) {
    const customers = {};
    rawData.forEach(row => {
        const { CustomerNo, CustomerName, OrderDate, DeliveryDate, SalesAmount } = row;
        if (!customers[CustomerNo]) {
            customers[CustomerNo] = {
                CustomerName,
                totalSales: 0,
                leadTimes: [],
                deliveryCount: 0
            };
        }

        const sales = parseFloat(SalesAmount);
        const order = new Date(OrderDate);
        const delivery = new Date(DeliveryDate);
        const leadTime = (delivery - order) / (1000 * 60 * 60 * 24);

        customers[CustomerNo].totalSales += sales;
        customers[CustomerNo].leadTimes.push(leadTime);
        customers[CustomerNo].deliveryCount += 1;
    });

    return Object.entries(customers).map(([customerNo, data]) => ({
        customerNo,
        customerName: data.CustomerName,
        totalSales: data.totalSales,
        avgLeadTime: data.leadTimes.length
            ? (data.leadTimes.reduce((sum, time) => sum + time, 0) / data.leadTimes.length).toFixed(2)
            : 0,
        deliveryCount: data.deliveryCount
    }));
}

function displayData(data) {
    const tableBody = document.querySelector('#customerTable tbody');
    tableBody.innerHTML = '';
    data.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.customerNo}</td>
            <td>${item.customerName}</td>
            <td>${item.totalSales.toLocaleString()}</td>
            <td>${item.avgLeadTime}</td>
            <td>${item.deliveryCount}</td>
        `;
        tableBody.appendChild(row);
    });
}

// 検索機能
document.getElementById('searchInput').addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    const filteredData = customerData.filter(item => 
        item.customerName.toLowerCase().includes(query)
    );
    displayData(filteredData);
});

// 並べ替え機能
document.querySelectorAll('.sort-btn').forEach(button => {
    button.addEventListener('click', () => {
        const column = button.dataset.column;
        const order = button.dataset.order;
        const sortedData = [...customerData].sort((a, b) => {
            const valueA = parseFloat(a[column]) || a[column];
            const valueB = parseFloat(b[column]) || b[column];
            if (order === 'asc') {
                return valueA > valueB ? 1 : -1;
            } else {
                return valueA < valueB ? 1 : -1;
            }
        });
        displayData(sortedData);
    });
});