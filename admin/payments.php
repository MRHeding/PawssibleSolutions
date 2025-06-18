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

// Set default filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$client_filter = isset($_GET['client_id']) ? $_GET['client_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get clients for filter dropdown
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'client' ORDER BY first_name, last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the invoices query
$query = "SELECT i.*, CONCAT(u.first_name, ' ', u.last_name) as client_name, u.email, u.phone,
         a.appointment_number, a.appointment_date, a.appointment_time, p.name as pet_name
         FROM invoices i
         JOIN users u ON i.client_id = u.id
         LEFT JOIN appointments a ON i.appointment_id = a.id
         LEFT JOIN pets p ON a.pet_id = p.id
         WHERE 1=1";

// Add filters
if (!empty($status_filter)) {
    if ($status_filter === 'paid') {
        $query .= " AND i.paid = 1";
    } elseif ($status_filter === 'unpaid') {
        $query .= " AND i.paid = 0";
    }
}

if (!empty($date_filter)) {
    $query .= " AND DATE(i.created_at) = :date";
}

if (!empty($client_filter)) {
    $query .= " AND i.client_id = :client_id";
}

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR a.appointment_number LIKE :search)";
}

$query .= " ORDER BY i.created_at DESC";

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind filter parameters
if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}
if (!empty($client_filter)) {
    $stmt->bindParam(':client_id', $client_filter);
}
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

// Get payment statistics
$stats_query = "SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_amount,
                SUM(CASE WHEN paid = 1 THEN total_amount ELSE 0 END) as paid_amount,
                SUM(CASE WHEN paid = 0 THEN total_amount ELSE 0 END) as unpaid_amount,
                COUNT(CASE WHEN paid = 1 THEN 1 END) as paid_count,
                COUNT(CASE WHEN paid = 0 THEN 1 END) as unpaid_count
                FROM invoices";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Payment Management</h1>
                <p class="text-white text-opacity-90 mt-2">Manage invoices and track payments</p>
            </div>
            <div class="flex space-x-3">
                <a href="create_invoice.php" class="bg-white hover:bg-gray-100 text-green-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-plus mr-2"></i> Create Invoice
                </a>
                <a href="payment_reports.php" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-chart-bar mr-2"></i> Reports
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Payment Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Invoices</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_invoices']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-peso-sign text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Revenue</p>
                    <h3 class="text-2xl font-bold">₱<?php echo number_format($stats['total_amount'], 2); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-emerald-100 p-3 rounded-full mr-4">
                    <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Paid Amount</p>
                    <h3 class="text-2xl font-bold text-emerald-600">₱<?php echo number_format($stats['paid_amount'], 2); ?></h3>
                    <p class="text-xs text-gray-500"><?php echo $stats['paid_count']; ?> invoices</p>
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
                    <p class="text-xs text-gray-500"><?php echo $stats['unpaid_count']; ?> invoices</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Payments</h3>
        <form action="" method="get" class="flex flex-wrap md:flex-nowrap gap-4">
            <div class="w-full md:w-1/5">
                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="unpaid" <?php echo $status_filter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                </select>
            </div>
            
            <div class="w-full md:w-1/5">
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Date</label>
                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
            </div>
            
            <div class="w-full md:w-1/5">
                <label for="client_id" class="block text-gray-700 text-sm font-bold mb-2">Client</label>
                <select id="client_id" name="client_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $client_filter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-2/5">
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <div class="flex">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by client name or invoice #..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($status_filter) || !empty($date_filter) || !empty($client_filter) || !empty($search)): ?>
            <div class="mt-4 flex justify-end">
                <a href="payments.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times-circle mr-1"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Invoices List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($invoice = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#INV-<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($invoice['email']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($invoice['appointment_number']): ?>
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($invoice['appointment_number']); ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($invoice['appointment_date'])); ?>
                                            <?php if ($invoice['pet_name']): ?>
                                                - <?php echo htmlspecialchars($invoice['pet_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">No appointment</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">₱<?php echo number_format($invoice['total_amount'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $invoice['paid'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $invoice['paid'] ? 'Paid' : 'Unpaid'; ?>
                                    </span>
                                    <?php if ($invoice['paid'] && $invoice['payment_date']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo date('M d, Y', strtotime($invoice['payment_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($invoice['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Invoice">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-green-600 hover:text-green-900" title="Edit Invoice">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$invoice['paid']): ?>
                                            <a href="record_payment.php?id=<?php echo $invoice['id']; ?>" class="text-emerald-600 hover:text-emerald-900" title="Record Payment">
                                                <i class="fas fa-peso-sign"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="print_invoice.php?id=<?php echo $invoice['id']; ?>" target="_blank" class="text-purple-600 hover:text-purple-900" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-file-invoice text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Invoices Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($status_filter) || !empty($date_filter) || !empty($client_filter) || !empty($search)): ?>
                    No invoices match your current filters. Try adjusting your filter criteria.
                <?php else: ?>
                    There are no invoices in the system yet.
                <?php endif; ?>
            </p>
            <a href="create_invoice.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded transition">
                Create First Invoice
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>