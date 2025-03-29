<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

// Check if appointment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: appointments.php");
    exit;
}

$appointment_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = '';

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

// Get appointment details with pet and owner information
$query = "SELECT a.*, 
          p.id as pet_id, p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth, p.weight, p.microchip_id,
          CONCAT(u.first_name, ' ', u.last_name) as owner_name, u.email as owner_email, u.phone as owner_phone
          FROM appointments a 
          JOIN pets p ON a.pet_id = p.id
          JOIN users u ON p.owner_id = u.id
          WHERE a.id = :appointment_id AND a.vet_id = :vet_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':appointment_id', $appointment_id);
$stmt->bindParam(':vet_id', $vet_id);
$stmt->execute();

// If no appointment found or it's not scheduled, redirect back to appointments page
if ($stmt->rowCount() === 0) {
    header("Location: appointments.php");
    exit;
}

$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if appointment is not scheduled
if ($appointment['status'] !== 'scheduled') {
    header("Location: view_appointment.php?id=" . $appointment_id);
    exit;
}

// Calculate pet's age
$pet_age = 'Unknown';
if (!empty($appointment['date_of_birth'])) {
    $birthdate = new DateTime($appointment['date_of_birth']);
    $today = new DateTime();
    $age = $birthdate->diff($today);
    $pet_age = $age->y . ' years, ' . $age->m . ' months';
}

// Get past medical records for this pet
$records_query = "SELECT mr.*, CONCAT(u.first_name, ' ', u.last_name) as vet_name
                 FROM medical_records mr
                 JOIN users u ON mr.created_by = u.id
                 WHERE mr.pet_id = :pet_id
                 ORDER BY mr.record_date DESC
                 LIMIT 3";
$records_stmt = $db->prepare($records_query);
$records_stmt->bindParam(':pet_id', $appointment['pet_id']);
$records_stmt->execute();
$past_records = $records_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission to update visit notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_notes') {
    $notes = $_POST['notes'];
    
    $update_query = "UPDATE appointments SET notes = :notes WHERE id = :appointment_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':notes', $notes);
    $update_stmt->bindParam(':appointment_id', $appointment_id);
    
    if ($update_stmt->execute()) {
        $appointment['notes'] = $notes;
        $success_msg = "notes_updated";
    } else {
        $error_msg = "Failed to update notes";
    }
}

// Check if there's already a medical record for this appointment
$check_record_query = "SELECT id FROM medical_records WHERE appointment_id = :appointment_id";
$check_record_stmt = $db->prepare($check_record_query);
$check_record_stmt->bindParam(':appointment_id', $appointment_id);
$check_record_stmt->execute();
$has_medical_record = $check_record_stmt->rowCount() > 0;

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-6">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-white rounded-full flex items-center justify-center text-violet-600 mr-3">
                        <i class="fas fa-stethoscope"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-white">Active Appointment</h1>
                </div>
                <p class="text-white text-opacity-90 mt-1">
                    <?php echo date('l, F d, Y', strtotime($appointment['appointment_date'])); ?> at 
                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex flex-wrap gap-2">
                <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                    <i class="fas fa-eye mr-2"></i> View Details
                </a>
                <a href="appointments.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                    <i class="fas fa-calendar mr-2"></i> All Appointments
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-6">
    <?php if (!empty($success_msg)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
            <?php if ($success_msg === 'notes_updated'): ?>
                <span class="font-bold">Success!</span> Appointment notes have been updated.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <span class="font-bold">Error:</span> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content - Left & Middle columns -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="h-12 w-12 bg-violet-100 rounded-full flex items-center justify-center text-violet-600 mr-4">
                        <?php
                        $icon = 'fa-paw';
                        if (strtolower($appointment['species']) === 'dog') {
                            $icon = 'fa-dog';
                        } elseif (strtolower($appointment['species']) === 'cat') {
                            $icon = 'fa-cat';
                        } elseif (strtolower($appointment['species']) === 'bird') {
                            $icon = 'fa-dove';
                        } elseif (strtolower($appointment['species']) === 'fish') {
                            $icon = 'fa-fish';
                        }
                        ?>
                        <i class="fas <?php echo $icon; ?> text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($appointment['pet_name']); ?></h2>
                        <p class="text-gray-600">
                            <?php echo htmlspecialchars($appointment['species']); ?>
                            <?php if (!empty($appointment['breed'])): ?> - <?php echo htmlspecialchars($appointment['breed']); ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-xs text-gray-500">Age</p>
                        <p class="font-medium"><?php echo $pet_age; ?></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-xs text-gray-500">Gender</p>
                        <p class="font-medium"><?php echo ucfirst($appointment['gender']); ?></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-md">
                        <p class="text-xs text-gray-500">Weight</p>
                        <p class="font-medium"><?php echo !empty($appointment['weight']) ? $appointment['weight'] . ' kg' : 'Not recorded'; ?></p>
                    </div>
                </div>
                
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="font-medium mb-2">Reason for Visit</h3>
                    <p><?php echo htmlspecialchars($appointment['reason']); ?></p>
                </div>
            </div>
            
            <!-- Appointment Notes Section -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Appointment Notes</h3>
                <form method="post" action="">
                    <input type="hidden" name="action" value="update_notes">
                    <div class="mb-4">
                        <textarea name="notes" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500" placeholder="Enter examination notes, observations, and findings"><?php echo htmlspecialchars($appointment['notes'] ?? ''); ?></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                            <i class="fas fa-save mr-2"></i> Save Notes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Recent Medical History -->
            <?php if (count($past_records) > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Recent Medical History</h3>
                <div class="space-y-4">
                    <?php foreach ($past_records as $record): ?>
                    <div class="border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                        <div class="flex justify-between items-start mb-1">
                            <p class="text-sm font-medium"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></p>
                            <p class="text-xs text-gray-500">By: <?php echo htmlspecialchars($record['vet_name']); ?></p>
                        </div>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                        <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo htmlspecialchars($record['treatment']); ?></p>
                        <div class="mt-2">
                            <a href="view_record.php?id=<?php echo $record['id']; ?>" class="text-violet-600 hover:text-violet-800 text-sm">
                                View complete record
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3">
                    <a href="view_pet.php?id=<?php echo $appointment['pet_id']; ?>" class="text-violet-600 hover:text-violet-800 inline-flex items-center text-sm">
                        <i class="fas fa-clipboard-list mr-1"></i> View all medical records
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar - Right column -->
        <div>
            <!-- Owner Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-3">Owner Information</h3>
                <div class="space-y-2">
                    <p class="font-medium"><?php echo htmlspecialchars($appointment['owner_name']); ?></p>
                    <p class="text-sm">
                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                        <a href="mailto:<?php echo htmlspecialchars($appointment['owner_email']); ?>" class="text-gray-600 hover:text-violet-600">
                            <?php echo htmlspecialchars($appointment['owner_email']); ?>
                        </a>
                    </p>
                    <?php if (!empty($appointment['owner_phone'])): ?>
                    <p class="text-sm">
                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                        <a href="tel:<?php echo htmlspecialchars($appointment['owner_phone']); ?>" class="text-gray-600 hover:text-violet-600">
                            <?php echo htmlspecialchars($appointment['owner_phone']); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <?php if (!$has_medical_record): ?>
                    <a href="create_record.php?appointment_id=<?php echo $appointment_id; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-file-medical mr-2"></i> Create Medical Record
                    </a>
                    <?php else: ?>
                    <a href="create_record.php?appointment_id=<?php echo $appointment_id; ?>" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Medical Record
                    </a>
                    <?php endif; ?>
                    
                    <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=completed&redirect=appointments.php" class="block w-full bg-violet-600 hover:bg-violet-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-check-circle mr-2"></i> Complete Appointment
                    </a>
                    
                    <div class="border-t border-gray-200 my-3 pt-3">
                        <p class="text-sm text-gray-500 mb-2">Other Options</p>
                    </div>
                    
                    <a href="#" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors" onclick="window.print();">
                        <i class="fas fa-print mr-2"></i> Print Visit Summary
                    </a>
                    
                    <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=no-show&redirect=appointments.php" onclick="return confirm('Are you sure you want to mark this as no-show?');" class="block w-full bg-yellow-100 hover:bg-yellow-200 text-yellow-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-user-times mr-2"></i> Mark as No-Show
                    </a>
                    
                    <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=cancelled&redirect=appointments.php" onclick="return confirm('Are you sure you want to cancel this appointment?');" class="block w-full bg-red-100 hover:bg-red-200 text-red-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-times-circle mr-2"></i> Cancel Appointment
                    </a>
                </div>
            </div>
            
            <!-- Equipment/Notes Widget -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-3">Common Procedures</h3>
                <div class="space-y-2">
                    <div class="p-3 bg-gray-50 rounded-md flex justify-between">
                        <span>Physical Examination</span>
                        <span>$60</span>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-md flex justify-between">
                        <span>Vaccination</span>
                        <span>$45</span>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-md flex justify-between">
                        <span>Blood Work - Basic</span>
                        <span>$95</span>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-md flex justify-between">
                        <span>X-Ray</span>
                        <span>$120</span>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="#" class="text-violet-600 hover:text-violet-800 text-sm">
                        View full price list
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
