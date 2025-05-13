<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageClass = '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get vet information
$vet_query = "SELECT * FROM vets WHERE user_id = :user_id";
$vet_stmt = $db->prepare($vet_query);
$vet_stmt->bindParam(':user_id', $user_id);
$vet_stmt->execute();
$vet = $vet_stmt->fetch(PDO::FETCH_ASSOC);
$vet_id = $vet['id'];

// Check if appointment ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    
    // Verify that the appointment belongs to this vet and is currently scheduled
    $verify_query = "SELECT COUNT(*) FROM appointments 
                    WHERE id = :appointment_id 
                    AND vet_id = :vet_id 
                    AND status = 'scheduled'";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(':appointment_id', $appointment_id);
    $verify_stmt->bindParam(':vet_id', $vet_id);
    $verify_stmt->execute();
    
    if ($verify_stmt->fetchColumn() == 0) {
        $_SESSION['error_message'] = "You do not have permission to start this appointment or it is not in a scheduled status.";
        header("Location: appointments.php");
        exit;
    } else {
        // Prepare to update status to "in progress"
        // First, check if vet_notes column exists in the appointments table
        $checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'vet_notes'";
        $checkColumnStmt = $db->prepare($checkColumnQuery);
        $checkColumnStmt->execute();
        $vetNotesExists = $checkColumnStmt->rowCount() > 0;
        
        // Update the appointment status to "in progress"
        if ($vetNotesExists) {
            $update_query = "UPDATE appointments SET 
                            status = 'in progress', 
                            updated_at = NOW() 
                            WHERE id = :appointment_id AND vet_id = :vet_id";
        } else {
            $update_query = "UPDATE appointments SET 
                            status = 'in progress',
                            updated_at = NOW() 
                            WHERE id = :appointment_id AND vet_id = :vet_id";
        }
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':appointment_id', $appointment_id);
        $update_stmt->bindParam(':vet_id', $vet_id);
        
        try {
            if ($update_stmt->execute()) {
                // Try to log the status change if table exists
                try {
                    $log_query = "INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, details, created_at) 
                                VALUES (:user_id, 'update', 'appointment', :appointment_id, :details, NOW())";
                    
                    $log_details = "Appointment started by vet";
                    $log_stmt = $db->prepare($log_query);
                    $log_stmt->bindParam(':user_id', $_SESSION['user_id']);
                    $log_stmt->bindParam(':appointment_id', $appointment_id);
                    $log_stmt->bindParam(':details', $log_details);
                    $log_stmt->execute();
                } catch (PDOException $logError) {
                    // Silently continue if activity_logs table doesn't exist
                }
                
                $_SESSION['success_message'] = "Appointment has been started successfully!";
                
                // Redirect to the appointment view
                header("Location: view_appointment.php?id=" . $appointment_id);
                exit;
            } else {
                $_SESSION['error_message'] = "Error starting appointment. Please try again.";
                header("Location: appointments.php");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
            header("Location: appointments.php");
            exit;
        }
    }
} else {
    // No appointment ID provided
    $_SESSION['error_message'] = "No appointment selected.";
    header("Location: appointments.php");
    exit;
}
?>
