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

// Fetch current settings
$query = "SELECT * FROM settings ORDER BY setting_key";
$stmt = $db->prepare($query);
$stmt->execute();
$settings = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = [
        'id' => $row['id'],
        'value' => $row['setting_value'],
        'description' => $row['description']
    ];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Begin transaction
        $db->beginTransaction();
        
        // Process each setting
        foreach ($_POST as $key => $value) {
            // Skip the submit button
            if ($key === 'submit') continue;
            
            // Check if this is a setting we track
            if (isset($settings[$key])) {
                $update_query = "UPDATE settings SET setting_value = :value WHERE setting_key = :key";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':value', $value);
                $update_stmt->bindParam(':key', $key);
                $update_stmt->execute();
                
                // Update our local settings array for display
                $settings[$key]['value'] = $value;
            }
        }
        
        // Commit transaction
        $db->commit();
        $success = "Settings updated successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        $error = "Error updating settings: " . $e->getMessage();
    }
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">System Settings</h1>
        <p class="text-white text-opacity-90 mt-2">Configure your clinic settings</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
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
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Clinic Information</h2>
                <div class="space-y-4">
                    <div>
                        <label for="clinic_name" class="block text-gray-700 text-sm font-bold mb-2">Clinic Name</label>
                        <input type="text" name="clinic_name" id="clinic_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($settings['clinic_name']) ? htmlspecialchars($settings['clinic_name']['value']) : ''; ?>">
                    </div>
                    
                    <div>
                        <label for="clinic_address" class="block text-gray-700 text-sm font-bold mb-2">Clinic Address</label>
                        <textarea name="clinic_address" id="clinic_address" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo isset($settings['clinic_address']) ? htmlspecialchars($settings['clinic_address']['value']) : ''; ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="clinic_phone" class="block text-gray-700 text-sm font-bold mb-2">Main Phone Number</label>
                            <input type="tel" name="clinic_phone" id="clinic_phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($settings['clinic_phone']) ? htmlspecialchars($settings['clinic_phone']['value']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label for="emergency_phone" class="block text-gray-700 text-sm font-bold mb-2">Emergency Phone Number</label>
                            <input type="tel" name="emergency_phone" id="emergency_phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($settings['emergency_phone']) ? htmlspecialchars($settings['emergency_phone']['value']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div>
                        <label for="clinic_email" class="block text-gray-700 text-sm font-bold mb-2">Email Address</label>
                        <input type="email" name="clinic_email" id="clinic_email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($settings['clinic_email']) ? htmlspecialchars($settings['clinic_email']['value']) : ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Business Hours</h2>
                <div>
                    <label for="business_hours" class="block text-gray-700 text-sm font-bold mb-2">Business Hours (JSON format)</label>
                    <p class="text-sm text-gray-500 mb-2">Format: {"monday":"8:00-18:00","tuesday":"8:00-18:00",...}</p>
                    <textarea name="business_hours" id="business_hours" rows="4" class="font-mono shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo isset($settings['business_hours']) ? htmlspecialchars($settings['business_hours']['value']) : ''; ?></textarea>
                </div>
            </div>
            
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold mb-4">Appointment Settings</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="appointment_interval" class="block text-gray-700 text-sm font-bold mb-2">Default Appointment Duration (minutes)</label>
                            <input type="number" name="appointment_interval" id="appointment_interval" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($settings['appointment_interval']) ? htmlspecialchars($settings['appointment_interval']['value']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label for="max_advance_booking_days" class="block text-gray-700 text-sm font-bold mb-2">Max Advance Booking Days</label>
                            <input type="number" name="max_advance_booking_days" id="max_advance_booking_days" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($settings['max_advance_booking_days']) ? htmlspecialchars($settings['max_advance_booking_days']['value']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="allow_online_scheduling" id="allow_online_scheduling" value="true" <?php echo (isset($settings['allow_online_scheduling']) && $settings['allow_online_scheduling']['value'] === 'true') ? 'checked' : ''; ?> class="mr-2">
                        <label for="allow_online_scheduling" class="text-gray-700 font-bold">Allow Online Scheduling</label>
                    </div>
                    
                    <div>
                        <label for="default_cancellation_policy" class="block text-gray-700 text-sm font-bold mb-2">Cancellation Policy</label>
                        <textarea name="default_cancellation_policy" id="default_cancellation_policy" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo isset($settings['default_cancellation_policy']) ? htmlspecialchars($settings['default_cancellation_policy']['value']) : ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <div class="flex justify-end">
                    <button type="submit" name="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline">
                        Save Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Add New Setting Button -->
    <div class="mt-8 flex justify-end">
        <a href="add_setting.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Custom Setting
        </a>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
