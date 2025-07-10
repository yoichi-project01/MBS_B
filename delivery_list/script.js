document.addEventListener('DOMContentLoaded', function() {
    // URLから注文IDを取得
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('order_id');

    if (orderId) {
        // 注文データを取得
        fetch(`get_delivery_data.php?order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                // 納品書にデータを表示
                document.getElementById('order-number').textContent = data.order_info.order_id;
                document.getElementById('order-date').textContent = data.order_info.order_date;
                document.getElementById('customer-name').textContent = data.order_info.customer_name;

                const orderItems = document.getElementById('order-items');
                let totalAmount = 0;

                data.order_details.forEach(item => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>&yen;${item.price}</td>
                        <td>&yen;${item.price * item.quantity}</td>
                    `;
                    orderItems.appendChild(row);
                    totalAmount += item.price * item.quantity;
                });

                document.getElementById('total-amount').textContent = `&yen;${totalAmount}`;
            })
            .catch(error => console.error('Error:', error));
    }
});