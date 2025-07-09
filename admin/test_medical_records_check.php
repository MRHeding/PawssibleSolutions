<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

echo "<h1>Medical Records Check Test</h1>";

// Test query to see appointments with medical records
$query = "SELECT a.id as appointment_id, a.appointment_date, a.status,
         p.name as pet_name, 
         CONCAT(o.first_name, ' ', o.last_name) as owner_name,
         mr.id as medical_record_id
         FROM appointments a 
         JOIN pets p ON a.pet_id = p.id 
         JOIN users o ON p.owner_id = o.id
         LEFT JOIN medical_records mr ON a.id = mr.appointment_id
         WHERE a.status = 'completed'
         ORDER BY a.appointment_date DESC 
         LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Completed Appointments and Medical Records Status:</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 10px; text-align: left;'>Appointment ID</th>";
echo "<th style='padding: 10px; text-align: left;'>Date</th>";
echo "<th style='padding: 10px; text-align: left;'>Pet</th>";
echo "<th style='padding: 10px; text-align: left;'>Owner</th>";
echo "<th style='padding: 10px; text-align: left;'>Medical Record</th>";
echo "<th style='padding: 10px; text-align: left;'>Action</th>";
echo "</tr>";

foreach ($appointments as $apt) {
    echo "<tr>";
    echo "<td style='padding: 10px;'>" . $apt['appointment_id'] . "</td>";
    echo "<td style='padding: 10px;'>" . date('M d, Y', strtotime($apt['appointment_date'])) . "</td>";
    echo "<td style='padding: 10px;'>" . htmlspecialchars($apt['pet_name']) . "</td>";
    echo "<td style='padding: 10px;'>" . htmlspecialchars($apt['owner_name']) . "</td>";
    
    if (!empty($apt['medical_record_id'])) {
        echo "<td style='padding: 10px; color: green;'><strong>✓ EXISTS (ID: " . $apt['medical_record_id'] . ")</strong></td>";
        echo "<td style='padding: 10px;'><a href='../view_medical_record.php?id=" . $apt['medical_record_id'] . "' style='color: blue;'>View Record</a></td>";
    } else {
        echo "<td style='padding: 10px; color: red;'><strong>✗ NOT FOUND</strong></td>";
        echo "<td style='padding: 10px;'><a href='add_medical_record.php?appointment_id=" . $apt['appointment_id'] . "' style='color: green;'>Add Record</a></td>";
    }
    echo "</tr>";
}

echo "</table>";

// Show some statistics
$total_completed = count($appointments);
$with_records = count(array_filter($appointments, function($apt) { return !empty($apt['medical_record_id']); }));
$without_records = $total_completed - $with_records;

echo "<h3>Statistics (Last 10 completed appointments):</h3>";
echo "<p><strong>Total Completed Appointments:</strong> $total_completed</p>";
echo "<p><strong>With Medical Records:</strong> $with_records</p>";
echo "<p><strong>Without Medical Records:</strong> $without_records</p>";

echo "<hr>";
echo "<p><a href='appointments.php'>← Back to Appointments</a></p>";
?>
