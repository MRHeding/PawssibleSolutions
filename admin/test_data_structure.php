<?php
// Test the appointment lookup to verify data structure
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$appointment_id = 8; // Test with appointment ID 8

echo "<h1>Data Structure Test for Appointment ID: $appointment_id</h1>";

$appointment_query = "SELECT a.id as appointment_id, a.appointment_date, a.reason,
                     p.id as id, p.name as pet_name, p.species, p.breed, 
                     u.first_name, u.last_name, u.id as owner_id
                     FROM appointments a
                     JOIN pets p ON a.pet_id = p.id 
                     JOIN users u ON p.owner_id = u.id 
                     WHERE a.id = :appointment_id";
$appointment_stmt = $db->prepare($appointment_query);
$appointment_stmt->bindParam(':appointment_id', $appointment_id);
$appointment_stmt->execute();

if ($appointment_stmt->rowCount() > 0) {
    $appointment_info = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
    $pet_id = $appointment_info['id'];
    $pet = $appointment_info;
    
    // Add consistency fields
    $pet['pet_id'] = $pet['id'];
    if (!isset($pet['name']) && isset($pet['pet_name'])) {
        $pet['name'] = $pet['pet_name'];
    }
    
    echo "<h2>✓ Data Structure After Processing:</h2>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    
    foreach ($pet as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>Key Fields Check:</h3>";
    echo "<p><strong>pet['id']:</strong> " . (isset($pet['id']) ? "✓ EXISTS (" . $pet['id'] . ")" : "✗ MISSING") . "</p>";
    echo "<p><strong>pet['pet_name']:</strong> " . (isset($pet['pet_name']) ? "✓ EXISTS (" . $pet['pet_name'] . ")" : "✗ MISSING") . "</p>";
    echo "<p><strong>pet['name']:</strong> " . (isset($pet['name']) ? "✓ EXISTS (" . $pet['name'] . ")" : "✗ MISSING") . "</p>";
    
    echo "<hr>";
    echo "<p><a href='add_medical_record.php?appointment_id=$appointment_id'>Test the fixed add_medical_record.php</a></p>";
    
} else {
    echo "<p>No appointment found with ID: $appointment_id</p>";
}

echo "<p><a href='appointments.php'>← Back to Appointments</a></p>";
?>
