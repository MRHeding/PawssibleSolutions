<?php
// Simple test to verify the appointment lookup works
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Test with appointment ID 8
$appointment_id = 8;

$appointment_query = "SELECT a.id as appointment_id, a.appointment_date, a.reason,
                     p.id as pet_id, p.name as pet_name, p.species, p.breed, 
                     u.first_name, u.last_name, u.id as owner_id
                     FROM appointments a
                     JOIN pets p ON a.pet_id = p.id 
                     JOIN users u ON p.owner_id = u.id 
                     WHERE a.id = :appointment_id";
$appointment_stmt = $db->prepare($appointment_query);
$appointment_stmt->bindParam(':appointment_id', $appointment_id);
$appointment_stmt->execute();

echo "<h1>Test Appointment Lookup for ID: $appointment_id</h1>";

if ($appointment_stmt->rowCount() > 0) {
    $appointment_info = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h2>✓ Appointment Found!</h2>";
    echo "<p><strong>Pet Name:</strong> " . htmlspecialchars($appointment_info['pet_name']) . "</p>";
    echo "<p><strong>Species:</strong> " . htmlspecialchars($appointment_info['species']) . "</p>";
    echo "<p><strong>Breed:</strong> " . htmlspecialchars($appointment_info['breed']) . "</p>";
    echo "<p><strong>Owner:</strong> " . htmlspecialchars($appointment_info['first_name'] . ' ' . $appointment_info['last_name']) . "</p>";
    echo "<p><strong>Appointment Date:</strong> " . htmlspecialchars($appointment_info['appointment_date']) . "</p>";
    echo "<p><strong>Reason:</strong> " . htmlspecialchars($appointment_info['reason']) . "</p>";
    
    echo "<hr>";
    echo "<p><a href='add_medical_record.php?appointment_id=$appointment_id'>Go to Add Medical Record with this appointment</a></p>";
} else {
    echo "<h2>✗ Appointment Not Found</h2>";
    echo "<p>No appointment found with ID: $appointment_id</p>";
}

echo "<hr>";
echo "<p><a href='appointments.php'>← Back to Appointments</a></p>";
?>
