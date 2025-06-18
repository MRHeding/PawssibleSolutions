<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$success_message = '';
$error_message = '';
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$specialization = '';
$license_number = '';
$years_of_experience = '';
$bio = '';
$custom_password = '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $license_number = trim($_POST['license_number']);
    $years_of_experience = trim($_POST['years_of_experience']);
    $bio = trim($_POST['bio']);
    $custom_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Generate a username from first name and last name
    $username = strtolower(substr($first_name, 0, 1) . $last_name);
    $username = preg_replace('/[^a-z0-9]/', '', $username); // Remove special characters
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "First name, last name and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (empty($custom_password)) {
        $error_message = "Password is required.";
    } elseif (strlen($custom_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($custom_password !== $confirm_password) {
        $error_message = "Password and confirm password do not match.";
    } elseif (!empty($years_of_experience) && !is_numeric($years_of_experience)) {
        $error_message = "Years of experience must be a number.";
    } else {
        try {
            // Begin transaction
            $db->beginTransaction();
            
            // First check if email already exists
            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                $error_message = "Email already exists in the system.";
            } else {
                // Check if username already exists, append number if needed
                $username_exists = true;
                $username_counter = 1;
                $original_username = $username;
                
                while ($username_exists) {
                    $check_username_query = "SELECT id FROM users WHERE username = :username";
                    $check_username_stmt = $db->prepare($check_username_query);
                    $check_username_stmt->bindParam(':username', $username);
                    $check_username_stmt->execute();
                    
                    if ($check_username_stmt->rowCount() > 0) {
                        // Username exists, append counter and try again
                        $username = $original_username . $username_counter;
                        $username_counter++;
                    } else {
                        $username_exists = false;
                    }
                }
                
                // Hash the custom password
                $hashed_password = password_hash($custom_password, PASSWORD_DEFAULT);
                
                // 1. Create user account with username field
                $user_query = "INSERT INTO users (first_name, last_name, username, email, password, phone, role, created_at) 
                              VALUES (:first_name, :last_name, :username, :email, :password, :phone, 'vet', NOW())";
                $user_stmt = $db->prepare($user_query);
                $user_stmt->bindParam(':first_name', $first_name);
                $user_stmt->bindParam(':last_name', $last_name);
                $user_stmt->bindParam(':username', $username);
                $user_stmt->bindParam(':email', $email);
                $user_stmt->bindParam(':password', $hashed_password);
                $user_stmt->bindParam(':phone', $phone);
                $user_stmt->execute();
                
                $user_id = $db->lastInsertId();
                
                // 2. Create vet profile with new fields
                $vet_query = "INSERT INTO vets (user_id, specialization, license_number, years_of_experience, bio) 
                             VALUES (:user_id, :specialization, :license_number, :years_of_experience, :bio)";
                $vet_stmt = $db->prepare($vet_query);
                $vet_stmt->bindParam(':user_id', $user_id);
                $vet_stmt->bindParam(':specialization', $specialization);
                $vet_stmt->bindParam(':license_number', $license_number);
                $vet_stmt->bindParam(':years_of_experience', $years_of_experience);
                $vet_stmt->bindParam(':bio', $bio);
                $vet_stmt->execute();
                
                // Commit transaction
                $db->commit();
                
                $success_message = "Veterinarian added successfully!";
                
                // Clear form but keep password for display in success modal
                $success_first_name = $first_name;
                $success_last_name = $last_name;
                $success_email = $email;
                $success_username = $username;
                $success_license_number = $license_number;
                $success_specialization = $specialization;
                $success_password = $custom_password; // Keep for display only
                
                // Clear form variables
                $first_name = '';
                $last_name = '';
                $email = '';
                $phone = '';
                $specialization = '';
                $license_number = '';
                $years_of_experience = '';
                $bio = '';
                $custom_password = '';
            }
        } catch (PDOException $e) {
            // Roll back transaction on error
            $db->rollBack();
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Add New Veterinarian</h1>
        <p class="text-white text-opacity-90 mt-2">Create a new veterinarian account</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6 max-w-3xl mx-auto">
        <?php if (!empty($success_message)): ?>
            <!-- Success Modal Popup for Veterinarian Account -->
            <div id="vetSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <div class="flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mx-auto">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4 text-center">Veterinarian Account Created Successfully!</h3>
                        
                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-800 mb-3">Account Information</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Name:</span>
                                    <span class="text-gray-900">Dr. <?php echo isset($success_first_name) ? htmlspecialchars($success_first_name . ' ' . $success_last_name) : ''; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Email:</span>
                                    <span class="text-gray-900"><?php echo isset($success_email) ? htmlspecialchars($success_email) : ''; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Username:</span>
                                    <span class="text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded"><?php echo isset($success_username) ? htmlspecialchars($success_username) : ''; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Password:</span>
                                    <span class="text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded"><?php echo isset($success_password) ? htmlspecialchars($success_password) : ''; ?></span>
                                </div>
                                <?php if (isset($success_license_number) && !empty($success_license_number)): ?>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">License Number:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($success_license_number); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (isset($success_specialization) && !empty($success_specialization)): ?>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Specialization:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($success_specialization); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="font-semibold text-yellow-800 mb-2">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Important Instructions
                            </h4>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• Please share the login credentials securely with the veterinarian</li>
                                <li>• The veterinarian can change their password anytime from their profile</li>
                                <li>• They can access the system at: <span class="font-mono"><?php echo $_SERVER['HTTP_HOST']; ?>/vet/</span></li>
                                <li>• Account role: <strong>Veterinarian</strong></li>
                            </ul>
                        </div>
                        
                        <div class="items-center px-4 py-3 mt-6">
                            <button id="closeVetSuccessModal" class="px-6 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                                <i class="fas fa-check mr-2"></i>I've Noted the Account Information
                            </button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <button id="copyCredentials" class="text-sm text-blue-600 hover:text-blue-800 underline">
                                <i class="fas fa-copy mr-1"></i>Copy Credentials to Clipboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="first_name" class="block text-gray-700 font-medium mb-2">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="last_name" class="block text-gray-700 font-medium mb-2">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="email" class="block text-gray-700 font-medium mb-2">Email Address *</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <!-- Password Fields -->
                <div>
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password *</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($custom_password); ?>" 
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10" 
                               required minlength="6" placeholder="Enter password (min. 6 characters)">
                        <button type="button" onclick="togglePassword('password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password *</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" 
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10" 
                               required minlength="6" placeholder="Confirm password">
                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div>
                    <label for="license_number" class="block text-gray-700 font-medium mb-2">License Number</label>
                    <input type="text" id="license_number" name="license_number" value="<?php echo htmlspecialchars($license_number); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="e.g. VL12345">
                </div>
                
                <div>
                    <label for="years_of_experience" class="block text-gray-700 font-medium mb-2">Years of Experience</label>
                    <input type="number" id="years_of_experience" name="years_of_experience" min="0" max="70" 
                           value="<?php echo htmlspecialchars($years_of_experience); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="md:col-span-2">
                    <label for="specialization" class="block text-gray-700 font-medium mb-2">Specialties/Areas of Expertise</label>
                    <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($specialization); ?>" 
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="e.g. Cardiology, Dermatology, Surgery">
                </div>
                
                <div class="md:col-span-2">
                    <label for="bio" class="block text-gray-700 font-medium mb-2">Professional Bio</label>
                    <textarea id="bio" name="bio" rows="4" 
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Password Requirements
                    </h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Minimum 6 characters</li>
                        <li>• Use a strong, secure password</li>
                        <li>• The veterinarian can change this password later from their profile</li>
                    </ul>
                </div>
                
                <div class="flex items-center justify-between">
                    <a href="vets.php" class="text-blue-600 hover:text-blue-800">Cancel and return to vet list</a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                        Add Veterinarian
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Modal functionality for veterinarian account creation
document.addEventListener('DOMContentLoaded', function() {
    const vetSuccessModal = document.getElementById('vetSuccessModal');
    const closeVetSuccessModal = document.getElementById('closeVetSuccessModal');
    const copyCredentials = document.getElementById('copyCredentials');
    
    // Close modal functionality
    if (closeVetSuccessModal && vetSuccessModal) {
        closeVetSuccessModal.addEventListener('click', function() {
            vetSuccessModal.style.display = 'none';
        });
        
        // Prevent closing modal by clicking outside or pressing Escape
        // This ensures admin must manually acknowledge the information
        vetSuccessModal.addEventListener('click', function(e) {
            // Do not close when clicking outside - admin must click the button
            e.stopPropagation();
        });
        
        document.addEventListener('keydown', function(e) {
            // Do not close with Escape key - admin must click the button
            if (e.key === 'Escape') {
                e.preventDefault();
            }
        });
    }
    
    // Copy credentials to clipboard functionality
    if (copyCredentials) {
        copyCredentials.addEventListener('click', function() {
            <?php if (!empty($success_message) && isset($success_username)): ?>
            const credentials = `Veterinarian Account Details:
Name: Dr. <?php echo isset($success_first_name) ? htmlspecialchars($success_first_name . ' ' . $success_last_name) : ''; ?>
Email: <?php echo isset($success_email) ? htmlspecialchars($success_email) : ''; ?>
Username: <?php echo isset($success_username) ? htmlspecialchars($success_username) : ''; ?>
Password: <?php echo isset($success_password) ? htmlspecialchars($success_password) : ''; ?>
Access URL: <?php echo $_SERVER['HTTP_HOST']; ?>/vet/
<?php if (isset($success_license_number) && !empty($success_license_number)): ?>License Number: <?php echo htmlspecialchars($success_license_number); ?><?php endif; ?>
<?php if (isset($success_specialization) && !empty($success_specialization)): ?>
Specialization: <?php echo htmlspecialchars($success_specialization); ?><?php endif; ?>

Password can be changed from profile settings.`;
            
            // Copy to clipboard
            navigator.clipboard.writeText(credentials).then(function() {
                // Change button text temporarily to show success
                const originalText = copyCredentials.innerHTML;
                copyCredentials.innerHTML = '<i class="fas fa-check mr-1"></i>Copied to Clipboard!';
                copyCredentials.className = 'text-sm text-green-600 hover:text-green-800 underline';
                
                setTimeout(function() {
                    copyCredentials.innerHTML = originalText;
                    copyCredentials.className = 'text-sm text-blue-600 hover:text-blue-800 underline';
                }, 2000);
            }).catch(function(err) {
                alert('Failed to copy credentials to clipboard. Please copy manually.');
                console.error('Copy failed: ', err);
            });
            <?php endif; ?>
        });
    }
});
</script>
