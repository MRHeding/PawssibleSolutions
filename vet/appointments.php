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

// Set default filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$pet_filter = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';

// Get pets for the dropdown filter (optional enhancement)
$pets_query = "SELECT DISTINCT p.id, p.name 
             FROM pets p 
             JOIN appointments a ON p.id = a.pet_id 
             WHERE a.vet_id = :vet_id 
             ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':vet_id', $vet_id);
$pets_stmt->execute();
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the appointments query
$query = "SELECT a.*, p.name as pet_name, p.species, p.breed,
         CONCAT(u.first_name, ' ', u.last_name) as owner_name
         FROM appointments a 
         JOIN pets p ON a.pet_id = p.id 
         JOIN users u ON p.owner_id = u.id
         WHERE a.vet_id = :vet_id";

// Add filters
if (!empty($status_filter)) {
    $query .= " AND a.status = :status";
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = :date";
}

if (!empty($pet_filter)) {
    $query .= " AND p.id = :pet_id";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search 
               OR a.reason LIKE :search)";
}

// Add sorting
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

// Prepare and execute the query
$stmt = $db->prepare($query);
$stmt->bindParam(':vet_id', $vet_id);

// Bind filter parameters if they exist
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}

if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}

if (!empty($pet_filter)) {
    $stmt->bindParam(':pet_id', $pet_filter);
}

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">My Appointments</h1>
            <a href="dashboard.php" class="bg-white hover:bg-gray-100 text-indigo-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
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
            
            <?php if (count($pets) > 0): ?>
            <div class="w-full md:w-1/4">
                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                <select id="pet_id" name="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Pets</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo $pet_filter == $pet['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="w-full md:w-1/4">
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <div class="flex">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($status_filter) || !empty($date_filter) || !empty($pet_filter) || !empty($search)): ?>
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
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if ($appointment['status'] === 'scheduled'): ?>
                                            <?php if (strtotime($appointment['appointment_date']) >= strtotime(date('Y-m-d'))): ?>
                                                <a href="start_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900" title="Start Appointment">
                                                    <i class="fas fa-play-circle"></i>
                                                </a>
                                                
                                                <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=no-show" class="text-yellow-600 hover:text-yellow-900" title="Mark as No-Show">
                                                    <i class="fas fa-user-times"></i>
                                                </a>
                                                
                                                <a href="update_status.php?id=<?php echo $appointment['id']; ?>&status=cancelled" class="text-red-600 hover:text-red-900" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php elseif ($appointment['status'] === 'completed'): ?>
                                            <a href="view_medical_record.php?appointment_id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900" title="View/Edit Medical Record">
                                                <i class="fas fa-file-medical"></i>
                                            </a>
                                            
                                            <!-- Follow-up appointment button for completed appointments -->
                                            <a href="view_appointment.php?id=<?php echo $appointment['id']; ?>#followup" class="text-indigo-600 hover:text-indigo-900" title="Schedule Follow-up Appointment">
                                                <i class="fas fa-calendar-plus"></i>
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
                <?php if (!empty($status_filter) || !empty($date_filter) || !empty($pet_filter) || !empty($search)): ?>
                    No appointments match your current filters. Try changing your filter criteria.
                <?php else: ?>
                    You don't have any appointments scheduled yet.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Quick Actions Card -->
    <div class="mt-8 bg-blue-50 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold mb-4">Quick Actions</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="view_schedule.php" class="bg-white p-4 rounded-lg shadow border border-gray-200 hover:shadow-md transition flex items-center">
                <div class="rounded-full bg-blue-100 p-3 mr-4">
                    <i class="fas fa-calendar-alt text-blue-600"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">View My Schedule</h4>
                    <p class="text-sm text-gray-600">Check your upcoming schedule</p>
                </div>
            </a>
            
            <a href="patients.php" class="bg-white p-4 rounded-lg shadow border border-gray-200 hover:shadow-md transition flex items-center">
                <div class="rounded-full bg-green-100 p-3 mr-4">
                    <i class="fas fa-paw text-green-600"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">My Patients</h4>
                    <p class="text-sm text-gray-600">View all patient information</p>
                </div>
            </a>
            
            <a href="create_record.php" class="bg-white p-4 rounded-lg shadow border border-gray-200 hover:shadow-md transition flex items-center">
                <div class="rounded-full bg-purple-100 p-3 mr-4">
                    <i class="fas fa-file-medical text-purple-600"></i>
                </div>
                <div>
                    <h4 class="font-medium text-gray-800">Create Medical Record</h4>
                    <p class="text-sm text-gray-600">Add a new medical record</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
