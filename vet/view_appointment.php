<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize variables
$appointment = null;
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
    
    // Verify that the appointment belongs to this vet
    $verify_query = "SELECT COUNT(*) FROM appointments WHERE id = :appointment_id AND vet_id = :vet_id";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->bindParam(':appointment_id', $appointment_id);
    $verify_stmt->bindParam(':vet_id', $vet_id);
    $verify_stmt->execute();
    
    if ($verify_stmt->fetchColumn() == 0) {
        $message = "You do not have permission to view this appointment";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    } else {
        // Handle status update if form is submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
            $new_status = $_POST['status'];
            $vet_notes = $_POST['vet_notes'] ?? '';
            
            // First, check if vet_notes column exists in the appointments table
            $checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'vet_notes'";
            $checkColumnStmt = $db->prepare($checkColumnQuery);
            $checkColumnStmt->execute();
            $vetNotesExists = $checkColumnStmt->rowCount() > 0;
            
            // Prepare the appropriate update query based on whether vet_notes exists
            if ($vetNotesExists) {
                $update_query = "UPDATE appointments SET 
                                status = :status, 
                                vet_notes = :vet_notes,
                                updated_at = NOW() 
                                WHERE id = :appointment_id AND vet_id = :vet_id";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':status', $new_status);
                $update_stmt->bindParam(':vet_notes', $vet_notes);
                $update_stmt->bindParam(':appointment_id', $appointment_id);
                $update_stmt->bindParam(':vet_id', $vet_id);
            } else {
                $update_query = "UPDATE appointments SET 
                                status = :status,
                                updated_at = NOW() 
                                WHERE id = :appointment_id AND vet_id = :vet_id";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':status', $new_status);
                $update_stmt->bindParam(':appointment_id', $appointment_id);
                $update_stmt->bindParam(':vet_id', $vet_id);
            }
            
            if ($update_stmt->execute()) {
                $message = "Appointment status updated successfully";
                $messageClass = "bg-green-100 border-green-400 text-green-700";
            } else {
                $message = "Error updating appointment status";
                $messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        }
        
        // Fetch appointment details
        $query = "SELECT a.*, p.name as pet_name, p.species, p.breed, 
                CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                u.email, u.phone 
                FROM appointments a 
                LEFT JOIN pets p ON a.pet_id = p.id 
                LEFT JOIN users u ON p.owner_id = u.id 
                WHERE a.id = :appointment_id AND a.vet_id = :vet_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':appointment_id', $appointment_id);
        $stmt->bindParam(':vet_id', $vet_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Appointment not found";
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    }
} else {
    header("Location: appointments.php");
    exit;
}

// Check if vet_notes column exists for UI display purposes
$vetNotesExists = false;
$checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'vet_notes'";
$checkColumnStmt = $db->prepare($checkColumnQuery);
$checkColumnStmt->execute();
$vetNotesExists = $checkColumnStmt->rowCount() > 0;

// Check for existing medical records
$medical_record_exists = false;
$medical_record_query = "SELECT COUNT(*) FROM medical_records WHERE appointment_id = :appointment_id";
$medical_record_stmt = $db->prepare($medical_record_query);
$medical_record_stmt->bindParam(':appointment_id', $appointment_id);
$medical_record_stmt->execute();
$medical_record_exists = ($medical_record_stmt->fetchColumn() > 0);

// Include header
include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Appointment Details</h1>
            <a href="appointments.php" class="bg-white hover:bg-gray-100 text-violet-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-6">
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($appointment): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-violet-50">
                <h2 class="text-xl font-semibold text-violet-800">Appointment #<?php echo $appointment['id']; ?></h2>
                <p class="text-sm text-gray-600">
                    Created on: <?php echo date('F j, Y, g:i a', strtotime($appointment['created_at'])); ?>
                </p>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Appointment Information</h3>
                    <p class="mb-2"><span class="font-semibold">Date:</span> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                    <p class="mb-2"><span class="font-semibold">Time:</span> <?php echo date('g:i a', strtotime($appointment['appointment_time'])); ?></p>
                    <?php if (!empty($appointment['service'])): ?>
                    <p class="mb-2"><span class="font-semibold">Service:</span> <?php echo htmlspecialchars($appointment['service']); ?></p>
                    <?php endif; ?>
                    <p class="mb-2"><span class="font-semibold">Status:</span> 
                        <span class="<?php 
                            $statusColor = 'text-gray-700';
                            if ($appointment['status'] === 'scheduled') $statusColor = 'text-blue-600';
                            if ($appointment['status'] === 'completed') $statusColor = 'text-green-600';
                            if ($appointment['status'] === 'cancelled') $statusColor = 'text-red-600';
                            if ($appointment['status'] === 'no-show') $statusColor = 'text-yellow-600';
                            echo $statusColor;
                        ?> font-semibold">
                            <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                        </span>
                    </p>
                    <div class="mt-4">
                        <h4 class="font-semibold mb-2">Reason for Visit:</h4>
                        <p class="text-gray-700"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                    </div>
                    <?php if (!empty($appointment['notes'])): ?>
                    <div class="mt-4">
                        <h4 class="font-semibold mb-2">Client Notes:</h4>
                        <p class="text-gray-700"><?php echo htmlspecialchars($appointment['notes']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Pet & Owner Information</h3>
                    <p class="mb-2"><span class="font-semibold">Pet Name:</span> <?php echo htmlspecialchars($appointment['pet_name']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Species:</span> <?php echo htmlspecialchars($appointment['species']); ?></p>
                    <?php if (!empty($appointment['breed'])): ?>
                    <p class="mb-2"><span class="font-semibold">Breed:</span> <?php echo htmlspecialchars($appointment['breed']); ?></p>
                    <?php endif; ?>
                    <p class="mb-2"><span class="font-semibold">Owner:</span> <?php echo htmlspecialchars($appointment['owner_name']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Email:</span> <?php echo htmlspecialchars($appointment['email']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($appointment['phone']); ?></p>
                    
                    <!-- Patient History Link -->
                    <div class="mt-4">
                        <a href="patient_history.php?pet_id=<?php echo $appointment['pet_id']; ?>" class="inline-flex items-center text-violet-600 hover:text-violet-800">
                            <i class="fas fa-history mr-2"></i> View Patient History
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons Section -->
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-wrap gap-3">
                    <?php if ($appointment['status'] === 'scheduled'): ?>
                        <a href="start_appointment.php?id=<?php echo $appointment['id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-play-circle mr-2"></i> Start Appointment
                        </a>
                        <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=no-show" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="return confirm('Are you sure you want to mark this appointment as No-Show?');">
                            <i class="fas fa-user-times mr-2"></i> Mark as No-Show
                        </a>
                        <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=cancelled" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="return confirm('Are you sure you want to cancel this appointment?');">
                            <i class="fas fa-times mr-2"></i> Cancel Appointment
                        </a>
                    <?php elseif ($appointment['status'] === 'completed'): ?>
                        <?php if ($medical_record_exists): ?>
                            <a href="view_medical_record.php?appointment_id=<?php echo $appointment['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-file-medical mr-2"></i> View Medical Record
                            </a>
                            <a href="edit_medical_record.php?appointment_id=<?php echo $appointment['id']; ?>" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-edit mr-2"></i> Edit Medical Record
                            </a>
                        <?php else: ?>
                            <a href="add_medical_record.php?appointment_id=<?php echo $appointment['id']; ?>" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-plus-circle mr-2"></i> Add Medical Record
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Update Status Form -->
            <?php if ($appointment['status'] !== 'completed'): ?>
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
                    
                    <?php if ($vetNotesExists): ?>
                    <div class="mb-4">
                        <label for="vet_notes" class="block text-gray-700 text-sm font-bold mb-2">Vet Notes (Internal)</label>
                        <textarea name="vet_notes" id="vet_notes" rows="4" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($appointment['vet_notes'] ?? ''); ?></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <button type="submit" name="update_status" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Appointment
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

<?php include_once '../includes/vet_footer.php'; ?>
