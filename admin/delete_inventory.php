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

// Check if item_id is provided via POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item_id'])) {
    $item_id = (int)$_POST['item_id'];
    
    // First, get the item details for the success message
    $get_item_query = "SELECT name FROM inventory WHERE id = :id";
    $get_item_stmt = $db->prepare($get_item_query);
    $get_item_stmt->bindParam(':id', $item_id);
    $get_item_stmt->execute();
    
    if ($get_item_stmt->rowCount() > 0) {
        $item = $get_item_stmt->fetch(PDO::FETCH_ASSOC);
        $item_name = $item['name'];
        
        // Delete the item
        $delete_query = "DELETE FROM inventory WHERE id = :id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':id', $item_id);
        
        try {
            if ($delete_stmt->execute()) {
                $success = "Item '" . htmlspecialchars($item_name) . "' has been successfully deleted.";
            } else {
                $error = "Failed to delete the item. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Item not found.";
    }
} else {
    // Redirect if accessed directly without POST data
    header("Location: inventory.php");
    exit;
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Delete Inventory Item</h1>
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
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $error; ?></span>
                </div>
            </div>
            <div class="flex justify-center mt-6">
                <a href="inventory.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    Return to Inventory
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo $success; ?></span>
                </div>
            </div>
            <div class="text-center">
                <p class="text-gray-600 mb-6">The item has been removed from inventory.</p>
                <a href="inventory.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    Return to Inventory
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>