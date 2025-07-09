<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

echo "<h1>Appointment Status Debug Test</h1>";

// Get a few appointments to test
$query = "SELECT id, status, appointment_date, appointment_time, updated_at FROM appointments ORDER BY id DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Current Appointments Status:</h2>";
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>ID</th><th>Status</th><th>Date</th><th>Time</th><th>Updated At</th></tr>";

foreach ($appointments as $apt) {
    echo "<tr>";
    echo "<td>" . $apt['id'] . "</td>";
    echo "<td><strong>" . $apt['status'] . "</strong></td>";
    echo "<td>" . $apt['appointment_date'] . "</td>";
    echo "<td>" . $apt['appointment_time'] . "</td>";
    echo "<td>" . $apt['updated_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test status update if requested
if (isset($_GET['test_update']) && isset($_GET['appointment_id']) && isset($_GET['new_status'])) {
    $appointment_id = intval($_GET['appointment_id']);
    $new_status = $_GET['new_status'];
    
    echo "<h2>Testing Status Update...</h2>";
    echo "<p>Updating appointment ID: $appointment_id to status: $new_status</p>";
    
    $update_query = "UPDATE appointments SET status = :status, updated_at = NOW() WHERE id = :appointment_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':appointment_id', $appointment_id);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>✓ Update successful!</p>";
        
        // Verify the update
        $verify_query = "SELECT status, updated_at FROM appointments WHERE id = :appointment_id";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->bindParam(':appointment_id', $appointment_id);
        $verify_stmt->execute();
        $updated_apt = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>New status: <strong>" . $updated_apt['status'] . "</strong></p>";
        echo "<p>Updated at: " . $updated_apt['updated_at'] . "</p>";
        
        echo "<p><a href='debug_status.php'>Refresh to see updated list</a></p>";
    } else {
        echo "<p style='color: red;'>✗ Update failed!</p>";
        $errorInfo = $update_stmt->errorInfo();
        echo "<p>Error: " . $errorInfo[2] . "</p>";
    }
}

// Test links for the first appointment if available
if (!empty($appointments)) {
    $first_apt = $appointments[0];
    echo "<h2>Test Status Updates for Appointment ID: " . $first_apt['id'] . "</h2>";
    echo "<p>Current Status: <strong>" . $first_apt['status'] . "</strong></p>";
    
    $test_statuses = ['scheduled', 'completed', 'cancelled', 'no-show'];
    foreach ($test_statuses as $status) {
        if ($status !== $first_apt['status']) {
            echo "<a href='debug_status.php?test_update=1&appointment_id=" . $first_apt['id'] . "&new_status=$status' style='margin-right: 10px; padding: 5px 10px; background: #007cba; color: white; text-decoration: none; border-radius: 3px;'>Set to $status</a>";
        }
    }
}

echo "<hr>";
echo "<p><a href='admin/appointments.php'>← Back to Admin Appointments</a></p>";
?>
