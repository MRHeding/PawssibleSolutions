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
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$order = isset($_GET['order']) ? $_GET['order'] : 'asc';
$low_stock = isset($_GET['low_stock']) ? true : false;

// Get unique categories for filter
$categories_query = "SELECT DISTINCT category FROM inventory ORDER BY category";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

// Build the query
$query = "SELECT * FROM inventory WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR description LIKE :search OR supplier LIKE :search)";
}

if (!empty($category_filter)) {
    $query .= " AND category = :category";
}

if ($low_stock) {
    $query .= " AND quantity <= reorder_level";
}

// Add sorting
switch ($sort) {
    case 'name':
        $query .= " ORDER BY name " . $order;
        break;
    case 'category':
        $query .= " ORDER BY category " . $order . ", name ASC";
        break;
    case 'quantity':
        $query .= " ORDER BY quantity " . $order;
        break;
    case 'price':
        $query .= " ORDER BY unit_price " . $order;
        break;
    case 'expiry':
        $query .= " ORDER BY CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date " . $order;
        break;
    default:
        $query .= " ORDER BY name " . $order;
        break;
}

// Prepare and execute the query
$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

if (!empty($category_filter)) {
    $stmt->bindParam(':category', $category_filter);
}

$stmt->execute();

// Count low stock items for notification
$low_stock_query = "SELECT COUNT(*) FROM inventory WHERE quantity <= reorder_level";
$low_stock_stmt = $db->prepare($low_stock_query);
$low_stock_stmt->execute();
$low_stock_count = $low_stock_stmt->fetchColumn();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Inventory Management</h1>
        <p class="text-white text-opacity-90 mt-2">Manage clinic supplies, medications, and products</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Search and filter section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between gap-4">
            <form action="" method="get" class="w-full md:w-1/2">
                <div class="flex">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search items..." class="shadow appearance-none border rounded-l w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-r">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <div class="flex items-center gap-2">
                <label for="category" class="text-gray-600">Filter by Category:</label>
                <select name="category" id="category" onchange="updateFilters()" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php if($category_filter === $category) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center">
                <input type="checkbox" id="low_stock" name="low_stock" <?php if($low_stock) echo 'checked'; ?> onchange="updateFilters()" class="mr-2">
                <label for="low_stock" class="text-gray-600">Low Stock Items (<?php echo $low_stock_count; ?>)</label>
            </div>
        </div>
        
        <div class="flex justify-between mt-4 items-center">
            <div class="flex items-center">
                <span class="mr-2 text-gray-600">Sort by:</span>
                <div class="relative">
                    <select id="sort-select" onchange="updateSort(this.value)" class="block appearance-none bg-white border border-gray-300 hover:border-gray-400 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline">
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="category" <?php echo $sort === 'category' ? 'selected' : ''; ?>>Category</option>
                        <option value="quantity" <?php echo $sort === 'quantity' ? 'selected' : ''; ?>>Quantity</option>
                        <option value="price" <?php echo $sort === 'price' ? 'selected' : ''; ?>>Unit Price</option>
                        <option value="expiry" <?php echo $sort === 'expiry' ? 'selected' : ''; ?>>Expiry Date</option>
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
            
            <div>
                <?php if (!empty($search) || !empty($category_filter) || $low_stock): ?>
                    <a href="inventory.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-times-circle mr-1"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add New Item Button -->
    <div class="flex justify-between mb-6">
        <div>
            <?php if ($low_stock_count > 0 && !$low_stock): ?>
                <a href="?low_stock=1" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i> View Low Stock Items (<?php echo $low_stock_count; ?>)
                </a>
            <?php endif; ?>
        </div>
        <div>
            <a href="add_inventory.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                <i class="fas fa-plus mr-2"></i> Add New Item
            </a>
        </div>
    </div>
    
    <!-- Inventory Items List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($item = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="<?php echo ($item['quantity'] <= $item['reorder_level']) ? 'bg-yellow-50' : ''; ?> hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <?php if (!empty($item['description'])): ?>
                                        <div class="text-xs text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($item['description']); ?>">
                                            <?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        switch($item['category']) {
                                            case 'Medication':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'Vaccine':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Medical Supply':
                                                echo 'bg-purple-100 text-purple-800';
                                                break;
                                            case 'Food':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-1" title="Low Stock"></i>
                                        <?php endif; ?>
                                        <span class="text-sm <?php echo ($item['quantity'] <= $item['reorder_level']) ? 'text-yellow-700 font-medium' : 'text-gray-900'; ?>">
                                            <?php echo $item['quantity'] . ' ' . htmlspecialchars($item['unit']); ?>
                                        </span>
                                    </div>
                                    <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                                        <div class="text-xs text-gray-500">Reorder Level: <?php echo $item['reorder_level']; ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">$<?php echo number_format($item['unit_price'], 2); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['supplier'] ?: 'N/A'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($item['expiry_date'])): ?>
                                        <div class="text-sm 
                                            <?php 
                                            $expiry = new DateTime($item['expiry_date']);
                                            $now = new DateTime();
                                            $diff = $now->diff($expiry);
                                            
                                            if ($expiry < $now) {
                                                echo 'text-red-600 font-medium';
                                            } elseif ($diff->days < 30) {
                                                echo 'text-yellow-600';
                                            } else {
                                                echo 'text-gray-900';
                                            }
                                            ?>">
                                            <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?>
                                            
                                            <?php if ($expiry < $now): ?>
                                                <span class="text-red-600 text-xs font-medium">(Expired)</span>
                                            <?php elseif ($diff->days < 30): ?>
                                                <span class="text-yellow-600 text-xs">(Soon)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-500">N/A</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="edit_inventory.php?id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')" class="text-red-600 hover:text-red-900">
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
                <i class="fas fa-box-open text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Inventory Items Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($search) || !empty($category_filter)): ?>
                    No items match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    There are no items in your inventory yet.
                <?php endif; ?>
            </p>
            <a href="add_inventory.php" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded transition">
                Add Your First Item
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal (Hidden by Default) -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">Confirm Deletion</h3>
        <p class="mb-6">Are you sure you want to delete <span id="itemName" class="font-semibold"></span>? This action cannot be undone.</p>
        <div class="flex justify-end">
            <button onclick="closeDeleteModal()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded mr-2">
                Cancel
            </button>
            <form action="delete_inventory.php" method="post" class="inline">
                <input type="hidden" name="item_id" id="delete_item_id">
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function updateFilters() {
        const category = document.getElementById('category').value;
        const lowStock = document.getElementById('low_stock').checked;
        
        const urlParams = new URLSearchParams(window.location.search);
        
        if (category) {
            urlParams.set('category', category);
        } else {
            urlParams.delete('category');
        }
        
        if (lowStock) {
            urlParams.set('low_stock', 1);
        } else {
            urlParams.delete('low_stock');
        }
        
        window.location.search = urlParams.toString();
    }
    
    function updateSort(value) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('sort', value);
        window.location.search = urlParams.toString();
    }
    
    function toggleOrder() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentOrder = urlParams.get('order') || 'asc';
        urlParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
        window.location.search = urlParams.toString();
    }
    
    function confirmDelete(itemId, itemName) {
        document.getElementById('itemName').textContent = itemName;
        document.getElementById('delete_item_id').value = itemId;
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
