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
          p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth, p.weight, p.microchip_id,
          CONCAT(u.first_name, ' ', u.last_name) as owner_name, u.email as owner_email, u.phone as owner_phone
          FROM appointments a 
          JOIN pets p ON a.pet_id = p.id
          JOIN users u ON p.owner_id = u.id
          WHERE a.id = :appointment_id AND a.vet_id = :vet_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':appointment_id', $appointment_id);
$stmt->bindParam(':vet_id', $vet_id);
$stmt->execute();

// If no appointment found, redirect back to appointments page
if ($stmt->rowCount() === 0) {
    header("Location: appointments.php");
    exit;
}

$appointment = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate pet's age
$pet_age = 'Unknown';
if (!empty($appointment['date_of_birth'])) {
    $birthdate = new DateTime($appointment['date_of_birth']);
    $today = new DateTime();
    $age = $birthdate->diff($today);
    $pet_age = $age->y . ' years, ' . $age->m . ' months';
}

// Check if there's a medical record for this appointment
$record_query = "SELECT * FROM medical_records WHERE appointment_id = :appointment_id";
$record_stmt = $db->prepare($record_query);
$record_stmt->bindParam(':appointment_id', $appointment_id);
$record_stmt->execute();
$has_medical_record = $record_stmt->rowCount() > 0;
$medical_record = $record_stmt->fetch(PDO::FETCH_ASSOC);

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Appointment Details</h1>
                <p class="text-white text-opacity-90 mt-2">
                    <?php echo date('l, F d, Y', strtotime($appointment['appointment_date'])); ?> at 
                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                </p>
            </div>
            <div>
                <a href="appointments.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Appointment Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Appointment Information</h2>
                    <span class="<?php 
                        echo match($appointment['status']) {
                            'scheduled' => 'bg-blue-100 text-blue-800',
                            'completed' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            'no-show' => 'bg-yellow-100 text-yellow-800',
                            default => 'bg-gray-100 text-gray-800'
                        };
                    ?> px-3 py-1 rounded-full text-sm font-semibold">
                        <?php echo ucfirst($appointment['status']); ?>
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Date</p>
                        <p class="font-medium"><?php echo date('l, F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Time</p>
                        <p class="font-medium"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-600">Reason for Visit</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                    </div>
                    <?php if (!empty($appointment['notes'])): ?>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Additional Notes</p>
                            <p class="font-medium"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pet Information -->
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
                    <h2 class="text-xl font-semibold text-gray-900">Pet Information</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Name</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['pet_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Species</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['species']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Breed</p>
                        <p class="font-medium"><?php echo !empty($appointment['breed']) ? htmlspecialchars($appointment['breed']) : 'Not specified'; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Gender</p>
                        <p class="font-medium"><?php echo ucfirst($appointment['gender']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Age</p>
                        <p class="font-medium"><?php echo $pet_age; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Weight</p>
                        <p class="font-medium"><?php echo !empty($appointment['weight']) ? $appointment['weight'] . ' kg' : 'Not recorded'; ?></p>
                    </div>
                    <?php if (!empty($appointment['microchip_id'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Microchip ID</p>
                            <p class="font-medium"><?php echo htmlspecialchars($appointment['microchip_id']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 flex">
                    <a href="view_pet.php?id=<?php echo $appointment['pet_id']; ?>" class="text-violet-600 hover:text-violet-800 inline-flex items-center">
                        <i class="fas fa-clipboard-list mr-1"></i> View Medical History
                    </a>
                </div>
            </div>
            
            <!-- Medical Record Section -->
            <?php if ($has_medical_record): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Medical Record</h2>
                        <a href="view_record.php?id=<?php echo $medical_record['id']; ?>" class="text-violet-600 hover:text-violet-800">
                            View Complete Record
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Diagnosis</p>
                            <p class="font-medium"><?php echo htmlspecialchars($medical_record['diagnosis']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Treatment</p>
                            <p class="font-medium"><?php echo nl2br(htmlspecialchars($medical_record['treatment'])); ?></p>
                        </div>
                        <?php if (!empty($medical_record['medications'])): ?>
                            <div>
                                <p class="text-sm text-gray-600">Medications</p>
                                <p class="font-medium"><?php echo nl2br(htmlspecialchars($medical_record['medications'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Owner Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Owner Information</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Name</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['owner_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['owner_email']); ?></p>
                    </div>
                    <?php if (!empty($appointment['owner_phone'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($appointment['owner_phone']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Actions</h2>
                
                <?php if ($appointment['status'] === 'scheduled'): ?>
                    <!-- Actions for scheduled appointments -->
                    <div class="space-y-3">
                        <a href="start_appointment.php?id=<?php echo $appointment_id; ?>" class="block w-full bg-violet-600 hover:bg-violet-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-stethoscope mr-1"></i> Start Appointment
                        </a>
                        
                        <?php if (!$has_medical_record): ?>
                            <a href="create_record.php?appointment_id=<?php echo $appointment_id; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-file-medical mr-1"></i> Create Medical Record
                            </a>
                        <?php else: ?>
                            <a href="edit_record.php?id=<?php echo $medical_record['id']; ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-edit mr-1"></i> Edit Medical Record
                            </a>
                        <?php endif; ?>
                        
                        <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=completed" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-check-circle mr-1"></i> Mark as Completed
                        </a>
                        
                        <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=cancelled" onclick="return confirm('Are you sure you want to cancel this appointment?');" class="block w-full bg-red-100 hover:bg-red-200 text-red-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-times-circle mr-1"></i> Cancel Appointment
                        </a>
                        
                        <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=no-show" onclick="return confirm('Are you sure you want to mark this as no-show?');" class="block w-full bg-yellow-100 hover:bg-yellow-200 text-yellow-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-user-times mr-1"></i> Mark as No-Show
                        </a>
                    </div>
                <?php elseif ($appointment['status'] === 'completed'): ?>
                    <!-- Actions for completed appointments -->
                    <div class="space-y-3">
                        <?php if (!$has_medical_record): ?>
                            <a href="create_record.php?appointment_id=<?php echo $appointment_id; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-file-medical mr-1"></i> Add Medical Record
                            </a>
                        <?php else: ?>
                            <a href="edit_record.php?id=<?php echo $medical_record['id']; ?>" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-edit mr-1"></i> Edit Medical Record
                            </a>
                            
                            <a href="print_record.php?id=<?php echo $medical_record['id']; ?>" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                                <i class="fas fa-print mr-1"></i> Print Medical Record
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- Actions for cancelled/no-show appointments -->
                    <div class="space-y-3">
                        <a href="update_status.php?id=<?php echo $appointment_id; ?>&status=scheduled" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-calendar-check mr-1"></i> Reschedule Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
