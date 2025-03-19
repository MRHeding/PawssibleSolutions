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
$pet_filter = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get user's pets for filter dropdown (for clients)
$pets = [];
if ($user_role == 'client') {
    $pets_query = "SELECT id, name FROM pets WHERE owner_id = :owner_id ORDER BY name";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->bindParam(':owner_id', $user_id);
    $pets_stmt->execute();
    $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Build the medical records query based on user role and filters
if ($user_role == 'client') {
    // Client viewing their pets' medical records
    $query = "SELECT mr.*, p.name as pet_name, p.species, 
             CONCAT(u.first_name, ' ', u.last_name) as vet_name,
             a.reason as appointment_reason
             FROM medical_records mr
             JOIN pets p ON mr.pet_id = p.id
             LEFT JOIN appointments a ON mr.appointment_id = a.id
             LEFT JOIN users u ON mr.created_by = u.id
             WHERE p.owner_id = :user_id";

    // Add filters
    if (!empty($pet_filter)) {
        $query .= " AND p.id = :pet_id";
    }
    if (!empty($date_from)) {
        $query .= " AND mr.record_date >= :date_from";
    }
    if (!empty($date_to)) {
        $query .= " AND mr.record_date <= :date_to";
    }
    if (!empty($search)) {
        $query .= " AND (mr.diagnosis LIKE :search OR mr.treatment LIKE :search OR mr.medications LIKE :search)";
    }
} else {
    // Admin/vet can see all records or specific records based on filters
    $query = "SELECT mr.*, p.name as pet_name, p.species,
             CONCAT(o.first_name, ' ', o.last_name) as owner_name,
             CONCAT(v.first_name, ' ', v.last_name) as vet_name,
             a.reason as appointment_reason
             FROM medical_records mr
             JOIN pets p ON mr.pet_id = p.id
             JOIN users o ON p.owner_id = o.id
             LEFT JOIN appointments a ON mr.appointment_id = a.id
             LEFT JOIN users v ON mr.created_by = v.id
             WHERE 1=1";

    // Add filters
    if (!empty($pet_filter)) {
        $query .= " AND p.id = :pet_id";
    }
    if (!empty($date_from)) {
        $query .= " AND mr.record_date >= :date_from";
    }
    if (!empty($date_to)) {
        $query .= " AND mr.record_date <= :date_to";
    }
    if (!empty($search)) {
        $query .= " AND (mr.diagnosis LIKE :search OR mr.treatment LIKE :search OR mr.medications LIKE :search OR p.name LIKE :search OR o.first_name LIKE :search OR o.last_name LIKE :search)";
    }
}

// Add sorting
$query .= " ORDER BY mr.record_date DESC, mr.created_at DESC";

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind parameters based on role
if ($user_role == 'client') {
    $stmt->bindParam(':user_id', $user_id);
}

// Bind filter parameters if they exist
if (!empty($pet_filter)) {
    $stmt->bindParam(':pet_id', $pet_filter);
}
if (!empty($date_from)) {
    $stmt->bindParam(':date_from', $date_from);
}
if (!empty($date_to)) {
    $stmt->bindParam(':date_to', $date_to);
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
        <h1 class="text-3xl font-bold text-white">Medical Records</h1>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Records</h3>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php if ($user_role == 'client' && count($pets) > 0): ?>
            <div>
                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                <select id="pet_id" name="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Pets</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo $pet_filter == $pet['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div>
                <label for="date_from" class="block text-gray-700 text-sm font-bold mb-2">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div>
                <label for="date_to" class="block text-gray-700 text-sm font-bold mb-2">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div>
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <div class="flex">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search records..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($pet_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
            <div class="mt-4 flex justify-end">
                <a href="medical_records.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times-circle mr-1"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Medical Records List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($record = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="bg-blue-50 p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-bold text-lg"><?php echo htmlspecialchars($record['pet_name']); ?></h3>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($record['species']); ?></p>
                                <p class="text-gray-500 text-sm mt-1"><?php echo date('F d, Y', strtotime($record['record_date'])); ?></p>
                            </div>
                            <?php if (isset($record['owner_name']) && $user_role != 'client'): ?>
                                <p class="text-sm text-gray-600">Owner: <?php echo htmlspecialchars($record['owner_name']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($record['appointment_reason'])): ?>
                            <div class="mb-2">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                    <?php echo htmlspecialchars($record['appointment_reason']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <?php if (!empty($record['diagnosis'])): ?>
                                <p class="text-sm"><span class="font-medium">Diagnosis:</span> 
                                    <?php echo strlen($record['diagnosis']) > 100 ? 
                                        htmlspecialchars(substr($record['diagnosis'], 0, 100)) . '...' : 
                                        htmlspecialchars($record['diagnosis']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if (!empty($record['treatment'])): ?>
                                <p class="text-sm mt-1"><span class="font-medium">Treatment:</span> 
                                    <?php echo strlen($record['treatment']) > 100 ? 
                                        htmlspecialchars(substr($record['treatment'], 0, 100)) . '...' : 
                                        htmlspecialchars($record['treatment']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex justify-between items-center mt-4 pt-2 border-t border-gray-100">
                            <small class="text-gray-500">By Dr. <?php echo htmlspecialchars($record['vet_name']); ?></small>
                            <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-file-medical-alt text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Medical Records Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($pet_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                    No medical records match your current filters. Try changing your filter criteria.
                <?php else: ?>
                    There are no medical records available at this time.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
