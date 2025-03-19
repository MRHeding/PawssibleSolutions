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

// Set default filter and sorting
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'last_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$specialization = isset($_GET['specialization']) ? $_GET['specialization'] : '';

// Get specializations for filter
$spec_query = "SELECT DISTINCT specialization FROM vets WHERE specialization IS NOT NULL ORDER BY specialization";
$spec_stmt = $db->prepare($spec_query);
$spec_stmt->execute();
$specializations = $spec_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build the query
$query = "SELECT v.*, u.first_name, u.last_name, u.email, u.phone, u.last_login 
          FROM vets v
          JOIN users u ON v.user_id = u.id
          WHERE u.role = 'vet'";

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR v.specialization LIKE :search OR v.license_number LIKE :search)";
}

if (!empty($specialization)) {
    $query .= " AND v.specialization LIKE :specialization";
}

// Add sorting
switch ($sort) {
    case 'first_name':
        $query .= " ORDER BY u.first_name " . $order;
        break;
    case 'last_name':
        $query .= " ORDER BY u.last_name " . $order;
        break;
    case 'email':
        $query .= " ORDER BY u.email " . $order;
        break;
    case 'experience':
        $query .= " ORDER BY v.years_of_experience " . $order;
        break;
    case 'specialization':
        $query .= " ORDER BY v.specialization " . $order;
        break;
    default:
        $query .= " ORDER BY u.last_name " . $order;
        break;
}

// Prepare and execute the query
$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

if (!empty($specialization)) {
    $spec_param = '%' . $specialization . '%';
    $stmt->bindParam(':specialization', $spec_param);
}

$stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Manage Veterinarians</h1>
        <p class="text-white text-opacity-90 mt-2">View and manage veterinarian staff</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Search and filter section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between gap-4">
            <form action="" method="get" class="w-full md:w-1/2">
                <div class="flex">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, specialization..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <div class="flex items-center gap-2">
                <label for="specialization" class="text-gray-600">Filter by Specialization:</label>
                <select name="specialization" id="specialization" onchange="this.form.submit()" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Specializations</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?php echo htmlspecialchars($spec); ?>" <?php if($specialization === $spec) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($spec); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center">
                <span class="mr-2 text-gray-600">Sort by:</span>
                <div class="relative">
                    <select id="sort-select" onchange="updateSort(this.value)" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline">
                        <option value="last_name" <?php echo $sort === 'last_name' ? 'selected' : ''; ?>>Last Name</option>
                        <option value="first_name" <?php echo $sort === 'first_name' ? 'selected' : ''; ?>>First Name</option>
                        <option value="experience" <?php echo $sort === 'experience' ? 'selected' : ''; ?>>Experience</option>
                        <option value="specialization" <?php echo $sort === 'specialization' ? 'selected' : ''; ?>>Specialization</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-2">
                    <button onclick="toggleOrder()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded">
                        <i class="fas <?php echo $order === 'asc' ? 'fa-sort-up' : 'fa-sort-down'; ?>"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Vet Button -->
    <div class="flex justify-end mb-6">
        <a href="add_vet.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Veterinarian
        </a>
    </div>
    
    <!-- Vets List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($vet = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-indigo-100 p-6 flex items-center">
                        <div class="bg-indigo-600 text-white rounded-full h-16 w-16 flex items-center justify-center font-bold text-xl">
                            <?php echo strtoupper(substr($vet['first_name'], 0, 1) . substr($vet['last_name'], 0, 1)); ?>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold">Dr. <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?></h3>
                            <?php if (!empty($vet['specialization'])): ?>
                                <p class="text-gray-600"><?php echo htmlspecialchars($vet['specialization']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Contact Information</p>
                            <p class="text-gray-900"><?php echo htmlspecialchars($vet['email']); ?></p>
                            <?php if (!empty($vet['phone'])): ?>
                                <p class="text-gray-900"><?php echo htmlspecialchars($vet['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-500">License Number</p>
                                <p class="text-gray-900"><?php echo !empty($vet['license_number']) ? htmlspecialchars($vet['license_number']) : 'N/A'; ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Experience</p>
                                <p class="text-gray-900"><?php echo !empty($vet['years_of_experience']) ? htmlspecialchars($vet['years_of_experience']) . ' years' : 'N/A'; ?></p>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-200 flex justify-between">
                            <a href="view_vet.php?id=<?php echo $vet['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                <i class="fas fa-eye mr-1"></i> View Details
                            </a>
                            <div>
                                <a href="edit_vet.php?id=<?php echo $vet['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="#" onclick="confirmDelete(<?php echo $vet['id']; ?>, 'Dr. <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?>')" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-user-md text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Veterinarians Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($search) || !empty($specialization)): ?>
                    No veterinarians match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    There are no veterinarians registered in the system yet.
                <?php endif; ?>
            </p>
            <a href="add_vet.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded transition">
                Add Your First Veterinarian
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal (Hidden by Default) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Confirm Deletion</h3>
        <p class="mb-6">Are you sure you want to delete <span id="vetName" class="font-semibold"></span>? This action cannot be undone and will affect all associated appointments and medical records.</p>
        <div class="flex justify-end">
            <button onclick="closeDeleteModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                Cancel
            </button>
            <form action="delete_vet.php" method="post" class="inline">
                <input type="hidden" name="vet_id" id="delete_vet_id">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Sorting function
    function updateSort(value) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', value);
        window.location.search = urlParams.toString();
    }
    
    // Toggle order function
    function toggleOrder() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentOrder = urlParams.get('order') || 'asc';
        urlParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
        window.location.search = urlParams.toString();
    }
    
    // Delete confirmation functions
    function confirmDelete(vetId, vetName) {
        document.getElementById('vetName').textContent = vetName;
        document.getElementById('delete_vet_id').value = vetId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // Close modal if clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }
</script>

<?php include_once '../includes/admin_footer.php'; ?>
