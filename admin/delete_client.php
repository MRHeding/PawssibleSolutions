<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if client ID is provided
if (!isset($_POST['client_id']) || empty($_POST['client_id'])) {
    header("Location: clients.php");
    exit;
}

$client_id = $_POST['client_id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // First check if client exists and is actually a client
    $check_query = "SELECT id, first_name, last_name FROM users WHERE id = :client_id AND role = 'client'";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':client_id', $client_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        $_SESSION['error'] = "Client not found.";
        header("Location: clients.php");
        exit;
    }
    
    $client = $check_stmt->fetch(PDO::FETCH_ASSOC);
    $client_name = $client['first_name'] . ' ' . $client['last_name'];
    
    // Get the pets owned by this client
    $pets_query = "SELECT id FROM pets WHERE owner_id = :client_id";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->bindParam(':client_id', $client_id);
    $pets_stmt->execute();
    $pet_ids = $pets_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Delete invoices directly related to the client
    $client_invoices_query = "DELETE FROM invoices WHERE client_id = :client_id";
    $client_invoices_stmt = $db->prepare($client_invoices_query);
    $client_invoices_stmt->bindParam(':client_id', $client_id);
    $client_invoices_stmt->execute();
    
    // If client has pets, delete related records first to maintain integrity
    if (count($pet_ids) > 0) {
        // Placeholder for the IN clause
        $placeholders = implode(',', array_fill(0, count($pet_ids), '?'));
        
        // Delete invoice items for invoices related to appointments of this client's pets
        $invoice_items_query = "DELETE ii FROM invoice_items ii 
                               JOIN invoices i ON ii.invoice_id = i.id 
                               JOIN appointments a ON i.appointment_id = a.id 
                               WHERE a.pet_id IN ($placeholders)";
        $invoice_items_stmt = $db->prepare($invoice_items_query);
        foreach ($pet_ids as $index => $pet_id) {
            $invoice_items_stmt->bindValue($index + 1, $pet_id);
        }
        $invoice_items_stmt->execute();
        
        // Delete invoices related to appointments of this client's pets
        $pet_invoices_query = "DELETE i FROM invoices i 
                              JOIN appointments a ON i.appointment_id = a.id 
                              WHERE a.pet_id IN ($placeholders)";
        $pet_invoices_stmt = $db->prepare($pet_invoices_query);
        foreach ($pet_ids as $index => $pet_id) {
            $pet_invoices_stmt->bindValue($index + 1, $pet_id);
        }
        $pet_invoices_stmt->execute();
        
        // Delete medical records
        $med_query = "DELETE FROM medical_records WHERE pet_id IN ($placeholders)";
        $med_stmt = $db->prepare($med_query);
        foreach ($pet_ids as $index => $pet_id) {
            $med_stmt->bindValue($index + 1, $pet_id);
        }
        $med_stmt->execute();
        
        // Delete appointments
        $appt_query = "DELETE FROM appointments WHERE pet_id IN ($placeholders)";
        $appt_stmt = $db->prepare($appt_query);
        foreach ($pet_ids as $index => $pet_id) {
            $appt_stmt->bindValue($index + 1, $pet_id);
        }
        $appt_stmt->execute();
        
        // Now delete the pets
        $pets_delete_query = "DELETE FROM pets WHERE owner_id = :client_id";
        $pets_delete_stmt = $db->prepare($pets_delete_query);
        $pets_delete_stmt->bindParam(':client_id', $client_id);
        $pets_delete_stmt->execute();
    }
    
    // Finally, delete the client (user)
    $client_delete_query = "DELETE FROM users WHERE id = :client_id";
    $client_delete_stmt = $db->prepare($client_delete_query);
    $client_delete_stmt->bindParam(':client_id', $client_id);
    $client_delete_stmt->execute();
    
    // Commit the transaction
    $db->commit();
    
    $_SESSION['success'] = "Client '$client_name' and all associated records have been successfully deleted.";
} catch (Exception $e) {
    // Rollback the transaction on error
    $db->rollBack();
    $_SESSION['error'] = "Failed to delete client: " . $e->getMessage();
}

// Redirect back to clients page
header("Location: clients.php");
exit;
?>
