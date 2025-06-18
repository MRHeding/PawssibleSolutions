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

// Initialize variables
$message = '';
$messageClass = '';
$appointment_id = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : '';
$client_id = isset($_GET['client_id']) ? $_GET['client_id'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $appointment_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
    $notes = trim($_POST['notes']);
    $services = $_POST['services'] ?? [];
    
    if (empty($client_id)) {
        $message = "Please select a client";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    } elseif (empty($services)) {
        $message = "Please add at least one service";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    } else {
        try {
            $db->beginTransaction();
            
            // Calculate total amount
            $total_amount = 0;
            foreach ($services as $service) {
                $total_amount += $service['quantity'] * $service['unit_price'];
            }
            
            // Create invoice
            $invoice_query = "INSERT INTO invoices (appointment_id, client_id, total_amount, notes, created_at) 
                             VALUES (:appointment_id, :client_id, :total_amount, :notes, NOW())";
            $invoice_stmt = $db->prepare($invoice_query);
            $invoice_stmt->bindParam(':appointment_id', $appointment_id);
            $invoice_stmt->bindParam(':client_id', $client_id);
            $invoice_stmt->bindParam(':total_amount', $total_amount);
            $invoice_stmt->bindParam(':notes', $notes);
            $invoice_stmt->execute();
            
            $invoice_id = $db->lastInsertId();
            
            // Add invoice items
            $item_query = "INSERT INTO invoice_items (invoice_id, service_id, description, quantity, unit_price, total_price) 
                          VALUES (:invoice_id, :service_id, :description, :quantity, :unit_price, :total_price)";
            $item_stmt = $db->prepare($item_query);
            
            foreach ($services as $service) {
                $total_price = $service['quantity'] * $service['unit_price'];
                $item_stmt->bindParam(':invoice_id', $invoice_id);
                $item_stmt->bindParam(':service_id', $service['service_id']);
                $item_stmt->bindParam(':description', $service['description']);
                $item_stmt->bindParam(':quantity', $service['quantity']);
                $item_stmt->bindParam(':unit_price', $service['unit_price']);
                $item_stmt->bindParam(':total_price', $total_price);
                $item_stmt->execute();
            }
            
            $db->commit();
            
            $message = "Invoice created successfully!";
            $messageClass = "bg-green-100 border-green-400 text-green-700";
            
            // Redirect after 2 seconds
            header("refresh:2;url=view_invoice.php?id=" . $invoice_id);
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Error creating invoice: " . $e->getMessage();
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    }
}

// Get clients
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email FROM users WHERE role = 'client' ORDER BY first_name, last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get appointments for dropdown (completed appointments without invoices)
$appointments_query = "SELECT a.id, a.appointment_number, a.appointment_date, a.appointment_time,
                      CONCAT(u.first_name, ' ', u.last_name) as client_name, p.name as pet_name
                      FROM appointments a
                      JOIN pets p ON a.pet_id = p.id
                      JOIN users u ON p.owner_id = u.id
                      LEFT JOIN invoices i ON a.id = i.appointment_id
                      WHERE a.status = 'completed' AND i.id IS NULL
                      ORDER BY a.appointment_date DESC";
$appointments_stmt = $db->prepare($appointments_query);
$appointments_stmt->execute();
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get services
$services_query = "SELECT * FROM services WHERE active = 1 ORDER BY category, name";
$services_stmt = $db->prepare($services_query);
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group services by category
$services_by_category = [];
foreach ($services as $service) {
    $category = $service['category'] ?: 'Other';
    $services_by_category[$category][] = $service;
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Create Invoice</h1>
                <p class="text-white text-opacity-90 mt-2">Generate a new invoice for services</p>
            </div>
            <a href="payments.php" class="bg-white hover:bg-gray-100 text-green-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Payments
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-6 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="invoiceForm">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Invoice Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-6 text-green-700">Invoice Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                            <select name="client_id" id="client_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>" <?php echo ($client_id == $client['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['email']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="appointment_id" class="block text-sm font-medium text-gray-700 mb-2">Related Appointment (Optional)</label>
                            <select name="appointment_id" id="appointment_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">No Appointment</option>
                                <?php foreach ($appointments as $appointment): ?>
                                    <option value="<?php echo $appointment['id']; ?>" <?php echo ($appointment_id == $appointment['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($appointment['appointment_number']); ?> - 
                                        <?php echo htmlspecialchars($appointment['client_name']); ?> 
                                        (<?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Additional notes or payment terms..."></textarea>
                    </div>
                    
                    <!-- Action Buttons moved inside Invoice Details -->
                    <div class="mt-6 flex justify-end space-x-4">
                        <a href="payments.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                            Cancel
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                            Create Invoice
                        </button>
                    </div>
                </div>
                
                <!-- Services Section -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-green-700">Services & Items</h2>
                        <button type="button" onclick="addServiceRow()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                            <i class="fas fa-plus mr-1"></i> Add Service
                        </button>
                    </div>
                    
                    <div id="servicesContainer">
                        <!-- Service rows will be added here dynamically -->
                    </div>
                    
                    <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-center text-lg font-semibold">
                            <span>Total Amount:</span>
                            <span id="totalAmount">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Services Sidebar -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold mb-4 text-green-700">Available Services</h3>
                    
                    <?php foreach ($services_by_category as $category => $category_services): ?>
                        <div class="mb-4">
                            <h4 class="font-medium text-gray-800 mb-2"><?php echo htmlspecialchars($category); ?></h4>
                            <?php foreach ($category_services as $service): ?>
                                <div class="service-item border border-gray-200 rounded p-3 mb-2 cursor-pointer hover:bg-gray-50" 
                                     onclick="addServiceFromSidebar(<?php echo $service['id']; ?>, '<?php echo addslashes($service['name']); ?>', <?php echo $service['price']; ?>)">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-medium text-sm"><?php echo htmlspecialchars($service['name']); ?></div>
                                            <?php if ($service['description']): ?>
                                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(substr($service['description'], 0, 50)); ?>...</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-green-600 font-medium">₱<?php echo number_format($service['price'], 2); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
let serviceRowCounter = 0;

function addServiceRow(serviceId = '', serviceName = '', price = 0) {
    serviceRowCounter++;
    const container = document.getElementById('servicesContainer');
    
    const row = document.createElement('div');
    row.className = 'service-row border border-gray-200 rounded p-4 mb-4';
    row.id = 'service-row-' + serviceRowCounter;
    
    row.innerHTML = `
        <div class="grid grid-cols-12 gap-4 items-end">
            <div class="col-span-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Service Description</label>
                <input type="text" name="services[${serviceRowCounter}][description]" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                       value="${serviceName}" required>
                <input type="hidden" name="services[${serviceRowCounter}][service_id]" value="${serviceId}">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number" name="services[${serviceRowCounter}][quantity]" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                       value="1" min="1" step="1" onchange="calculateRowTotal(${serviceRowCounter})" required>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                <input type="number" name="services[${serviceRowCounter}][unit_price]" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                       value="${price}" min="0" step="0.01" onchange="calculateRowTotal(${serviceRowCounter})" required>
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Total</label>
                <div class="p-2 bg-gray-50 border border-gray-300 rounded-md text-right font-medium" id="row-total-${serviceRowCounter}">
                    ₱${price.toFixed(2)}
                </div>
            </div>
            <div class="col-span-1">
                <button type="button" onclick="removeServiceRow(${serviceRowCounter})" 
                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(row);
    calculateTotal();
}

function removeServiceRow(rowId) {
    const row = document.getElementById('service-row-' + rowId);
    if (row) {
        row.remove();
        calculateTotal();
    }
}

function calculateRowTotal(rowId) {
    const quantity = document.querySelector(`input[name="services[${rowId}][quantity]"]`).value;
    const unitPrice = document.querySelector(`input[name="services[${rowId}][unit_price]"]`).value;
    const total = (parseFloat(quantity) || 0) * (parseFloat(unitPrice) || 0);
    
    document.getElementById('row-total-' + rowId).textContent = '₱' + total.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    const rows = document.querySelectorAll('.service-row');
    
    rows.forEach(row => {
        const quantity = row.querySelector('input[name*="[quantity]"]').value;
        const unitPrice = row.querySelector('input[name*="[unit_price]"]').value;
        total += (parseFloat(quantity) || 0) * (parseFloat(unitPrice) || 0);
    });
    
    document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2);
}

function addServiceFromSidebar(serviceId, serviceName, price) {
    addServiceRow(serviceId, serviceName, price);
}

// Add initial service row
document.addEventListener('DOMContentLoaded', function() {
    addServiceRow();
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>