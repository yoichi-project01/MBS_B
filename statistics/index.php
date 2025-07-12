<?php
require_once(__DIR__ . '/../component/autoloader.php');
require_once(__DIR__ . '/../component/db.php');
include(__DIR__ . '/../component/header.php');

SessionManager::start();
$storeName = $_GET['store'] ?? $_COOKIE['selectedStore'] ?? '';
if (empty($storeName)) {
    die("店舗が選択されていません。");
}

$totalCustomers = 0;
$monthlySales = 0;
$salesTrend = 0;
$totalDeliveries = 0;
$avgLeadTime = 0;
$customerList = [];

try {
    $totalCustomersStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE store_name = :storeName");
    $totalCustomersStmt->bindParam(':storeName', $storeName);
    $totalCustomersStmt->execute();
    $totalCustomers = $totalCustomersStmt->fetchColumn();

    $dashboardQuery = "
        SELECT 
            SUM(total_sales) as total_sales, 
            SUM(delivery_count) as total_deliveries, 
            AVG(avg_lead_time) as avg_lead_time 
        FROM customer_summary
        WHERE store_name = :storeName
    ";
    $dashboardMetricsStmt = $pdo->prepare($dashboardQuery);
    $dashboardMetricsStmt->bindParam(':storeName', $storeName);
    $dashboardMetricsStmt->execute();
    $dashboardMetrics = $dashboardMetricsStmt->fetch(PDO::FETCH_ASSOC);

    $totalDeliveries = $dashboardMetrics['total_deliveries'] ?? 0;
    $avgLeadTime = $dashboardMetrics['avg_lead_time'] ?? 0;

    $currentMonthQuery = "
        SELECT SUM(di.amount) 
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        JOIN order_items oi ON di.order_item_no = oi.order_item_no
        JOIN orders o ON oi.order_no = o.order_no
        JOIN customers c ON o.customer_no = c.customer_no
        WHERE YEAR(d.delivery_record) = YEAR(CURDATE()) 
          AND MONTH(d.delivery_record) = MONTH(CURDATE())
          AND c.store_name = :storeName
    ";
    $currentMonthSalesStmt = $pdo->prepare($currentMonthQuery);
    $currentMonthSalesStmt->bindParam(':storeName', $storeName);
    $currentMonthSalesStmt->execute();
    $monthlySales = $currentMonthSalesStmt->fetchColumn() ?? 0;

    $previousMonthQuery = "
        SELECT SUM(di.amount) 
        FROM deliveries d
        JOIN delivery_items di ON d.delivery_no = di.delivery_no
        JOIN order_items oi ON di.order_item_no = oi.order_item_no
        JOIN orders o ON oi.order_no = o.order_no
        JOIN customers c ON o.customer_no = c.customer_no
        WHERE YEAR(d.delivery_record) = YEAR(CURDATE() - INTERVAL 1 MONTH) 
          AND MONTH(d.delivery_record) = MONTH(CURDATE() - INTERVAL 1 MONTH)
          AND c.store_name = :storeName
    ";
    $previousMonthSalesStmt = $pdo->prepare($previousMonthQuery);
    $previousMonthSalesStmt->bindParam(':storeName', $storeName);
    $previousMonthSalesStmt->execute();
    $previousMonthSales = $previousMonthSalesStmt->fetchColumn() ?? 0;

    if ($previousMonthSales > 0) {
        $salesTrend = (($monthlySales - $previousMonthSales) / $previousMonthSales) * 100;
    } elseif ($monthlySales > 0) {
        $salesTrend = 100;
    } else {
        $salesTrend = 0;
    }

    $customerListQuery = "SELECT * FROM customer_summary WHERE store_name = :storeName ORDER BY total_sales DESC";
    $customersStmt = $pdo->prepare($customerListQuery);
    $customersStmt->bindParam(':storeName', $storeName);
    $customersStmt->execute();
    $customerList = $customersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Statistics page database error: " . $e->getMessage());
    die("データベースへの接続中にエラーが発生しました。しばらくしてからもう一度お試しください。");
}

function format_yen($amount)
{
    if ($amount >= 1000000) {
        return '¥' . number_format($amount / 1000000, 2) . 'M';
    } elseif ($amount >= 1000) {
        return '¥' . number_format($amount / 1000, 1) . 'K';
    }
    return '¥' . number_format($amount);
}

function format_days($days)
{
    return number_format($days, 2) . '日';
}
?>

<!-- Statistics content starts here -->
<?php include 'dashboard_content.php'; ?>
<?php include 'customer_list_content.php'; ?>
<?php include 'all_customers_content.php'; ?>

</div>
</main>
</div>

<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="detailTitle">顧客詳細</h2>
            <button class="close-modal" onclick="closeModal('detailModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailContent">
        </div>
    </div>
</div>

<script>
window.customerData = <?php echo json_encode($customerList); ?>;
</script>

</body>

</html>