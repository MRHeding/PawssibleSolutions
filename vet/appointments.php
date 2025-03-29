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

// Default filter values
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_date_start = isset($_GET['date_start']) ? $_GET['date_start'] : date('Y-m-d', strtotime('-30 days'));
$filter_date_end = isset($_GET['date_end']) ? $_GET['date_end'] : date('Y-m-d', strtotime('+30 days'));

// Build the query based on filters
$query = "SELECT a.*, p.name as pet_name, p.species, p.breed,
          CONCAT(u.first_name, ' ', u.last_name) as owner_name
          FROM appointments a 
          JOIN pets p ON a.pet_id = p.id
          JOIN users u ON p.owner_id = u.id
          WHERE a.vet_id = :vet_id ";

// Add status filter
if ($filter_status !== 'all') {
    $query .= "AND a.status = :status ";
}

// Add date range filter
$query .= "AND a.appointment_date BETWEEN :date_start AND :date_end ";

// Order by date and time
$query .= "ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':vet_id', $vet_id);

if ($filter_status !== 'all') {
    $stmt->bindParam(':status', $filter_status);
}

$stmt->bindParam(':date_start', $filter_date_start);
$stmt->bindParam(':date_end', $filter_date_end);
$stmt->execute();

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Appointments</h1>
        <p class="text-white text-opacity-90 mt-2">Manage all your appointments</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Appointments</h3>
        <form method="get" action="appointments.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="scheduled" <?php echo $filter_status == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="no-show" <?php echo $filter_status == 'no-show' ? 'selected' : ''; ?>>No Show</option>
                </select>
            </div>
            
            <div>
                <label for="date_start" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input type="date" id="date_start" name="date_start" value="<?php echo $filter_date_start; ?>" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            
            <div>
                <label for="date_end" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input type="date" id="date_end" name="date_end" value="<?php echo $filter_date_end; ?>" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                    Apply Filters
                </button>
                <a href="appointments.php" class="ml-2 text-violet-600 hover:text-violet-800 py-2 px-3">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Appointments List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold">Appointment List</h3>
            <div class="flex space-x-2">
                <span class="text-sm text-gray-500">
                    <?php echo $stmt->rowCount(); ?> appointments found
                </span>
            </div>
        </div>
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($appointment = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
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
                                    <?php
                                    $statusClasses = [
                                        'scheduled' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'no-show' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $statusClass = isset($statusClasses[$appointment['status']]) ? $statusClasses[$appointment['status']] : 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-3">
                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-violet-600 hover:text-violet-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($appointment['status'] === 'scheduled'): ?>
                                            <a href="start_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-play-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment['status'] === 'scheduled'): ?>
                                            <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=cancelled" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                <i class="fas fa-times-circle"></i>
                                            </a>
                                        <?php endif; ?>
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
                <h3 class="mt-2 text-sm font-medium text-gray-900">No appointments found</h3>
                <p class="mt-1 text-sm text-gray-500">No appointments match your filter criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
