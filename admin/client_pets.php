<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if owner_id is provided
if (!isset($_GET['owner_id']) || empty($_GET['owner_id'])) {
    header("Location: clients.php");
    exit;
}

$owner_id = $_GET['owner_id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get client information
$client_query = "SELECT * FROM users WHERE id = :owner_id AND role = 'client'";
$client_stmt = $db->prepare($client_query);
$client_stmt->bindParam(':owner_id', $owner_id);
$client_stmt->execute();

if ($client_stmt->rowCount() == 0) {
    header("Location: clients.php");
    exit;
}

$client = $client_stmt->fetch(PDO::FETCH_ASSOC);

// Get client's pets with additional information
$pets_query = "SELECT p.*, 
               COUNT(DISTINCT a.id) as total_appointments,
               COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_appointments,
               COUNT(DISTINCT mr.id) as medical_records_count,
               MAX(a.appointment_date) as last_appointment_date
               FROM pets p
               LEFT JOIN appointments a ON p.id = a.pet_id
               LEFT JOIN medical_records mr ON p.id = mr.pet_id
               WHERE p.owner_id = :owner_id
               GROUP BY p.id
               ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':owner_id', $owner_id);
$pets_stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">
                    <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>'s Pets
                </h1>
                <p class="text-white text-opacity-90 mt-2">Manage pets for this client</p>
            </div>
            <div class="flex space-x-3">
                <a href="add_pet.php?owner_id=<?php echo $owner_id; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-plus mr-2"></i> Add New Pet
                </a>
                <a href="view_client.php?id=<?php echo $owner_id; ?>" class="bg-white text-purple-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                    <i class="fas fa-user mr-1"></i> View Client Profile
                </a>
                <a href="clients.php" class="bg-transparent text-white border border-white px-4 py-2 rounded-lg font-medium hover:bg-white hover:bg-opacity-10 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Clients
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Client Information Card -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="h-16 w-16 bg-gradient-to-r from-purple-400 to-indigo-500 rounded-full flex items-center justify-center text-white text-xl font-bold">
                    <?php echo strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)); ?>
                </div>
                <div class="ml-4">
                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($client['email']); ?></p>
                    <?php if (!empty($client['phone'])): ?>
                        <p class="text-gray-600"><?php echo htmlspecialchars($client['phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-600">Total Pets: <span class="font-semibold"><?php echo $pets_stmt->rowCount(); ?></span></p>
                <p class="text-sm text-gray-600">Client since: <span class="font-semibold"><?php echo date('M d, Y', strtotime($client['created_at'])); ?></span></p>
            </div>
        </div>
    </div>

    <!-- Pets List -->
    <?php if ($pets_stmt->rowCount() > 0): ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($pet = $pets_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="bg-<?php 
                        // Change color based on species
                        switch (strtolower($pet['species'])) {
                            case 'dog': echo 'blue'; break;
                            case 'cat': echo 'yellow'; break;
                            case 'bird': echo 'green'; break;
                            case 'rabbit': echo 'purple'; break;
                            default: echo 'gray';
                        }
                    ?>-100 p-4 flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="bg-white rounded-full p-2 mr-3">
                                <i class="fas <?php 
                                    // Change icon based on species
                                    switch (strtolower($pet['species'])) {
                                        case 'dog': echo 'fa-dog text-blue-500'; break;
                                        case 'cat': echo 'fa-cat text-yellow-500'; break;
                                        case 'bird': echo 'fa-dove text-green-500'; break;
                                        case 'rabbit': echo 'fa-rabbit text-purple-500'; break;
                                        default: echo 'fa-paw text-gray-500';
                                    }
                                ?> text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($pet['name']); ?></h3>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($pet['species']); ?></span>
                    </div>
                    
                    <div class="p-4">
                        <div class="mb-3">
                            <?php if (!empty($pet['breed'])): ?>
                                <p><span class="font-medium">Breed:</span> <?php echo htmlspecialchars($pet['breed']); ?></p>
                            <?php endif; ?>
                            <p><span class="font-medium">Gender:</span> <?php echo ucfirst($pet['gender']); ?></p>
                            <?php if (!empty($pet['date_of_birth'])): ?>
                                <p><span class="font-medium">Age:</span> 
                                    <?php 
                                        $birthDate = new DateTime($pet['date_of_birth']);
                                        $today = new DateTime();
                                        $diff = $today->diff($birthDate);
                                        echo $diff->y . " years, " . $diff->m . " months";
                                    ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($pet['weight'])): ?>
                                <p><span class="font-medium">Weight:</span> <?php echo htmlspecialchars($pet['weight']); ?> kg</p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Pet Statistics -->
                        <div class="grid grid-cols-3 gap-2 mb-3 text-center text-xs">
                            <div class="bg-blue-50 p-2 rounded">
                                <div class="font-bold text-blue-600"><?php echo $pet['total_appointments']; ?></div>
                                <div class="text-gray-600">Appointments</div>
                            </div>
                            <div class="bg-green-50 p-2 rounded">
                                <div class="font-bold text-green-600"><?php echo $pet['completed_appointments']; ?></div>
                                <div class="text-gray-600">Completed</div>
                            </div>
                            <div class="bg-purple-50 p-2 rounded">
                                <div class="font-bold text-purple-600"><?php echo $pet['medical_records_count']; ?></div>
                                <div class="text-gray-600">Records</div>
                            </div>
                        </div>
                        
                        <?php if ($pet['last_appointment_date']): ?>
                            <p class="text-xs text-gray-500 mb-3">
                                Last visit: <?php echo date('M d, Y', strtotime($pet['last_appointment_date'])); ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center">
                            <a href="../pet_details.php?id=<?php echo $pet['id']; ?>" class="text-purple-600 hover:text-purple-800 font-medium text-sm">
                                View Details
                            </a>
                            <div class="flex space-x-2">
                                <a href="schedule_appointment.php?pet_id=<?php echo $pet['id']; ?>" class="text-blue-600 hover:text-blue-800" title="Schedule Appointment">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                                <a href="add_medical_record.php?pet_id=<?php echo $pet['id']; ?>" class="text-green-600 hover:text-green-800" title="Add Medical Record">
                                    <i class="fas fa-notes-medical"></i>
                                </a>
                                <a href="edit_pet.php?id=<?php echo $pet['id']; ?>" class="text-yellow-600 hover:text-yellow-800" title="Edit Pet">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <!-- No pets found -->
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-paw text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Pets Found</h3>
            <p class="text-gray-600 mb-6">This client hasn't registered any pets yet.</p>
            <a href="add_pet.php?owner_id=<?php echo $owner_id; ?>" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded transition">
                Add First Pet
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>