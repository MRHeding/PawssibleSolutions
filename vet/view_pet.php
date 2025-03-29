<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

// Check if pet ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: patients.php");
    exit;
}

$pet_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';

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

// Get pet details with owner information
$query = "SELECT p.*, 
          CONCAT(u.first_name, ' ', u.last_name) as owner_name, 
          u.email as owner_email, u.phone as owner_phone, u.id as owner_id
          FROM pets p 
          JOIN users u ON p.owner_id = u.id
          WHERE p.id = :pet_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':pet_id', $pet_id);
$stmt->execute();

// If no pet found, redirect back to patients page
if ($stmt->rowCount() === 0) {
    header("Location: patients.php");
    exit;
}

$pet = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate pet's age
$pet_age = 'Unknown';
if (!empty($pet['date_of_birth'])) {
    $birthdate = new DateTime($pet['date_of_birth']);
    $today = new DateTime();
    $age = $birthdate->diff($today);
    $pet_age = $age->y . ' years, ' . $age->m . ' months';
}

// Verify this vet has seen this pet before
$access_check_query = "SELECT COUNT(*) as count FROM appointments 
                      WHERE pet_id = :pet_id AND vet_id = :vet_id";
$access_check_stmt = $db->prepare($access_check_query);
$access_check_stmt->bindParam(':pet_id', $pet_id);
$access_check_stmt->bindParam(':vet_id', $vet_id);
$access_check_stmt->execute();
$access_result = $access_check_stmt->fetch(PDO::FETCH_ASSOC);

// If the vet has never seen this pet, redirect to patients page
if ($access_result['count'] == 0) {
    header("Location: patients.php");
    exit;
}

// Get medical records for this pet
$records_query = "SELECT mr.*, 
                 CONCAT(u.first_name, ' ', u.last_name) as vet_name,
                 a.appointment_date, a.reason
                 FROM medical_records mr
                 JOIN users u ON mr.created_by = u.id
                 LEFT JOIN appointments a ON mr.appointment_id = a.id
                 WHERE mr.pet_id = :pet_id
                 ORDER BY mr.record_date DESC";
$records_stmt = $db->prepare($records_query);
$records_stmt->bindParam(':pet_id', $pet_id);
$records_stmt->execute();
$medical_records = $records_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vaccination records for this pet
$vaccinations_query = "SELECT vr.*, CONCAT(u.first_name, ' ', u.last_name) as vet_name
                      FROM vaccination_records vr
                      JOIN users u ON vr.administered_by = u.id
                      WHERE vr.pet_id = :pet_id
                      ORDER BY vr.vaccination_date DESC";
$vaccinations_stmt = $db->prepare($vaccinations_query);
$vaccinations_stmt->bindParam(':pet_id', $pet_id);
$vaccinations_stmt->execute();
$vaccination_records = $vaccinations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get appointments for this pet with this vet
$appointments_query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as owner_name
                      FROM appointments a
                      JOIN pets p ON a.pet_id = p.id
                      JOIN users u ON p.owner_id = u.id
                      WHERE a.pet_id = :pet_id AND a.vet_id = :vet_id
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointments_stmt = $db->prepare($appointments_query);
$appointments_stmt->bindParam(':pet_id', $pet_id);
$appointments_stmt->bindParam(':vet_id', $vet_id);
$appointments_stmt->execute();
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($pet['name']); ?></h1>
                <p class="text-white text-opacity-90 mt-2">
                    <?php echo htmlspecialchars($pet['species']); ?>
                    <?php if (!empty($pet['breed'])): ?> - <?php echo htmlspecialchars($pet['breed']); ?><?php endif; ?>
                </p>
            </div>
            <div>
                <a href="patients.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Patients
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if ($success_msg === 'weight_updated'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="font-bold">Success!</span> Pet's weight has been updated successfully.
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Pet Profile Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-6">
                    <div class="h-16 w-16 bg-violet-100 rounded-full flex items-center justify-center text-violet-600 mr-4">
                        <?php
                        $icon = 'fa-paw';
                        if (strtolower($pet['species']) === 'dog') {
                            $icon = 'fa-dog';
                        } elseif (strtolower($pet['species']) === 'cat') {
                            $icon = 'fa-cat';
                        } elseif (strtolower($pet['species']) === 'bird') {
                            $icon = 'fa-dove';
                        } elseif (strtolower($pet['species']) === 'fish') {
                            $icon = 'fa-fish';
                        }
                        ?>
                        <i class="fas <?php echo $icon; ?> text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900"><?php echo htmlspecialchars($pet['name']); ?></h2>
                        <p class="text-gray-600">
                            <?php echo htmlspecialchars($pet['species']); ?>
                            <?php if (!empty($pet['breed'])): ?> - <?php echo htmlspecialchars($pet['breed']); ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-600">Age</p>
                        <p class="font-medium"><?php echo $pet_age; ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Gender</p>
                        <p class="font-medium"><?php echo ucfirst($pet['gender']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Date of Birth</p>
                        <p class="font-medium">
                            <?php echo !empty($pet['date_of_birth']) ? date('F d, Y', strtotime($pet['date_of_birth'])) : 'Not specified'; ?>
                        </p>
                    </div>
                    <div>
                        <form id="weightForm" action="update_pet_weight.php" method="post" class="flex items-end gap-2">
                            <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">
                            <div class="flex-grow">
                                <p class="text-sm text-gray-600">Weight</p>
                                <div class="flex items-center">
                                    <input type="number" name="weight" step="0.1" min="0" max="1000" 
                                          value="<?php echo $pet['weight'] ?? ''; ?>" 
                                          placeholder="Enter weight" 
                                          class="font-medium border-gray-300 focus:border-violet-500 focus:ring-violet-500 w-20 rounded-md">
                                    <span class="ml-2">kg</span>
                                </div>
                            </div>
                            <button type="submit" class="text-violet-600 hover:text-violet-800">
                                <i class="fas fa-save"></i> Save
                            </button>
                        </form>
                    </div>
                    <?php if (!empty($pet['microchip_id'])): ?>
                    <div class="md:col-span-2">
                        <p class="text-sm text-gray-600">Microchip ID</p>
                        <p class="font-medium"><?php echo htmlspecialchars($pet['microchip_id']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Medical Records Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Medical History</h2>
                    <a href="create_record.php?pet_id=<?php echo $pet_id; ?>" class="text-violet-600 hover:text-violet-800 flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i> Add Record
                    </a>
                </div>
                
                <?php if (count($medical_records) > 0): ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($medical_records as $record): ?>
                            <div class="py-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <div class="flex items-center">
                                            <span class="font-medium text-gray-900"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></span>
                                            <span class="mx-2 text-gray-400">•</span>
                                            <span class="text-sm text-gray-600">Dr. <?php echo htmlspecialchars($record['vet_name']); ?></span>
                                        </div>
                                        <h3 class="text-lg font-semibold mt-1"><?php echo htmlspecialchars($record['diagnosis']); ?></h3>
                                    </div>
                                    <a href="view_record.php?id=<?php echo $record['id']; ?>" class="text-violet-600 hover:text-violet-800">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars(substr($record['treatment'], 0, 150) . (strlen($record['treatment']) > 150 ? '...' : ''))); ?></p>
                                </div>
                                <?php if (!empty($record['medications'])): ?>
                                    <div class="mt-2 flex items-start">
                                        <span class="text-gray-600 mr-2"><i class="fas fa-prescription-bottle-alt"></i></span>
                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars(substr($record['medications'], 0, 100) . (strlen($record['medications']) > 100 ? '...' : '')); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No medical records</h3>
                        <p class="mt-1 text-sm text-gray-500">No medical records have been created for this pet yet.</p>
                        <div class="mt-6">
                            <a href="create_record.php?pet_id=<?php echo $pet_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                                <i class="fas fa-plus-circle mr-2"></i> Create Medical Record
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Vaccination Records Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Vaccination History</h2>
                    <a href="#" class="text-violet-600 hover:text-violet-800 flex items-center">
                        <i class="fas fa-syringe mr-1"></i> Add Vaccination
                    </a>
                </div>
                
                <?php if (count($vaccination_records) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccine</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Administered By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($vaccination_records as $vaccine): ?>
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($vaccine['vaccination_date'])); ?></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?php if (!empty($vaccine['valid_until'])): ?>
                                                <div class="text-sm text-gray-900">
                                                    <?php 
                                                    $valid_until = new DateTime($vaccine['valid_until']);
                                                    $today = new DateTime();
                                                    $is_expired = $valid_until < $today;
                                                    
                                                    echo date('M d, Y', strtotime($vaccine['valid_until']));
                                                    
                                                    if ($is_expired) {
                                                        echo ' <span class="text-red-600 text-xs font-medium">(Expired)</span>';
                                                    }
                                                    ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-sm text-gray-500">Not specified</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">Dr. <?php echo htmlspecialchars($vaccine['vet_name']); ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No vaccination records</h3>
                        <p class="mt-1 text-sm text-gray-500">No vaccination records have been added for this pet yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Appointment History Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Appointment History</h2>
                    <a href="create_appointment.php?pet_id=<?php echo $pet_id; ?>" class="text-violet-600 hover:text-violet-800 flex items-center">
                        <i class="fas fa-calendar-plus mr-1"></i> Schedule Appointment
                    </a>
                </div>
                
                <?php if (count($appointments) > 0): ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($appointments as $appt): ?>
                            <div class="py-4 flex justify-between items-center">
                                <div>
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-900">
                                            <?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?> at
                                            <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?>
                                        </span>
                                        <span class="ml-3">
                                            <?php
                                            $statusClasses = [
                                                'scheduled' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'no-show' => 'bg-yellow-100 text-yellow-800'
                                            ];
                                            $statusClass = isset($statusClasses[$appt['status']]) ? $statusClasses[$appt['status']] : 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($appt['status']); ?>
                                            </span>
                                        </span>
                                    </div>
                                    <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($appt['reason']); ?></p>
                                </div>
                                <a href="view_appointment.php?id=<?php echo $appt['id']; ?>" class="text-violet-600 hover:text-violet-800">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No appointment history</h3>
                        <p class="mt-1 text-sm text-gray-500">This pet doesn't have any appointment records with you yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Owner Information Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Owner Information</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Name</p>
                        <p class="font-medium"><?php echo htmlspecialchars($pet['owner_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Email</p>
                        <a href="mailto:<?php echo $pet['owner_email']; ?>" class="font-medium text-violet-600 hover:text-violet-800">
                            <?php echo htmlspecialchars($pet['owner_email']); ?>
                        </a>
                    </div>
                    <?php if (!empty($pet['owner_phone'])): ?>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <a href="tel:<?php echo $pet['owner_phone']; ?>" class="font-medium text-violet-600 hover:text-violet-800">
                                <?php echo htmlspecialchars($pet['owner_phone']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions Card -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <a href="create_record.php?pet_id=<?php echo $pet_id; ?>" class="block w-full bg-violet-600 hover:bg-violet-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-file-medical mr-2"></i> Add Medical Record
                    </a>
                    
                    <a href="create_appointment.php?pet_id=<?php echo $pet_id; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-calendar-plus mr-2"></i> Schedule Appointment
                    </a>
                    
                    <a href="#" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-syringe mr-2"></i> Add Vaccination
                    </a>
                    
                    <a href="#" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-center font-medium py-2 px-4 rounded-md transition-colors"
                       onclick="window.print();">
                        <i class="fas fa-print mr-2"></i> Print Summary
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple weight update script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const weightForm = document.getElementById('weightForm');
    if(weightForm) {
        weightForm.addEventListener('submit', function(e) {
            // For future AJAX implementation if needed
            // Currently using standard form submission
        });
    }
});
</script>

<?php include_once '../includes/vet_footer.php'; ?>
