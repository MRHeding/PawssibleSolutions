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
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'desc';

// Build the query
$query = "SELECT u.*, COUNT(p.id) as pet_count 
          FROM users u 
          LEFT JOIN pets p ON u.id = p.owner_id 
          WHERE u.role = 'client'";

if (!empty($search)) {
    $query .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
}

$query .= " GROUP BY u.id";

// Add sorting
switch ($sort) {
    case 'name':
        $query .= " ORDER BY u.first_name " . $order . ", u.last_name " . $order;
        break;
    case 'email':
        $query .= " ORDER BY u.email " . $order;
        break;
    case 'pet_count':
        $query .= " ORDER BY pet_count " . $order;
        break;
    case 'created_at':
    default:
        $query .= " ORDER BY u.created_at " . $order;
        break;
}

// Prepare and execute the query
$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Manage Clients</h1>
        <p class="text-white text-opacity-90 mt-2">View and manage client accounts</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $_SESSION['success']; ?></span>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span><?php echo $_SESSION['error']; ?></span>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Search and filter section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between">
            <form action="" method="get" class="w-full md:w-1/2 mb-4 md:mb-0">
                <div class="flex">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email or phone..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            <div class="flex items-center">
                <span class="mr-2 text-gray-600">Sort by:</span>
                <div class="relative">
                    <select id="sort-select" onchange="updateSort(this.value)" class="block appearance-none w-full bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline">
                        <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Registration Date</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="pet_count" <?php echo $sort === 'pet_count' ? 'selected' : ''; ?>>Pet Count</option>
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

    <!-- Add New Client Button -->
    <div class="flex justify-end mb-6">
        <a href="add_client.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Client
        </a>
    </div>
    
    <!-- Clients List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pets</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($client = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center font-bold">
                                            <?php echo strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                @<?php echo htmlspecialchars($client['username']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($client['email']); ?></div>
                                    <?php if (!empty($client['phone'])): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($client['phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium"><?php echo $client['pet_count']; ?> pet(s)</div>
                                    <?php if ($client['pet_count'] > 0): ?>
                                        <a href="../admin/client_pets.php?owner_id=<?php echo $client['id']; ?>" class="text-xs text-indigo-600 hover:text-indigo-900">View Pets</a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo date('M d, Y', strtotime($client['created_at'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo date('h:i A', strtotime($client['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view_client.php?id=<?php echo $client['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="text-green-600 hover:text-green-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
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
                <i class="fas fa-users text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Clients Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($search)): ?>
                    No clients match your search criteria. Try different search terms.
                <?php else: ?>
                    There are no clients registered in the system yet.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal (Hidden by Default) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Confirm Client Deletion</h3>
        <p class="mb-6">Are you sure you want to delete <span id="clientName" class="font-semibold"></span>? This action cannot be undone and will delete all associated pets, appointments, and medical records.</p>
        <div class="flex justify-end">
            <button onclick="closeDeleteModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                Cancel
            </button>
            <form action="delete_client.php" method="post" class="inline">
                <input type="hidden" name="client_id" id="delete_client_id">
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
        const currentOrder = urlParams.get('order') || 'desc';
        urlParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
        window.location.search = urlParams.toString();
    }
    
    // Delete confirmation functions
    function confirmDelete(clientId, clientName) {
        document.getElementById('clientName').textContent = clientName;
        document.getElementById('delete_client_id').value = clientId;
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
