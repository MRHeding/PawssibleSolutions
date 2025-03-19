<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_appointments.php");
    exit;
}

$appointment_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if the appointment belongs to one of the user's pets
$appointment_check = "SELECT a.*, p.name as pet_name, p.owner_id, p.id as pet_id, v.id as vet_id,
                     CONCAT(u.first_name, ' ', u.last_name) as vet_name 
                     FROM appointments a 
                     JOIN pets p ON a.pet_id = p.id 
                     JOIN vets v ON a.vet_id = v.id 
                     JOIN users u ON v.user_id = u.id 
                     WHERE a.id = :appointment_id AND p.owner_id = :owner_id 
                     AND a.status = 'scheduled'
                     AND a.appointment_date >= CURDATE()";
$check_stmt = $db->prepare($appointment_check);
$check_stmt->bindParam(':appointment_id', $appointment_id);
$check_stmt->bindParam(':owner_id', $user_id);
$check_stmt->execute();

if ($check_stmt->rowCount() == 0) {
    header("Location: my_appointments.php");
    exit;
}

$appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);

// Get user's pets for the dropdown
$pets_query = "SELECT id, name FROM pets WHERE owner_id = :owner_id ORDER BY name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':owner_id', $user_id);
$pets_stmt->execute();

// Get vets for the dropdown
$vets_query = "SELECT v.id, u.first_name, u.last_name, v.specialization 
              FROM vets v 
              JOIN users u ON v.user_id = u.id 
              ORDER BY u.first_name, u.last_name";
$vets_stmt = $db->prepare($vets_query);
$vets_stmt->execute();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pet_id = $_POST['pet_id'];
    $vet_id = $_POST['vet_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $reason = trim($_POST['reason']);
    $notes = trim($_POST['notes']);
    
    // Validation
    if (empty($pet_id) || empty($vet_id) || empty($appointment_date) || empty($appointment_time) || empty($reason)) {
        $error = "Please fill in all required fields";
    } else {
        // Check if the selected date is in the future
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            $error = "Appointment date must be in the future";
        } else {
            // Check if the vet is available at the selected time (excluding the current appointment)
            $availability_check = "SELECT id FROM appointments 
                                 WHERE vet_id = :vet_id 
                                 AND appointment_date = :appointment_date 
                                 AND appointment_time = :appointment_time 
                                 AND status != 'cancelled'
                                 AND id != :appointment_id";
            $check_avail_stmt = $db->prepare($availability_check);
            $check_avail_stmt->bindParam(':vet_id', $vet_id);
            $check_avail_stmt->bindParam(':appointment_date', $appointment_date);
            $check_avail_stmt->bindParam(':appointment_time', $appointment_time);
            $check_avail_stmt->bindParam(':appointment_id', $appointment_id);
            $check_avail_stmt->execute();
            
            if ($check_avail_stmt->rowCount() > 0) {
                $error = "The selected veterinarian is not available at the chosen time. Please select another time.";
            } else {
                // Update the appointment
                $query = "UPDATE appointments SET 
                         pet_id = :pet_id, 
                         vet_id = :vet_id, 
                         appointment_date = :appointment_date, 
                         appointment_time = :appointment_time, 
                         reason = :reason, 
                         notes = :notes 
                         WHERE id = :appointment_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':pet_id', $pet_id);
                $stmt->bindParam(':vet_id', $vet_id);
                $stmt->bindParam(':appointment_date', $appointment_date);
                $stmt->bindParam(':appointment_time', $appointment_time);
                $stmt->bindParam(':reason', $reason);
                $stmt->bindParam(':notes', $notes);
                $stmt->bindParam(':appointment_id', $appointment_id);
                
                try {
                    if ($stmt->execute()) {
                        $success = "Appointment updated successfully!";
                        // Refresh appointment data
                        $check_stmt->execute();
                        $appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = "Something went wrong. Please try again.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Edit Appointment</h1>
            <a href="my_appointments.php" class="text-white hover:text-blue-100 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $appointment_id); ?>" method="post">
            <div class="mb-4">
                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Select Pet *</label>
                <select name="pet_id" id="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Select a pet</option>
                    <?php 
                    $pets_stmt->execute(); // Reset the cursor
                    while ($pet = $pets_stmt->fetch(PDO::FETCH_ASSOC)): 
                    ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo ($pet['id'] == $appointment['pet_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="vet_id" class="block text-gray-700 text-sm font-bold mb-2">Select Veterinarian *</label>
                <select name="vet_id" id="vet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Select a veterinarian</option>
                    <?php 
                    $vets_stmt->execute(); // Reset the cursor
                    while ($vet = $vets_stmt->fetch(PDO::FETCH_ASSOC)): 
                    ?>
                        <option value="<?php echo $vet['id']; ?>" <?php echo ($vet['id'] == $appointment['vet_id']) ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?>
                            <?php if (!empty($vet['specialization'])): ?>
                                (<?php echo htmlspecialchars($vet['specialization']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="appointment_date" class="block text-gray-700 text-sm font-bold mb-2">Appointment Date *</label>
                    <input type="date" name="appointment_date" id="appointment_date" min="<?php echo date('Y-m-d'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo $appointment['appointment_date']; ?>" required>
                </div>
                
                <div>
                    <label for="appointment_time" class="block text-gray-700 text-sm font-bold mb-2">Appointment Time *</label>
                    <select name="appointment_time" id="appointment_time" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select a time</option>
                        <?php
                        // Generate time slots from 8 AM to 5 PM at 30-minute intervals
                        $start = strtotime('8:00 AM');
                        $end = strtotime('5:00 PM');
                        $current_time = $appointment['appointment_time'];
                        
                        for ($time = $start; $time <= $end; $time += 30 * 60) {
                            $formattedTime = date('H:i:s', $time);
                            $displayTime = date('g:i A', $time);
                            $selected = ($formattedTime == $current_time) ? 'selected' : '';
                            echo "<option value=\"$formattedTime\" $selected>$displayTime</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="reason" class="block text-gray-700 text-sm font-bold mb-2">Reason for Visit *</label>
                <select name="reason" id="reason" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Select reason</option>
                    <option value="Wellness Exam" <?php echo ($appointment['reason'] == 'Wellness Exam') ? 'selected' : ''; ?>>Wellness Exam</option>
                    <option value="Vaccination" <?php echo ($appointment['reason'] == 'Vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                    <option value="Sick Visit" <?php echo ($appointment['reason'] == 'Sick Visit') ? 'selected' : ''; ?>>Sick Visit</option>
                    <option value="Injury" <?php echo ($appointment['reason'] == 'Injury') ? 'selected' : ''; ?>>Injury</option>
                    <option value="Dental" <?php echo ($appointment['reason'] == 'Dental') ? 'selected' : ''; ?>>Dental Care</option>
                    <option value="Surgery" <?php echo ($appointment['reason'] == 'Surgery') ? 'selected' : ''; ?>>Surgery Consultation</option>
                    <option value="Follow-up" <?php echo ($appointment['reason'] == 'Follow-up') ? 'selected' : ''; ?>>Follow-up Visit</option>
                    <option value="Other" <?php echo ($appointment['reason'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Additional Notes (optional)</label>
                <textarea name="notes" id="notes" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Please provide any additional information about the reason for your visit..."><?php echo htmlspecialchars($appointment['notes']); ?></textarea>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <a href="my_appointments.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancel
                </a>
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
