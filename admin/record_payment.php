<?php
session_start();
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

// Initialize variables
$message = '';
$messageClass = '';

// Get invoice details
$invoice_query = "SELECT i.*, CONCAT(u.first_name, ' ', u.last_name) as client_name, u.email, u.phone,
                 a.appointment_number, a.appointment_date, p.name as pet_name
                 FROM invoices i
                 JOIN users u ON i.client_id = u.id
                 LEFT JOIN appointments a ON i.appointment_id = a.id
                 LEFT JOIN pets p ON a.pet_id = p.id
                 WHERE i.id = :invoice_id";
$invoice_stmt = $db->prepare($invoice_query);
$invoice_stmt->bindParam(':invoice_id', $invoice_id);
$invoice_stmt->execute();

if ($invoice_stmt->rowCount() == 0) {
    header("Location: payments.php");
    exit;
}

$invoice = $invoice_stmt->fetch(PDO::FETCH_ASSOC);

// Check if already paid
if ($invoice['paid']) {
    $_SESSION['message'] = "This invoice has already been paid.";
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_amount = floatval($_POST['payment_amount']);
    $payment_method = trim($_POST['payment_method']);
    $payment_notes = trim($_POST['payment_notes']);
    
    if ($payment_amount <= 0) {
        $message = "Please enter a valid payment amount";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    } elseif (empty($payment_method)) {
        $message = "Please select a payment method";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    } else {
        try {
            // Calculate change if payment exceeds invoice amount
            $change_amount = $payment_amount - $invoice['total_amount'];
            
            // Update invoice with payment information
            $update_query = "UPDATE invoices SET 
                            paid = 1,
                            payment_date = NOW(),
                            payment_method = :payment_method,
                            payment_amount = :payment_amount,
                            change_amount = :change_amount,
                            notes = CONCAT(COALESCE(notes, ''), CASE WHEN :payment_notes != '' THEN CONCAT('\n\nPayment Notes: ', :payment_notes) ELSE '' END)
                            WHERE id = :invoice_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':payment_method', $payment_method);
            $update_stmt->bindParam(':payment_amount', $payment_amount);
            $update_stmt->bindParam(':change_amount', $change_amount);
            $update_stmt->bindParam(':payment_notes', $payment_notes);
            $update_stmt->bindParam(':invoice_id', $invoice_id);
            
            if ($update_stmt->execute()) {
                if ($change_amount > 0) {
                    $message = "Payment recorded successfully! Change due: ₱" . number_format($change_amount, 2);
                } else {
                    $message = "Payment recorded successfully!";
                }
                $messageClass = "bg-green-100 border-green-400 text-green-700";
                
                // Redirect after 3 seconds to give time to read change amount
                header("refresh:3;url=view_invoice.php?id=" . $invoice_id);
            } else {
                $message = "Error recording payment";
                $messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        } catch (Exception $e) {
            $message = "Error recording payment: " . $e->getMessage();
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    }
}

// Get invoice items
$items_query = "SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id";
$items_stmt = $db->prepare($items_query);
$items_stmt->bindParam(':invoice_id', $invoice_id);
$items_stmt->execute();
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Record Payment</h1>
                <p class="text-white text-opacity-90 mt-2">Process payment for invoice #INV-<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="bg-white hover:bg-gray-100 text-green-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-eye mr-2"></i> View Invoice
                </a>
                <a href="payments.php" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Payments
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-6 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Invoice Summary -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-6 text-green-700">Invoice Summary</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-medium">Invoice Number:</span>
                    <span>#INV-<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-medium">Client:</span>
                    <span><?php echo htmlspecialchars($invoice['client_name']); ?></span>
                </div>
                
                <?php if ($invoice['appointment_number']): ?>
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-medium">Appointment:</span>
                    <span><?php echo htmlspecialchars($invoice['appointment_number']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-medium">Invoice Date:</span>
                    <span><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b">
                    <span class="font-medium">Status:</span>
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Unpaid</span>
                </div>
                
                <div class="flex justify-between items-center py-2 text-lg font-bold text-green-600">
                    <span>Total Amount:</span>
                    <span>₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <!-- Invoice Items -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-4">Invoice Items</h3>
                <div class="space-y-2">
                    <?php foreach ($items as $item): ?>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($item['description']); ?></div>
                                <div class="text-sm text-gray-600">
                                    Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['unit_price'], 2); ?>
                                </div>
                            </div>
                            <div class="font-medium">₱<?php echo number_format($item['total_price'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Payment Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-6 text-green-700">Payment Details</h2>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $invoice_id); ?>">
                <div class="space-y-6">
                    <div>
                        <label for="payment_amount" class="block text-sm font-medium text-gray-700 mb-2">Payment Amount *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">₱</span>
                            <input type="number" name="payment_amount" id="payment_amount" 
                                   class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                   value="<?php echo $invoice['total_amount']; ?>" 
                                   min="0.01" step="0.01" required>
                        </div>
                        <div class="mt-2 space-y-1">
                            <p class="text-xs text-gray-500">Invoice Total: ₱<?php echo number_format($invoice['total_amount'], 2); ?></p>
                            <div id="change-display" class="text-sm font-medium hidden">
                                <span id="change-label">Change:</span> ₱<span id="change-amount">0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                        <select name="payment_method" id="payment_method" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" required>
                            <option value="cash" selected>Cash</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">Payment Notes</label>
                        <textarea name="payment_notes" id="payment_notes" rows="4" 
                                  class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" 
                                  placeholder="Additional payment details, transaction ID, check number, etc."></textarea>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                            <div class="text-sm text-blue-700">
                                <p class="font-medium mb-1">Payment Processing Information:</p>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Recording this payment will mark the invoice as paid</li>
                                    <li>This action cannot be easily undone</li>
                                    <li>Ensure payment has been received before proceeding</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <a href="payments.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded">
                            Cancel
                        </a>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded">
                            <i class="fas fa-check mr-2"></i> Record Payment
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-focus on payment amount
    document.getElementById('payment_amount').focus();
    
    const paymentInput = document.getElementById('payment_amount');
    const changeDisplay = document.getElementById('change-display');
    const changeAmount = document.getElementById('change-amount');
    const invoiceTotal = <?php echo $invoice['total_amount']; ?>;
    
    // Calculate and display change
    function calculateChange() {
        const paymentAmount = parseFloat(paymentInput.value) || 0;
        const change = paymentAmount - invoiceTotal;
        const changeLabel = document.getElementById('change-label');
        
        if (change > 0) {
            changeAmount.textContent = change.toFixed(2);
            changeLabel.textContent = 'Change:';
            changeDisplay.classList.remove('hidden');
            changeDisplay.classList.add('text-green-600');
            changeDisplay.classList.remove('text-red-600');
        } else if (change < 0) {
            changeAmount.textContent = Math.abs(change).toFixed(2);
            changeLabel.textContent = 'Amount Short:';
            changeDisplay.classList.remove('hidden');
            changeDisplay.classList.add('text-red-600');
            changeDisplay.classList.remove('text-green-600');
        } else {
            changeDisplay.classList.add('hidden');
        }
    }
    
    // Update change calculation on input
    paymentInput.addEventListener('input', calculateChange);
    
    // Initial calculation
    calculateChange();
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>