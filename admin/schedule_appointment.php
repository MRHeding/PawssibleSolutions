<?php
session_start();
include_once '../config/database.php';
include_once '../includes/appointment_number_generator.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize appointment number generator
$appointmentNumberGenerator = new AppointmentNumberGenerator($db);

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
        
        // Check for time conflicts for the vet
        $conflict_check = "SELECT COUNT(*) FROM appointments 
                          WHERE vet_id = :vet_id 
                          AND appointment_date = :date 
                          AND appointment_time = :time 
                          AND status != 'cancelled'";
        $conflict_stmt = $db->prepare($conflict_check);
        $conflict_stmt->bindParam(':vet_id', $vet_id);
        $conflict_stmt->bindParam(':date', $appointment_date);
        $conflict_stmt->bindParam(':time', $appointment_time);
        $conflict_stmt->execute();
        
        if ($conflict_stmt->fetchColumn() > 0) {
            $message = "There is already an appointment scheduled for this veterinarian at the selected time";
            $alert_class = "bg-red-100 text-red-700";
        } else {
            // Generate appointment number
            $appointment_number = $appointmentNumberGenerator->generateAppointmentNumber();
            
            // Insert the new appointment
            $insert_query = "INSERT INTO appointments (appointment_number, pet_id, vet_id, appointment_date, appointment_time, reason, notes, status, created_at) 
                            VALUES (:appointment_number, :pet_id, :vet_id, :appointment_date, :appointment_time, :reason, :notes, :status, NOW())";
            
            try {
                $stmt = $db->prepare($insert_query);
                $stmt->bindParam(':appointment_number', $appointment_number);
                $stmt->bindParam(':pet_id', $pet_id);
                $stmt->bindParam(':vet_id', $vet_id);
                $stmt->bindParam(':appointment_date', $appointment_date);
                $stmt->bindParam(':appointment_time', $appointment_time);
                $stmt->bindParam(':reason', $reason);
                $stmt->bindParam(':notes', $notes);
                $stmt->bindParam(':status', $status);
                
                if ($stmt->execute()) {
                    $message = "Appointment #{$appointment_number} scheduled successfully";
                    $alert_class = "bg-green-100 text-green-700";
                } else {
                    $message = "Error scheduling appointment";
                    $alert_class = "bg-red-100 text-red-700";
                }
            } catch (PDOException $e) {
                $message = "Database error: " . $e->getMessage();
                $alert_class = "bg-red-100 text-red-700";
            }
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

// Get selected vet's schedule if a vet is selected
$vet_schedule = [];
if (isset($_GET['vet_id']) && !empty($_GET['vet_id'])) {
    $selected_vet = $_GET['vet_id'];
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+14 days')); // Show 2 weeks of schedule
    
    $schedule_query = "SELECT appointment_date, appointment_time FROM appointments 
                      WHERE vet_id = :vet_id 
                      AND appointment_date BETWEEN :start_date AND :end_date
                      AND status != 'cancelled'
                      ORDER BY appointment_date, appointment_time";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->bindParam(':vet_id', $selected_vet);
    $schedule_stmt->bindParam(':start_date', $start_date);
    $schedule_stmt->bindParam(':end_date', $end_date);
    $schedule_stmt->execute();
    
    while ($row = $schedule_stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = $row['appointment_date'];
        $time = $row['appointment_time'];
        if (!isset($vet_schedule[$date])) {
            $vet_schedule[$date] = [];
        }
        $vet_schedule[$date][] = $time;
    }
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Schedule Appointment</h1>
            <a href="appointments.php" class="bg-white text-violet-600 px-4 py-2 rounded hover:bg-violet-50 transition">
                View All Appointments
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
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Appointment Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-6 text-violet-700 border-b pb-2">Appointment Details</h2>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="appointmentForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="pet_id" class="block text-sm font-medium text-gray-700 mb-2">Select Pet</label>
                            <select name="pet_id" id="pet_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500" required>
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
                            <select name="vet_id" id="vet_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                                <option value="">-- Select Veterinarian --</option>
                                <?php 
                                $vets_stmt->execute(); // Reset the cursor
                                while ($vet = $vets_stmt->fetch(PDO::FETCH_ASSOC)): 
                                    $selected = (isset($_GET['vet_id']) && $_GET['vet_id'] == $vet['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $vet['id']; ?>" <?php echo $selected; ?>>
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
                            <input type="date" id="appointment_date" name="appointment_date" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div>
                            <label for="appointment_time" class="block text-sm font-medium text-gray-700 mb-2">Appointment Time</label>
                            <select id="appointment_time" name="appointment_time" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                                <option value="">-- Select Time --</option>
                                <?php
                                // Generate time slots from 8 AM to 5 PM
                                $start = 8 * 60; // 8:00 AM in minutes
                                $end = 17 * 60; // 5:00 PM in minutes
                                $interval = 30; // 30 minute intervals
                                
                                for ($mins = $start; $mins < $end; $mins += $interval) {
                                    $hour = floor($mins / 60);
                                    $min = $mins % 60;
                                    $ampm = $hour >= 12 ? 'PM' : 'AM';
                                    $hour12 = $hour > 12 ? $hour - 12 : ($hour == 0 ? 12 : $hour);
                                    $time_display = sprintf('%02d:%02d %s', $hour12, $min, $ampm);
                                    $time_value = sprintf('%02d:%02d:00', $hour, $min);
                                    echo "<option value=\"$time_value\">$time_display</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit</label>
                            <select name="reason" id="reason" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500" required>
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
                            <textarea id="notes" name="notes" rows="4" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end">
                        <button type="reset" class="bg-gray-300 text-gray-800 px-4 py-2 rounded-md mr-2 hover:bg-gray-400 transition-colors">Clear Form</button>
                        <button type="submit" class="bg-violet-600 text-white px-6 py-2 rounded-md hover:bg-violet-700 transition-colors">Schedule Appointment</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Calendar/Schedule Sidebar -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-violet-700 border-b pb-2">Vet Availability</h2>
                
                <?php if (!isset($_GET['vet_id']) || empty($_GET['vet_id'])): ?>
                    <div class="text-center py-10 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="mt-2">Select a veterinarian to view their schedule</p>
                        <button id="viewScheduleBtn" class="mt-4 bg-violet-100 text-violet-700 px-4 py-2 rounded hover:bg-violet-200 transition">
                            View Schedule
                        </button>
                    </div>
                <?php else: ?>
                    <?php
                    // Display selected vet's schedule for the next 7 days
                    $current_date = date('Y-m-d');
                    $selected_vet_id = $_GET['vet_id'];
                    
                    // Get vet name
                    $vet_name_query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as name 
                                     FROM vets v JOIN users u ON v.user_id = u.id 
                                     WHERE v.id = :vet_id";
                    $vet_name_stmt = $db->prepare($vet_name_query);
                    $vet_name_stmt->bindParam(':vet_id', $selected_vet_id);
                    $vet_name_stmt->execute();
                    $vet_name = $vet_name_stmt->fetchColumn();
                    ?>
                    
                    <div class="mb-4">
                        <h3 class="text-lg font-medium">Dr. <?php echo htmlspecialchars($vet_name); ?></h3>
                        <p class="text-gray-500 text-sm">Available times for the next 7 days</p>
                    </div>
                    
                    <div class="space-y-4">
                        <?php
                        // Show schedule for the next 7 days
                        for ($i = 0; $i < 7; $i++) {
                            $date = date('Y-m-d', strtotime("+$i days"));
                            $date_display = date('l, M d', strtotime($date));
                            $is_weekend = (date('N', strtotime($date)) >= 6); // 6 = Saturday, 7 = Sunday
                            
                            echo "<div class='border rounded-lg p-3 " . ($is_weekend ? 'bg-gray-50' : '') . "'>";
                            echo "<h4 class='font-medium'>" . htmlspecialchars($date_display) . "</h4>";
                            
                            if ($is_weekend) {
                                echo "<p class='text-gray-500 italic text-sm mt-1'>Weekend - No regular appointments</p>";
                            } else {
                                // Display booked appointments for this date
                                if (isset($vet_schedule[$date]) && count($vet_schedule[$date]) > 0) {
                                    echo "<div class='mt-2'>";
                                    echo "<p class='text-sm text-gray-600'>Booked times:</p>";
                                    echo "<div class='flex flex-wrap gap-1 mt-1'>";
                                    foreach ($vet_schedule[$date] as $time) {
                                        $time_display = date('g:i A', strtotime($time));
                                        echo "<span class='bg-red-100 text-red-800 text-xs px-2 py-1 rounded'>$time_display</span>";
                                    }
                                    echo "</div>";
                                    echo "</div>";
                                } else {
                                    echo "<p class='text-green-600 text-sm mt-1'>All time slots available</p>";
                                }
                                
                                echo "<button class='select-date-btn mt-2 text-violet-600 text-sm hover:text-violet-800' 
                                      data-date='$date'>Select this date</button>";
                            }
                            
                            echo "</div>";
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View schedule button
    const viewScheduleBtn = document.getElementById('viewScheduleBtn');
    if (viewScheduleBtn) {
        viewScheduleBtn.addEventListener('click', function() {
            const vetId = document.getElementById('vet_id').value;
            if (vetId) {
                window.location.href = '?vet_id=' + vetId;
            } else {
                alert('Please select a veterinarian first');
            }
        });
    }
    
    // Handle date selection
    const dateButtons = document.querySelectorAll('.select-date-btn');
    dateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const selectedDate = this.getAttribute('data-date');
            document.getElementById('appointment_date').value = selectedDate;
        });
    });
    
    // Vet selection change
    const vetSelect = document.getElementById('vet_id');
    vetSelect.addEventListener('change', function() {
        const vetId = this.value;
        if (vetId) {
            // Update URL without reloading
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('vet_id', vetId);
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            window.history.pushState({path: newUrl}, '', newUrl);
        }
    });
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>
