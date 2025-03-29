<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Default empty variables
$selected_client_id = $_GET['client_id'] ?? null;
$selected_pet_id = $_GET['pet_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-6 months'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
// Removing record_type filter as it doesn't exist in the database schema
// $record_type = $_GET['record_type'] ?? '';

// Get all clients (pet owners) for the dropdown
$clients_query = "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email, 
                 (SELECT COUNT(*) FROM pets WHERE owner_id = u.id) as pet_count
                 FROM users u
                 JOIN pets p ON u.id = p.owner_id
                 WHERE u.role = 'client'
                 ORDER BY u.last_name, u.first_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();

// Get pets for selected client
$pets = [];
if ($selected_client_id) {
    $pets_query = "SELECT p.id, p.name, p.species, p.breed, p.date_of_birth, 
                  (SELECT COUNT(*) FROM medical_records WHERE pet_id = p.id) as record_count
                  FROM pets p
                  WHERE p.owner_id = :owner_id
                  ORDER BY p.name";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->bindParam(':owner_id', $selected_client_id);
    $pets_stmt->execute();
    $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get client details
    $client_query = "SELECT first_name, last_name, email, phone FROM users WHERE id = :id";
    $client_stmt = $db->prepare($client_query);
    $client_stmt->bindParam(':id', $selected_client_id);
    $client_stmt->execute();
    $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
}

// Get medical records
$records = [];
if ($selected_client_id) {
    // Fixed query to use created_by instead of vet_id
    $records_query = "SELECT mr.*, p.name as pet_name, p.species, p.breed,
                     CONCAT(v.first_name, ' ', v.last_name) as vet_name
                     FROM medical_records mr
                     JOIN pets p ON mr.pet_id = p.id
                     JOIN users v ON mr.created_by = v.id
                     WHERE p.owner_id = :owner_id";
    
    // Add filters if provided
    $params = [':owner_id' => $selected_client_id];
    
    if ($selected_pet_id) {
        $records_query .= " AND mr.pet_id = :pet_id";
        $params[':pet_id'] = $selected_pet_id;
    }
    
    // Remove filtering by record_type as it doesn't exist in the schema
    /*
    if ($record_type) {
        $records_query .= " AND mr.record_type = :record_type";
        $params[':record_type'] = $record_type;
    }
    */
    
    $records_query .= " AND mr.record_date BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date;
    $params[':end_date'] = $end_date;
    
    $records_query .= " ORDER BY mr.record_date DESC, mr.id DESC";
    
    $records_stmt = $db->prepare($records_query);
    foreach ($params as $key => $value) {
        $records_stmt->bindParam($key, $params[$key]);
    }
    $records_stmt->execute();
    $records = $records_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get record types for filter - Since record_type doesn't exist in the schema, we'll remove this
// $types_query = "SELECT DISTINCT record_type FROM medical_records ORDER BY record_type";
// $types_stmt = $db->prepare($types_query);
// $types_stmt->execute();
// $record_types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Client Medical Records</h1>
            <div>
                <a href="dashboard.php" class="bg-white text-violet-600 px-4 py-2 rounded-md hover:bg-violet-50 transition mr-2">
                    Dashboard
                </a>
                <a href="clients.php" class="bg-white text-violet-600 px-4 py-2 rounded-md hover:bg-violet-50 transition">
                    All Clients
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Client Selection Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">Select Client</label>
                <select name="client_id" id="client_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500" onchange="this.form.submit()">
                    <option value="">-- Select Client --</option>
                    <?php while ($client_row = $clients_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $client_row['id']; ?>" <?php echo ($selected_client_id == $client_row['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client_row['last_name'] . ', ' . $client_row['first_name']); ?> 
                            (<?php echo $client_row['pet_count']; ?> pets)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <?php if ($selected_client_id && !empty($pets)): ?>
                <div>
                    <label for="pet_id" class="block text-sm font-medium text-gray-700 mb-2">Select Pet</label>
                    <select name="pet_id" id="pet_id" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">All Pets</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['id']; ?>" <?php echo ($selected_pet_id == $pet['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Removed record_type filter as it's not in the database schema -->
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($selected_client_id): ?>
                <div class="md:col-span-2 lg:col-span-3 flex justify-end">
                    <button type="submit" class="bg-violet-600 text-white px-4 py-2 rounded-md hover:bg-violet-700 transition">
                        Apply Filters
                    </button>
                    
                    <?php if (!empty($records)): ?>
                        <a href="export_medical_records.php?client_id=<?php echo $selected_client_id; ?>&pet_id=<?php echo $selected_pet_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                           class="ml-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                            Export Records
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <?php if ($selected_client_id): ?>
        <!-- Client Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-semibold text-violet-700"><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($client['email']); ?></p>
                    <?php if (!empty($client['phone'])): ?>
                        <p class="text-gray-600"><?php echo htmlspecialchars($client['phone']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Total Pets: <span class="font-semibold"><?php echo count($pets); ?></span></p>
                    <p class="text-sm text-gray-600">Total Records: <span class="font-semibold"><?php echo count($records); ?></span></p>
                </div>
            </div>
            
            <?php if (!empty($pets)): ?>
                <div class="mt-6 border-t pt-4">
                    <h3 class="text-lg font-medium mb-3">Pets</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($pets as $pet): ?>
                            <div class="border rounded-lg p-3 <?php echo ($selected_pet_id == $pet['id']) ? 'bg-violet-50 border-violet-300' : ''; ?>">
                                <div class="flex justify-between">
                                    <h4 class="font-medium"><?php echo htmlspecialchars($pet['name']); ?></h4>
                                    <span class="text-xs bg-violet-100 text-violet-700 px-2 py-1 rounded-full">
                                        <?php echo $pet['record_count']; ?> records
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($pet['species']); ?>
                                    <?php if (!empty($pet['breed'])): ?> 
                                        - <?php echo htmlspecialchars($pet['breed']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($pet['date_of_birth'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        DOB: <?php echo date('M d, Y', strtotime($pet['date_of_birth'])); ?>
                                        (<?php echo floor((time() - strtotime($pet['date_of_birth'])) / 31536000); ?> years old)
                                    </p>
                                <?php endif; ?>
                                <a href="?client_id=<?php echo $selected_client_id; ?>&pet_id=<?php echo $pet['id']; ?>" 
                                   class="text-violet-600 text-sm hover:text-violet-800 mt-2 inline-block">
                                    View Records
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Medical Records -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-violet-700">Medical Records</h2>
                <div>
                    <span class="text-sm text-gray-500">
                        <?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </span>
                </div>
            </div>
            
            <?php if (empty($records)): ?>
                <div class="text-center py-10">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No records found</h3>
                    <p class="mt-1 text-sm text-gray-500">No medical records match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <!-- Removed record_type column -->
                                <?php if (empty($selected_pet_id)): ?>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                                <?php endif; ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis/Treatment</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></div>
                                    </td>
                                    <!-- Removed record_type cell -->
                                    <?php if (empty($selected_pet_id)): ?>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['pet_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['species']); ?></div>
                                        </td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">Dr. <?php echo htmlspecialchars($record['vet_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($record['diagnosis'])): ?>
                                            <div class="text-sm text-gray-900">
                                                <span class="font-medium">Diagnosis:</span> <?php echo htmlspecialchars($record['diagnosis']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($record['treatment'])): ?>
                                            <div class="text-sm text-gray-900 mt-1">
                                                <span class="font-medium">Treatment:</span> <?php echo htmlspecialchars($record['treatment']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="view_medical_record.php?id=<?php echo $record['id']; ?>" class="text-violet-600 hover:text-violet-900 mr-3">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="edit_medical_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-10 text-center">
            <svg class="mx-auto h-16 w-16 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Select a client</h3>
            <p class="mt-2 text-gray-500">Please select a client from the dropdown menu to view their medical records.</p>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
