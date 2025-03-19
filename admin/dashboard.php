<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get today's appointments count
$today = date('Y-m-d');
$today_query = "SELECT COUNT(*) FROM appointments WHERE appointment_date = :today";
$today_stmt = $db->prepare($today_query);
$today_stmt->bindParam(':today', $today);
$today_stmt->execute();
$today_count = $today_stmt->fetchColumn();

// Get total client count
$clients_query = "SELECT COUNT(*) FROM users WHERE role = 'client'";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients_count = $clients_stmt->fetchColumn();

// Get total pet count
$pets_query = "SELECT COUNT(*) FROM pets";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->execute();
$pets_count = $pets_stmt->fetchColumn();

// Get recent appointments
$recent_appts_query = "SELECT a.*, p.name as pet_name, 
                      CONCAT(o.first_name, ' ', o.last_name) as owner_name,
                      CONCAT(v.first_name, ' ', v.last_name) as vet_name
                      FROM appointments a
                      JOIN pets p ON a.pet_id = p.id
                      JOIN users o ON p.owner_id = o.id
                      JOIN vets vt ON a.vet_id = vt.id
                      JOIN users v ON vt.user_id = v.id
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC
                      LIMIT 5";
$recent_appts_stmt = $db->prepare($recent_appts_query);
$recent_appts_stmt->execute();

// Get recent registrations
$recent_users_query = "SELECT * FROM users 
                      WHERE role = 'client' 
                      ORDER BY created_at DESC 
                      LIMIT 5";
$recent_users_stmt = $db->prepare($recent_users_query);
$recent_users_stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
        <p class="text-white text-opacity-90 mt-2">Manage your veterinary practice</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
            <div class="bg-blue-100 p-3 rounded-full mr-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Today's Appointments</p>
                <h3 class="text-2xl font-bold"><?php echo $today_count; ?></h3>
                <a href="../appointments.php?date=<?php echo $today; ?>" class="text-blue-600 hover:text-blue-800 text-xs">View Details</a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
            <div class="bg-green-100 p-3 rounded-full mr-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Clients</p>
                <h3 class="text-2xl font-bold"><?php echo $clients_count; ?></h3>
                <a href="clients.php" class="text-green-600 hover:text-green-800 text-xs">View Clients</a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
            <div class="bg-purple-100 p-3 rounded-full mr-4">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Total Pets</p>
                <h3 class="text-2xl font-bold"><?php echo $pets_count; ?></h3>
                <a href="pets.php" class="text-purple-600 hover:text-purple-800 text-xs">View Registry</a>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="../appointments.php" class="bg-blue-50 hover:bg-blue-100 p-4 rounded-lg text-center transition">
                <svg class="w-8 h-8 text-blue-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="block text-gray-800 font-medium">Appointments</span>
            </a>
            
            <a href="clients.php" class="bg-green-50 hover:bg-green-100 p-4 rounded-lg text-center transition">
                <svg class="w-8 h-8 text-green-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <span class="block text-gray-800 font-medium">Manage Clients</span>
            </a>
            
            <a href="vets.php" class="bg-purple-50 hover:bg-purple-100 p-4 rounded-lg text-center transition">
                <svg class="w-8 h-8 text-purple-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="block text-gray-800 font-medium">Manage Vets</span>
            </a>
            
            <!-- New Inventory Management Block -->
            <a href="inventory.php" class="bg-amber-50 hover:bg-amber-100 p-4 rounded-lg text-center transition">
                <svg class="w-8 h-8 text-amber-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span class="block text-gray-800 font-medium">Inventory</span>
            </a>
            
            <a href="register_admin.php" class="bg-red-50 hover:bg-red-100 p-4 rounded-lg text-center transition">
                <svg class="w-8 h-8 text-red-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                <span class="block text-gray-800 font-medium">Add Administrator</span>
            </a>
            
            <a href="settings.php" class="bg-gray-50 hover:bg-gray-100 p-4 rounded-lg text-center transition">
                <svg class="w-8 h-8 text-gray-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="block text-gray-800 font-medium">Settings</span>
            </a>
        </div>
    </div>
    
    <!-- Two Columns Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Appointments -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Recent Appointments</h2>
                <a href="../appointments.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
            </div>
            
            <?php if ($recent_appts_stmt->rowCount() > 0): ?>
                <div class="space-y-4">
                    <?php while ($appt = $recent_appts_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="border-l-4 
                            <?php
                            switch($appt['status']) {
                                case 'scheduled':
                                    echo 'border-blue-500 bg-blue-50';
                                    break;
                                case 'completed':
                                    echo 'border-green-500 bg-green-50';
                                    break;
                                case 'cancelled':
                                    echo 'border-red-500 bg-red-50';
                                    break;
                                case 'no-show':
                                    echo 'border-yellow-500 bg-yellow-50';
                                    break;
                                default:
                                    echo 'border-gray-500 bg-gray-50';
                            }
                            ?> pl-4 py-3 rounded-r-lg"
                        >
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?> - <?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></p>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($appt['pet_name']); ?> (Owner: <?php echo htmlspecialchars($appt['owner_name']); ?>)</p>
                                    <p class="text-xs text-gray-500 mt-1">Dr. <?php echo htmlspecialchars($appt['vet_name']); ?> - <?php echo htmlspecialchars($appt['reason']); ?></p>
                                </div>
                                <span class="px-2 h-min py-1 text-xs font-semibold rounded-full 
                                    <?php
                                    switch($appt['status']) {
                                        case 'scheduled':
                                            echo 'bg-blue-100 text-blue-800';
                                            break;
                                        case 'completed':
                                            echo 'bg-green-100 text-green-800';
                                            break;
                                        case 'cancelled':
                                            echo 'bg-red-100 text-red-800';
                                            break;
                                        case 'no-show':
                                            echo 'bg-yellow-100 text-yellow-800';
                                            break;
                                        default:
                                            echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No recent appointments found</p>
            <?php endif; ?>
        </div>
        
        <!-- Recent Client Registrations -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Recent Client Registrations</h2>
                <a href="clients.php" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
            </div>
            
            <?php if ($recent_users_stmt->rowCount() > 0): ?>
                <div class="space-y-4">
                    <?php while ($user = $recent_users_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="flex items-center border-b border-gray-200 pb-4">
                            <div class="bg-blue-100 h-10 w-10 rounded-full flex items-center justify-center mr-3 text-blue-600 font-semibold">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <div class="flex-grow">
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">No recent registrations found</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
