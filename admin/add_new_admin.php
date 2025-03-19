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

// Admin credentials to add
$username = 'sherly';
$password = 'sherly^10';
$first_name = 'Sherly';
$last_name = 'Admin';
$email = 'sherly@petcare.com';
$phone = '123-456-7897';
$role = 'admin';

// Check if the admin already exists
$check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':username', $username);
$check_stmt->bindParam(':email', $email);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    $error = "Admin with username 'sherly' or email 'sherly@petcare.com' already exists.";
} else {
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert the new admin
    $query = "INSERT INTO users (username, password, first_name, last_name, email, phone, role) 
              VALUES (:username, :password, :first_name, :last_name, :email, :phone, :role)";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':role', $role);
    
    try {
        if ($stmt->execute()) {
            $success = "New administrator 'sherly' added successfully!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Add New Administrator</h1>
        <p class="text-white text-opacity-90 mt-2">Adding pre-configured admin account</p>
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
        
        <div class="flex items-center justify-between">
            <div>
                <?php if (!empty($success)): ?>
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-700 mb-2">Admin Credentials:</h3>
                        <ul class="list-disc pl-5 text-sm text-gray-600">
                            <li>Username: <?php echo $username; ?></li>
                            <li>Password: <?php echo $password; ?></li>
                            <li>Email: <?php echo $email; ?></li>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <a href="dashboard.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
