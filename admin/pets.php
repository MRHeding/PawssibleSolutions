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

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$species_filter = isset($_GET['species']) ? trim($_GET['species']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;
$message = '';
$messageClass = '';

// Check if we have a message from a redirect
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageClass = isset($_SESSION['message_class']) ? $_SESSION['message_class'] : 'bg-green-100 border-green-400 text-green-700';
    unset($_SESSION['message']);
    unset($_SESSION['message_class']);
}

// Get unique species for filter dropdown
$species_query = "SELECT DISTINCT species FROM pets ORDER BY species";
$species_stmt = $db->prepare($species_query);
$species_stmt->execute();
$species_list = $species_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build the base query
$count_query = "SELECT COUNT(*) FROM pets p JOIN users u ON p.owner_id = u.id WHERE 1=1";
$pets_query = "SELECT p.*, u.first_name, u.last_name 
              FROM pets p 
              JOIN users u ON p.owner_id = u.id 
              WHERE 1=1";

// Add search condition if search is provided
if (!empty($search)) {
    $search_condition = " AND (p.name LIKE :search OR p.breed LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
    $count_query .= $search_condition;
    $pets_query .= $search_condition;
}

// Add species filter if provided
if (!empty($species_filter)) {
    $species_condition = " AND p.species = :species";
    $count_query .= $species_condition;
    $pets_query .= $species_condition;
}

// Finalize the query with ordering and pagination
$pets_query .= " ORDER BY p.name ASC LIMIT :offset, :items_per_page";

// Prepare and execute count query
$count_stmt = $db->prepare($count_query);
if (!empty($search)) {
    $search_param = "%{$search}%";
    $count_stmt->bindParam(':search', $search_param);
}
if (!empty($species_filter)) {
    $count_stmt->bindParam(':species', $species_filter);
}
$count_stmt->execute();
$total_pets = $count_stmt->fetchColumn();
$total_pages = ceil($total_pets / $items_per_page);

// Prepare and execute pets query
$pets_stmt = $db->prepare($pets_query);
if (!empty($search)) {
    $search_param = "%{$search}%";
    $pets_stmt->bindParam(':search', $search_param);
}
if (!empty($species_filter)) {
    $pets_stmt->bindParam(':species', $species_filter);
}
$pets_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$pets_stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
$pets_stmt->execute();
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle pet deletion if requested
if (isset($_POST['delete_pet']) && isset($_POST['pet_id'])) {
    $pet_id = intval($_POST['pet_id']);
    
    // First check if pet has any records that would be affected
    $check_appointments = $db->prepare("SELECT COUNT(*) FROM appointments WHERE pet_id = :pet_id");
    $check_appointments->bindParam(':pet_id', $pet_id);
    $check_appointments->execute();
    $has_appointments = $check_appointments->fetchColumn() > 0;
    
    $check_medical = $db->prepare("SELECT COUNT(*) FROM medical_records WHERE pet_id = :pet_id");
    $check_medical->bindParam(':pet_id', $pet_id);
    $check_medical->execute();
    $has_medical = $check_medical->fetchColumn() > 0;
    
    if ($has_appointments || $has_medical) {
        $_SESSION['message'] = "Cannot delete pet: There are appointments or medical records associated with this pet.";
        $_SESSION['message_class'] = "bg-red-100 border-red-400 text-red-700";
    } else {
        // No records found, proceed with deletion
        $delete_stmt = $db->prepare("DELETE FROM pets WHERE id = :pet_id");
        $delete_stmt->bindParam(':pet_id', $pet_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['message'] = "Pet deleted successfully.";
            $_SESSION['message_class'] = "bg-green-100 border-green-400 text-green-700";
        } else {
            $_SESSION['message'] = "Error deleting pet.";
            $_SESSION['message_class'] = "bg-red-100 border-red-400 text-red-700";
        }
    }
    
    // Redirect to refresh the page and avoid resubmission
    header("Location: pets.php");
    exit;
}

// Include header
include_once '../includes/admin_header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manage Pets</h1>
        <a href="add_pet.php" class="bg-violet-600 hover:bg-violet-700 text-white py-2 px-4 rounded">
            <i class="fas fa-plus mr-2"></i> Add New Pet
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter and Search Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-4">
            <div class="w-full md:w-1/3">
                <label for="search" class="block text-gray-700 text-sm font-bold mb-2">Search</label>
                <input type="text" name="search" id="search" placeholder="Search by name, breed, or owner" 
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="w-full md:w-1/3">
                <label for="species" class="block text-gray-700 text-sm font-bold mb-2">Filter by Species</label>
                <select name="species" id="species" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Species</option>
                    <?php foreach ($species_list as $species): ?>
                        <option value="<?php echo htmlspecialchars($species); ?>" <?php echo ($species === $species_filter) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($species); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-full md:w-1/3 flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded w-full md:w-auto">
                    <i class="fas fa-search mr-2"></i> Apply Filters
                </button>
                <a href="pets.php" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                    <i class="fas fa-undo mr-2"></i> Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Pets List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Breed</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($pets) > 0): ?>
                        <?php foreach ($pets as $pet): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pet['name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700"><?php echo htmlspecialchars($pet['species']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700"><?php echo htmlspecialchars($pet['breed']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        <?php 
                                        if (!empty($pet['date_of_birth'])) {
                                            $dob = new DateTime($pet['date_of_birth']);
                                            $now = new DateTime();
                                            $age = $now->diff($dob);
                                            echo $age->y . " years";
                                        } else {
                                            echo "Unknown";
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_pet.php?id=<?php echo $pet['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="edit_pet.php?id=<?php echo $pet['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="add_medical_record.php?pet_id=<?php echo $pet['id']; ?>" class="text-violet-600 hover:text-violet-900 mr-3">
                                        <i class="fas fa-notes-medical"></i> Medical
                                    </a>
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="inline-block">
                                        <input type="hidden" name="pet_id" value="<?php echo $pet['id']; ?>">
                                        <button type="submit" name="delete_pet" class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Are you sure you want to delete this pet? This action cannot be undone.')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                No pets found. <?php echo !empty($search) || !empty($species_filter) ? 'Try adjusting your search or filter.' : ''; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <!-- Pagination -->
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                        <span class="font-medium"><?php echo min($offset + $items_per_page, $total_pets); ?></span> of 
                        <span class="font-medium"><?php echo $total_pets; ?></span> pets
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&species=<?php echo urlencode($species_filter); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&species=<?php echo urlencode($species_filter); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 
                                     <?php echo $i === $page ? 'bg-violet-100 text-violet-800' : 'bg-white text-gray-700'; ?> 
                                     hover:bg-gray-50 text-sm font-medium">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&species=<?php echo urlencode($species_filter); ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
