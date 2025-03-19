<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'client';

// Check if pet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_pets.php");
    exit;
}

$pet_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Verify ownership if user is a client
if ($user_role == 'client') {
    $owner_check = "SELECT id FROM pets WHERE id = :pet_id AND owner_id = :owner_id";
    $check_stmt = $db->prepare($owner_check);
    $check_stmt->bindParam(':pet_id', $pet_id);
    $check_stmt->bindParam(':owner_id', $user_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        header("Location: my_pets.php");
        exit;
    }
}

// Get pet details
$pet_query = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as owner_name, u.email as owner_email, u.phone as owner_phone
             FROM pets p
             JOIN users u ON p.owner_id = u.id
             WHERE p.id = :pet_id";
$pet_stmt = $db->prepare($pet_query);
$pet_stmt->bindParam(':pet_id', $pet_id);
$pet_stmt->execute();

if ($pet_stmt->rowCount() == 0) {
    header("Location: my_pets.php");
    exit;
}

$pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent medical records
$records_query = "SELECT mr.*, CONCAT(u.first_name, ' ', u.last_name) as vet_name,
                 a.reason as appointment_reason
                 FROM medical_records mr
                 LEFT JOIN users u ON mr.created_by = u.id
                 LEFT JOIN appointments a ON mr.appointment_id = a.id
                 WHERE mr.pet_id = :pet_id
                 ORDER BY mr.record_date DESC
                 LIMIT 5";
$records_stmt = $db->prepare($records_query);
$records_stmt->bindParam(':pet_id', $pet_id);
$records_stmt->execute();

// Get upcoming appointments
$appt_query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as vet_name
              FROM appointments a
              JOIN vets v ON a.vet_id = v.id
              JOIN users u ON v.user_id = u.id
              WHERE a.pet_id = :pet_id
              AND a.appointment_date >= CURDATE()
              AND a.status = 'scheduled'
              ORDER BY a.appointment_date, a.appointment_time
              LIMIT 3";
$appt_stmt = $db->prepare($appt_query);
$appt_stmt->bindParam(':pet_id', $pet_id);
$appt_stmt->execute();

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white"><?php echo htmlspecialchars($pet['name']); ?></h1>
            <a href="my_pets.php" class="text-white hover:text-blue-100 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to My Pets
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Pet Info -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gray-50 p-6 flex items-start">
                    <div class="bg-<?php 
                        switch (strtolower($pet['species'])) {
                            case 'dog': echo 'blue'; break;
                            case 'cat': echo 'yellow'; break;
                            case 'bird': echo 'green'; break;
                            case 'rabbit': echo 'purple'; break;
                            default: echo 'gray';
                        }
                    ?>-100 p-4 rounded-full mr-6">
                        <i class="fas <?php 
                            switch (strtolower($pet['species'])) {
                                case 'dog': echo 'fa-dog text-blue-500'; break;
                                case 'cat': echo 'fa-cat text-yellow-500'; break;
                                case 'bird': echo 'fa-dove text-green-500'; break;
                                case 'rabbit': echo 'fa-rabbit text-purple-500'; break;
                                default: echo 'fa-paw text-gray-500';
                            }
                        ?> text-3xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($pet['name']); ?></h2>
                        <p class="text-gray-600">
                            <?php echo htmlspecialchars($pet['species']); ?>
                            <?php if (!empty($pet['breed'])): ?> 
                                - <?php echo htmlspecialchars($pet['breed']); ?>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($pet['microchip_id'])): ?>
                            <p class="mt-2 text-xs text-gray-500">Microchip ID: <?php echo htmlspecialchars($pet['microchip_id']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if ($user_role == 'client'): ?>
                    <div class="ml-auto">
                        <a href="edit_pet.php?id=<?php echo $pet_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Pet Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Gender</p>
                                <p class="font-medium"><?php echo ucfirst($pet['gender']); ?></p>
                            </div>
                            
                            <?php if (!empty($pet['date_of_birth'])): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500">Date of Birth</p>
                                    <p class="font-medium"><?php echo date('F d, Y', strtotime($pet['date_of_birth'])); ?></p>
                                    <p class="text-sm text-gray-500">
                                        (Age: <?php 
                                            $birthDate = new DateTime($pet['date_of_birth']);
                                            $today = new DateTime();
                                            $diff = $today->diff($birthDate);
                                            echo $diff->y . " years, " . $diff->m . " months";
                                        ?>)
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <?php if (!empty($pet['weight'])): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500">Weight</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($pet['weight']); ?> kg</p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user_role != 'client'): ?>
                                <div class="mb-4">
                                    <p class="text-sm text-gray-500">Owner</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($pet['owner_name']); ?></p>
                                    <p class="text-sm"><?php echo htmlspecialchars($pet['owner_email']); ?></p>
                                    <?php if (!empty($pet['owner_phone'])): ?>
                                        <p class="text-sm"><?php echo htmlspecialchars($pet['owner_phone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Medical Records Section -->
                <div class="px-6 pb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Medical History</h3>
                        <a href="medical_records.php?pet_id=<?php echo $pet_id; ?>" class="text-blue-600 hover:text-blue-800 text-sm">View All Records</a>
                    </div>
                    
                    <?php if ($records_stmt->rowCount() > 0): ?>
                        <div class="space-y-4">
                            <?php while ($record = $records_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="border-l-4 border-blue-500 pl-4 py-2">
                                    <div class="flex justify-between">
                                        <div>
                                            <p class="font-medium"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></p>
                                            <?php if (!empty($record['appointment_reason'])): ?>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($record['appointment_reason']); ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($record['diagnosis'])): ?>
                                                <p class="text-sm mt-1"><span class="font-medium">Diagnosis:</span> 
                                                    <?php echo strlen($record['diagnosis']) > 70 ? 
                                                        htmlspecialchars(substr($record['diagnosis'], 0, 70)) . '...' : 
                                                        htmlspecialchars($record['diagnosis']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                            View
                                        </a>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Dr. <?php echo htmlspecialchars($record['vet_name']); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                            <p class="text-gray-500">No medical records available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div>
            <!-- Actions Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Actions</h3>
                <div class="space-y-3">
                    <?php if ($user_role == 'client'): ?>
                        <a href="schedule_appointment.php?pet_id=<?php echo $pet_id; ?>" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-md text-blue-700 transition">
                            <i class="fas fa-calendar-plus mr-3"></i>
                            <span>Schedule Appointment</span>
                        </a>
                        <a href="edit_pet.php?id=<?php echo $pet_id; ?>" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-md text-green-700 transition">
                            <i class="fas fa-edit mr-3"></i>
                            <span>Edit Pet Details</span>
                        </a>
                        <a href="#" onclick="confirmDelete(<?php echo $pet_id; ?>, '<?php echo htmlspecialchars($pet['name']); ?>')" class="flex items-center p-3 bg-red-50 hover:bg-red-100 rounded-md text-red-700 transition">
                            <i class="fas fa-trash-alt mr-3"></i>
                            <span>Delete Pet</span>
                        </a>
                    <?php else: ?>
                        <!-- Actions for vets/admins -->
                        <a href="../appointments.php?pet_id=<?php echo $pet_id; ?>" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-md text-blue-700 transition">
                            <i class="fas fa-calendar mr-3"></i>
                            <span>View Appointments</span>
                        </a>
                        <a href="create_medical_record.php?pet_id=<?php echo $pet_id; ?>" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-md text-green-700 transition">
                            <i class="fas fa-file-medical mr-3"></i>
                            <span>Create Medical Record</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Upcoming Appointments</h3>
                    <a href="my_appointments.php?pet_id=<?php echo $pet_id; ?>" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                </div>
                
                <?php if ($appt_stmt->rowCount() > 0): ?>
                    <div class="space-y-4">
                        <?php while ($appt = $appt_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="border rounded-lg p-3 hover:bg-gray-50">
                                <div class="font-medium">
                                    <?php echo date('l, M d', strtotime($appt['appointment_date'])); ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?> with Dr. <?php echo htmlspecialchars($appt['vet_name']); ?>
                                </div>
                                <div class="mt-2 text-sm">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                        <?php echo htmlspecialchars($appt['reason']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
                        <p class="text-gray-500">No upcoming appointments</p>
                        <?php if ($user_role == 'client'): ?>
                            <a href="schedule_appointment.php?pet_id=<?php echo $pet_id; ?>" class="mt-2 inline-block text-blue-600 hover:text-blue-800">
                                Schedule Now
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Hidden by Default) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Confirm Pet Deletion</h3>
        <p class="mb-6">Are you sure you want to delete <span id="petName" class="font-semibold"></span>? This action cannot be undone and will delete all associated records.</p>
        <div class="flex justify-end">
            <button onclick="closeDeleteModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                Cancel
            </button>
            <form action="delete_pet.php" method="post" class="inline">
                <input type="hidden" name="pet_id" id="delete_pet_id">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Delete Pet
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Delete confirmation functions
    function confirmDelete(petId, petName) {
        document.getElementById('petName').textContent = petName;
        document.getElementById('delete_pet_id').value = petId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // Close modal if clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }
</script>

<?php include_once 'includes/footer.php'; ?>
