<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process form submission
$message = '';
$alert_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and process the form data
    if (empty($_POST['pet_id']) || empty($_POST['vet_id']) || empty($_POST['appointment_date']) || 
        empty($_POST['appointment_time']) || empty($_POST['reason'])) {
        $message = "All fields are required";
        $alert_class = "bg-red-100 text-red-700";
    } else {
        // Process the form data
        $pet_id = $_POST['pet_id'];
        $vet_id = $_POST['vet_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $reason = $_POST['reason'];
        $notes = $_POST['notes'] ?? '';
        
        // Default status for new appointments
        $status = 'scheduled';
        
        // Insert the new appointment
        $insert_query = "INSERT INTO appointments (pet_id, vet_id, appointment_date, appointment_time, reason, notes, status, created_at) 
                         VALUES (:pet_id, :vet_id, :appointment_date, :appointment_time, :reason, :notes, :status, NOW())";
        
        try {
            $stmt = $db->prepare($insert_query);
            $stmt->bindParam(':pet_id', $pet_id);
            $stmt->bindParam(':vet_id', $vet_id);
            $stmt->bindParam(':appointment_date', $appointment_date);
            $stmt->bindParam(':appointment_time', $appointment_time);
            $stmt->bindParam(':reason', $reason);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':status', $status);
            
            if ($stmt->execute()) {
                $message = "Appointment added successfully";
                $alert_class = "bg-green-100 text-green-700";
            } else {
                $message = "Error adding appointment";
                $alert_class = "bg-red-100 text-red-700";
            }
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $alert_class = "bg-red-100 text-red-700";
        }
    }
}

// Get all vets for the dropdown
$vets_query = "SELECT v.id, CONCAT(u.first_name, ' ', u.last_name) as name, v.specialization
              FROM vets v
              JOIN users u ON v.user_id = u.id
              ORDER BY u.last_name, u.first_name";
$vets_stmt = $db->prepare($vets_query);
$vets_stmt->execute();

// Get all pets for the dropdown
$pets_query = "SELECT p.id, p.name, p.species, p.breed, CONCAT(u.first_name, ' ', u.last_name) as owner_name
              FROM pets p
              JOIN users u ON p.owner_id = u.id
              ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Add New Appointment</h1>
            <a href="appointments.php" class="bg-white text-violet-600 px-4 py-2 rounded hover:bg-blue-50">
                Back to Appointments
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($message)): ?>
        <div class="<?php echo $alert_class; ?> p-4 mb-6 rounded-md">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="pet_id" class="block text-sm font-medium text-gray-700 mb-2">Select Pet</label>
                    <select name="pet_id" id="pet_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Select Pet --</option>
                        <?php while ($pet = $pets_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $pet['id']; ?>">
                                <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>)
                                - Owner: <?php echo htmlspecialchars($pet['owner_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="vet_id" class="block text-sm font-medium text-gray-700 mb-2">Select Veterinarian</label>
                    <select name="vet_id" id="vet_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Select Veterinarian --</option>
                        <?php while ($vet = $vets_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $vet['id']; ?>">
                                Dr. <?php echo htmlspecialchars($vet['name']); ?>
                                <?php if (!empty($vet['specialization'])): ?>
                                    (<?php echo htmlspecialchars($vet['specialization']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label for="appointment_date" class="block text-sm font-medium text-gray-700 mb-2">Appointment Date</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <div>
                    <label for="appointment_time" class="block text-sm font-medium text-gray-700 mb-2">Appointment Time</label>
                    <input type="time" id="appointment_time" name="appointment_time" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit</label>
                    <select name="reason" id="reason" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
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
                
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="4" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            
            <div class="mt-8 flex justify-end">
                <button type="reset" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2 hover:bg-gray-400 transition-colors">Clear Form</button>
                <button type="submit" class="bg-violet-600 text-white px-6 py-2 rounded-md hover:bg-violet-700 transition-colors">Add Appointment</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
