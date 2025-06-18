<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Set default date range (current month)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$client_filter = isset($_GET['client_id']) ? $_GET['client_id'] : '';
$payment_method_filter = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

// Get payment statistics for the date range
$stats_query = "SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN paid = 1 THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN paid = 0 THEN total_amount ELSE 0 END) as unpaid_amount,
                COUNT(CASE WHEN paid = 1 THEN 1 END) as paid_count,
                COUNT(CASE WHEN paid = 0 THEN 1 END) as unpaid_count,
                AVG(total_amount) as avg_invoice_amount
                FROM invoices 
                WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

if (!empty($client_filter)) {
    $stats_query .= " AND client_id = :client_id";
}

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':start_date', $start_date);
$stats_stmt->bindParam(':end_date', $end_date);
if (!empty($client_filter)) {
    $stats_stmt->bindParam(':client_id', $client_filter);
}
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get payment method breakdown
$payment_methods_query = "SELECT payment_method, 
                         COUNT(*) as count, 
                         SUM(total_amount) as total 
                         FROM invoices 
                         WHERE paid = 1 AND payment_method IS NOT NULL 
                         AND DATE(payment_date) BETWEEN :start_date AND :end_date";

if (!empty($client_filter)) {
    $payment_methods_query .= " AND client_id = :client_id";
}

$payment_methods_query .= " GROUP BY payment_method ORDER BY total DESC";

$payment_methods_stmt = $db->prepare($payment_methods_query);
$payment_methods_stmt->bindParam(':start_date', $start_date);
$payment_methods_stmt->bindParam(':end_date', $end_date);
if (!empty($client_filter)) {
    $payment_methods_stmt->bindParam(':client_id', $client_filter);
}
$payment_methods_stmt->execute();
$payment_methods = $payment_methods_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily revenue data for chart
$daily_revenue_query = "SELECT DATE(created_at) as date,
                       SUM(CASE WHEN paid = 1 THEN total_amount ELSE 0 END) as revenue,
                       COUNT(*) as invoices_created,
                       COUNT(CASE WHEN paid = 1 THEN 1 END) as payments_received
                       FROM invoices 
                       WHERE DATE(created_at) BETWEEN :start_date AND :end_date";

if (!empty($client_filter)) {
    $daily_revenue_query .= " AND client_id = :client_id";
}

$daily_revenue_query .= " GROUP BY DATE(created_at) ORDER BY DATE(created_at)";

$daily_revenue_stmt = $db->prepare($daily_revenue_query);
$daily_revenue_stmt->bindParam(':start_date', $start_date);
$daily_revenue_stmt->bindParam(':end_date', $end_date);
if (!empty($client_filter)) {
    $daily_revenue_stmt->bindParam(':client_id', $client_filter);
}
$daily_revenue_stmt->execute();
$daily_revenue = $daily_revenue_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top clients by revenue
$top_clients_query = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as client_name,
                     COUNT(i.id) as invoice_count,
                     SUM(i.total_amount) as total_revenue,
                     SUM(CASE WHEN i.paid = 1 THEN i.total_amount ELSE 0 END) as paid_revenue
                     FROM users u
                     JOIN invoices i ON u.id = i.client_id
                     WHERE DATE(i.created_at) BETWEEN :start_date AND :end_date
                     GROUP BY u.id
                     ORDER BY total_revenue DESC
                     LIMIT 10";

$top_clients_stmt = $db->prepare($top_clients_query);
$top_clients_stmt->bindParam(':start_date', $start_date);
$top_clients_stmt->bindParam(':end_date', $end_date);
$top_clients_stmt->execute();
$top_clients = $top_clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clients for filter
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'client' ORDER BY first_name, last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Payment Reports</h1>
                <p class="text-white text-opacity-90 mt-2">Financial analytics and billing reports</p>
            </div>
            <div class="flex space-x-3">
                <a href="payments.php" class="bg-white hover:bg-gray-100 text-blue-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Payments
                </a>
                <button onclick="exportReport()" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-download mr-2"></i> Export Report
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Report Filters</h3>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div>
                <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div>
                <label for="client_id" class="block text-gray-700 text-sm font-bold mb-2">Client (Optional)</label>
                <select id="client_id" name="client_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $client_filter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                    <i class="fas fa-chart-bar mr-2"></i> Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-peso-sign text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-green-600">₱<?php echo number_format($stats['paid_amount'], 2); ?></h3>
                    <p class="text-xs text-gray-500"><?php echo $stats['paid_count']; ?> payments</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Invoiced</p>
                    <h3 class="text-2xl font-bold">₱<?php echo number_format($stats['total_amount'], 2); ?></h3>
                    <p class="text-xs text-gray-500"><?php echo $stats['total_invoices']; ?> invoices</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-red-100 p-3 rounded-full mr-4">
                    <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Outstanding</p>
                    <h3 class="text-2xl font-bold text-red-600">₱<?php echo number_format($stats['unpaid_amount'], 2); ?></h3>
                    <p class="text-xs text-gray-500"><?php echo $stats['unpaid_count']; ?> unpaid</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-full mr-4">
                    <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Avg Invoice</p>
                    <h3 class="text-2xl font-bold">₱<?php echo number_format($stats['avg_invoice_amount'], 2); ?></h3>
                    <p class="text-xs text-gray-500">
                        <?php echo $stats['total_invoices'] > 0 ? round(($stats['paid_count'] / $stats['total_invoices']) * 100, 1) : 0; ?>% paid
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Daily Revenue Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Daily Revenue Trend</h3>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Payment Methods -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Payment Methods</h3>
            <?php if (!empty($payment_methods)): ?>
                <div class="space-y-4">
                    <?php foreach ($payment_methods as $method): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <div class="font-medium capitalize"><?php echo str_replace('_', ' ', $method['payment_method']); ?></div>
                                <div class="text-sm text-gray-600"><?php echo $method['count']; ?> transactions</div>
                            </div>
                            <div class="text-lg font-bold text-blue-600">₱<?php echo number_format($method['total'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No payment data available for the selected period</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Clients -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Top Clients by Revenue</h3>
        <?php if (!empty($top_clients)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoices</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($top_clients as $client): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="client_pets.php?owner_id=<?php echo $client['id']; ?>" class="text-blue-600 hover:text-blue-900 font-medium">
                                        <?php echo htmlspecialchars($client['client_name']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $client['invoice_count']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ₱<?php echo number_format($client['total_revenue'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                    ₱<?php echo number_format($client['paid_revenue'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $payment_rate = $client['total_revenue'] > 0 ? ($client['paid_revenue'] / $client['total_revenue']) * 100 : 0;
                                    $rate_class = $payment_rate >= 80 ? 'text-green-600' : ($payment_rate >= 50 ? 'text-yellow-600' : 'text-red-600');
                                    ?>
                                    <span class="text-sm font-medium <?php echo $rate_class; ?>">
                                        <?php echo round($payment_rate, 1); ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">No client data available for the selected period</p>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const dailyData = <?php echo json_encode($daily_revenue); ?>;

const labels = dailyData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});

const revenueData = dailyData.map(item => parseFloat(item.revenue) || 0);

new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Daily Revenue',
            data: revenueData,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toFixed(0);
                    }
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});

function exportReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const clientId = document.getElementById('client_id').value;
    
    let url = 'export_payment_report.php?start_date=' + startDate + '&end_date=' + endDate;
    if (clientId) {
        url += '&client_id=' + clientId;
    }
    
    window.open(url, '_blank');
}
</script>

<?php include_once '../includes/admin_footer.php'; ?>