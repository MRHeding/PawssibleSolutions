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

echo "<h1>Medical Record Duplicate Prevention Test</h1>";

// Test 1: Find completed appointments
echo "<h2>Test 1: Completed Appointments</h2>";
$completed_query = "SELECT a.id, a.appointment_date, p.name as pet_name, a.status 
                   FROM appointments a 
                   JOIN pets p ON a.pet_id = p.id 
                   WHERE a.status = 'completed' 
                   ORDER BY a.appointment_date DESC 
                   LIMIT 5";
$completed_stmt = $db->prepare($completed_query);
$completed_stmt->execute();
$completed_appointments = $completed_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($completed_appointments)) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Date</th><th>Pet</th><th>Status</th><th>Test Action</th></tr>";
    foreach ($completed_appointments as $apt) {
        echo "<tr>";
        echo "<td>" . $apt['id'] . "</td>";
        echo "<td>" . date('M d, Y', strtotime($apt['appointment_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($apt['pet_name']) . "</td>";
        echo "<td>" . $apt['status'] . "</td>";
        echo "<td><a href='add_medical_record.php?appointment_id=" . $apt['id'] . "'>Test Add Medical Record</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No completed appointments found.</p>";
}

echo "<hr>";

// Test 2: Check medical records with appointment links
echo "<h2>Test 2: Existing Medical Records with Appointment Links</h2>";
$medical_records_query = "SELECT mr.id, mr.appointment_id, mr.record_date, p.name as pet_name 
                         FROM medical_records mr 
                         JOIN pets p ON mr.pet_id = p.id 
                         WHERE mr.appointment_id IS NOT NULL 
                         ORDER BY mr.record_date DESC 
                         LIMIT 5";
$medical_records_stmt = $db->prepare($medical_records_query);
$medical_records_stmt->execute();
$medical_records = $medical_records_stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($medical_records)) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Record ID</th><th>Appointment ID</th><th>Date</th><th>Pet</th><th>Test Action</th></tr>";
    foreach ($medical_records as $record) {
        echo "<tr>";
        echo "<td>" . $record['id'] . "</td>";
        echo "<td>" . ($record['appointment_id'] ?: 'N/A') . "</td>";
        echo "<td>" . date('M d, Y', strtotime($record['record_date'])) . "</td>";
        echo "<td>" . htmlspecialchars($record['pet_name']) . "</td>";
        if ($record['appointment_id']) {
            echo "<td><a href='add_medical_record.php?appointment_id=" . $record['appointment_id'] . "'>Try Add Again (Should Redirect)</a></td>";
        } else {
            echo "<td>No appointment link</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No medical records with appointment links found.</p>";
}

echo "<hr>";

// Test 3: Show the updated appointments query in action
echo "<h2>Test 3: Appointments with Medical Record Status</h2>";
$updated_query = "SELECT a.id as appointment_id, a.appointment_date, a.status,
                 p.name as pet_name, 
                 CONCAT(o.first_name, ' ', o.last_name) as owner_name,
                 mr.id as medical_record_id
                 FROM appointments a 
                 JOIN pets p ON a.pet_id = p.id 
                 JOIN users o ON p.owner_id = o.id
                 LEFT JOIN medical_records mr ON a.id = mr.appointment_id
                 ORDER BY a.appointment_date DESC 
                 LIMIT 10";

$updated_stmt = $db->prepare($updated_query);
$updated_stmt->execute();
$updated_appointments = $updated_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th style='padding: 8px;'>Appointment ID</th>";
echo "<th style='padding: 8px;'>Date</th>";
echo "<th style='padding: 8px;'>Pet</th>";
echo "<th style='padding: 8px;'>Status</th>";
echo "<th style='padding: 8px;'>Medical Record</th>";
echo "<th style='padding: 8px;'>Action Available</th>";
echo "</tr>";

foreach ($updated_appointments as $apt) {
    echo "<tr>";
    echo "<td style='padding: 8px;'>" . $apt['appointment_id'] . "</td>";
    echo "<td style='padding: 8px;'>" . date('M d, Y', strtotime($apt['appointment_date'])) . "</td>";
    echo "<td style='padding: 8px;'>" . htmlspecialchars($apt['pet_name']) . "</td>";
    echo "<td style='padding: 8px;'>" . ucfirst($apt['status']) . "</td>";
    
    if (!empty($apt['medical_record_id'])) {
        echo "<td style='padding: 8px; color: green;'><strong>✓ EXISTS (ID: " . $apt['medical_record_id'] . ")</strong></td>";
        if ($apt['status'] === 'completed') {
            echo "<td style='padding: 8px;'><a href='../view_medical_record.php?id=" . $apt['medical_record_id'] . "'>View Record Only</a></td>";
        } else {
            echo "<td style='padding: 8px;'>N/A (Not Completed)</td>";
        }
    } else {
        echo "<td style='padding: 8px; color: red;'><strong>✗ NOT FOUND</strong></td>";
        if ($apt['status'] === 'completed') {
            echo "<td style='padding: 8px;'>Can Add Record</td>";
        } else {
            echo "<td style='padding: 8px;'>N/A (Not Completed)</td>";
        }
    }
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h3>Instructions for Testing:</h3>";
echo "<ol>";
echo "<li><strong>New Medical Records:</strong> Click on completed appointments without medical records to test adding new records.</li>";
echo "<li><strong>Duplicate Prevention:</strong> Click on completed appointments that already have medical records - you should be redirected to view the existing record.</li>";
echo "<li><strong>Visual Indicators:</strong> Go to <a href='appointments.php'>appointments.php</a> to see the visual indicators for appointments with medical records.</li>";
echo "</ol>";

echo "<p><a href='appointments.php'>← Back to Appointments</a></p>";
?>
