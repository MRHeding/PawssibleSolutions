<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

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

// Get today's appointments
$today = date('Y-m-d');
$today_appointments_query = "SELECT a.*, p.name as pet_name, p.species, p.breed,
                          CONCAT(u.first_name, ' ', u.last_name) as owner_name
                          FROM appointments a 
                          JOIN pets p ON a.pet_id = p.id
                          JOIN users u ON p.owner_id = u.id
                          WHERE a.vet_id = :vet_id 
                          AND a.appointment_date = :today
                          AND a.status = 'scheduled'
                          ORDER BY a.appointment_time";
$today_stmt = $db->prepare($today_appointments_query);
$today_stmt->bindParam(':vet_id', $vet_id);
$today_stmt->bindParam(':today', $today);
$today_stmt->execute();

// Get upcoming appointments (future dates)
$upcoming_appointments_query = "SELECT a.*, p.name as pet_name, p.species, p.breed,
                             CONCAT(u.first_name, ' ', u.last_name) as owner_name
                             FROM appointments a 
                             JOIN pets p ON a.pet_id = p.id
                             JOIN users u ON p.owner_id = u.id
                             WHERE a.vet_id = :vet_id 
                             AND a.appointment_date > :today
                             AND a.status = 'scheduled'
                             ORDER BY a.appointment_date, a.appointment_time
                             LIMIT 10";
$upcoming_stmt = $db->prepare($upcoming_appointments_query);
$upcoming_stmt->bindParam(':vet_id', $vet_id);
$upcoming_stmt->bindParam(':today', $today);
$upcoming_stmt->execute();

// Count total appointments for today
$count_today = $today_stmt->rowCount();

// Count total upcoming appointments
$count_upcoming_query = "SELECT COUNT(*) FROM appointments 
                      WHERE vet_id = :vet_id 
                      AND appointment_date > :today
                      AND status = 'scheduled'";
$count_upcoming_stmt = $db->prepare($count_upcoming_query);
$count_upcoming_stmt->bindParam(':vet_id', $vet_id);
$count_upcoming_stmt->bindParam(':today', $today);
$count_upcoming_stmt->execute();
$count_upcoming = $count_upcoming_stmt->fetchColumn();

// Count completed appointments this week
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));
$count_completed_query = "SELECT COUNT(*) FROM appointments 
                       WHERE vet_id = :vet_id 
                       AND appointment_date BETWEEN :week_start AND :week_end
                       AND status = 'completed'";
$count_completed_stmt = $db->prepare($count_completed_query);
$count_completed_stmt->bindParam(':vet_id', $vet_id);
$count_completed_stmt->bindParam(':week_start', $week_start);
$count_completed_stmt->bindParam(':week_end', $week_end);
$count_completed_stmt->execute();
$count_completed = $count_completed_stmt->fetchColumn();

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-indigo-500 to-blue-500 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Welcome, Dr. <?php echo $first_name . ' ' . $last_name; ?></h1>
        <?php if (!empty($vet['specialization'])): ?>
            <p class="text-white text-opacity-90 mt-2"><?php echo htmlspecialchars($vet['specialization']); ?></p>
        <?php endif; ?>
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
                <h3 class="text-2xl font-bold"><?php echo $count_today; ?></h3>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
            <div class="bg-green-100 p-3 rounded-full mr-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0h10a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Upcoming Appointments</p>
                <h3 class="text-2xl font-bold"><?php echo $count_upcoming; ?></h3>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
            <div class="bg-indigo-100 p-3 rounded-full mr-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <p class="text-gray-500 text-sm">Completed This Week</p>
                <h3 class="text-2xl font-bold"><?php echo $count_completed; ?></h3>
            </div>
        </div>
    </div>
    
    <!-- Calendar and Today's Appointments in 2 columns on larger screens -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Today's Appointments -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold">Today's Schedule</h3>
                    <span class="text-sm text-gray-500"><?php echo date('l, F d, Y'); ?></span>
                </div>
                
                <?php if ($count_today > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($appointment = $today_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['pet_name']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($appointment['species']); ?>
                                                <?php if (!empty($appointment['breed'])): ?>
                                                    - <?php echo htmlspecialchars($appointment['breed']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['owner_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['reason']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                Scheduled
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex space-x-2">
                                                <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="start_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900">
                                                    <i class="fas fa-play-circle"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No appointments today</h3>
                        <p class="mt-1 text-sm text-gray-500">You have no scheduled appointments for today.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="appointments.php" class="bg-blue-100 text-blue-700 hover:bg-blue-200 p-3 rounded-md text-center transition-colors">
                        <i class="fas fa-calendar-alt mb-1"></i>
                        <span class="block text-sm">All Appointments</span>
                    </a>
                    <a href="create_record.php" class="bg-green-100 text-green-700 hover:bg-green-200 p-3 rounded-md text-center transition-colors">
                        <i class="fas fa-file-medical mb-1"></i>
                        <span class="block text-sm">New Record</span>
                    </a>
                    <a href="patients.php" class="bg-purple-100 text-purple-700 hover:bg-purple-200 p-3 rounded-md text-center transition-colors">
                        <i class="fas fa-paw mb-1"></i>
                        <span class="block text-sm">View Patients</span>
                    </a>
                    <a href="profile.php" class="bg-gray-100 text-gray-700 hover:bg-gray-200 p-3 rounded-md text-center transition-colors">
                        <i class="fas fa-user-edit mb-1"></i>
                        <span class="block text-sm">Edit Profile</span>
                    </a>
                </div>
            </div>
            
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Upcoming Appointments</h3>
                    <a href="appointments.php" class="text-sm text-indigo-600 hover:text-indigo-800">View All</a>
                </div>
                
                <?php if ($upcoming_stmt->rowCount() > 0): ?>
                    <div class="space-y-4">
                        <?php while ($appointment = $upcoming_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="border rounded-lg p-3 hover:bg-gray-50">
                                <div class="flex justify-between">
                                    <div class="font-medium text-sm">
                                        <?php echo date('M d', strtotime($appointment['appointment_date'])); ?>,
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    <span class="text-xs text-gray-500">
                                        <?php 
                                            $appt_date = new DateTime($appointment['appointment_date']);
                                            $now = new DateTime();
                                            $diff = $now->diff($appt_date);
                                            if ($diff->days == 0) {
                                                echo "Today";
                                            } elseif ($diff->days == 1) {
                                                echo "Tomorrow";
                                            } else {
                                                echo "In " . $diff->days . " days";
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-medium"><?php echo htmlspecialchars($appointment['pet_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($appointment['owner_name']); ?></p>
                                        </div>
                                        <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full bg-blue-50 text-blue-700">
                                            <?php echo htmlspecialchars($appointment['reason']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm italic">No upcoming appointments scheduled.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
