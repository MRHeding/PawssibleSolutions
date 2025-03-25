<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$message = '';
$messageClass = '';
$user = null;

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $message = "User not found";
    $messageClass = "bg-red-100 border-red-400 text-red-700";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate input
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if email already exists (but exclude current user)
    if (!empty($email)) {
        $check_email = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $email_stmt = $db->prepare($check_email);
        $email_stmt->bindParam(':email', $email);
        $email_stmt->bindParam(':user_id', $user_id);
        $email_stmt->execute();
        
        if ($email_stmt->rowCount() > 0) {
            $errors[] = "Email already in use by another account";
        }
    }
    
    if (empty($errors)) {
        // Update user profile
        $update_query = "UPDATE users SET 
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        updated_at = NOW()
                        WHERE id = :user_id";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':first_name', $first_name);
        $update_stmt->bindParam(':last_name', $last_name);
        $update_stmt->bindParam(':email', $email);
        $update_stmt->bindParam(':phone', $phone);
        $update_stmt->bindParam(':user_id', $user_id);
        
        if ($update_stmt->execute()) {
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            $message = "Profile updated successfully";
            $messageClass = "bg-green-100 border-green-400 text-green-700";
            
            // Refresh user data
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Error updating profile";
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Get form data
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    $errors = [];
    if (empty($current_password)) $errors[] = "Current password is required";
    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "New password must be at least 8 characters";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }
    
    if (empty($errors)) {
        // Verify current password
        if (password_verify($current_password, $user['password']) || 
            ($user['role'] === 'admin' && $current_password === $user['password'])) {
            
            // Hash new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_query = "UPDATE users SET 
                            password = :password,
                            updated_at = NOW()
                            WHERE id = :user_id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':password', $password_hash);
            $update_stmt->bindParam(':user_id', $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Password changed successfully";
                $messageClass = "bg-green-100 border-green-400 text-green-700";
            } else {
                $message = "Error changing password";
                $messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        } else {
            $message = "Current password is incorrect";
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    }
}

// Include header
include_once '../includes/admin_header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Profile</h1>
        <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
            Back to Dashboard
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($user): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Profile Information -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold">Profile Information</h2>
                </div>
                
                <div class="p-6">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="first_name" class="block text-gray-700 text-sm font-bold mb-2">First Name</label>
                                <input type="text" name="first_name" id="first_name" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                            
                            <div>
                                <label for="last_name" class="block text-gray-700 text-sm font-bold mb-2">Last Name</label>
                                <input type="text" name="last_name" id="last_name" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" name="email" id="email" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                            <input type="text" name="phone" id="phone" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                            <input type="text" id="username" class="bg-gray-100 shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <p class="text-sm text-gray-500 mt-1">Username cannot be changed</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                            <input type="text" id="role" class="bg-gray-100 shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_profile" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold">Change Password</h2>
                </div>
                
                <div class="p-6">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-4">
                            <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="change_password" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden md:col-span-2">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold">Account Information</h2>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 mb-1">Account Created</h3>
                            <p class="text-gray-700"><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 mb-1">Last Updated</h3>
                            <p class="text-gray-700"><?php echo !empty($user['updated_at']) ? date('F j, Y, g:i a', strtotime($user['updated_at'])) : 'Never'; ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-sm font-bold text-gray-700 mb-1">Last Login</h3>
                            <p class="text-gray-700"><?php echo !empty($user['last_login']) ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'Never'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            User information not available.
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
