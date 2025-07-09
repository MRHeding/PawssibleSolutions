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
$pets = [];
$services = [
    'Check-up',
    'Vaccination',
    'Surgery',
    'Dental Cleaning',
    'Grooming',
    'Emergency',
    'Laboratory Test',
    'X-Ray',
    'Consultation'
];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Fetch all pets for dropdown
$pets_query = "SELECT p.id, p.name, p.species, u.first_name, u.last_name 
               FROM pets p 
               JOIN users u ON p.owner_id = u.id 
               ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->execute();
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if appointment ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    
    // Check if admin_notes column exists
    $adminNotesExists = false;
    $checkColumnQuery = "SHOW COLUMNS FROM appointments LIKE 'admin_notes'";
    $checkColumnStmt = $db->prepare($checkColumnQuery);
    $checkColumnStmt->execute();
    $adminNotesExists = $checkColumnStmt->rowCount() > 0;

    // Handle form submission for updating appointment
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment'])) {
        // Collect form data
        $pet_id = $_POST['pet_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $service = $_POST['service'];
        $status = $_POST['status'];
        $reason = $_POST['reason'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        // Validate inputs
        $errors = [];
        if (empty($pet_id)) $errors[] = "Pet is required";
        if (empty($appointment_date)) $errors[] = "Appointment date is required";
        if (empty($appointment_time)) $errors[] = "Appointment time is required";
        if (empty($service)) $errors[] = "Service is required";
        
        if (empty($errors)) {
            // Update appointment in database - handle admin_notes conditionally
            if ($adminNotesExists) {
                $update_query = "UPDATE appointments SET 
                                pet_id = :pet_id,
                                appointment_date = :appointment_date,
                                appointment_time = :appointment_time,
                                service = :service,
                                status = :status,
                                reason = :reason,
                                admin_notes = :admin_notes,
                                updated_at = NOW()
                                WHERE id = :appointment_id";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':pet_id', $pet_id);
                $update_stmt->bindParam(':appointment_date', $appointment_date);
                $update_stmt->bindParam(':appointment_time', $appointment_time);
                $update_stmt->bindParam(':service', $service);
                $update_stmt->bindParam(':status', $status);
                $update_stmt->bindParam(':reason', $reason);
                $update_stmt->bindParam(':admin_notes', $admin_notes);
                $update_stmt->bindParam(':appointment_id', $appointment_id);
            } else {
                $update_query = "UPDATE appointments SET 
                                pet_id = :pet_id,
                                appointment_date = :appointment_date,
                                appointment_time = :appointment_time,
                                service = :service,
                                status = :status,
                                reason = :reason,
                                updated_at = NOW()
                                WHERE id = :appointment_id";
                
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':pet_id', $pet_id);
                $update_stmt->bindParam(':appointment_date', $appointment_date);
                $update_stmt->bindParam(':appointment_time', $appointment_time);
                $update_stmt->bindParam(':service', $service);
                $update_stmt->bindParam(':status', $status);
                $update_stmt->bindParam(':reason', $reason);
                $update_stmt->bindParam(':appointment_id', $appointment_id);
            }
            
            if ($update_stmt->execute()) {
                $message = "Appointment updated successfully";
                $messageClass = "bg-green-100 border-green-400 text-green-700";
                
                // Refresh appointment data
                $query = "SELECT a.*, p.name as pet_name, p.species, p.breed, 
                          u.first_name, u.last_name, u.email, u.phone 
                          FROM appointments a 
                          LEFT JOIN pets p ON a.pet_id = p.id 
                          LEFT JOIN users u ON p.owner_id = u.id 
                          WHERE a.id = :appointment_id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':appointment_id', $appointment_id);
                $stmt->execute();
                $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $message = "Error updating appointment";
                $messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        } else {
            $message = implode("<br>", $errors);
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    }
    
    // Fetch appointment details if not already fetched
    if (!$appointment) {
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
    }
} else {
    header("Location: appointments.php");
    exit;
}

// Include header
include_once '../includes/admin_header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Appointment</h1>
        <div>
            <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded mr-2">
                View Details
            </a>
            <a href="appointments.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                Back to All Appointments
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($appointment): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold">Editing Appointment <?php echo htmlspecialchars($appointment['appointment_number']); ?></h2>
                <p class="text-sm text-gray-600">
                    Created on: <?php echo date('F j, Y, g:i a', strtotime($appointment['created_at'])); ?>
                </p>
            </div>
            
            <div class="p-6">
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-violet-700">Appointment Details</h3>
                            
                            <div class="mb-4">
                                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                                <select name="pet_id" id="pet_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="">Select Pet</option>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?php echo $pet['id']; ?>" <?php echo ($pet['id'] == $appointment['pet_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>) - 
                                            Owner: <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="appointment_date" class="block text-gray-700 text-sm font-bold mb-2">Appointment Date</label>
                                <input type="date" name="appointment_date" id="appointment_date" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo $appointment['appointment_date']; ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="appointment_time" class="block text-gray-700 text-sm font-bold mb-2">Appointment Time</label>
                                <input type="time" name="appointment_time" id="appointment_time" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo $appointment['appointment_time']; ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="service" class="block text-gray-700 text-sm font-bold mb-2">Service</label>
                                <select name="service" id="service" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service; ?>" <?php echo ($service == $appointment['service']) ? 'selected' : ''; ?>>
                                            <?php echo $service; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-violet-700">Additional Information</h3>
                            
                            <div class="mb-4">
                                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                                <select name="status" id="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <option value="pending" <?php echo ($appointment['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo ($appointment['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="completed" <?php echo ($appointment['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="canceled" <?php echo ($appointment['status'] === 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                                    <option value="no-show" <?php echo ($appointment['status'] === 'no-show') ? 'selected' : ''; ?>>No Show</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="reason" class="block text-gray-700 text-sm font-bold mb-2">Reason for Visit</label>
                                <textarea name="reason" id="reason" rows="3" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($appointment['reason']); ?></textarea>
                            </div>
                            
                            <?php if ($adminNotesExists): ?>
                            <div class="mb-4">
                                <label for="admin_notes" class="block text-gray-700 text-sm font-bold mb-2">Admin Notes</label>
                                <textarea name="admin_notes" id="admin_notes" rows="3" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($appointment['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="flex justify-end">
                            <a href="appointments.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </a>
                            <button type="submit" name="update_appointment" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Appointment
                            </button>
                        </div>
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
