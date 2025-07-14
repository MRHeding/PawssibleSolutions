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

// Get invoice details
$invoice_query = "SELECT i.*, CONCAT(u.first_name, ' ', u.last_name) as client_name, u.email, u.phone, u.first_name, u.last_name,
                 a.appointment_number, a.appointment_date, a.appointment_time, p.name as pet_name, p.species
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

// Get invoice items
$items_query = "SELECT ii.*, s.name as service_name, s.category 
                FROM invoice_items ii
                LEFT JOIN services s ON ii.service_id = s.id
                WHERE ii.invoice_id = :invoice_id ORDER BY ii.id";
$items_stmt = $db->prepare($items_query);
$items_stmt->bindParam(':invoice_id', $invoice_id);
$items_stmt->execute();
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clinic settings for invoice header
$settings_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email')";
$settings_stmt = $db->prepare($settings_query);
$settings_stmt->execute();
$clinic_settings = [];
while ($setting = $settings_stmt->fetch(PDO::FETCH_ASSOC)) {
    $clinic_settings[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #INV-<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?> - Print</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #invoice-content, #invoice-content * {
                visibility: visible;
            }
            #invoice-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .print-page {
            max-width: 8.5in;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
    </style>
</head>
<body class="bg-gray-100" onload="window.print()">
    <!-- Print Controls -->
    <div class="no-print bg-white border-b shadow-sm p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-semibold">Print Invoice #INV-<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></h1>
            <div class="space-x-3">
                <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-print mr-2"></i> Print Again
                </button>
                <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Invoice
                </a>
            </div>
        </div>
    </div>

    <!-- Printable Invoice -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8" id="invoice-content">
            <!-- Invoice Header -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($clinic_settings['clinic_name'] ?? 'PetCare Veterinary Clinic'); ?></h2>
                    <div class="text-gray-600 mt-2">
                        <?php if (isset($clinic_settings['clinic_address'])): ?>
                            <p><?php echo htmlspecialchars($clinic_settings['clinic_address']); ?></p>
                        <?php endif; ?>
                        <div class="flex space-x-4 mt-1">
                            <?php if (isset($clinic_settings['clinic_phone'])): ?>
                                <span>Phone: <?php echo htmlspecialchars($clinic_settings['clinic_phone']); ?></span>
                            <?php endif; ?>
                            <?php if (isset($clinic_settings['clinic_email'])): ?>
                                <span>Email: <?php echo htmlspecialchars($clinic_settings['clinic_email']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <h3 class="text-2xl font-bold text-green-600">INVOICE</h3>
                    <p class="text-lg font-semibold text-gray-800">#INV-<?php echo str_pad($invoice['id'], 4, '0', STR_PAD_LEFT); ?></p>
                    <div class="mt-2">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            <?php echo $invoice['paid'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $invoice['paid'] ? 'PAID' : 'UNPAID'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Bill To -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Bill To:</h4>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="font-semibold"><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                        <p class="text-gray-600"><?php echo htmlspecialchars($invoice['email']); ?></p>
                        <?php if (!empty($invoice['phone'])): ?>
                            <p class="text-gray-600"><?php echo htmlspecialchars($invoice['phone']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoice Info -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Invoice Details:</h4>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Invoice Date:</span>
                            <span class="font-medium"><?php echo date('M d, Y', strtotime($invoice['created_at'])); ?></span>
                        </div>
                        <?php if ($invoice['appointment_number']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Appointment:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($invoice['appointment_number']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Appointment Date:</span>
                                <span class="font-medium"><?php echo date('M d, Y', strtotime($invoice['appointment_date'])); ?></span>
                            </div>
                            <?php if ($invoice['pet_name']): ?>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Pet:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($invoice['pet_name']); ?> (<?php echo htmlspecialchars($invoice['species']); ?>)</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($invoice['paid']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Date:</span>
                                <span class="font-medium text-green-600"><?php echo date('M d, Y', strtotime($invoice['payment_date'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="font-medium capitalize"><?php echo str_replace('_', ' ', $invoice['payment_method']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Invoice Items -->
            <div class="mb-8">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Services & Items:</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse border border-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="border border-gray-300 px-4 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                                <th class="border border-gray-300 px-4 py-3 text-center text-sm font-semibold text-gray-700">Quantity</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-semibold text-gray-700">Unit Price</th>
                                <th class="border border-gray-300 px-4 py-3 text-right text-sm font-semibold text-gray-700">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-3">
                                        <div class="font-medium"><?php echo htmlspecialchars($item['description']); ?></div>
                                        <?php if ($item['service_name'] && $item['service_name'] !== $item['description']): ?>
                                            <div class="text-sm text-gray-500">Service: <?php echo htmlspecialchars($item['service_name']); ?></div>
                                        <?php endif; ?>
                                        <?php if ($item['category']): ?>
                                            <div class="text-xs text-gray-400">Category: <?php echo htmlspecialchars($item['category']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="border border-gray-300 px-4 py-3 text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="border border-gray-300 px-4 py-3 text-right">₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td class="border border-gray-300 px-4 py-3 text-right font-medium">₱<?php echo number_format($item['total_price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoice Totals -->
            <div class="flex justify-end mb-8">
                <div class="w-full max-w-sm">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <div class="space-y-3">
                            <div class="flex justify-between text-lg">
                                <span class="font-semibold">Subtotal:</span>
                                <span>₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                            </div>
                            <div class="border-t border-gray-300 pt-3">
                                <div class="flex justify-between text-xl font-bold text-green-600">
                                    <span>Total:</span>
                                    <span>₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                                </div>
                            </div>
                            <?php if ($invoice['paid']): ?>
                                <div class="border-t border-gray-300 pt-3">
                                    <div class="flex justify-between text-lg font-semibold text-green-600">
                                        <span>Amount Paid:</span>
                                        <span>₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-lg font-semibold">
                                        <span>Balance Due:</span>
                                        <span>₱0.00</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="border-t border-gray-300 pt-3">
                                    <div class="flex justify-between text-lg font-semibold text-red-600">
                                        <span>Amount Due:</span>
                                        <span>₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if (!empty($invoice['notes'])): ?>
                <div class="mb-8">
                    <h4 class="text-lg font-semibold text-gray-800 mb-3">Notes:</h4>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($invoice['notes']); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="border-t border-gray-300 pt-6 text-center text-gray-600">
                <p class="text-sm">Thank you for choosing <?php echo htmlspecialchars($clinic_settings['clinic_name'] ?? 'PetCare Veterinary Clinic'); ?>!</p>
                <p class="text-xs mt-2">Please contact us if you have any questions about this invoice.</p>
            </div>
        </div>
    </div>
</body>
</html>