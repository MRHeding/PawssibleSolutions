<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if the form was submitted via POST and contains vet_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vet_id'])) {
    $vet_id = filter_input(INPUT_POST, 'vet_id', FILTER_VALIDATE_INT);
    
    if (!$vet_id) {
        $_SESSION['error'] = "Invalid veterinarian ID.";
        header("Location: vets.php");
        exit;
    }
    
    try {
        // Initialize database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Get the user_id associated with this vet before deleting
        $user_query = "SELECT user_id FROM vets WHERE id = :vet_id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':vet_id', $vet_id);
        $user_stmt->execute();
        
        if ($user_stmt->rowCount() > 0) {
            $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $user_data['user_id'];
            
            // Begin transaction
            $db->beginTransaction();
            
            // Delete any related records first (appointments, etc.)
            // Delete from appointments where vet_id = :vet_id
            $appointments_query = "DELETE FROM appointments WHERE vet_id = :vet_id";
            $appointments_stmt = $db->prepare($appointments_query);
            $appointments_stmt->bindParam(':vet_id', $vet_id);
            $appointments_stmt->execute();
            
            // Delete from vets table
            $vet_query = "DELETE FROM vets WHERE id = :vet_id";
            $vet_stmt = $db->prepare($vet_query);
            $vet_stmt->bindParam(':vet_id', $vet_id);
            $vet_stmt->execute();
            
            // Delete from users table
            $user_delete_query = "DELETE FROM users WHERE id = :user_id";
            $user_delete_stmt = $db->prepare($user_delete_query);
            $user_delete_stmt->bindParam(':user_id', $user_id);
            $user_delete_stmt->execute();
            
            // Commit transaction
            $db->commit();
            
            $_SESSION['success'] = "Veterinarian deleted successfully.";
        } else {
            $_SESSION['error'] = "Veterinarian not found.";
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

// Redirect back to the vets page
header("Location: vets.php");
exit;
?>
