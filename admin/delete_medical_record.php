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

// Check if record ID is provided via GET parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Medical record ID is required.";
    header("Location: medical_records.php");
    exit;
}

$record_id = intval($_GET['id']);

try {
    // First, get the medical record details to verify it exists
    $check_query = "SELECT mr.id, mr.record_date, p.name as pet_name, p.species, 
                   CONCAT(u.first_name, ' ', u.last_name) as owner_name
                   FROM medical_records mr
                   JOIN pets p ON mr.pet_id = p.id
                   JOIN users u ON p.owner_id = u.id
                   WHERE mr.id = :record_id";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':record_id', $record_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        $_SESSION['error'] = "Medical record not found.";
        header("Location: medical_records.php");
        exit;
    }
    
    $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle the deletion if confirmed
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        // Delete the medical record
        $delete_query = "DELETE FROM medical_records WHERE id = :record_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':record_id', $record_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Medical record for " . htmlspecialchars($record['pet_name']) . " has been successfully deleted.";
            header("Location: medical_records.php");
            exit;
        } else {
            $error = "Failed to delete the medical record. Please try again.";
        }
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-red-600 to-red-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Delete Medical Record</h1>
            <a href="medical_records.php" class="text-white hover:text-red-100">
                <i class="fas fa-arrow-left mr-2"></i> Back to Medical Records
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
                <a href="medical_records.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                    Return to Medical Records
                </a>
            </div>
        <?php else: ?>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Delete Medical Record</h3>
                
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="text-left">
                        <p class="text-sm text-gray-600 mb-2"><strong>Pet:</strong> <?php echo htmlspecialchars($record['pet_name']); ?> (<?php echo htmlspecialchars($record['species']); ?>)</p>
                        <p class="text-sm text-gray-600 mb-2"><strong>Owner:</strong> <?php echo htmlspecialchars($record['owner_name']); ?></p>
                        <p class="text-sm text-gray-600"><strong>Record Date:</strong> <?php echo date('F d, Y', strtotime($record['record_date'])); ?></p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <p class="text-sm text-gray-500 mb-6">
                        Are you sure you want to delete this medical record? This action cannot be undone.
                    </p>
                    
                    <div class="flex justify-center space-x-4">
                        <a href="medical_records.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                            Cancel
                        </a>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?id=' . $record_id); ?>" method="post" class="inline">
                            <button type="submit" name="confirm_delete" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                                Delete Record
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>