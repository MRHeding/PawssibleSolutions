<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if pet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pets.php");
    exit;
}

$pet_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get pet details with owner information
$pet_query = "SELECT p.*, u.first_name, u.last_name, u.email, u.phone 
              FROM pets p 
              JOIN users u ON p.owner_id = u.id 
              WHERE p.id = :pet_id";

$pet_stmt = $db->prepare($pet_query);
$pet_stmt->bindParam(':pet_id', $pet_id);
$pet_stmt->execute();

$pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    header("Location: pets.php");
    exit;
}

// Get medical records for this pet
$medical_query = "SELECT mr.*, CONCAT(u.first_name, ' ', u.last_name) as vet_name,
                  a.appointment_number, a.appointment_date
                  FROM medical_records mr
                  LEFT JOIN users u ON mr.created_by = u.id
                  LEFT JOIN appointments a ON mr.appointment_id = a.id
                  WHERE mr.pet_id = :pet_id
                  ORDER BY mr.record_date DESC, mr.created_at DESC
                  LIMIT 5";

$medical_stmt = $db->prepare($medical_query);
$medical_stmt->bindParam(':pet_id', $pet_id);
$medical_stmt->execute();
$medical_records = $medical_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent appointments for this pet
$appointments_query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as vet_name
                       FROM appointments a
                       JOIN vets v ON a.vet_id = v.id
                       JOIN users u ON v.user_id = u.id
                       WHERE a.pet_id = :pet_id
                       ORDER BY a.appointment_date DESC, a.appointment_time DESC
                       LIMIT 5";

$appointments_stmt = $db->prepare($appointments_query);
$appointments_stmt->bindParam(':pet_id', $pet_id);
$appointments_stmt->execute();
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate age from date of birth
$age_text = '';
if ($pet['date_of_birth']) {
    $birth_date = new DateTime($pet['date_of_birth']);
    $today = new DateTime();
    $age = $birth_date->diff($today);
    $age_text = $age->y . ' years, ' . $age->m . ' months';
}

include_once '../includes/admin_header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($pet['name']); ?></h1>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php echo htmlspecialchars($pet['species']); ?> - <?php echo htmlspecialchars($pet['breed']); ?>
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="edit_pet.php?id=<?php echo $pet['id']; ?>" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit"></i> Edit Pet
                        </a>
                        <a href="add_medical_record.php?pet_id=<?php echo $pet['id']; ?>" 
                           class="bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 transition-colors">
                            <i class="fas fa-notes-medical"></i> Add Medical Record
                        </a>
                        <a href="pets.php" 
                           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-arrow-left"></i> Back to Pets
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Pet Information -->
            <div class="lg:col-span-2">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Pet Information</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-3">Basic Information</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Name</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($pet['name']); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Species</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($pet['species']); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Breed</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($pet['breed']); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Gender</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                  <?php echo $pet['gender'] == 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                                                <?php echo ucfirst($pet['gender']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500 mb-3">Additional Details</h3>
                                <div class="space-y-3">
                                    <?php if ($pet['date_of_birth']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo date('F j, Y', strtotime($pet['date_of_birth'])); ?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Age</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo $age_text; ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($pet['weight']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Weight</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($pet['weight']); ?> kg</p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($pet['microchip_id']): ?>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Microchip ID</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($pet['microchip_id']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo date('F j, Y g:i A', strtotime($pet['created_at'])); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Last Updated</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo date('F j, Y g:i A', strtotime($pet['updated_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Owner Information -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Owner Information</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Owner Name</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <a href="view_client.php?id=<?php echo $pet['owner_id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                                    </a>
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <a href="mailto:<?php echo htmlspecialchars($pet['email']); ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <?php echo htmlspecialchars($pet['email']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php if ($pet['phone']): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <p class="mt-1 text-sm text-gray-900">
                                    <a href="tel:<?php echo htmlspecialchars($pet['phone']); ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <?php echo htmlspecialchars($pet['phone']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Records -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Medical Records</h2>
                    <a href="client_medical_records.php?pet_id=<?php echo $pet['id']; ?>" 
                       class="text-sm text-blue-600 hover:text-blue-900">
                        View All Records
                    </a>
                </div>
            </div>
            <div class="px-6 py-4">
                <?php if (count($medical_records) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($medical_records as $record): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            <?php echo date('F j, Y', strtotime($record['record_date'])); ?>
                                        </h3>
                                        <?php if ($record['appointment_number']): ?>
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                                            Appointment #<?php echo htmlspecialchars($record['appointment_number']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-1">
                                        <strong>Diagnosis:</strong> 
                                        <?php echo strlen($record['diagnosis']) > 100 ? 
                                            htmlspecialchars(substr($record['diagnosis'], 0, 100)) . '...' : 
                                            htmlspecialchars($record['diagnosis']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        By: <?php echo htmlspecialchars($record['vet_name'] ?: 'Unknown'); ?>
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="../view_medical_record.php?id=<?php echo $record['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900" title="View Record">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="print_medical_record.php?id=<?php echo $record['id']; ?>" 
                                       target="_blank" class="text-purple-600 hover:text-purple-900" title="Print Record">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-notes-medical text-4xl"></i>
                        </div>
                        <p class="text-gray-600">No medical records found for this pet.</p>
                        <a href="add_medical_record.php?pet_id=<?php echo $pet['id']; ?>" 
                           class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-violet-600 hover:bg-violet-700">
                            <i class="fas fa-plus mr-2"></i> Add First Medical Record
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Appointments -->
        <div class="mt-6 bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Appointments</h2>
                    <a href="appointments.php?pet_id=<?php echo $pet['id']; ?>" 
                       class="text-sm text-blue-600 hover:text-blue-900">
                        View All Appointments
                    </a>
                </div>
            </div>
            <div class="px-6 py-4">
                <?php if (count($appointments) > 0): ?>
                    <div class="space-y-4">
                        <?php foreach ($appointments as $appointment): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                            at <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                        </h3>
                                        <span class="px-2 py-1 text-xs rounded
                                              <?php 
                                              switch($appointment['status']) {
                                                  case 'scheduled':
                                                      echo 'bg-yellow-100 text-yellow-800';
                                                      break;
                                                  case 'completed':
                                                      echo 'bg-green-100 text-green-800';
                                                      break;
                                                  case 'cancelled':
                                                      echo 'bg-red-100 text-red-800';
                                                      break;
                                                  default:
                                                      echo 'bg-gray-100 text-gray-800';
                                              }
                                              ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-1">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($appointment['reason']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        With: <?php echo htmlspecialchars($appointment['vet_name']); ?>
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900" title="View Appointment">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-calendar-alt text-4xl"></i>
                        </div>
                        <p class="text-gray-600">No appointments found for this pet.</p>
                        <a href="schedule_appointment.php?pet_id=<?php echo $pet['id']; ?>" 
                           class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i> Schedule Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
