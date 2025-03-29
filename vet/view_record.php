<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: records.php");
    exit;
}

$record_id = $_GET['id'];
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

// Get medical record details with pet and owner information
$query = "SELECT mr.*, 
          p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth, p.weight, p.microchip_id,
          CONCAT(u.first_name, ' ', u.last_name) as owner_name, u.email as owner_email, u.phone as owner_phone,
          CONCAT(v.first_name, ' ', v.last_name) as vet_name,
          a.id as appointment_id, a.appointment_date, a.appointment_time, a.reason as visit_reason
          FROM medical_records mr
          JOIN pets p ON mr.pet_id = p.id
          JOIN users u ON p.owner_id = u.id
          JOIN users v ON mr.created_by = v.id
          LEFT JOIN appointments a ON mr.appointment_id = a.id
          WHERE mr.id = :record_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':record_id', $record_id);
$stmt->execute();

// If no record found, redirect back to records page
if ($stmt->rowCount() === 0) {
    header("Location: records.php");
    exit;
}

$record = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate pet's age at the time of the record
$pet_age = 'Unknown';
if (!empty($record['date_of_birth'])) {
    $birthdate = new DateTime($record['date_of_birth']);
    $record_date = new DateTime($record['record_date']);
    $age = $birthdate->diff($record_date);
    $pet_age = $age->y . ' years, ' . $age->m . ' months';
}

// Get other medical records for this pet (excluding current one)
$other_records_query = "SELECT mr.id, mr.record_date, mr.diagnosis
                       FROM medical_records mr
                       WHERE mr.pet_id = :pet_id AND mr.id != :record_id
                       ORDER BY mr.record_date DESC
                       LIMIT 5";
$other_records_stmt = $db->prepare($other_records_query);
$other_records_stmt->bindParam(':pet_id', $record['pet_id']);
$other_records_stmt->bindParam(':record_id', $record_id);
$other_records_stmt->execute();
$other_records = $other_records_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vaccination records for this pet
$vaccinations_query = "SELECT * FROM vaccination_records
                      WHERE pet_id = :pet_id
                      ORDER BY vaccination_date DESC
                      LIMIT 5";
$vaccinations_stmt = $db->prepare($vaccinations_query);
$vaccinations_stmt->bindParam(':pet_id', $record['pet_id']);
$vaccinations_stmt->execute();
$vaccinations = $vaccinations_stmt->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Medical Record</h1>
                <p class="text-white text-opacity-90 mt-2">
                    <?php echo date('F d, Y', strtotime($record['record_date'])); ?> - <?php echo htmlspecialchars($record['pet_name']); ?>
                </p>
            </div>
            <div>
                <?php if (!empty($record['appointment_id'])): ?>
                    <a href="view_appointment.php?id=<?php echo $record['appointment_id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-calendar-day mr-2"></i> View Appointment
                    </a>
                <?php else: ?>
                    <a href="records.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Records
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if ($success_msg === 'created'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="font-bold">Success!</span> Medical record has been created successfully.
        </div>
    <?php elseif ($success_msg === 'updated'): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="font-bold">Success!</span> Medical record has been updated successfully.
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Medical Record Details -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="border-b border-gray-200 pb-4 mb-4">
                    <div class="flex justify-between items-start">
                        <h2 class="text-xl font-semibold text-gray-900">Diagnosis & Treatment</h2>
                        <span class="text-sm text-gray-500">Record #<?php echo $record_id; ?></span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        Created by Dr. <?php echo htmlspecialchars($record['vet_name']); ?> on <?php echo date('M d, Y', strtotime($record['created_at'])); ?>
                    </p>
                </div>
                
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-2">Diagnosis</h3>
                        <p class="text-gray-800"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                    </div>
                    
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-2">Treatment</h3>
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                    </div>
                    
                    <?php if (!empty($record['medications'])): ?>
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-2">Medications Prescribed</h3>
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($record['medications'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($record['notes'])): ?>
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-2">Additional Notes</h3>
                        <p class="text-gray-800"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($record['appointment_id'])): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-md font-medium text-gray-900 mb-2">Visit Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Date & Time</p>
                            <p class="font-medium">
                                <?php echo date('M d, Y', strtotime($record['appointment_date'])); ?> at 
                                <?php echo date('h:i A', strtotime($record['appointment_time'])); ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Reason for Visit</p>
                            <p class="font-medium"><?php echo htmlspecialchars($record['visit_reason']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Other Medical Records for this Pet -->
            <?php if (count($other_records) > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Other Medical History</h2>
                <div class="space-y-4">
                    <?php foreach ($other_records as $other_record): ?>
                    <div class="border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                        <div class="flex justify-between items-center">
                            <p class="font-medium"><?php echo htmlspecialchars($other_record['diagnosis']); ?></p>
                            <span class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($other_record['record_date'])); ?></span>
                        </div>
                        <div class="mt-2">
                            <a href="view_record.php?id=<?php echo $other_record['id']; ?>" class="text-violet-600 hover:text-violet-800 text-sm">View details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4 pt-2">
                    <a href="view_pet.php?id=<?php echo $record['pet_id']; ?>" class="text-violet-600 hover:text-violet-800 text-sm inline-flex items-center">
                        <i class="fas fa-history mr-1"></i> View complete medical history
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Vaccinations -->
            <?php if (count($vaccinations) > 0): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Vaccination Records</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vaccine</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Until</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($vaccinations as $vaccine): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vaccine['vaccine_name']); ?></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($vaccine['vaccination_date'])); ?></div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <?php if (!empty($vaccine['valid_until'])): ?>
                                        <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($vaccine['valid_until'])); ?></div>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-500">Not specified</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Pet Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 h-10 w-10 bg-violet-100 rounded-full flex items-center justify-center text-violet-600">
                        <?php
                        $icon = 'fa-paw';
                        if (strtolower($record['species']) === 'dog') {
                            $icon = 'fa-dog';
                        } elseif (strtolower($record['species']) === 'cat') {
                            $icon = 'fa-cat';
                        } elseif (strtolower($record['species']) === 'bird') {
                            $icon = 'fa-dove';
                        } elseif (strtolower($record['species']) === 'fish') {
                            $icon = 'fa-fish';
                        }
                        ?>
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <h2 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($record['pet_name']); ?></h2>
                        <p class="text-sm text-gray-600">
                            <?php echo htmlspecialchars($record['species']); ?>
                            <?php if (!empty($record['breed'])): ?> - <?php echo htmlspecialchars($record['breed']); ?><?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Age at time of record</p>
                        <p class="font-medium"><?php echo $pet_age; ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Gender</p>
                        <p class="font-medium"><?php echo ucfirst($record['gender']); ?></p>
                    </div>
                    <?php if (!empty($record['weight'])): ?>
                    <div>
                        <p class="text-xs text-gray-500">Weight</p>
                        <p class="font-medium"><?php echo $record['weight']; ?> kg</p>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($record['microchip_id'])): ?>
                    <div>
                        <p class="text-xs text-gray-500">Microchip ID</p>
                        <p class="font-medium"><?php echo htmlspecialchars($record['microchip_id']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="view_pet.php?id=<?php echo $record['pet_id']; ?>" class="text-violet-600 hover:text-violet-800 inline-flex items-center text-sm">
                        <i class="fas fa-folder-open mr-1"></i> View pet's complete profile
                    </a>
                </div>
            </div>
            
            <!-- Owner Information -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold mb-3">Owner Information</h2>
                <div class="space-y-2">
                    <p class="font-medium"><?php echo htmlspecialchars($record['owner_name']); ?></p>
                    <p class="text-sm">
                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                        <a href="mailto:<?php echo htmlspecialchars($record['owner_email']); ?>" class="text-gray-600 hover:text-violet-600">
                            <?php echo htmlspecialchars($record['owner_email']); ?>
                        </a>
                    </p>
                    <?php if (!empty($record['owner_phone'])): ?>
                    <p class="text-sm">
                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                        <a href="tel:<?php echo htmlspecialchars($record['owner_phone']); ?>" class="text-gray-600 hover:text-violet-600">
                            <?php echo htmlspecialchars($record['owner_phone']); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold mb-4">Actions</h2>
                <div class="space-y-3">
                    <a href="edit_record.php?id=<?php echo $record_id; ?>" class="block w-full bg-violet-600 hover:bg-violet-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Record
                    </a>
                    
                    <a href="print_record.php?id=<?php echo $record_id; ?>" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-center font-medium py-2 px-4 rounded-md transition-colors">
                        <i class="fas fa-print mr-2"></i> Print Record
                    </a>
                    
                    <?php if (empty($record['appointment_id'])): ?>
                        <a href="create_appointment.php?pet_id=<?php echo $record['pet_id']; ?>" class="block w-full bg-green-600 hover:bg-green-700 text-white text-center font-medium py-2 px-4 rounded-md transition-colors">
                            <i class="fas fa-calendar-plus mr-2"></i> Schedule Follow-up
                        </a>
                    <?php endif; ?>
                    
                    <a href="#" onclick="history.back(); return false;" class="block w-full text-center text-violet-600 hover:text-violet-800 font-medium py-2">
                        <i class="fas fa-arrow-left mr-1"></i> Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
