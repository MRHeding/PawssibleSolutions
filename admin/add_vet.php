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
    $password = password_hash('Vet@'.date('Y'), PASSWORD_DEFAULT); // Default password
    
    // Generate a username from first name and last name
    $username = strtolower(substr($first_name, 0, 1) . $last_name);
    $username = preg_replace('/[^a-z0-9]/', '', $username); // Remove special characters
    
    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = "First name, last name and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
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
                
                // 1. Create user account with username field
                $user_query = "INSERT INTO users (first_name, last_name, username, email, password, phone, role, created_at) 
                              VALUES (:first_name, :last_name, :username, :email, :password, :phone, 'vet', NOW())";
                $user_stmt = $db->prepare($user_query);
                $user_stmt->bindParam(':first_name', $first_name);
                $user_stmt->bindParam(':last_name', $last_name);
                $user_stmt->bindParam(':username', $username);
                $user_stmt->bindParam(':email', $email);
                $user_stmt->bindParam(':password', $password);
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
                
                $success_message = "Veterinarian added successfully! Default password: Vet@" . date('Y') . " | Username: " . $username;
                
                // Clear form
                $first_name = '';
                $last_name = '';
                $email = '';
                $phone = '';
                $specialization = '';
                $license_number = '';
                $years_of_experience = '';
                $bio = '';
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
                                    <span class="text-gray-900">Dr. <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Email:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($email); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Username:</span>
                                    <span class="text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($username); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Default Password:</span>
                                    <span class="text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">Vet@<?php echo date('Y'); ?></span>
                                </div>
                                <?php if (!empty($license_number)): ?>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">License Number:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($license_number); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($specialization)): ?>
                                <div class="flex justify-between">
                                    <span class="font-medium text-gray-700">Specialization:</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($specialization); ?></span>
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
                                <li>• The veterinarian should change their password upon first login</li>
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
                <p class="text-sm text-gray-600 mb-4">
                    <strong>Note:</strong> A default password will be assigned to the new veterinarian account. 
                    The vet will be prompted to change it upon first login.
                </p>
                
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
            <?php if (!empty($success_message)): ?>
            const credentials = `Veterinarian Account Details:
Name: Dr. <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
Email: <?php echo htmlspecialchars($email); ?>
Username: <?php echo htmlspecialchars($username); ?>
Password: Vet@<?php echo date('Y'); ?>
Access URL: <?php echo $_SERVER['HTTP_HOST']; ?>/vet/
<?php if (!empty($license_number)): ?>License Number: <?php echo htmlspecialchars($license_number); ?><?php endif; ?>
<?php if (!empty($specialization)): ?>
Specialization: <?php echo htmlspecialchars($specialization); ?><?php endif; ?>

Please change password upon first login.`;
            
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
