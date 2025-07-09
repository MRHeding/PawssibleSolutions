<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$appointment = null;
$message = '';
$messageClass = '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if appointment ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    
    // Handle status update if form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $new_status = $_POST['status'];
        $notes = $_POST['admin_notes'] ?? '';
        
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
            $message = "Appointment status updated successfully";
            $messageClass = "bg-green-100 border-green-400 text-green-700";
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
    
    <?php if ($appointment): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold">Appointment #<?php echo $appointment['id']; ?></h2>
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
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Appointment information not available.
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
