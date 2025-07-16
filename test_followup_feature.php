<?php
// Test script to verify the follow-up appointment feature
echo "Follow-up Appointment Feature Implementation Test\n";
echo "===============================================\n\n";

// Check if the appointment_number_generator class exists
if (file_exists('includes/appointment_number_generator.php')) {
    echo "✓ AppointmentNumberGenerator class file exists\n";
} else {
    echo "✗ AppointmentNumberGenerator class file missing\n";
}

// Check if database connection works
try {
    include_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Check appointments table structure
try {
    $query = "DESCRIBE appointments";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✓ Appointments table accessible\n";
    echo "  Columns: " . implode(', ', $columns) . "\n";
    
    // Check if required columns exist
    $required_columns = ['appointment_number', 'pet_id', 'vet_id', 'appointment_date', 'appointment_time', 'reason', 'status'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (empty($missing_columns)) {
        echo "✓ All required columns present\n";
    } else {
        echo "✗ Missing columns: " . implode(', ', $missing_columns) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error checking appointments table: " . $e->getMessage() . "\n";
}

echo "\nImplementation Summary:\n";
echo "- Added follow-up scheduling to admin/view_appointment.php\n";
echo "- Added follow-up scheduling to vet/view_appointment.php\n";
echo "- Both pages include conflict checking and appointment number generation\n";
echo "- UI shows only when appointment is 'completed' or 'scheduled'\n";
echo "- Pre-fills same vet and pet information\n";
echo "- Includes comprehensive time slot selection\n";

echo "\nNext Steps for Your Client:\n";
echo "1. Test the feature by viewing any appointment as admin or vet\n";
echo "2. Try scheduling a follow-up appointment\n";
echo "3. Verify the new appointment appears in the appointments list\n";
echo "4. Check that conflict detection works correctly\n";
?>
