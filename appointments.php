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

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Set default filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$pet_filter = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get pets for filter dropdown (for clients)
$pets = [];
if ($user_role == 'client') {
    $pets_query = "SELECT id, name FROM pets WHERE owner_id = :owner_id ORDER BY name";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->bindParam(':owner_id', $user_id);
    $pets_stmt->execute();
    $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Build the appointments query based on user role and filters
if ($user_role == 'client') {
    // Client viewing their pets' appointments
    $query = "SELECT a.*, p.name as pet_name, p.species, 
             CONCAT(u.first_name, ' ', u.last_name) as vet_name 
             FROM appointments a 
             JOIN pets p ON a.pet_id = p.id 
             JOIN vets v ON a.vet_id = v.id 
             JOIN users u ON v.user_id = u.id 
             WHERE p.owner_id = :user_id";

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
        $query .= " AND (p.name LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR a.reason LIKE :search)";
    }
} else {
    // Staff/admin can see all appointments
    $query = "SELECT a.*, p.name as pet_name, p.species, 
             CONCAT(o.first_name, ' ', o.last_name) as owner_name,
             CONCAT(v.first_name, ' ', v.last_name) as vet_name 
             FROM appointments a 
             JOIN pets p ON a.pet_id = p.id 
             JOIN users o ON p.owner_id = o.id
             JOIN vets vt ON a.vet_id = vt.id 
             JOIN users v ON vt.user_id = v.id";

    // Add filters for admin view
    if (!empty($status_filter)) {
        $query .= " WHERE a.status = :status";
    } else {
        $query .= " WHERE 1=1"; // Placeholder for additional filters
    }
    if (!empty($date_filter)) {
        $query .= " AND a.appointment_date = :date";
    }
    if (!empty($search)) {
        $query .= " AND (p.name LIKE :search OR o.first_name LIKE :search OR o.last_name LIKE :search 
                       OR v.first_name LIKE :search OR v.last_name LIKE :search OR a.reason LIKE :search)";
    }
}

// Add sorting
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind parameters based on role
if ($user_role == 'client') {
    $stmt->bindParam(':user_id', $user_id);
}

// Bind filter parameters if they exist
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}
if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}
if ($user_role == 'client' && !empty($pet_filter)) {
    $stmt->bindParam(':pet_id', $pet_filter);
}
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Appointments</h1>
            <?php if ($user_role == 'client'): ?>
                <a href="schedule_appointment.php" class="bg-white hover:bg-gray-100 text-teal-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-plus mr-2"></i> Schedule New
                </a>
            <?php endif; ?>
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
            
            <?php if ($user_role == 'client' && count($pets) > 0): ?>
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
                    <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded-r">
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
                            <?php if ($user_role != 'client'): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <?php endif; ?>
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
                                <?php if ($user_role != 'client'): ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['owner_name']); ?></div>
                                    </td>
                                <?php endif; ?>
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
                                    <?php if ($appointment['status'] === 'scheduled'): ?>
                                        <?php if (strtotime($appointment['appointment_date']) >= strtotime(date('Y-m-d'))): ?>
                                            <a href="<?php echo $user_role == 'client' ? 'edit_appointment.php?id=' : 'admin/edit_appointment.php?id='; ?><?php echo $appointment['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo $user_role == 'client' ? 'cancel_appointment.php?id=' : 'admin/cancel_appointment.php?id='; ?><?php echo $appointment['id']; ?>" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif ($appointment['status'] === 'completed'): ?>
                                        <a href="<?php echo $user_role == 'client' ? 'view_medical_record.php?appointment_id=' : 'admin/view_medical_record.php?appointment_id='; ?><?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-file-medical"></i> View Record
                                        </a>
                                    <?php endif; ?>
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
                    You haven't scheduled any appointments yet.
                <?php endif; ?>
            </p>
            <?php if ($user_role == 'client'): ?>
                <a href="schedule_appointment.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded transition">
                    Schedule an Appointment
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
