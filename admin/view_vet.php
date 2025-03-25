<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if vet ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: vets.php");
    exit;
}

$vet_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get vet information
$vet_query = "SELECT v.*, u.first_name, u.last_name, u.email, u.phone, u.username, u.created_at 
              FROM vets v 
              JOIN users u ON v.user_id = u.id 
              WHERE v.id = :vet_id";
$vet_stmt = $db->prepare($vet_query);
$vet_stmt->bindParam(':vet_id', $vet_id);
$vet_stmt->execute();

if ($vet_stmt->rowCount() == 0) {
    header("Location: vets.php");
    exit;
}

$vet = $vet_stmt->fetch(PDO::FETCH_ASSOC);

// Get appointment statistics
$stats_query = "SELECT 
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN a.appointment_date >= CURDATE() THEN 1 END) as upcoming_appointments,
                COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments
                FROM appointments a
                JOIN vets v ON a.vet_id = v.id
                WHERE v.id = :vet_id";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bindParam(':vet_id', $vet_id);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get recent appointments
$recent_appts_query = "SELECT a.*, p.name as pet_name, 
                      CONCAT(o.first_name, ' ', o.last_name) as owner_name
                      FROM appointments a
                      JOIN pets p ON a.pet_id = p.id
                      JOIN users o ON p.owner_id = o.id
                      JOIN vets v ON a.vet_id = v.id
                      WHERE v.id = :vet_id
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC
                      LIMIT 5";
$recent_appts_stmt = $db->prepare($recent_appts_query);
$recent_appts_stmt->bindParam(':vet_id', $vet_id);
$recent_appts_stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Dr. <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?></h1>
                <p class="text-white text-opacity-90 mt-2">Veterinarian Details</p>
            </div>
            <div>
                <a href="edit_vet.php?id=<?php echo $vet_id; ?>" class="bg-white text-purple-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition mr-2">
                    <i class="fas fa-edit mr-1"></i> Edit Profile
                </a>
                <a href="vets.php" class="bg-transparent text-white border border-white px-4 py-2 rounded-lg font-medium hover:bg-white hover:bg-opacity-10 transition">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Vet Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-center mb-6">
                    <div class="h-32 w-32 bg-gradient-to-r from-purple-400 to-indigo-500 rounded-full mx-auto flex items-center justify-center text-white text-3xl font-bold">
                        <?php echo strtoupper(substr($vet['first_name'], 0, 1) . substr($vet['last_name'], 0, 1)); ?>
                    </div>
                    <h2 class="text-xl font-semibold mt-4">Dr. <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?></h2>
                    <p class="text-gray-600">
                        <?php echo !empty($vet['specialization']) ? htmlspecialchars($vet['specialization']) : 'General Veterinarian'; ?>
                    </p>
                </div>
                
                <div class="border-t border-gray-200 pt-4">
                    <div class="space-y-3">
                        <div class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span><?php echo htmlspecialchars($vet['email']); ?></span>
                        </div>
                        
                        <?php if (!empty($vet['phone'])): ?>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <span><?php echo htmlspecialchars($vet['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($vet['license_number'])): ?>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                            </svg>
                            <span>License: <?php echo htmlspecialchars($vet['license_number']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($vet['years_of_experience'])): ?>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><?php echo htmlspecialchars($vet['years_of_experience']); ?> years of experience</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Username: <?php echo htmlspecialchars($vet['username']); ?></span>
                        </div>
                        
                        <div class="flex items-center text-gray-700">
                            <svg class="w-5 h-5 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Joined: <?php echo date('M d, Y', strtotime($vet['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Appointment Statistics</h3>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="text-2xl font-bold text-blue-600"><?php echo $stats['total_appointments']; ?></p>
                        <p class="text-sm text-gray-600">Total</p>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <p class="text-2xl font-bold text-green-600"><?php echo $stats['completed_appointments']; ?></p>
                        <p class="text-sm text-gray-600">Completed</p>
                    </div>
                    <div class="bg-purple-50 p-3 rounded-lg">
                        <p class="text-2xl font-bold text-purple-600"><?php echo $stats['upcoming_appointments']; ?></p>
                        <p class="text-sm text-gray-600">Upcoming</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bio and Recent Appointments -->
        <div class="lg:col-span-2">
            <!-- Bio Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Professional Bio</h3>
                <?php if (!empty($vet['bio'])): ?>
                    <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($vet['bio']); ?></p>
                <?php else: ?>
                    <p class="text-gray-500 italic">No bio information available.</p>
                <?php endif; ?>
            </div>
            
            <!-- Recent Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Recent Appointments</h3>
                    <a href="../appointments.php?vet_id=<?php echo $vet_id; ?>" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
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
                                        <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($appt['reason']); ?></p>
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
                    <p class="text-gray-500 text-center py-4">No appointments found for this veterinarian.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
