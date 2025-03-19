<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if client ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$client_id = $_GET['id'];
$error = "";
$success = "";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if client exists and is a client
$client_query = "SELECT * FROM users WHERE id = :client_id AND role = 'client'";
$client_stmt = $db->prepare($client_query);
$client_stmt->bindParam(':client_id', $client_id);
$client_stmt->execute();

if ($client_stmt->rowCount() == 0) {
    header("Location: clients.php");
    exit;
}

$client = $client_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $confirm_password = !empty($_POST['confirm_password']) ? trim($_POST['confirm_password']) : null;
    
    // Validation
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email)) {
        $error = "Username, first name, last name, and email are required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($password !== null && $password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($password !== null && strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Check if username exists and is not the current client
        $check_query = "SELECT id FROM users WHERE username = :username AND id != :client_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->bindParam(':client_id', $client_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Username already exists. Please choose another username.";
        } else {
            // Check if email exists and is not the current client
            $check_query = "SELECT id FROM users WHERE email = :email AND id != :client_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->bindParam(':client_id', $client_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error = "Email already exists. Please use another email address.";
            } else {
                try {
                    // Update the client information
                    if ($password !== null) {
                        // Update with new password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_query = "UPDATE users SET 
                                        username = :username,
                                        password = :password,
                                        first_name = :first_name,
                                        last_name = :last_name,
                                        email = :email,
                                        phone = :phone
                                        WHERE id = :client_id";
                        $update_stmt = $db->prepare($update_query);
                        $update_stmt->bindParam(':password', $hashed_password);
                    } else {
                        // Update without changing password
                        $update_query = "UPDATE users SET 
                                        username = :username,
                                        first_name = :first_name,
                                        last_name = :last_name,
                                        email = :email,
                                        phone = :phone
                                        WHERE id = :client_id";
                        $update_stmt = $db->prepare($update_query);
                    }
                    
                    $update_stmt->bindParam(':username', $username);
                    $update_stmt->bindParam(':first_name', $first_name);
                    $update_stmt->bindParam(':last_name', $last_name);
                    $update_stmt->bindParam(':email', $email);
                    $update_stmt->bindParam(':phone', $phone);
                    $update_stmt->bindParam(':client_id', $client_id);
                    
                    if ($update_stmt->execute()) {
                        $success = "Client information updated successfully!";
                        
                        // Refresh client data
                        $client_stmt->execute();
                        $client = $client_stmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $error = "Something went wrong. Please try again.";
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Edit Client</h1>
            <a href="view_client.php?id=<?php echo $client_id; ?>" class="text-white hover:text-indigo-100">
                <i class="fas fa-arrow-left mr-2"></i> Back to Client Details
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
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $client_id); ?>" method="post">
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Personal Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($client['first_name']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($client['last_name']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold mb-4">Account Information</h2>
                
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username *</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($client['username']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                </div>
                
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-4">Password</h2>
                <p class="text-sm text-gray-600 mb-4">Leave blank to keep current password</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                        <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-between">
                <a href="view_client.php?id=<?php echo $client_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancel
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Update Client
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
