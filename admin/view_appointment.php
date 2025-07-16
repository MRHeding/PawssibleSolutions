<?php
session_start();
include_once '../config/database.php';
include_once '../includes/appointment_number_generator.php';
include_once '../includes/service_price_mapper.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$appointment = null;
$message = '';
$messageClass = '';
$followup_message = '';
$followup_messageClass = '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize appointment number generator
$appointmentNumberGenerator = new AppointmentNumberGenerator($db);

// Check if appointment ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    
    // Handle follow-up appointment scheduling
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_followup'])) {
        $followup_date = $_POST['followup_date'];
        $followup_time = $_POST['followup_time'];
        $followup_reason = $_POST['followup_reason'];
        $followup_notes = $_POST['followup_notes'] ?? '';
        
        if (empty($followup_date) || empty($followup_time) || empty($followup_reason)) {
            $followup_message = "All follow-up appointment fields are required";
            $followup_messageClass = "bg-red-100 border-red-400 text-red-700";
        } else {
            // Get current appointment details for follow-up
            $current_apt_query = "SELECT pet_id, vet_id FROM appointments WHERE id = :appointment_id";
            $current_apt_stmt = $db->prepare($current_apt_query);
            $current_apt_stmt->bindParam(':appointment_id', $appointment_id);
            $current_apt_stmt->execute();
            $current_appointment = $current_apt_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_appointment) {
                $pet_id = $current_appointment['pet_id'];
                $vet_id = $current_appointment['vet_id'];
                
                // Check for time conflicts
                $conflict_check = "SELECT COUNT(*) FROM appointments 
                                  WHERE vet_id = :vet_id 
                                  AND appointment_date = :date 
                                  AND appointment_time = :time 
                                  AND status != 'cancelled'";
                $conflict_stmt = $db->prepare($conflict_check);
                $conflict_stmt->bindParam(':vet_id', $vet_id);
                $conflict_stmt->bindParam(':date', $followup_date);
                $conflict_stmt->bindParam(':time', $followup_time);
                $conflict_stmt->execute();
                
                if ($conflict_stmt->fetchColumn() > 0) {
                    $followup_message = "There is already an appointment scheduled for this veterinarian at the selected time";
                    $followup_messageClass = "bg-red-100 border-red-400 text-red-700";
                } else {
                    // Generate appointment number
                    $appointment_number = $appointmentNumberGenerator->generateAppointmentNumber();
                    
                    // Insert the new follow-up appointment
                    $insert_query = "INSERT INTO appointments (appointment_number, pet_id, vet_id, appointment_date, appointment_time, reason, notes, status, created_at) 
                                    VALUES (:appointment_number, :pet_id, :vet_id, :appointment_date, :appointment_time, :reason, :notes, 'scheduled', NOW())";
                    
                    try {
                        $stmt = $db->prepare($insert_query);
                        $stmt->bindParam(':appointment_number', $appointment_number);
                        $stmt->bindParam(':pet_id', $pet_id);
                        $stmt->bindParam(':vet_id', $vet_id);
                        $stmt->bindParam(':appointment_date', $followup_date);
                        $stmt->bindParam(':appointment_time', $followup_time);
                        $stmt->bindParam(':reason', $followup_reason);
                        $stmt->bindParam(':notes', $followup_notes);
                        
                        if ($stmt->execute()) {
                            $followup_message = "Follow-up appointment #{$appointment_number} scheduled successfully";
                            $followup_messageClass = "bg-green-100 border-green-400 text-green-700";
                        } else {
                            $followup_message = "Error scheduling follow-up appointment";
                            $followup_messageClass = "bg-red-100 border-red-400 text-red-700";
                        }
                    } catch (PDOException $e) {
                        $followup_message = "Database error: " . $e->getMessage();
                        $followup_messageClass = "bg-red-100 border-red-400 text-red-700";
                    }
                }
            } else {
                $followup_message = "Unable to retrieve current appointment details";
                $followup_messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        }
    }
    
    // Handle status update if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        $notes = $_POST['admin_notes'] ?? '';
        
        // Get current status before updating
        $status_check_query = "SELECT status FROM appointments WHERE id = :appointment_id";
        $status_check_stmt = $db->prepare($status_check_query);
        $status_check_stmt->bindParam(':appointment_id', $appointment_id);
        $status_check_stmt->execute();
        $old_status = $status_check_stmt->fetchColumn();
        
        // First, check if admin_notes column exists in the appointments table
        $checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'admin_notes'";
        $checkColumnStmt = $db->prepare($checkColumnQuery);
        $checkColumnStmt->execute();
        $adminNotesExists = $checkColumnStmt->rowCount() > 0;
        
        // Prepare the appropriate update query based on whether admin_notes exists
        if ($adminNotesExists) {
            $update_query = "UPDATE appointments SET 
                            status = :status, 
                            admin_notes = :admin_notes,
                            updated_at = NOW() 
                            WHERE id = :appointment_id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':status', $new_status);
            $update_stmt->bindParam(':admin_notes', $notes);
            $update_stmt->bindParam(':appointment_id', $appointment_id);
        } else {
            $update_query = "UPDATE appointments SET 
                            status = :status,
                            updated_at = NOW() 
                            WHERE id = :appointment_id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':status', $new_status);
            $update_stmt->bindParam(':appointment_id', $appointment_id);
        }
        
        if ($update_stmt->execute()) {
            // Auto-generate invoice if appointment status changed to 'completed'
            if ($new_status === 'completed' && $old_status !== 'completed') {
                try {
                    ServicePriceMapper::autoGenerateInvoice($db, $appointment_id);
                    $_SESSION['success_message'] = "Appointment status updated successfully. Invoice has been automatically generated.";
                } catch (Exception $invoiceError) {
                    // Log invoice generation error but don't fail the status update
                    error_log("Invoice generation failed for appointment $appointment_id: " . $invoiceError->getMessage());
                    $_SESSION['success_message'] = "Appointment status updated successfully, but invoice generation failed.";
                }
            } else {
                $_SESSION['success_message'] = "Appointment status updated successfully";
            }
            
            header("Location: appointments.php");
            exit;
        } else {
            $message = "Error updating appointment status";
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    }
    
    // Fetch appointment details
    $query = "SELECT a.*, p.name as pet_name, p.species, p.breed, 
              u.first_name, u.last_name, u.email, u.phone 
              FROM appointments a 
              LEFT JOIN pets p ON a.pet_id = p.id 
              LEFT JOIN users u ON p.owner_id = u.id 
              WHERE a.id = :appointment_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = "Appointment not found";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    }
} else {
    header("Location: appointments.php");
    exit;
}

// Check if admin_notes column exists for UI display purposes
$adminNotesExists = false;
$checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'admin_notes'";
$checkColumnStmt = $db->prepare($checkColumnQuery);
$checkColumnStmt->execute();
$adminNotesExists = $checkColumnStmt->rowCount() > 0;

// Include header
include_once '../includes/admin_header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Appointment Details</h1>
        <a href="appointments.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
            Back to All Appointments
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($followup_message)): ?>
        <div class="<?php echo $followup_messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $followup_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($appointment): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold">Appointment <?php echo htmlspecialchars($appointment['appointment_number']); ?></h2>
                <p class="text-sm text-gray-600">
                    Created on: <?php echo date('F j, Y, g:i a', strtotime($appointment['created_at'])); ?>
                </p>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Appointment Information</h3>
                    <p class="mb-2"><span class="font-semibold">Date:</span> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                    <p class="mb-2"><span class="font-semibold">Time:</span> <?php echo date('g:i a', strtotime($appointment['appointment_time'])); ?></p>
                    <p class="mb-2"><span class="font-semibold">Service:</span> <?php echo htmlspecialchars($appointment['service'] ?? 'Not specified'); ?></p>
                    <p class="mb-2"><span class="font-semibold">Status:</span> 
                        <span class="<?php 
                            $statusColor = 'text-gray-700';
                            if ($appointment['status'] === 'confirmed') $statusColor = 'text-green-600';
                            if ($appointment['status'] === 'canceled') $statusColor = 'text-red-600';
                            if ($appointment['status'] === 'pending') $statusColor = 'text-yellow-600';
                            echo $statusColor;
                        ?> font-semibold">
                            <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                        </span>
                    </p>
                    <div class="mt-4">
                        <h4 class="font-semibold mb-2">Reason for Visit:</h4>
                        <p class="text-gray-700"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Pet & Owner Information</h3>
                    <p class="mb-2"><span class="font-semibold">Pet Name:</span> <?php echo htmlspecialchars($appointment['pet_name']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Species:</span> <?php echo htmlspecialchars($appointment['species']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Breed:</span> <?php echo htmlspecialchars($appointment['breed']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Owner:</span> <?php echo htmlspecialchars($appointment['first_name']) . ' ' . htmlspecialchars($appointment['last_name']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Email:</span> <?php echo htmlspecialchars($appointment['email']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($appointment['phone']); ?></p>
                </div>
            </div>
            
            <div class="p-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold mb-3 text-violet-700">Update Appointment Status</h3>
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
                    <div class="mb-4">
                        <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" id="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="scheduled" <?php echo ($appointment['status'] === 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="completed" <?php echo ($appointment['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($appointment['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="no-show" <?php echo ($appointment['status'] === 'no-show') ? 'selected' : ''; ?>>No Show</option>
                        </select>
                    </div>
                    
                    <?php if ($adminNotesExists): ?>
                    <div class="mb-4">
                        <label for="admin_notes" class="block text-gray-700 text-sm font-bold mb-2">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" rows="4" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($appointment['admin_notes'] ?? ''); ?></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <button type="submit" name="update_status" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Appointment
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Follow-up Appointment Scheduling Section -->
            <?php if ($appointment['status'] === 'completed' || $appointment['status'] === 'scheduled'): ?>
            <div id="followup" class="p-6 border-t border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold mb-3 text-indigo-700">Schedule Follow-up Appointment</h3>
                
                <?php if (!empty($followup_message)): ?>
                    <div class="<?php echo $followup_messageClass; ?> px-4 py-3 rounded mb-4">
                        <?php echo $followup_message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="followup_date" class="block text-gray-700 text-sm font-bold mb-2">Follow-up Date</label>
                            <input type="date" name="followup_date" id="followup_date" 
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        <div>
                            <label for="followup_time" class="block text-gray-700 text-sm font-bold mb-2">Follow-up Time</label>
                            <select name="followup_time" id="followup_time" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select Time</option>
                                <option value="09:00:00">9:00 AM</option>
                                <option value="09:30:00">9:30 AM</option>
                                <option value="10:00:00">10:00 AM</option>
                                <option value="10:30:00">10:30 AM</option>
                                <option value="11:00:00">11:00 AM</option>
                                <option value="11:30:00">11:30 AM</option>
                                <option value="12:00:00">12:00 PM</option>
                                <option value="12:30:00">12:30 PM</option>
                                <option value="13:00:00">1:00 PM</option>
                                <option value="13:30:00">1:30 PM</option>
                                <option value="14:00:00">2:00 PM</option>
                                <option value="14:30:00">2:30 PM</option>
                                <option value="15:00:00">3:00 PM</option>
                                <option value="15:30:00">3:30 PM</option>
                                <option value="16:00:00">4:00 PM</option>
                                <option value="16:30:00">4:30 PM</option>
                                <option value="17:00:00">5:00 PM</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="followup_reason" class="block text-gray-700 text-sm font-bold mb-2">Reason for Follow-up</label>
                        <select name="followup_reason" id="followup_reason" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <option value="">-- Select Reason --</option>
                            <option value="Check-up">Regular Check-up</option>
                            <option value="Vaccination">Vaccination</option>
                            <option value="Illness">Illness</option>
                            <option value="Injury">Injury</option>
                            <option value="Surgery">Surgery</option>
                            <option value="Dental">Dental Care</option>
                            <option value="Follow-up">Follow-up Visit</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="followup_notes" class="block text-gray-700 text-sm font-bold mb-2">Additional Notes (Optional)</label>
                        <textarea name="followup_notes" id="followup_notes" rows="3" 
                                  placeholder="Any specific instructions or notes for the follow-up appointment..."
                                  class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    
                    <div class="text-sm text-gray-600 mb-4">
                        <p><strong>Note:</strong> The follow-up appointment will be scheduled with the same veterinarian (<?php 
                        // Get vet name for display
                        $vet_query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as vet_name 
                                     FROM vets v 
                                     JOIN users u ON v.user_id = u.id 
                                     WHERE v.id = :vet_id";
                        $vet_stmt = $db->prepare($vet_query);
                        $vet_stmt->bindParam(':vet_id', $appointment['vet_id']);
                        $vet_stmt->execute();
                        $vet_info = $vet_stmt->fetch(PDO::FETCH_ASSOC);
                        echo htmlspecialchars($vet_info['vet_name'] ?? 'Unknown');
                        ?>) and the same pet (<?php echo htmlspecialchars($appointment['pet_name']); ?>).</p>
                    </div>
                    
                    <div>
                        <button type="submit" name="schedule_followup" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-calendar-plus mr-2"></i>Schedule Follow-up
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Appointment information not available.
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
