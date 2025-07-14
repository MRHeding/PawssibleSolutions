<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
    } else {
        header("Location: ../login.php");
    }
    exit;
}

// Include database and service price mapper
include_once '../config/database.php';
include_once '../includes/service_price_mapper.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize response
$success = false;
$message = 'An error occurred';
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Process both GET and POST requests
$requestData = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

// Check if admin_notes column exists
$adminNotesExists = false;
$checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'admin_notes'";
$checkColumnStmt = $db->prepare($checkColumnQuery);
$checkColumnStmt->execute();
$adminNotesExists = $checkColumnStmt->rowCount() > 0;

if (isset($requestData['appointment_id']) || isset($requestData['id'])) {
    // Get appointment ID from either POST or GET
    $appointment_id = isset($requestData['appointment_id']) ? intval($requestData['appointment_id']) : intval($requestData['id']);
    
    // Get status from either POST or GET
    if (isset($requestData['status'])) {
        $status = trim($requestData['status']);
        $admin_notes = isset($requestData['admin_notes']) ? trim($requestData['admin_notes']) : '';
        
        // Validate status value - must match database enum exactly
        $valid_statuses = ['scheduled', 'completed', 'cancelled', 'no-show'];
        if (in_array($status, $valid_statuses)) {
            // Check if appointment exists and get current status
            $check_query = "SELECT id, status FROM appointments WHERE id = :appointment_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':appointment_id', $appointment_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $current_appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);
                $old_status = $current_appointment['status'];
                
                // Update appointment status - handle admin_notes conditionally
                if ($adminNotesExists && !empty($admin_notes)) {
                    $update_query = "UPDATE appointments SET 
                                    status = :status,
                                    admin_notes = :admin_notes,
                                    updated_at = NOW() 
                                    WHERE id = :appointment_id";
                    
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':status', $status);
                    $update_stmt->bindParam(':admin_notes', $admin_notes);
                    $update_stmt->bindParam(':appointment_id', $appointment_id);
                } else {
                    $update_query = "UPDATE appointments SET 
                                    status = :status,
                                    updated_at = NOW() 
                                    WHERE id = :appointment_id";
                    
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(':status', $status);
                    $update_stmt->bindParam(':appointment_id', $appointment_id);
                }
                
                try {
                    if ($update_stmt->execute()) {
                        // Auto-generate invoice if appointment status changed to 'completed'
                        if ($status === 'completed' && $old_status !== 'completed') {
                            try {
                                ServicePriceMapper::autoGenerateInvoice($db, $appointment_id);
                            } catch (Exception $invoiceError) {
                                // Log invoice generation error but don't fail the status update
                                error_log("Invoice generation failed for appointment $appointment_id: " . $invoiceError->getMessage());
                            }
                        }
                        
                        // Try to log the status change if table exists
                        try {
                            $log_query = "INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, details, created_at) 
                                         VALUES (:user_id, 'update', 'appointment', :appointment_id, :details, NOW())";
                            
                            $log_details = "Status changed to '$status'" . ($admin_notes ? " with note: $admin_notes" : "");
                            $log_stmt = $db->prepare($log_query);
                            $log_stmt->bindParam(':user_id', $_SESSION['user_id']);
                            $log_stmt->bindParam(':appointment_id', $appointment_id);
                            $log_stmt->bindParam(':details', $log_details);
                            $log_stmt->execute();
                        } catch (PDOException $logError) {
                            // Silently continue if activity_logs table doesn't exist
                        }
                        
                        $success = true;
                        $message = "Appointment status updated successfully from '$old_status' to '$status'";
                        
                        // Add invoice generation confirmation if applicable
                        if ($status === 'completed' && $old_status !== 'completed') {
                            $message .= ". Invoice has been automatically generated.";
                        }
                        
                        // Set session message for non-AJAX requests
                        if (!$isAjaxRequest) {
                            $_SESSION['success_message'] = $message;
                        }
                    } else {
                        $message = 'Failed to update appointment status';
                        if (!$isAjaxRequest) {
                            $_SESSION['error_message'] = $message;
                        }
                    }
                } catch (PDOException $e) {
                    $message = 'Database error: ' . $e->getMessage();
                    if (!$isAjaxRequest) {
                        $_SESSION['error_message'] = $message;
                    }
                }
            } else {
                $message = 'Appointment not found';
            }
        } else {
            $message = 'Invalid status value';
        }
    } else {
        $message = 'Status parameter is required';
    }
} else {
    $message = 'Appointment ID parameter is required';
}

// Handle response
if ($isAjaxRequest) {
    // Return JSON for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'status' => $status ?? null,
        'appointment_id' => $appointment_id ?? null
    ]);
} else {
    // Set session message for direct URL access
    $_SESSION['message'] = $message;
    $_SESSION['message_class'] = $success ? 'success' : 'error';
    
    // Redirect to appointment view or list
    if (isset($appointment_id)) {
        header("Location: view_appointment.php?id=$appointment_id&updated=" . time());
    } else {
        header("Location: appointments.php?updated=" . time());
    }
    exit;
}
?>
