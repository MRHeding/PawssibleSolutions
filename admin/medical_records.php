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

// Set default filter values
$pet_filter = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';
$vet_filter = isset($_GET['vet_id']) ? $_GET['vet_id'] : '';
$client_filter = isset($_GET['client_id']) ? $_GET['client_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$records_per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Get all pets for filter dropdown
$pets_query = "SELECT p.id, p.name, p.species, CONCAT(u.first_name, ' ', u.last_name) as owner_name
              FROM pets p
              JOIN users u ON p.owner_id = u.id
              ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->execute();
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all vets for filter dropdown
$vets_query = "SELECT v.id, CONCAT(u.first_name, ' ', u.last_name) as name
              FROM vets v
              JOIN users u ON v.user_id = u.id
              ORDER BY u.first_name, u.last_name";
$vets_stmt = $db->prepare($vets_query);
$vets_stmt->execute();
$vets = $vets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all clients for filter dropdown
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name
                 FROM users
                 WHERE role = 'client'
                 ORDER BY first_name, last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the medical records query with filters
$where_conditions = [];
$params = [];

if (!empty($pet_filter)) {
    $where_conditions[] = "mr.pet_id = :pet_id";
    $params[':pet_id'] = $pet_filter;
}

if (!empty($vet_filter)) {
    $where_conditions[] = "v.id = :vet_id";
    $params[':vet_id'] = $vet_filter;
}

if (!empty($client_filter)) {
    $where_conditions[] = "p.owner_id = :client_id";
    $params[':client_id'] = $client_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "mr.record_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "mr.record_date <= :date_to";
    $params[':date_to'] = $date_to;
}

if (!empty($search)) {
    $where_conditions[] = "(mr.diagnosis LIKE :search OR mr.treatment LIKE :search OR mr.medications LIKE :search OR p.name LIKE :search OR owner.first_name LIKE :search OR owner.last_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total
               FROM medical_records mr
               JOIN pets p ON mr.pet_id = p.id
               JOIN users owner ON p.owner_id = owner.id
               LEFT JOIN vets v ON mr.created_by = v.user_id
               $where_clause";

$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Main query for medical records
$query = "SELECT mr.*, p.name as pet_name, p.species, p.breed,
         CONCAT(owner.first_name, ' ', owner.last_name) as owner_name,
         owner.id as owner_id,
         CONCAT(vet_user.first_name, ' ', vet_user.last_name) as vet_name,
         a.appointment_number, a.reason as appointment_reason,
         a.appointment_date, a.appointment_time
         FROM medical_records mr
         JOIN pets p ON mr.pet_id = p.id
         JOIN users owner ON p.owner_id = owner.id
         LEFT JOIN appointments a ON mr.appointment_id = a.id
         LEFT JOIN users vet_user ON mr.created_by = vet_user.id
         LEFT JOIN vets v ON mr.created_by = v.user_id
         $where_clause
         ORDER BY mr.record_date DESC, mr.created_at DESC
         LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-indigo-600 to-purple-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Medical Records Management</h1>
                <p class="text-white text-opacity-90 mt-2">View and manage all patient medical records</p>
            </div>
            <div class="flex space-x-3">
                <a href="add_medical_record.php" class="bg-white hover:bg-gray-100 text-indigo-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-plus mr-2"></i> Add Record
                </a>
                <button onclick="exportRecords()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-download mr-2"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php
        // Get statistics
        $stats_query = "SELECT 
                       COUNT(*) as total_records,
                       COUNT(DISTINCT mr.pet_id) as unique_pets,
                       COUNT(DISTINCT mr.created_by) as active_vets,
                       COUNT(CASE WHEN mr.record_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_records
                       FROM medical_records mr";
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-file-medical text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-700">Total Records</h3>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($stats['total_records']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-paw text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-700">Unique Pets</h3>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($stats['unique_pets']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-user-md text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-700">Active Vets</h3>
                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['active_vets']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-700">Last 30 Days</h3>
                    <p class="text-2xl font-bold text-orange-600"><?php echo number_format($stats['recent_records']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Filter Medical Records</h3>
            <div class="text-sm text-gray-600">
                Showing <?php echo number_format($total_records); ?> total records
            </div>
        </div>
        
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <div>
                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                <select id="pet_id" name="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Pets</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo $pet_filter == $pet['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['owner_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="vet_id" class="block text-gray-700 text-sm font-bold mb-2">Veterinarian</label>
                <select id="vet_id" name="vet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Vets</option>
                    <?php foreach ($vets as $vet): ?>
                        <option value="<?php echo $vet['id']; ?>" <?php echo $vet_filter == $vet['id'] ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($vet['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="client_id" class="block text-gray-700 text-sm font-bold mb-2">Pet Owner</label>
                <select id="client_id" name="client_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Owners</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $client_filter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="date_from" class="block text-gray-700 text-sm font-bold mb-2">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div>
                <label for="date_to" class="block text-gray-700 text-sm font-bold mb-2">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            
            <div>
                <label for="per_page" class="block text-gray-700 text-sm font-bold mb-2">Records per page</label>
                <select id="per_page" name="per_page" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $records_per_page == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
            
            <div class="xl:col-span-6">
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <div class="flex">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search diagnosis, treatment, medications, pet name, or owner..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-6 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($pet_filter) || !empty($vet_filter) || !empty($client_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
            <div class="mt-4 flex justify-end">
                <a href="medical_records.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times-circle mr-1"></i> Clear All Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Medical Records Table -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet & Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Treatment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($record = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-paw text-gray-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($record['pet_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($record['species']); ?> 
                                                <?php if (!empty($record['breed'])): ?>
                                                    • <?php echo htmlspecialchars($record['breed']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                Owner: <?php echo htmlspecialchars($record['owner_name']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($record['record_date'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('g:i A', strtotime($record['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($record['appointment_number'])): ?>
                                        <div class="text-sm font-medium text-blue-600">
                                            #<?php echo htmlspecialchars($record['appointment_number']); ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?php echo htmlspecialchars($record['appointment_reason']); ?>
                                        </div>
                                        <?php if (!empty($record['appointment_date'])): ?>
                                            <div class="text-xs text-gray-400">
                                                <?php echo date('M d, Y', strtotime($record['appointment_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">No appointment</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        $diagnosis = $record['diagnosis'];
                                        echo !empty($diagnosis) ? 
                                            (strlen($diagnosis) > 80 ? htmlspecialchars(substr($diagnosis, 0, 80)) . '...' : htmlspecialchars($diagnosis)) : 
                                            '<span class="text-gray-400">No diagnosis recorded</span>';
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        $treatment = $record['treatment'];
                                        echo !empty($treatment) ? 
                                            (strlen($treatment) > 80 ? htmlspecialchars(substr($treatment, 0, 80)) . '...' : htmlspecialchars($treatment)) : 
                                            '<span class="text-gray-400">No treatment recorded</span>';
                                        ?>
                                    </div>
                                    <?php if (!empty($record['medications'])): ?>
                                        <div class="text-xs text-blue-600 mt-1">
                                            <i class="fas fa-pills mr-1"></i>Medications prescribed
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Dr. <?php echo htmlspecialchars($record['vet_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="../view_medical_record.php?id=<?php echo $record['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="add_medical_record.php?edit=<?php echo $record['id']; ?>" class="text-green-600 hover:text-green-900" title="Edit Record">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="client_medical_records.php?pet_id=<?php echo $record['pet_id']; ?>" class="text-blue-600 hover:text-blue-900" title="Pet's History">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="view_client.php?id=<?php echo $record['owner_id']; ?>" class="text-purple-600 hover:text-purple-900" title="View Owner">
                                            <i class="fas fa-user"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> results
                </div>
                <div class="flex space-x-1">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                           class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-indigo-600' : 'text-gray-700 bg-white hover:bg-gray-50'; ?> border border-gray-300 rounded-md">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-file-medical-alt text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Medical Records Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($pet_filter) || !empty($vet_filter) || !empty($client_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                    No medical records match your current filters. Try adjusting your search criteria.
                <?php else: ?>
                    There are no medical records in the system yet.
                <?php endif; ?>
            </p>
            <div class="space-x-4">
                <a href="add_medical_record.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded transition">
                    Create First Record
                </a>
                <?php if (!empty($pet_filter) || !empty($vet_filter) || !empty($client_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                    <a href="medical_records.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded transition">
                        Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function exportRecords() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    // Create a temporary link to download the export
    const exportUrl = 'export_medical_records.php?' + params.toString();
    window.open(exportUrl, '_blank');
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const selects = form.querySelectorAll('select:not(#per_page)');
    const dateInputs = form.querySelectorAll('input[type="date"]');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            form.submit();
        });
    });
    
    dateInputs.forEach(input => {
        input.addEventListener('change', function() {
            form.submit();
        });
    });
    
    // Per page selector
    document.getElementById('per_page').addEventListener('change', function() {
        form.submit();
    });
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>