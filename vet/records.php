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

// Get filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$pet_id = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
// Removed record_type filter as it doesn't exist in the database

// Build the base query for records
$query = "SELECT mr.*, p.name as pet_name, p.species, p.breed, 
          CONCAT(u.first_name, ' ', u.last_name) as owner_name
          FROM medical_records mr
          JOIN pets p ON mr.pet_id = p.id
          JOIN users u ON p.owner_id = u.id
          JOIN appointments a ON mr.appointment_id = a.id
          WHERE a.vet_id = :vet_id ";

// Add filters to the query
if (!empty($search)) {
    $query .= "AND (p.name LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search OR mr.diagnosis LIKE :search) ";
}

if (!empty($pet_id)) {
    $query .= "AND mr.pet_id = :pet_id ";
}

if (!empty($date_from)) {
    $query .= "AND mr.record_date >= :date_from ";
}

if (!empty($date_to)) {
    $query .= "AND mr.record_date <= :date_to ";
}

// Removed record_type filter condition as it doesn't exist

// Order by most recent first
$query .= "ORDER BY mr.record_date DESC, mr.id DESC";

// Prepare and execute the query
$stmt = $db->prepare($query);
$stmt->bindParam(':vet_id', $vet_id);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

if (!empty($pet_id)) {
    $stmt->bindParam(':pet_id', $pet_id);
}

if (!empty($date_from)) {
    $stmt->bindParam(':date_from', $date_from);
}

if (!empty($date_to)) {
    $stmt->bindParam(':date_to', $date_to);
}

// Removed record_type bind param as it doesn't exist

$stmt->execute();

// Get list of pets this vet has treated for the filter dropdown
$pets_query = "SELECT DISTINCT p.id, p.name, p.species
               FROM pets p
               JOIN medical_records mr ON p.id = mr.pet_id
               JOIN appointments a ON mr.appointment_id = a.id
               WHERE a.vet_id = :vet_id
               ORDER BY p.name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':vet_id', $vet_id);
$pets_stmt->execute();

// Removed types_query and types_stmt as record_type doesn't exist in the database

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Medical Records</h1>
        <p class="text-white text-opacity-90 mt-2">View and manage patient medical histories</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Records</h3>
        <form method="get" action="records.php" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by pet, owner, or diagnosis"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            
            <div>
                <label for="pet_id" class="block text-sm font-medium text-gray-700 mb-1">Pet</label>
                <select id="pet_id" name="pet_id" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">All Pets</option>
                    <?php while ($pet = $pets_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo $pet['id']; ?>" <?php echo $pet_id == $pet['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <!-- Removed record_type filter dropdown as it doesn't exist in database -->
            
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" 
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" 
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                <a href="records.php" class="ml-3 text-violet-600 hover:text-violet-800 py-2 px-3">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>
    
    <!-- Records List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold">Medical Record List</h3>
            <div class="flex items-center">
                <span class="text-sm text-gray-500 mr-4">
                    <?php echo $stmt->rowCount(); ?> records found
                </span>
                <a href="create_record.php" class="bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium py-2 px-4 rounded-md transition-colors">
                    <i class="fas fa-plus-circle mr-1"></i> New Record
                </a>
            </div>
        </div>
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <!-- Removed Type column as record_type doesn't exist -->
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($record = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($record['record_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 bg-violet-100 rounded-full flex items-center justify-center text-violet-600">
                                            <?php
                                            $icon = 'fa-paw';
                                            if (strtolower($record['species']) === 'dog') {
                                                $icon = 'fa-dog';
                                            } elseif (strtolower($record['species']) === 'cat') {
                                                $icon = 'fa-cat';
                                            } elseif (strtolower($record['species']) === 'bird') {
                                                $icon = 'fa-dove';
                                            } elseif (strtolower($record['species']) === 'fish') {
                                                $icon = 'fa-fish';
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon; ?> text-sm"></i>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['pet_name']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php 
                                                if (!empty($record['breed'])) {
                                                    echo htmlspecialchars($record['breed']);
                                                } else {
                                                    echo htmlspecialchars($record['species']);
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($record['owner_name']); ?></div>
                                </td>
                                <!-- Removed record_type column as it doesn't exist -->
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate">
                                        <?php echo htmlspecialchars($record['diagnosis']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-3">
                                        <a href="view_record.php?id=<?php echo $record['id']; ?>" class="text-violet-600 hover:text-violet-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="print_record.php?id=<?php echo $record['id']; ?>" class="text-gray-600 hover:text-gray-900">
                                            <i class="fas fa-print"></i>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No medical records found</h3>
                <p class="mt-1 text-sm text-gray-500">No records match your filter criteria.</p>
                <div class="mt-4">
                    <a href="create_record.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Create New Record
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
