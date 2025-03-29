<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get vet information
$query = "SELECT u.*, v.specialization, v.license_number, v.years_of_experience, v.bio 
          FROM users u
          JOIN vets v ON u.id = v.user_id
          WHERE u.id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$vet_info = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle personal information update
if (isset($_POST['update_personal'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_msg = "Name and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } else {
        try {
            $db->beginTransaction();
            
            // Check if email is already used by another user
            if ($email !== $vet_info['email']) {
                $check_email = "SELECT COUNT(*) as count FROM users WHERE email = :email AND id != :user_id";
                $check_stmt = $db->prepare($check_email);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->bindParam(':user_id', $user_id);
                $check_stmt->execute();
                $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    $error_msg = "This email address is already in use.";
                    $db->rollBack();
                } else {
                    // Update personal information
                    $update_query = "UPDATE users 
                                    SET first_name = :first_name, 
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
                    $update_stmt->execute();
                    
                    $db->commit();
                    
                    // Update session variables
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    
                    // Refresh vet info
                    $stmt->execute();
                    $vet_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $success_msg = "personal_updated";
                }
            } else {
                // Email hasn't changed, just update other fields
                $update_query = "UPDATE users 
                                SET first_name = :first_name, 
                                    last_name = :last_name, 
                                    phone = :phone,
                                    updated_at = NOW()
                                WHERE id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':first_name', $first_name);
                $update_stmt->bindParam(':last_name', $last_name);
                $update_stmt->bindParam(':phone', $phone);
                $update_stmt->bindParam(':user_id', $user_id);
                $update_stmt->execute();
                
                $db->commit();
                
                // Update session variables
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                
                // Refresh vet info
                $stmt->execute();
                $vet_info = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $success_msg = "personal_updated";
            }
            
        } catch (PDOException $e) {
            $db->rollBack();
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

// Handle professional information update
if (isset($_POST['update_professional'])) {
    $specialization = trim($_POST['specialization']);
    $license_number = trim($_POST['license_number']);
    $years_of_experience = intval($_POST['years_of_experience']);
    $bio = trim($_POST['bio']);
    
    try {
        // Update professional information
        $update_query = "UPDATE vets 
                        SET specialization = :specialization, 
                            license_number = :license_number, 
                            years_of_experience = :years_of_experience, 
                            bio = :bio
                        WHERE user_id = :user_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':specialization', $specialization);
        $update_stmt->bindParam(':license_number', $license_number);
        $update_stmt->bindParam(':years_of_experience', $years_of_experience);
        $update_stmt->bindParam(':bio', $bio);
        $update_stmt->bindParam(':user_id', $user_id);
        $update_stmt->execute();
        
        // Refresh vet info
        $stmt->execute();
        $vet_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success_msg = "professional_updated";
    } catch (PDOException $e) {
        $error_msg = "Database error: " . $e->getMessage();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_msg = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error_msg = "New password must be at least 8 characters long.";
    } else {
        // Verify current password
        if (password_verify($current_password, $vet_info['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            try {
                // Update the password
                $update_query = "UPDATE users SET password = :password WHERE id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':password', $hashed_password);
                $update_stmt->bindParam(':user_id', $user_id);
                $update_stmt->execute();
                
                $success_msg = "password_updated";
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Current password is incorrect.";
        }
    }
}

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">My Profile</h1>
        <p class="text-white text-opacity-90 mt-2">Update your personal and professional information</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($success_msg)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" role="alert">
            <?php if ($success_msg === 'personal_updated'): ?>
                <span class="font-bold">Success!</span> Your personal information has been updated.
            <?php elseif ($success_msg === 'professional_updated'): ?>
                <span class="font-bold">Success!</span> Your professional information has been updated.
            <?php elseif ($success_msg === 'password_updated'): ?>
                <span class="font-bold">Success!</span> Your password has been changed.
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_msg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="font-bold">Error:</span> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Personal Information Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Personal Information</h2>
            
            <form method="post" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($vet_info['first_name']); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($vet_info['last_name']); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($vet_info['email']); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($vet_info['phone'] ?? ''); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($vet_info['username']); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 bg-gray-50" readonly>
                        <p class="mt-1 text-xs text-gray-500">Username cannot be changed.</p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="update_personal" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Password Change Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Change Password</h2>
            
            <form method="post" action="">
                <div class="space-y-4 mb-6">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input type="password" id="current_password" name="current_password" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500" required>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="change_password" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-key mr-2"></i> Change Password
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Professional Information Section -->
        <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Professional Information</h2>
            
            <form method="post" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                        <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($vet_info['specialization'] ?? ''); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <p class="mt-1 text-xs text-gray-500">E.g., Cardiology, Dermatology, General Practice</p>
                    </div>
                    
                    <div>
                        <label for="license_number" class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                        <input type="text" id="license_number" name="license_number" value="<?php echo htmlspecialchars($vet_info['license_number'] ?? ''); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    
                    <div>
                        <label for="years_of_experience" class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label>
                        <input type="number" id="years_of_experience" name="years_of_experience" value="<?php echo htmlspecialchars($vet_info['years_of_experience'] ?? ''); ?>" 
                               class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500"
                               min="0" max="99">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Professional Bio</label>
                        <textarea id="bio" name="bio" rows="6" 
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500"><?php echo htmlspecialchars($vet_info['bio'] ?? ''); ?></textarea>
                        <p class="mt-1 text-xs text-gray-500">Tell clients about your background, expertise, and approach to veterinary care.</p>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="update_professional" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-save mr-2"></i> Save Professional Info
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Last Login Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Account Information</h2>
            
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Account Created</p>
                    <p class="font-medium"><?php echo date('F d, Y', strtotime($vet_info['created_at'])); ?></p>
                </div>
                
                <?php if (!empty($vet_info['last_login'])): ?>
                    <div>
                        <p class="text-sm text-gray-600">Last Login</p>
                        <p class="font-medium"><?php echo date('F d, Y \a\t h:i A', strtotime($vet_info['last_login'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <div>
                    <p class="text-sm text-gray-600">Account Type</p>
                    <p class="font-medium">Veterinarian</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
