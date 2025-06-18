<?php
session_start();
include_once 'config/database.php';
include_once 'includes/appointment_number_generator.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Check if a specific pet is pre-selected
$selected_pet_id = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize appointment number generator
$appointmentNumberGenerator = new AppointmentNumberGenerator($db);

// Get user's pets for the dropdown
$pets_query = "SELECT id, name, species FROM pets WHERE owner_id = :owner_id ORDER BY name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':owner_id', $user_id);
$pets_stmt->execute();

// Check if user has any pets
if ($pets_stmt->rowCount() == 0) {
    $_SESSION['error_message'] = "You need to add a pet before scheduling an appointment.";
    header("Location: add_pet.php");
    exit;
}

// Get all vets for the dropdown
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
        $error = "Please fill in all required fields.";
    } else {
        // Check if the selected date is in the future
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            $error = "Appointment date must be in the future.";
        } else {
            // Check if the date is not more than 3 months in the future
            $max_date = date('Y-m-d', strtotime('+3 months'));
            if (strtotime($appointment_date) > strtotime($max_date)) {
                $error = "Appointments can only be scheduled up to 3 months in advance.";
            } else {
                // Check if the vet is available at the selected time
                $availability_check = "SELECT id FROM appointments 
                                     WHERE vet_id = :vet_id 
                                     AND appointment_date = :appointment_date 
                                     AND appointment_time = :appointment_time 
                                     AND status != 'cancelled'";
                $check_stmt = $db->prepare($availability_check);
                $check_stmt->bindParam(':vet_id', $vet_id);
                $check_stmt->bindParam(':appointment_date', $appointment_date);
                $check_stmt->bindParam(':appointment_time', $appointment_time);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $error = "The selected veterinarian is not available at the chosen time. Please select another time.";
                } else {
                    // Generate appointment number
                    $appointment_number = $appointmentNumberGenerator->generateAppointmentNumber();
                    
                    // All validations passed, create the appointment
                    $query = "INSERT INTO appointments (appointment_number, pet_id, vet_id, appointment_date, appointment_time, reason, notes, status) 
                             VALUES (:appointment_number, :pet_id, :vet_id, :appointment_date, :appointment_time, :reason, :notes, 'scheduled')";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':appointment_number', $appointment_number);
                    $stmt->bindParam(':pet_id', $pet_id);
                    $stmt->bindParam(':vet_id', $vet_id);
                    $stmt->bindParam(':appointment_date', $appointment_date);
                    $stmt->bindParam(':appointment_time', $appointment_time);
                    $stmt->bindParam(':reason', $reason);
                    $stmt->bindParam(':notes', $notes);
                    
                    try {
                        if ($stmt->execute()) {
                            $success = "Appointment scheduled successfully!";
                            
                            // Get the pet name for the success message
                            $pet_name_query = "SELECT name FROM pets WHERE id = :pet_id";
                            $pet_name_stmt = $db->prepare($pet_name_query);
                            $pet_name_stmt->bindParam(':pet_id', $pet_id);
                            $pet_name_stmt->execute();
                            $pet_name = $pet_name_stmt->fetch(PDO::FETCH_ASSOC)['name'];
                            
                            $_SESSION['success_message'] = "Appointment #{$appointment_number} for " . $pet_name . " scheduled successfully for " . date('F d, Y', strtotime($appointment_date)) . " at " . date('h:i A', strtotime($appointment_time)) . ".";
                            header("Location: my_appointments.php");
                            exit;
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
}

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Schedule an Appointment</h1>
            <a href="my_appointments.php" class="text-white hover:text-blue-100 transition">
                <i class="fas fa-calendar-alt mr-2"></i> My Appointments
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
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" data-validate="true">
            <div class="mb-4">
                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Select Pet *</label>
                <select name="pet_id" id="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Select a pet</option>
                    <?php 
                    $pets_stmt->execute(); // Reset the cursor
                    while ($pet = $pets_stmt->fetch(PDO::FETCH_ASSOC)): 
                    ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo ($pet['id'] == $selected_pet_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="vet_id" class="block text-gray-700 text-sm font-bold mb-2">Select Veterinarian *</label>
                <select name="vet_id" id="vet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Select a veterinarian</option>
                    <?php while ($vet = $vets_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $vet['id']; ?>">
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
                    <input type="date" name="appointment_date" id="appointment_date" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div>
                    <label for="appointment_time" class="block text-gray-700 text-sm font-bold mb-2">Appointment Time *</label>
                    <select name="appointment_time" id="appointment_time" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select a time</option>
                        <?php
                        // Generate time slots from 8 AM to 5 PM at 30-minute intervals
                        $start = strtotime('8:00 AM');
                        $end = strtotime('5:00 PM');
                        for ($time = $start; $time <= $end; $time += 30 * 60) {
                            $formatted_time = date('H:i:s', $time);
                            $display_time = date('g:i A', $time);
                            echo "<option value=\"$formatted_time\">$display_time</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="reason" class="block text-gray-700 text-sm font-bold mb-2">Reason for Visit *</label>
                <select name="reason" id="reason" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    <option value="">Select reason</option>
                    <option value="Wellness Exam">Wellness Exam</option>
                    <option value="Vaccination">Vaccination</option>
                    <option value="Sick Visit">Sick Visit</option>
                    <option value="Injury">Injury</option>
                    <option value="Dental">Dental Care</option>
                    <option value="Surgery">Surgery Consultation</option>
                    <option value="Follow-up">Follow-up Visit</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="mb-6">
                <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Additional Notes (optional)</label>
                <textarea name="notes" id="notes" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Please provide any additional information about the reason for your visit..."></textarea>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <a href="my_appointments.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancel
                </a>
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Schedule Appointment
                </button>
            </div>
        </form>
    </div>
    
    <div class="max-w-2xl mx-auto mt-8 bg-blue-50 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold mb-3">Appointment Information</h3>
        <ul class="space-y-2 text-gray-600">
            <li class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                <span>Appointments can be scheduled up to 3 months in advance.</span>
            </li>
            <li class="flex items-start"></li>
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                <span>Appointments are available Monday through Friday, 8:00 AM to 5:00 PM, and Saturday 9:00 AM to 2:00 PM.</span>
            </li>
            <li class="flex items-start"></li>
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                <span>Please arrive 10 minutes before your scheduled appointment time.</span>
            </li>
            <li class="flex items-start"></li>
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                <span>If you need to cancel or reschedule, please do so at least 24 hours in advance.</span>
            </li>
        </ul>
    </div>
</div>

<script>
    document.getElementById('appointment_date').addEventListener('change', function() {
        // In a real implementation, this would fetch available times from the server
        // based on the selected date and veterinarian
        console.log('Date selected:', this.value);
    });
    
    document.getElementById('vet_id').addEventListener('change', function() {
        // Reset available times when vet changes
        console.log('Vet selected:', this.value);
    });
</script>

<?php include_once 'includes/footer.php'; ?>
