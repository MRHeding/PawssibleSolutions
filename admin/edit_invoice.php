<?php
session_start();

// Cache Control Headers - must be set before any output
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if invoice ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: payments.php");
    exit;
}

$invoice_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if invoice exists
$check_query = "SELECT id FROM invoices WHERE id = :invoice_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':invoice_id', $invoice_id);
$check_stmt->execute();

if ($check_stmt->rowCount() == 0) {
    $_SESSION['error'] = "Invoice not found.";
    header("Location: payments.php");
    exit;
}

// Initialize variables
$message = '';
$messageClass = '';

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
            
            // Update invoice
            $update_query = "UPDATE invoices SET appointment_id = :appointment_id, client_id = :client_id, 
                           total_amount = :total_amount, notes = :notes, updated_at = NOW() 
                           WHERE id = :invoice_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':appointment_id', $appointment_id);
            $update_stmt->bindParam(':client_id', $client_id);
            $update_stmt->bindParam(':total_amount', $total_amount);
            $update_stmt->bindParam(':notes', $notes);
            $update_stmt->bindParam(':invoice_id', $invoice_id);
            $update_stmt->execute();
            
            // Before deleting existing items, restore inventory quantities for inventory items
            $restore_query = "SELECT ii.service_id, ii.quantity, ii.description 
                             FROM invoice_items ii 
                             WHERE ii.invoice_id = :invoice_id AND ii.service_id IS NULL";
            $restore_stmt = $db->prepare($restore_query);
            $restore_stmt->bindParam(':invoice_id', $invoice_id);
            $restore_stmt->execute();
            $existing_inventory_items = $restore_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore inventory quantities for items that were inventory items
            foreach ($existing_inventory_items as $item) {
                // Try to match with inventory items by description to restore quantity
                $description_parts = explode(' (', $item['description']);
                $item_name = trim($description_parts[0]);
                
                $find_inv_query = "SELECT id FROM inventory WHERE name LIKE :item_name LIMIT 1";
                $find_inv_stmt = $db->prepare($find_inv_query);
                $search_name = '%' . $item_name . '%';
                $find_inv_stmt->bindParam(':item_name', $search_name);
                $find_inv_stmt->execute();
                $inv_result = $find_inv_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($inv_result) {
                    $restore_inv_query = "UPDATE inventory SET quantity = quantity + :restore_qty WHERE id = :inv_id";
                    $restore_inv_stmt = $db->prepare($restore_inv_query);
                    $restore_inv_stmt->bindParam(':restore_qty', $item['quantity']);
                    $restore_inv_stmt->bindParam(':inv_id', $inv_result['id']);
                    $restore_inv_stmt->execute();
                }
            }
            
            // Delete existing invoice items
            $delete_items_query = "DELETE FROM invoice_items WHERE invoice_id = :invoice_id";
            $delete_items_stmt = $db->prepare($delete_items_query);
            $delete_items_stmt->bindParam(':invoice_id', $invoice_id);
            $delete_items_stmt->execute();
            
            // Add new invoice items
            $item_query = "INSERT INTO invoice_items (invoice_id, service_id, description, quantity, unit_price, total_price) 
                          VALUES (:invoice_id, :service_id, :description, :quantity, :unit_price, :total_price)";
            $item_stmt = $db->prepare($item_query);
            
            foreach ($services as $service) {
                $total_price = $service['quantity'] * $service['unit_price'];
                
                // Handle inventory items (service_id starts with 'inv_')
                $service_id = null;
                if (strpos($service['service_id'], 'inv_') === 0) {
                    // For inventory items, set service_id to null
                    $service_id = null;
                    
                    // Also update inventory quantity
                    $inventory_id = str_replace('inv_', '', $service['service_id']);
                    $update_inventory = "UPDATE inventory SET quantity = quantity - :used_qty WHERE id = :inv_id";
                    $update_stmt = $db->prepare($update_inventory);
                    $update_stmt->bindParam(':used_qty', $service['quantity']);
                    $update_stmt->bindParam(':inv_id', $inventory_id);
                    $update_stmt->execute();
                } elseif (is_numeric($service['service_id'])) {
                    // For database services with numeric IDs, use the service_id
                    $service_id = $service['service_id'];
                } else {
                    // For appointment-type services (Check-up, Vaccination, etc.), set service_id to null
                    // These are treated as custom services and stored in description only
                    $service_id = null;
                }
                
                $item_stmt->bindParam(':invoice_id', $invoice_id);
                $item_stmt->bindParam(':service_id', $service_id);
                $item_stmt->bindParam(':description', $service['description']);
                $item_stmt->bindParam(':quantity', $service['quantity']);
                $item_stmt->bindParam(':unit_price', $service['unit_price']);
                $item_stmt->bindParam(':total_price', $total_price);
                $item_stmt->execute();
            }
            
            $db->commit();
            
            $message = "Invoice updated successfully!";
            $messageClass = "bg-green-100 border-green-400 text-green-700";
            
            // Redirect after 2 seconds
            header("refresh:2;url=view_invoice.php?id=" . $invoice_id);
            
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Error updating invoice: " . $e->getMessage();
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    }
}

// Get current invoice details
$invoice_query = "SELECT i.*, CONCAT(u.first_name, ' ', u.last_name) as client_name
                 FROM invoices i
                 JOIN users u ON i.client_id = u.id
                 WHERE i.id = :invoice_id";
$invoice_stmt = $db->prepare($invoice_query);
$invoice_stmt->bindParam(':invoice_id', $invoice_id);
$invoice_stmt->execute();
$invoice = $invoice_stmt->fetch(PDO::FETCH_ASSOC);

// Get current invoice items
$items_query = "SELECT ii.*, s.name as service_name, s.category 
                FROM invoice_items ii
                LEFT JOIN services s ON ii.service_id = s.id
                WHERE ii.invoice_id = :invoice_id ORDER BY ii.id";
$items_stmt = $db->prepare($items_query);
$items_stmt->bindParam(':invoice_id', $invoice_id);
$items_stmt->execute();
$current_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clients
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name, email FROM users WHERE role = 'client' ORDER BY first_name, last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all appointments for the selected client
$appointments_query = "SELECT a.id, a.appointment_number, a.appointment_date, a.appointment_time,
                      CONCAT(u.first_name, ' ', u.last_name) as client_name, p.name as pet_name
                      FROM appointments a
                      JOIN pets p ON a.pet_id = p.id
                      JOIN users u ON p.owner_id = u.id
                      WHERE u.id = :client_id OR :client_id = 0
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointments_stmt = $db->prepare($appointments_query);
$appointments_stmt->bindParam(':client_id', $invoice['client_id']);
$appointments_stmt->execute();
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get services
$services_query = "SELECT id, name, price, category FROM services WHERE active = 1 ORDER BY category, name";
$services_stmt = $db->prepare($services_query);
$services_stmt->execute();
$available_services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get inventory items
$inventory_query = "SELECT id, name, unit_price, unit, category, quantity FROM inventory WHERE quantity > 0 ORDER BY category, name";
$inventory_stmt = $db->prepare($inventory_query);
$inventory_stmt->execute();
$available_inventory = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Edit Invoice #<?php echo $invoice_id; ?></h1>
        <p class="text-white text-opacity-90 mt-2">Modify invoice details and services</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> border px-4 py-3 rounded mb-6">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <form method="POST" id="editInvoiceForm">
            <!-- Client Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Client *</label>
                <select name="client_id" id="clientSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                    <option value="">Select a client</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo ($client['id'] == $invoice['client_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?> (<?php echo htmlspecialchars($client['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Appointment Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Related Appointment (Optional)</label>
                <select name="appointment_id" id="appointmentSelect" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    <option value="">No specific appointment</option>
                    <?php foreach ($appointments as $appointment): ?>
                        <option value="<?php echo $appointment['id']; ?>" <?php echo ($appointment['id'] == $invoice['appointment_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($appointment['appointment_number']); ?> - 
                            <?php echo date('M j, Y g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time'])); ?> - 
                            <?php echo htmlspecialchars($appointment['pet_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Services Section -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Services & Items</h3>
                    <div class="flex gap-2">
                        <button type="button" id="addServiceBtn" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                            <i class="fas fa-plus mr-2"></i>Add Service
                        </button>
                        <button type="button" id="addInventoryBtn" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            <i class="fas fa-box mr-2"></i>Add Item
                        </button>
                    </div>
                </div>

                <div id="servicesContainer">
                    <?php foreach ($current_items as $index => $item): ?>
                        <div class="service-item bg-gray-50 p-4 rounded-lg mb-4">
                            <?php 
                            // Determine if this is an inventory item vs appointment service
                            // Inventory items have NULL service_id AND description contains unit info in parentheses
                            // Appointment services have NULL service_id BUT are standard appointment reasons
                            $isInventoryItem = false;
                            $inventoryId = null;
                            $availableQty = 0;
                            
                            if (is_null($item['service_id'])) {
                                // Check if description pattern suggests it's an inventory item (contains units in parentheses)
                                $appointmentServices = ['Regular Check-up', 'Vaccination', 'Illness', 'Injury', 'Surgery', 'Dental Care', 'Follow-up Visit', 'Other'];
                                $isAppointmentService = in_array($item['description'], $appointmentServices);
                                
                                // If it's not a standard appointment service and contains unit info, treat as inventory
                                if (!$isAppointmentService && (strpos($item['description'], ' (') !== false)) {
                                    $isInventoryItem = true;
                                    
                                    // Try to find the inventory item by description
                                    $description_parts = explode(' (', $item['description']);
                                    $item_name = trim($description_parts[0]);
                                    
                                    $find_inv_query = "SELECT id, quantity FROM inventory WHERE name LIKE :item_name LIMIT 1";
                                    $find_inv_stmt = $db->prepare($find_inv_query);
                                    $search_name = '%' . $item_name . '%';
                                    $find_inv_stmt->bindParam(':item_name', $search_name);
                                    $find_inv_stmt->execute();
                                    $inv_result = $find_inv_stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($inv_result) {
                                        $inventoryId = $inv_result['id'];
                                        $availableQty = $inv_result['quantity'];
                                    }
                                }
                            }
                            ?>
                            
                            <?php if ($isInventoryItem): ?>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-green-600 bg-green-100 px-2 py-1 rounded">INVENTORY ITEM</span>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-purple-600 bg-purple-100 px-2 py-1 rounded">SERVICE</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Service/Item</label>
                                    <?php if ($isInventoryItem): ?>
                                        <select name="services[<?php echo $index; ?>][service_id]" class="inventory-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                                            <option value="">Select Inventory Item</option>
                                            <?php 
                                            $current_category = '';
                                            foreach ($available_inventory as $inv_item): 
                                                if ($inv_item['category'] !== $current_category):
                                                    if ($current_category !== '') echo '</optgroup>';
                                                    echo '<optgroup label="' . htmlspecialchars($inv_item['category']) . '">';
                                                    $current_category = $inv_item['category'];
                                                endif;
                                                $selected = ($inventoryId && $inventoryId == $inv_item['id']) ? 'selected' : '';
                                            ?>
                                                <option value="inv_<?php echo $inv_item['id']; ?>" 
                                                        data-price="<?php echo $inv_item['unit_price']; ?>"
                                                        data-name="<?php echo htmlspecialchars($inv_item['name']); ?>"
                                                        data-unit="<?php echo htmlspecialchars($inv_item['unit']); ?>"
                                                        data-quantity="<?php echo $inv_item['quantity']; ?>" <?php echo $selected; ?>>
                                                    <?php echo htmlspecialchars($inv_item['name']); ?> - ₱<?php echo number_format($inv_item['unit_price'], 2); ?> per <?php echo htmlspecialchars($inv_item['unit']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                            <?php if ($current_category !== '') echo '</optgroup>'; ?>
                                        </select>
                                    <?php else: ?>
                                        <select name="services[<?php echo $index; ?>][service_id]" class="service-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                                            <option value="">Select Service</option>
                                            <?php
                                            // Map description to service option value for proper selection
                                            $serviceMapping = [
                                                'Regular Check-up' => 'Check-up',
                                                'Wellness Exam' => 'Check-up',  // Map database service to appointment service
                                                'Vaccination' => 'Vaccination', 
                                                'Vaccination - Core' => 'Vaccination',
                                                'Vaccination - Non-Core' => 'Vaccination',
                                                'Illness' => 'Illness',
                                                'Injury' => 'Injury',
                                                'Surgery' => 'Surgery',
                                                'Spay/Neuter - Dog' => 'Surgery',
                                                'Spay/Neuter - Cat' => 'Surgery',
                                                'Dental Care' => 'Dental',
                                                'Dental Cleaning' => 'Dental',
                                                'Follow-up Visit' => 'Follow-up',
                                                'Other' => 'Other',
                                                'Emergency Consultation' => 'Other',
                                                'Microchipping' => 'Other',
                                                'Nail Trim' => 'Other',
                                                'Anal Gland Expression' => 'Other'
                                            ];
                                            $selectedValue = isset($serviceMapping[$item['description']]) ? $serviceMapping[$item['description']] : '';
                                            // Debug: Show what we're working with
                                            // echo "<!-- Debug: Description='{$item['description']}', SelectedValue='$selectedValue' -->";
                                            ?>
                                            <option value="Check-up" data-price="500.00" data-name="Regular Check-up" <?php echo ($selectedValue == 'Check-up') ? 'selected' : ''; ?>>Regular Check-up</option>
                                            <option value="Vaccination" data-price="500.00" data-name="Vaccination" <?php echo ($selectedValue == 'Vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                                            <option value="Illness" data-price="1000.00" data-name="Illness" <?php echo ($selectedValue == 'Illness') ? 'selected' : ''; ?>>Illness</option>
                                            <option value="Injury" data-price="2000.00" data-name="Injury" <?php echo ($selectedValue == 'Injury') ? 'selected' : ''; ?>>Injury</option>
                                            <option value="Surgery" data-price="700.00" data-name="Surgery" <?php echo ($selectedValue == 'Surgery') ? 'selected' : ''; ?>>Surgery</option>
                                            <option value="Dental" data-price="500.00" data-name="Dental Care" <?php echo ($selectedValue == 'Dental') ? 'selected' : ''; ?>>Dental Care</option>
                                            <option value="Follow-up" data-price="300.00" data-name="Follow-up Visit" <?php echo ($selectedValue == 'Follow-up') ? 'selected' : ''; ?>>Follow-up Visit</option>
                                            <option value="Other" data-price="500.00" data-name="Other" <?php echo ($selectedValue == 'Other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <input type="text" name="services[<?php echo $index; ?>][description]" 
                                           value="<?php echo htmlspecialchars($item['description']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                                           placeholder="Service description" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                    <input type="number" name="services[<?php echo $index; ?>][quantity]" 
                                           value="<?php echo $item['quantity']; ?>"
                                           class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                                           min="1" <?php echo $isInventoryItem ? 'max="' . ($availableQty + $item['quantity']) . '"' : 'readonly'; ?> step="1" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (₱)</label>
                                    <input type="number" name="services[<?php echo $index; ?>][unit_price]" 
                                           value="<?php echo $item['unit_price']; ?>"
                                           class="unit-price-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                                           min="0" step="0.01" required>
                                </div>
                                <div>
                                    <button type="button" class="remove-service bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 w-full">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-right">
                                <span class="text-sm text-gray-600">Total: ₱</span>
                                <span class="service-total font-semibold"><?php echo number_format($item['total_price'], 2); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-right text-lg font-semibold mt-4">
                    Total Amount: ₱<span id="grandTotal"><?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea name="notes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                          placeholder="Additional notes or comments"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between">
                <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" 
                   class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Invoice
                </a>
                <button type="submit" 
                        class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700">
                    <i class="fas fa-save mr-2"></i>Update Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let serviceIndex = <?php echo count($current_items); ?>;

// Add new service row
document.getElementById('addServiceBtn').addEventListener('click', function() {
    addServiceRow();
});

// Add new inventory row
document.getElementById('addInventoryBtn').addEventListener('click', function() {
    addInventoryRow();
});

function addServiceRow() {
    const container = document.getElementById('servicesContainer');
    const serviceItem = document.createElement('div');
    serviceItem.className = 'service-item bg-gray-50 p-4 rounded-lg mb-4';
    serviceItem.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-purple-600 bg-purple-100 px-2 py-1 rounded">SERVICE</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
                <select name="services[${serviceIndex}][service_id]" class="service-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
                    <option value="">Select Service</option>
                    <option value="Check-up" data-price="500.00" data-name="Regular Check-up">Regular Check-up</option>
                    <option value="Vaccination" data-price="500.00" data-name="Vaccination">Vaccination</option>
                    <option value="Illness" data-price="1000.00" data-name="Illness">Illness</option>
                    <option value="Injury" data-price="2000.00" data-name="Injury">Injury</option>
                    <option value="Surgery" data-price="700.00" data-name="Surgery">Surgery</option>
                    <option value="Dental" data-price="500.00" data-name="Dental Care">Dental Care</option>
                    <option value="Follow-up" data-price="300.00" data-name="Follow-up Visit">Follow-up Visit</option>
                    <option value="Other" data-price="500.00" data-name="Other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="services[${serviceIndex}][description]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                       placeholder="Service description" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number" name="services[${serviceIndex}][quantity]" value="1"
                       class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                       min="1" step="1" readonly required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (₱)</label>
                <input type="number" name="services[${serviceIndex}][unit_price]" value="0.00"
                       class="unit-price-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-purple-500 focus:border-purple-500" 
                       min="0" step="0.01" required>
            </div>
            <div>
                <button type="button" class="remove-service bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 w-full">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
        <div class="mt-2 text-right">
            <span class="text-sm text-gray-600">Total: ₱</span>
            <span class="service-total font-semibold">0.00</span>
        </div>
    `;
    container.appendChild(serviceItem);
    serviceIndex++;
    
    // Add event listeners to new elements
    attachServiceEventListeners(serviceItem);
}

function addInventoryRow() {
    const container = document.getElementById('servicesContainer');
    const serviceItem = document.createElement('div');
    serviceItem.className = 'service-item bg-gray-50 p-4 rounded-lg mb-4';
    serviceItem.innerHTML = `
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-green-600 bg-green-100 px-2 py-1 rounded">INVENTORY ITEM</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Inventory Item</label>
                <select name="services[${serviceIndex}][service_id]" class="inventory-select w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" required>
                    <option value="">Select Inventory Item</option>
                    <?php 
                    $current_category = '';
                    foreach ($available_inventory as $item): 
                        if ($item['category'] !== $current_category):
                            if ($current_category !== '') echo '</optgroup>';
                            echo '<optgroup label="' . htmlspecialchars($item['category']) . '">';
                            $current_category = $item['category'];
                        endif;
                    ?>
                        <option value="inv_<?php echo $item['id']; ?>" 
                                data-price="<?php echo $item['unit_price']; ?>"
                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                data-unit="<?php echo htmlspecialchars($item['unit']); ?>"
                                data-quantity="<?php echo $item['quantity']; ?>">
                            <?php echo htmlspecialchars($item['name']); ?> - ₱<?php echo number_format($item['unit_price'], 2); ?> per <?php echo htmlspecialchars($item['unit']); ?>
                        </option>
                    <?php endforeach; ?>
                    <?php if ($current_category !== '') echo '</optgroup>'; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="services[${serviceIndex}][description]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" 
                       placeholder="Item description" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number" name="services[${serviceIndex}][quantity]" value="1"
                       class="quantity-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" 
                       min="1" max="1" step="1" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price (₱)</label>
                <input type="number" name="services[${serviceIndex}][unit_price]" value="0.00"
                       class="unit-price-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" 
                       min="0" step="0.01" required>
            </div>
            <div>
                <button type="button" class="remove-service bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 w-full">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        </div>
        <div class="mt-2 text-right">
            <span class="text-sm text-gray-600">Total: ₱</span>
            <span class="service-total font-semibold">0.00</span>
        </div>
    `;
    container.appendChild(serviceItem);
    serviceIndex++;
    
    // Add event listeners to new elements
    attachInventoryEventListeners(serviceItem);
}

// Attach event listeners to service elements
function attachServiceEventListeners(serviceItem) {
    const serviceSelect = serviceItem.querySelector('.service-select');
    const descriptionInput = serviceItem.querySelector('input[name*="[description]"]');
    const quantityInput = serviceItem.querySelector('.quantity-input');
    const unitPriceInput = serviceItem.querySelector('.unit-price-input');
    const removeBtn = serviceItem.querySelector('.remove-service');
    
    // Service selection change
    serviceSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const serviceName = selectedOption.getAttribute('data-name');
            const servicePrice = selectedOption.getAttribute('data-price');
            
            descriptionInput.value = serviceName;
            unitPriceInput.value = parseFloat(servicePrice).toFixed(2);
            
            updateServiceTotal(serviceItem);
        }
    });
    
    // Quantity or price change (quantity is readonly for services, so only price changes will trigger this)
    unitPriceInput.addEventListener('input', () => updateServiceTotal(serviceItem));
    
    // Remove service
    removeBtn.addEventListener('click', function() {
        serviceItem.remove();
        updateGrandTotal();
    });
}

// Attach event listeners to inventory elements
function attachInventoryEventListeners(serviceItem) {
    const inventorySelect = serviceItem.querySelector('.inventory-select');
    const descriptionInput = serviceItem.querySelector('input[name*="[description]"]');
    const quantityInput = serviceItem.querySelector('.quantity-input');
    const unitPriceInput = serviceItem.querySelector('.unit-price-input');
    const removeBtn = serviceItem.querySelector('.remove-service');
    
    // Inventory selection change
    inventorySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const itemName = selectedOption.getAttribute('data-name');
            const itemPrice = selectedOption.getAttribute('data-price');
            const itemUnit = selectedOption.getAttribute('data-unit');
            const availableQty = selectedOption.getAttribute('data-quantity');
            
            descriptionInput.value = itemName + ' (' + itemUnit + ')';
            unitPriceInput.value = parseFloat(itemPrice).toFixed(2);
            
            // Update quantity constraints
            quantityInput.setAttribute('max', availableQty);
            quantityInput.value = Math.min(quantityInput.value, availableQty);
            
            updateServiceTotal(serviceItem);
        } else {
            // Reset when no selection
            quantityInput.setAttribute('max', '1');
            quantityInput.value = '1';
        }
    });
    
    // Quantity change with validation
    quantityInput.addEventListener('input', function() {
        const maxQty = parseInt(this.getAttribute('max'));
        const currentQty = parseInt(this.value);
        
        if (currentQty > maxQty) {
            this.value = maxQty;
            // Show warning message
            if (maxQty > 0) {
                alert(`Insufficient stock! Only ${maxQty} units available in inventory.`);
            }
        }
        
        updateServiceTotal(serviceItem);
    });
    
    unitPriceInput.addEventListener('input', () => updateServiceTotal(serviceItem));
    
    // Remove item
    removeBtn.addEventListener('click', function() {
        serviceItem.remove();
        updateGrandTotal();
    });
}

// Update individual service total
function updateServiceTotal(serviceItem) {
    const quantity = parseFloat(serviceItem.querySelector('.quantity-input').value) || 0;
    const unitPrice = parseFloat(serviceItem.querySelector('.unit-price-input').value) || 0;
    const total = quantity * unitPrice;
    
    serviceItem.querySelector('.service-total').textContent = total.toFixed(2);
    updateGrandTotal();
}

// Update grand total
function updateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.service-total').forEach(function(element) {
        grandTotal += parseFloat(element.textContent) || 0;
    });
    document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
}

// Attach event listeners to existing service items
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.service-item').forEach(function(serviceItem) {
        // Check if it's a service or inventory item based on existing content
        const selectElement = serviceItem.querySelector('select');
        if (selectElement && selectElement.classList.contains('inventory-select')) {
            attachInventoryEventListeners(serviceItem);
        } else {
            attachServiceEventListeners(serviceItem);
        }
    });
    
    // Client selection change to update appointments
    document.getElementById('clientSelect').addEventListener('change', function() {
        const clientId = this.value;
        if (clientId) {
            // You could implement AJAX here to load appointments for the selected client
            // For now, we'll keep the current functionality
        }
    });
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>
