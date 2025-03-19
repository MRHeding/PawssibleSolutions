<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if client ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$client_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get client information
$client_query = "SELECT * FROM users WHERE id = :client_id AND role = 'client'";
$client_stmt = $db->prepare($client_query);
$client_stmt->bindParam(':client_id', $client_id);
$client_stmt->execute();

if ($client_stmt->rowCount() == 0) {
    header("Location: clients.php");
    exit;
}

$client = $client_stmt->fetch(PDO::FETCH_ASSOC);

// Get client's pets
$pets_query = "SELECT * FROM pets WHERE owner_id = :owner_id ORDER BY name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':owner_id', $client_id);
$pets_stmt->execute();

// Get client's upcoming appointments
$appointments_query = "SELECT a.*, p.name as pet_name, CONCAT(u.first_name, ' ', u.last_name) as vet_name
                     FROM appointments a
                     JOIN pets p ON a.pet_id = p.id
                     JOIN vets v ON a.vet_id = v.id
                     JOIN users u ON v.user_id = u.id
                     WHERE p.owner_id = :owner_id
                     AND a.appointment_date >= CURDATE()
                     AND a.status = 'scheduled'
                     ORDER BY a.appointment_date, a.appointment_time
                     LIMIT 5";
$appointments_stmt = $db->prepare($appointments_query);
$appointments_stmt->bindParam(':owner_id', $client_id);
$appointments_stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Client Details</h1>
            <a href="clients.php" class="text-white hover:text-indigo-100">
                <i class="fas fa-arrow-left mr-2"></i> Back to Clients
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Client Information -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 p-6 flex items-center">
                    <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-2xl font-bold">
                        <?php echo strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)); ?>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h2>
                        <p class="text-gray-600">Member since <?php echo date('F Y', strtotime($client['created_at'])); ?></p>
                    </div>
                    <div class="ml-auto">
                        <a href="edit_client.php?id=<?php echo $client_id; ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                    </div>
                </div>
                
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 pb-2 border-b">Personal Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Username</p>
                                <p class="font-medium"><?php echo htmlspecialchars($client['username']); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Email Address</p>
                                <p class="font-medium"><?php echo htmlspecialchars($client['email']); ?></p>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Phone Number</p>
                                <p class="font-medium"><?php echo !empty($client['phone']) ? htmlspecialchars($client['phone']) : 'Not provided'; ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-sm text-gray-500">Last Login</p>
                                <p class="font-medium">
                                    <?php 
                                    if (!empty($client['last_login'])) {
                                        echo date('F d, Y - h:i A', strtotime($client['last_login']));
                                    } else {
                                        echo 'Never logged in';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Client's Pets Section -->
                <div class="px-6 pb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Pets</h3>
                        <a href="add_pet.php?owner_id=<?php echo $client_id; ?>" class="text-indigo-600 hover:text-indigo-800">
                            <i class="fas fa-plus mr-1"></i> Add Pet
                        </a>
                    </div>
                    
                    <?php if ($pets_stmt->rowCount() > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php while ($pet = $pets_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="border rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center">
                                            <div class="bg-<?php 
                                                switch (strtolower($pet['species'])) {
                                                    case 'dog': echo 'blue'; break;
                                                    case 'cat': echo 'yellow'; break;
                                                    case 'bird': echo 'green'; break;
                                                    case 'rabbit': echo 'purple'; break;
                                                    default: echo 'gray';
                                                }
                                            ?>-100 p-2 rounded-full mr-3">
                                                <i class="fas <?php 
                                                    switch (strtolower($pet['species'])) {
                                                        case 'dog': echo 'fa-dog text-blue-500'; break;
                                                        case 'cat': echo 'fa-cat text-yellow-500'; break;
                                                        case 'bird': echo 'fa-dove text-green-500'; break;
                                                        case 'rabbit': echo 'fa-rabbit text-purple-500'; break;
                                                        default: echo 'fa-paw text-gray-500';
                                                    }
                                                ?>"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-medium"><?php echo htmlspecialchars($pet['name']); ?></h4>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($pet['species']); ?>
                                                    <?php if (!empty($pet['breed'])): ?> 
                                                        - <?php echo htmlspecialchars($pet['breed']); ?>
                                                    <?php endif; ?>
                                                </p>
                                            </div>
                                        </div>
                                        <a href="../pet_details.php?id=<?php echo $pet['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                            View Details
                                        </a>
                                    </div>
                                    
                                    <div class="mt-3 text-xs text-gray-500 flex justify-between items-center">
                                        <?php if (!empty($pet['date_of_birth'])): ?>
                                            <span>Born: <?php echo date('M d, Y', strtotime($pet['date_of_birth'])); ?></span>
                                        <?php else: ?>
                                            <span>No birth date recorded</span>
                                        <?php endif; ?>
                                        <span><?php echo ucfirst($pet['gender']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
                            <p class="text-gray-500">No pets registered for this client</p>
                            <a href="add_pet.php?owner_id=<?php echo $client_id; ?>" class="mt-2 inline-block text-indigo-600 hover:text-indigo-800">
                                Add a Pet
                            </a>
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
                    <a href="schedule_appointment.php?client_id=<?php echo $client_id; ?>" class="flex items-center p-3 bg-blue-50 hover:bg-blue-100 rounded-md text-blue-700 transition">
                        <i class="fas fa-calendar-plus mr-3"></i>
                        <span>Schedule Appointment</span>
                    </a>
                    <a href="client_medical_records.php?client_id=<?php echo $client_id; ?>" class="flex items-center p-3 bg-green-50 hover:bg-green-100 rounded-md text-green-700 transition">
                        <i class="fas fa-file-medical mr-3"></i>
                        <span>View Medical Records</span>
                    </a>
                    <a href="client_invoices.php?client_id=<?php echo $client_id; ?>" class="flex items-center p-3 bg-yellow-50 hover:bg-yellow-100 rounded-md text-yellow-700 transition">
                        <i class="fas fa-file-invoice-dollar mr-3"></i>
                        <span>View Invoices</span>
                    </a>
                    <a href="#" onclick="confirmDelete(<?php echo $client_id; ?>, '<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>')" class="flex items-center p-3 bg-red-50 hover:bg-red-100 rounded-md text-red-700 transition">
                        <i class="fas fa-user-minus mr-3"></i>
                        <span>Delete Client</span>
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Upcoming Appointments</h3>
                    <a href="client_appointments.php?client_id=<?php echo $client_id; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm">View All</a>
                </div>
                
                <?php if ($appointments_stmt->rowCount() > 0): ?>
                    <div class="space-y-4">
                        <?php while ($appt = $appointments_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="border rounded-lg p-3 hover:bg-gray-50">
                                <div class="font-medium">
                                    <?php echo date('l, M d', strtotime($appt['appointment_date'])); ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?> with Dr. <?php echo htmlspecialchars($appt['vet_name']); ?>
                                </div>
                                <div class="mt-2 flex justify-between items-center">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                        <?php echo htmlspecialchars($appt['reason']); ?>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        Pet: <?php echo htmlspecialchars($appt['pet_name']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 border-2 border-dashed border-gray-300 rounded-lg">
                        <p class="text-gray-500">No upcoming appointments</p>
                        <a href="schedule_appointment.php?client_id=<?php echo $client_id; ?>" class="mt-2 inline-block text-indigo-600 hover:text-indigo-800">
                            Schedule Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Client Statistics -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Client Statistics</h3>
                
                <?php
                // Get statistics
                $stats_query = "SELECT 
                                (SELECT COUNT(*) FROM pets WHERE owner_id = :client_id) as pet_count,
                                (SELECT COUNT(*) FROM appointments a JOIN pets p ON a.pet_id = p.id WHERE p.owner_id = :client_id) as total_appts,
                                (SELECT COUNT(*) FROM appointments a JOIN pets p ON a.pet_id = p.id WHERE p.owner_id = :client_id AND a.status = 'completed') as completed_appts,
                                (SELECT COUNT(*) FROM appointments a JOIN pets p ON a.pet_id = p.id WHERE p.owner_id = :client_id AND a.status = 'cancelled') as cancelled_appts,
                                (SELECT COUNT(*) FROM medical_records mr JOIN pets p ON mr.pet_id = p.id WHERE p.owner_id = :client_id) as medical_records";
                $stats_stmt = $db->prepare($stats_query);
                $stats_stmt->bindParam(':client_id', $client_id);
                $stats_stmt->execute();
                $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-indigo-50 rounded-lg">
                        <p class="text-3xl font-bold text-indigo-600"><?php echo $stats['pet_count']; ?></p>
                        <p class="text-sm text-gray-600">Pets</p>
                    </div>
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_appts']; ?></p>
                        <p class="text-sm text-gray-600">Total Appointments</p>
                    </div>
                    <div class="text-center p-3 bg-green-50 rounded-lg">
                        <p class="text-3xl font-bold text-green-600"><?php echo $stats['completed_appts']; ?></p>
                        <p class="text-sm text-gray-600">Completed Visits</p>
                    </div>
                    <div class="text-center p-3 bg-yellow-50 rounded-lg">
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['medical_records']; ?></p>
                        <p class="text-sm text-gray-600">Medical Records</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Hidden by Default) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Confirm Client Deletion</h3>
        <p class="mb-6">Are you sure you want to delete <span id="clientName" class="font-semibold"></span>? This action cannot be undone and will delete all associated pets, appointments, and medical records.</p>
        <div class="flex justify-end">
            <button onclick="closeDeleteModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                Cancel
            </button>
            <form action="delete_client.php" method="post" class="inline">
                <input type="hidden" name="client_id" id="delete_client_id">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Delete confirmation functions
    function confirmDelete(clientId, clientName) {
        document.getElementById('clientName').textContent = clientName;
        document.getElementById('delete_client_id').value = clientId;
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

<?php include_once '../includes/admin_footer.php'; ?>
