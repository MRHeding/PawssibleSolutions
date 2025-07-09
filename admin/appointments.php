<?php
session_start();
include_once '../config/database.php';

// Add cache-busting headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if user is admin or staff
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Set default filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$vet_filter = isset($_GET['vet_id']) ? $_GET['vet_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get vets for filter dropdown
$vets_query = "SELECT v.id, CONCAT(u.first_name, ' ', u.last_name) as vet_name 
               FROM vets v 
               JOIN users u ON v.user_id = u.id 
               ORDER BY u.last_name, u.first_name";
$vets_stmt = $db->prepare($vets_query);
$vets_stmt->execute();
$vets = $vets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the appointments query
$query = "SELECT a.*, p.name as pet_name, p.species, 
         CONCAT(o.first_name, ' ', o.last_name) as owner_name,
         CONCAT(v.first_name, ' ', v.last_name) as vet_name,
         mr.id as medical_record_id
         FROM appointments a 
         JOIN pets p ON a.pet_id = p.id 
         JOIN users o ON p.owner_id = o.id
         JOIN vets vt ON a.vet_id = vt.id 
         JOIN users v ON vt.user_id = v.id
         LEFT JOIN medical_records mr ON a.id = mr.appointment_id";

// Add filters
if (!empty($status_filter)) {
    $query .= " WHERE a.status = :status";
} else {
    $query .= " WHERE 1=1"; // Placeholder for additional filters
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = :date";
}

if (!empty($vet_filter)) {
    $query .= " AND a.vet_id = :vet_id";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR o.first_name LIKE :search OR o.last_name LIKE :search 
               OR v.first_name LIKE :search OR v.last_name LIKE :search OR a.reason LIKE :search)";
}

// Add sorting
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind filter parameters if they exist
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}
if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}
if (!empty($vet_filter)) {
    $stmt->bindParam(':vet_id', $vet_filter);
}
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

// Include admin header
include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-purple-600 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Manage Appointments</h1>            <div>
                <a href="add_appointment.php" class="bg-white hover:bg-gray-100 text-purple-600 font-bold py-2 px-4 rounded inline-flex items-center transition mr-2">
                    <i class="fas fa-plus mr-2"></i> Add New
                </a>
                <a href="export_appointments.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="bg-white hover:bg-gray-100 text-green-600 font-bold py-2 px-4 rounded inline-flex items-center transition mr-2">
                    <i class="fas fa-download mr-2"></i> Export CSV
                </a>
                <a href="appointment_calendar.php" class="bg-white hover:bg-gray-100 text-blue-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-calendar-alt mr-2"></i> Calendar View
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php 
            echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Appointments</h3>
        <form action="" method="get" class="flex flex-wrap md:flex-nowrap gap-4">
            <div class="w-full md:w-1/4">
                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="no-show" <?php echo $status_filter === 'no-show' ? 'selected' : ''; ?>>No-Show</option>
                </select>
            </div>
            
            <div class="w-full md:w-1/4">
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Date</label>
                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
            </div>
            
            <div class="w-full md:w-1/4">
                <label for="vet_id" class="block text-gray-700 text-sm font-bold mb-2">Veterinarian</label>
                <select id="vet_id" name="vet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Veterinarians</option>
                    <?php foreach ($vets as $vet): ?>
                        <option value="<?php echo $vet['id']; ?>" <?php echo $vet_filter == $vet['id'] ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($vet['vet_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-1/4">
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <div class="flex">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($status_filter) || !empty($date_filter) || !empty($vet_filter) || !empty($search)): ?>
            <div class="mt-4 flex justify-end">
                <a href="appointments.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times-circle mr-1"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Appointments List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($appointment = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['pet_name']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($appointment['species']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['owner_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Dr. <?php echo htmlspecialchars($appointment['vet_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['reason']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        switch($appointment['status']) {
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
                                                echo 'bg-orange-100 text-orange-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                    <?php if ($appointment['status'] === 'completed' && !empty($appointment['medical_record_id'])): ?>
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <i class="fas fa-file-medical mr-1"></i>
                                                Record Added
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($appointment['status'] === 'scheduled'): ?>
                                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=completed" class="text-green-600 hover:text-green-900" title="Mark as Completed" onclick="return confirm('Are you sure you want to mark this appointment as completed?');">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            
                                            <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=no-show" class="text-yellow-600 hover:text-yellow-900" title="Mark as No-Show" onclick="return confirm('Are you sure you want to mark this appointment as no-show?');">
                                                <i class="fas fa-user-times"></i>
                                            </a>
                                            
                                            <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=cancelled" class="text-red-600 hover:text-red-900" title="Cancel" onclick="return confirm('Are you sure you want to cancel this appointment?');">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php elseif ($appointment['status'] === 'completed'): ?>
                                            <?php if (empty($appointment['medical_record_id'])): ?>
                                                <!-- No medical record exists yet -->
                                                <a href="add_medical_record.php?appointment_id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900" title="Add Medical Record">
                                                    <i class="fas fa-file-medical"></i>
                                                </a>
                                            <?php else: ?>
                                                <!-- Medical record already exists -->
                                                <a href="../view_medical_record.php?id=<?php echo $appointment['medical_record_id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Medical Record">
                                                    <i class="fas fa-file-medical-alt"></i>
                                                </a>
                                                <span class="text-gray-400 ml-2" title="Medical record already exists for this appointment">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <?php if ($user_role === 'admin'): ?>
                                            <a href="delete_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-red-600 hover:text-red-900" title="Delete" onclick="return confirm('Are you sure you want to delete this appointment?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-calendar-times text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Appointments Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($status_filter) || !empty($date_filter) || !empty($vet_filter) || !empty($search)): ?>
                    No appointments match your current filters. Try changing your filter criteria.
                <?php else: ?>
                    There are no appointments in the system.
                <?php endif; ?>
            </p>
            <a href="add_appointment.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded transition">
                Create New Appointment
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
