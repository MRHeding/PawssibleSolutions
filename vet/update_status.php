<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

// Check if the required parameters are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['status'])) {
    header("Location: appointments.php");
    exit;
}

$appointment_id = $_GET['id'];
$new_status = $_GET['status'];
$user_id = $_SESSION['user_id'];
$redirect_page = isset($_GET['redirect']) ? $_GET['redirect'] : 'view_appointment.php?id=' . $appointment_id;

// Validate status
$allowed_statuses = ['scheduled', 'completed', 'cancelled', 'no-show'];
if (!in_array($new_status, $allowed_statuses)) {
    header("Location: appointments.php");
    exit;
}

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

// Check if the appointment belongs to this vet
$check_query = "SELECT * FROM appointments WHERE id = :appointment_id AND vet_id = :vet_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':appointment_id', $appointment_id);
$check_stmt->bindParam(':vet_id', $vet_id);
$check_stmt->execute();

if ($check_stmt->rowCount() === 0) {
    // Appointment doesn't exist or doesn't belong to this vet
    header("Location: appointments.php");
    exit;
}

$appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);

try {
    $db->beginTransaction();
    
    // Update the appointment status
    $update_query = "UPDATE appointments 
                     SET status = :status, 
                         updated_at = NOW() 
                     WHERE id = :appointment_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':appointment_id', $appointment_id);
    $update_stmt->execute();
    
    // If status is changed to completed, make sure we record any notes
    if ($new_status === 'completed' && $appointment['status'] !== 'completed') {
        // Check if there's already a medical record for this appointment
        $record_check_query = "SELECT id FROM medical_records WHERE appointment_id = :appointment_id";
        $record_check_stmt = $db->prepare($record_check_query);
        $record_check_stmt->bindParam(':appointment_id', $appointment_id);
        $record_check_stmt->execute();
        
        // If no medical record exists and we have notes, create a basic record
        if ($record_check_stmt->rowCount() === 0 && !empty($appointment['notes'])) {
            // Get pet ID from appointment
            $pet_id = $appointment['pet_id'];
            
            $record_query = "INSERT INTO medical_records 
                            (pet_id, appointment_id, record_date, diagnosis, treatment, notes, created_by) 
                            VALUES 
                            (:pet_id, :appointment_id, :record_date, :diagnosis, :treatment, :notes, :created_by)";
            $record_stmt = $db->prepare($record_query);
            
            // Use current date as record date
            $record_date = date('Y-m-d');
            $diagnosis = 'Appointment completed';
            $treatment = 'See notes';
            
            $record_stmt->bindParam(':pet_id', $pet_id);
            $record_stmt->bindParam(':appointment_id', $appointment_id);
            $record_stmt->bindParam(':record_date', $record_date);
            $record_stmt->bindParam(':diagnosis', $diagnosis);
            $record_stmt->bindParam(':treatment', $treatment);
            $record_stmt->bindParam(':notes', $appointment['notes']);
            $record_stmt->bindParam(':created_by', $user_id);
            
            $record_stmt->execute();
        }
    }
    
    $db->commit();
    
    // Set success message based on status
    $status_messages = [
        'scheduled' => 'appointment_rescheduled',
        'completed' => 'appointment_completed',
        'cancelled' => 'appointment_cancelled',
        'no-show' => 'appointment_noshow'
    ];
    
    $success_msg = isset($status_messages[$new_status]) ? $status_messages[$new_status] : '';
    
    // Determine where to redirect
    if ($redirect_page === 'appointments.php') {
        header("Location: appointments.php");
    } else {
        header("Location: view_appointment.php?id=" . $appointment_id . "&status_updated=" . $new_status);
    }
    exit;
    
} catch (PDOException $e) {
    $db->rollBack();
    // Handle error - redirect with error message
    header("Location: view_appointment.php?id=" . $appointment_id . "&error=update_failed");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Updating Appointment Status - Pawssible Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md text-center">
        <div class="flex justify-center mb-6">
            <div class="h-16 w-16 bg-violet-100 rounded-full flex items-center justify-center text-violet-600">
                <i class="fas fa-sync-alt text-3xl animate-spin"></i>
            </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Updating Appointment Status</h1>
        <p class="text-gray-600 mb-6">Please wait while we update the appointment status...</p>
        <div class="bg-violet-100 text-violet-800 px-4 py-3 rounded">
            This may take a moment. You will be redirected automatically.
        </div>
    </div>
    
    <script>
        // This is only shown if the PHP redirect doesn't happen immediately
        setTimeout(function() {
            window.location.href = "<?php echo $redirect_page; ?>";
        }, 2000);
    </script>
</body>
</html>
