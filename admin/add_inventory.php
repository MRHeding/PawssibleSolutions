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

$error = "";
$success = "";

// Get existing categories for dropdown
$categories_query = "SELECT DISTINCT category FROM inventory ORDER BY category";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $quantity = (int)$_POST['quantity'];
    $unit = trim($_POST['unit']);
    $unit_price = (float)$_POST['unit_price'];
    $reorder_level = (int)$_POST['reorder_level'];
    $supplier = trim($_POST['supplier']);
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $location = trim($_POST['location']);
    
    // Category handling - if Other is selected, use the custom category
    if ($category === 'other' && !empty($_POST['custom_category'])) {
        $category = trim($_POST['custom_category']);
    }
    
    // Validation
    if (empty($name) || empty($category) || empty($unit)) {
        $error = "Name, category, and unit are required fields";
    } elseif ($quantity < 0) {
        $error = "Quantity cannot be negative";
    } elseif ($unit_price < 0) {
        $error = "Unit price cannot be negative";
    } elseif ($reorder_level < 0) {
        $error = "Reorder level cannot be negative";
    } else {
        // All validations passed, add the item
        $query = "INSERT INTO inventory (name, category, description, quantity, unit, unit_price, reorder_level, supplier, expiry_date, location) 
                  VALUES (:name, :category, :description, :quantity, :unit, :unit_price, :reorder_level, :supplier, :expiry_date, :location)";
                  
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':unit', $unit);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':reorder_level', $reorder_level);
        $stmt->bindParam(':supplier', $supplier);
        $stmt->bindParam(':expiry_date', $expiry_date);
        $stmt->bindParam(':location', $location);
        
        try {
            if ($stmt->execute()) {
                $success = "Item added successfully!";
                // Clear form fields
                $name = $description = $unit = $supplier = $location = "";
                $quantity = $reorder_level = 0;
                $unit_price = 0.00;
                $expiry_date = null;
            } else {
                $error = "Error adding item. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Add Inventory Item</h1>
            <a href="inventory.php" class="text-white hover:text-indigo-100">
                <i class="fas fa-arrow-left mr-2"></i> Back to Inventory
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Basic Information</h2>
                
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Item Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div class="mb-4">
                    <label for="category" class="block text-gray-700 text-sm font-bold mb-2">Category *</label>
                    <select id="category" name="category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="toggleCustomCategory()">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                        <option value="other">Other (specify)</option>
                    </select>
                </div>
                
                <div id="custom-category-container" class="mb-4 hidden">
                    <label for="custom_category" class="block text-gray-700 text-sm font-bold mb-2">Specify Category *</label>
                    <input type="text" id="custom_category" name="custom_category" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea id="description" name="description" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Quantity & Pricing</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="quantity" class="block text-gray-700 text-sm font-bold mb-2">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="0" value="<?php echo isset($quantity) ? htmlspecialchars($quantity) : '0'; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div>
                        <label for="unit" class="block text-gray-700 text-sm font-bold mb-2">Unit *</label>
                        <input type="text" id="unit" name="unit" placeholder="e.g., tablets, bottles, boxes" value="<?php echo isset($unit) ? htmlspecialchars($unit) : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div>
                        <label for="unit_price" class="block text-gray-700 text-sm font-bold mb-2">Unit Price ($) *</label>
                        <input type="number" id="unit_price" name="unit_price" min="0" step="0.01" value="<?php echo isset($unit_price) ? htmlspecialchars($unit_price) : '0.00'; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div>
                        <label for="reorder_level" class="block text-gray-700 text-sm font-bold mb-2">Reorder Level *</label>
                        <input type="number" id="reorder_level" name="reorder_level" min="0" value="<?php echo isset($reorder_level) ? htmlspecialchars($reorder_level) : '10'; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <p class="text-xs text-gray-500 mt-1">Minimum quantity before restocking</p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Additional Details</h2>
                
                <div class="mb-4">
                    <label for="supplier" class="block text-gray-700 text-sm font-bold mb-2">Supplier</label>
                    <input type="text" id="supplier" name="supplier" value="<?php echo isset($supplier) ? htmlspecialchars($supplier) : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div class="mb-4">
                    <label for="expiry_date" class="block text-gray-700 text-sm font-bold mb-2">Expiry Date</label>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?php echo isset($expiry_date) ? htmlspecialchars($expiry_date) : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <p class="text-xs text-gray-500 mt-1">Leave empty if not applicable</p>
                </div>
                
                <div class="mb-4">
                    <label for="location" class="block text-gray-700 text-sm font-bold mb-2">Storage Location</label>
                    <input type="text" id="location" name="location" placeholder="e.g., Cabinet A1, Refrigerator 2" value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <a href="inventory.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Add Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleCustomCategory() {
        const categorySelect = document.getElementById('category');
        const customCategoryContainer = document.getElementById('custom-category-container');
        const customCategoryInput = document.getElementById('custom_category');
        
        if (categorySelect.value === 'other') {
            customCategoryContainer.classList.remove('hidden');
            customCategoryInput.setAttribute('required', 'required');
        } else {
            customCategoryContainer.classList.add('hidden');
            customCategoryInput.removeAttribute('required');
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleCustomCategory();
    });
</script>

<?php include_once '../includes/admin_footer.php'; ?>
