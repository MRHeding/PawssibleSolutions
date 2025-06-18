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
$client_filter = isset($_GET['client_id']) ? $_GET['client_id'] : '';
$vet_filter = isset($_GET['vet_id']) ? $_GET['vet_id'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get pets for filter dropdown
$pets_query = "SELECT p.id, p.name, p.species, CONCAT(u.first_name, ' ', u.last_name) as owner_name 
               FROM pets p 
               JOIN users u ON p.owner_id = u.id 
               ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->execute();
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get clients for filter dropdown
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as name FROM users WHERE role = 'client' ORDER BY first_name, last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get vets for filter dropdown
$vets_query = "SELECT v.id, CONCAT(u.first_name, ' ', u.last_name) as name 
               FROM vets v 
               JOIN users u ON v.user_id = u.id 
               ORDER BY u.first_name, u.last_name";
$vets_stmt = $db->prepare($vets_query);
$vets_stmt->execute();
$vets = $vets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build the medical records query
$query = "SELECT mr.*, p.name as pet_name, p.species, p.breed,
         CONCAT(o.first_name, ' ', o.last_name) as owner_name,
         CONCAT(v.first_name, ' ', v.last_name) as vet_name,
         a.appointment_number, a.appointment_date
         FROM medical_records mr
         JOIN pets p ON mr.pet_id = p.id
         JOIN users o ON p.owner_id = o.id
         JOIN users v ON mr.created_by = v.id
         LEFT JOIN appointments a ON mr.appointment_id = a.id
         WHERE 1=1";

// Add filters
if (!empty($pet_filter)) {
    $query .= " AND mr.pet_id = :pet_id";
}

if (!empty($client_filter)) {
    $query .= " AND p.owner_id = :client_id";
}

if (!empty($vet_filter)) {
    $query .= " AND mr.created_by = :vet_id";
}

if (!empty($date_filter)) {
    $query .= " AND DATE(mr.record_date) = :date";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR o.first_name LIKE :search OR o.last_name LIKE :search OR mr.diagnosis LIKE :search OR mr.treatment LIKE :search)";
}

$query .= " ORDER BY mr.record_date DESC, mr.created_at DESC";

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind filter parameters
if (!empty($pet_filter)) {
    $stmt->bindParam(':pet_id', $pet_filter);
}
if (!empty($client_filter)) {
    $stmt->bindParam(':client_id', $client_filter);
}
if (!empty($vet_filter)) {
    $stmt->bindParam(':vet_id', $vet_filter);
}
if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

// Get medical records statistics
$stats_query = "SELECT 
                COUNT(*) as total_records,
                COUNT(DISTINCT pet_id) as unique_pets,
                COUNT(DISTINCT DATE(record_date)) as record_days,
                MAX(record_date) as latest_record
                FROM medical_records";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Medical Records Management</h1>
                <p class="text-white text-opacity-90 mt-2">View and manage all patient medical records</p>
            </div>
            <div class="flex space-x-3">
                <a href="add_medical_record.php" class="bg-white hover:bg-gray-100 text-teal-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-plus mr-2"></i> Add Medical Record
                </a>
                <a href="reports.php" class="bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-chart-bar mr-2"></i> Reports
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Medical Records Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-teal-100 p-3 rounded-full mr-4">
                    <i class="fas fa-file-medical text-teal-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Records</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_records']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-cyan-100 p-3 rounded-full mr-4">
                    <i class="fas fa-paw text-cyan-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Pets with Records</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['unique_pets']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Record Days</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['record_days']; ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-clock text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Latest Record</p>
                    <h3 class="text-lg font-bold">
                        <?php echo $stats['latest_record'] ? date('M d, Y', strtotime($stats['latest_record'])) : 'N/A'; ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Medical Records</h3>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                <select id="pet_id" name="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Pets</option>
                    <?php foreach ($pets as $pet): ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo $pet_filter == $pet['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['owner_name']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="client_id" class="block text-gray-700 text-sm font-bold mb-2">Client</label>
                <select id="client_id" name="client_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" <?php echo $client_filter == $client['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($client['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="vet_id" class="block text-gray-700 text-sm font-bold mb-2">Veterinarian</label>
                <select id="vet_id" name="vet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Vets</option>
                    <?php foreach ($vets as $vet): ?>
                        <option value="<?php echo $vet['id']; ?>" <?php echo $vet_filter == $vet['id'] ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($vet['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Date</label>
                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
            </div>
            
            <div class="md:col-span-2">
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <div class="flex">
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by pet name, owner, diagnosis..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($pet_filter) || !empty($client_filter) || !empty($vet_filter) || !empty($date_filter) || !empty($search)): ?>
            <div class="mt-4 flex justify-end">
                <a href="medical_records.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times-circle mr-1"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Medical Records List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet & Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Treatment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($record = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-teal-100 flex items-center justify-center">
                                                <i class="fas fa-paw text-teal-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['pet_name']); ?></div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($record['species']); ?>
                                                <?php if ($record['breed']): ?>
                                                    - <?php echo htmlspecialchars($record['breed']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-400">Owner: <?php echo htmlspecialchars($record['owner_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($record['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs">
                                        <?php 
                                        $diagnosis = htmlspecialchars($record['diagnosis']);
                                        echo strlen($diagnosis) > 50 ? substr($diagnosis, 0, 50) . '...' : $diagnosis;
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs">
                                        <?php 
                                        $treatment = htmlspecialchars($record['treatment']);
                                        echo strlen($treatment) > 50 ? substr($treatment, 0, 50) . '...' : $treatment;
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Dr. <?php echo htmlspecialchars($record['vet_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($record['appointment_number']): ?>
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['appointment_number']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($record['appointment_date'])); ?></div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">No appointment</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="../view_medical_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Record">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_medical_record.php?id=<?php echo $record['id']; ?>" class="text-green-600 hover:text-green-900" title="Edit Record">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="print_medical_record.php?id=<?php echo $record['id']; ?>" target="_blank" class="text-purple-600 hover:text-purple-900" title="Print Record">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="delete_medical_record.php?id=<?php echo $record['id']; ?>" class="text-red-600 hover:text-red-900" title="Delete Record" onclick="return confirm('Are you sure you want to delete this medical record?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
                <i class="fas fa-file-medical text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Medical Records Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($pet_filter) || !empty($client_filter) || !empty($vet_filter) || !empty($date_filter) || !empty($search)): ?>
                    No medical records match your current filters. Try adjusting your filter criteria.
                <?php else: ?>
                    There are no medical records in the system yet.
                <?php endif; ?>
            </p>
            <a href="add_medical_record.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded transition">
                Add First Medical Record
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Stats Modal -->
<div id="statsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-teal-100">
                <i class="fas fa-chart-bar text-teal-600 text-xl"></i>
            </div>
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Medical Records Statistics</h3>
                <div class="mt-4 text-left">
                    <div class="grid grid-cols-1 gap-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Records:</span>
                            <span class="font-semibold"><?php echo $stats['total_records']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Unique Pets:</span>
                            <span class="font-semibold"><?php echo $stats['unique_pets']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Record Days:</span>
                            <span class="font-semibold"><?php echo $stats['record_days']; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Latest Record:</span>
                            <span class="font-semibold">
                                <?php echo $stats['latest_record'] ? date('M d, Y', strtotime($stats['latest_record'])) : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="items-center px-4 py-3">
                    <button onclick="closeStatsModal()" class="px-4 py-2 bg-teal-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showStatsModal() {
    document.getElementById('statsModal').classList.remove('hidden');
}

function closeStatsModal() {
    document.getElementById('statsModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('statsModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeStatsModal();
    }
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>