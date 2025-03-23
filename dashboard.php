<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
$user_role = $_SESSION['user_role'];

// If admin or vet, redirect to appropriate dashboard
if ($user_role === 'admin') {
    header("Location: admin/dashboard.php");
    exit;
} elseif ($user_role === 'vet') {
    header("Location: vet/dashboard.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get user's pets count
$pets_query = "SELECT COUNT(*) FROM pets WHERE owner_id = :user_id";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':user_id', $user_id);
$pets_stmt->execute();
$pet_count = $pets_stmt->fetchColumn();

// Get upcoming appointments
$upcoming_query = "SELECT a.*, p.name as pet_name, p.species, 
                 CONCAT(u.first_name, ' ', u.last_name) as vet_name 
                 FROM appointments a 
                 JOIN pets p ON a.pet_id = p.id 
                 JOIN vets v ON a.vet_id = v.id 
                 JOIN users u ON v.user_id = u.id
                 WHERE p.owner_id = :user_id 
                 AND a.appointment_date >= CURDATE()
                 AND a.status = 'scheduled'
                 ORDER BY a.appointment_date, a.appointment_time
                 LIMIT 5";
$upcoming_stmt = $db->prepare($upcoming_query);
$upcoming_stmt->bindParam(':user_id', $user_id);
$upcoming_stmt->execute();

// Get recent medical records
$records_query = "SELECT mr.*, p.name as pet_name, p.species,
                 a.reason as appointment_reason
                 FROM medical_records mr
                 JOIN pets p ON mr.pet_id = p.id
                 LEFT JOIN appointments a ON mr.appointment_id = a.id
                 WHERE p.owner_id = :user_id
                 ORDER BY mr.record_date DESC
                 LIMIT 5";
$records_stmt = $db->prepare($records_query);
$records_stmt->bindParam(':user_id', $user_id);
$records_stmt->execute();

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Welcome, <?php echo $first_name . ' ' . $last_name; ?></h1>
        <p class="text-white text-opacity-90 mt-2">Manage your pets' health and appointments</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-paw text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">My Pets</p>
                    <h3 class="text-2xl font-bold"><?php echo $pet_count; ?></h3>
                </div>
            </div>
            <div class="mt-4">
                <a href="my_pets.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium inline-flex items-center">
                    View All Pets
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-calendar-alt text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Upcoming Appointments</p>
                    <h3 class="text-2xl font-bold"><?php echo $upcoming_stmt->rowCount(); ?></h3>
                </div>
            </div>
            <div class="mt-4">
                <a href="my_appointments.php" class="text-green-600 hover:text-green-800 text-sm font-medium inline-flex items-center">
                    View All Appointments
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-purple-100 p-3 rounded-full mr-4">
                    <i class="fas fa-file-medical text-purple-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Medical Records</p>
                    <h3 class="text-2xl font-bold"><?php echo $records_stmt->rowCount(); ?></h3>
                </div>
            </div>
            <div class="mt-4">
                <a href="medical_records.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium inline-flex items-center">
                    View All Records
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="schedule_appointment.php" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <div class="bg-blue-100 p-3 rounded-full mb-2">
                    <i class="fas fa-calendar-plus text-blue-600"></i>
                </div>
                <span class="text-sm font-medium text-gray-800">Schedule Appointment</span>
            </a>
            
            <a href="add_pet.php" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <div class="bg-green-100 p-3 rounded-full mb-2">
                    <i class="fas fa-plus text-green-600"></i>
                </div>
                <span class="text-sm font-medium text-gray-800">Add New Pet</span>
            </a>
            
            <a href="medical_records.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                <div class="bg-purple-100 p-3 rounded-full mb-2">
                    <i class="fas fa-file-medical text-purple-600"></i>
                </div>
                <span class="text-sm font-medium text-gray-800">View Records</span>
            </a>
            
            <a href="profile.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="bg-gray-200 p-3 rounded-full mb-2">
                    <i class="fas fa-user-edit text-gray-600"></i>
                </div>
                <span class="text-sm font-medium text-gray-800">Edit Profile</span>
            </a>
        </div>
    </div>
    
    <!-- Two Columns Layout for Upcoming Appointments and Recent Records -->
    <div class="grid md:grid-cols-2 gap-8">
        <!-- Upcoming Appointments -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Upcoming Appointments</h2>
                <a href="my_appointments.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
            </div>
            
            <?php if ($upcoming_stmt->rowCount() > 0): ?>
                <div class="space-y-4">
                    <?php while ($appointment = $upcoming_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="border rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">
                                        <?php echo date('l, M d', strtotime($appointment['appointment_date'])); ?> at
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($appointment['pet_name']); ?> with Dr. <?php echo htmlspecialchars($appointment['vet_name']); ?>
                                    </p>
                                    <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">
                                        <?php echo htmlspecialchars($appointment['reason']); ?>
                                    </span>
                                </div>
                                <?php if (strtotime($appointment['appointment_date']) > strtotime('today')): ?>
                                <div>
                                    <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 border rounded-lg border-dashed">
                    <div class="text-gray-400 mb-2">
                        <i class="fas fa-calendar text-4xl"></i>
                    </div>
                    <p class="text-gray-600 mb-4">No upcoming appointments</p>
                    <a href="schedule_appointment.php" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded text-sm">
                        Schedule Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Medical Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Recent Medical Records</h2>
                <a href="medical_records.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
            </div>
            
            <?php if ($records_stmt->rowCount() > 0): ?>
                <div class="space-y-4">
                    <?php while ($record = $records_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="border rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">
                                        <?php echo htmlspecialchars($record['pet_name']); ?> - 
                                        <?php echo date('M d, Y', strtotime($record['record_date'])); ?>
                                    </p>
                                    <?php if (!empty($record['appointment_reason'])): ?>
                                        <p class="text-sm text-gray-600">
                                            <?php echo htmlspecialchars($record['appointment_reason']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($record['diagnosis'])): ?>
                                        <p class="text-sm text-gray-600 mt-1">
                                            <span class="font-medium">Diagnosis:</span> 
                                            <?php echo strlen($record['diagnosis']) > 50 ? htmlspecialchars(substr($record['diagnosis'], 0, 50)) . '...' : htmlspecialchars($record['diagnosis']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                    View
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8 border rounded-lg border-dashed">
                    <div class="text-gray-400 mb-2">
                        <i class="fas fa-file-medical text-4xl"></i>
                    </div>
                    <p class="text-gray-600">No medical records found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pet Care Tips Section -->
    <div class="mt-8 bg-blue-50 rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Pet Care Tips</h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-bold text-lg mb-2">Regular Checkups</h3>
                <p class="text-gray-600">Regular veterinary checkups are essential for your pet's health, even when they appear healthy.</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-bold text-lg mb-2">Dental Health</h3>
                <p class="text-gray-600">Brushing your pet's teeth regularly helps prevent dental disease and other health issues.</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow">
                <h3 class="font-bold text-lg mb-2">Exercise</h3>
                <p class="text-gray-600">Regular exercise is important for your pet's physical and mental well-being.</p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
