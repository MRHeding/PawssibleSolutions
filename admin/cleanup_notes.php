<?php
// Clean up invoice notes to remove change information
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get all invoices with change information in notes
$query = "SELECT id, notes FROM invoices WHERE notes LIKE '%Change Given:%'";
$stmt = $db->prepare($query);
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($invoices as $invoice) {
    $cleaned_notes = $invoice['notes'];
    
    // Remove change given information from notes
    $cleaned_notes = preg_replace('/\n\nPayment Notes:\s*\nChange Given: ₱[\d,]+\.?\d*/', '\n\nPayment Notes:', $cleaned_notes);
    $cleaned_notes = preg_replace('/Payment Notes:\s*\nChange Given: ₱[\d,]+\.?\d*/', 'Payment Notes:', $cleaned_notes);
    $cleaned_notes = preg_replace('/\nChange Given: ₱[\d,]+\.?\d*/', '', $cleaned_notes);
    
    // Update the invoice
    $update_query = "UPDATE invoices SET notes = :notes WHERE id = :id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':notes', $cleaned_notes);
    $update_stmt->bindParam(':id', $invoice['id']);
    $update_stmt->execute();
    
    echo "Cleaned notes for invoice ID: " . $invoice['id'] . "\n";
}

echo "Notes cleanup completed!\n";
?>
