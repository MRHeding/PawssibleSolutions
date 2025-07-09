<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

echo "<h1>Medical Record Path Test</h1>";

// Test if the view_medical_record.php file exists in the correct location
$view_file_path = '../view_medical_record.php';
$file_exists = file_exists($view_file_path);

echo "<h2>File Path Verification:</h2>";
echo "<p><strong>Looking for:</strong> " . $view_file_path . "</p>";
echo "<p><strong>File exists:</strong> " . ($file_exists ? "✅ YES" : "❌ NO") . "</p>";

if ($file_exists) {
    echo "<p><strong>File path:</strong> " . realpath($view_file_path) . "</p>";
}

echo "<hr>";

// Get a medical record to test with
$database = new Database();
$db = $database->getConnection();

$test_query = "SELECT mr.id FROM medical_records mr LIMIT 1";
$test_stmt = $db->prepare($test_query);
$test_stmt->execute();

if ($test_stmt->rowCount() > 0) {
    $test_record = $test_stmt->fetch(PDO::FETCH_ASSOC);
    $test_id = $test_record['id'];
    
    echo "<h2>Test Links:</h2>";
    echo "<p><a href='../view_medical_record.php?id=$test_id' target='_blank'>Test View Medical Record (ID: $test_id)</a></p>";
    echo "<p><em>This link should open the medical record view page.</em></p>";
} else {
    echo "<h2>No Test Data:</h2>";
    echo "<p>No medical records found in the database to test with.</p>";
}

echo "<hr>";

echo "<h2>Path Structure:</h2>";
echo "<ul>";
echo "<li><strong>Current file location:</strong> admin/test_path_fix.php</li>";
echo "<li><strong>Target file location:</strong> view_medical_record.php (root)</li>";
echo "<li><strong>Relative path used:</strong> ../view_medical_record.php</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='appointments.php'>← Back to Appointments</a></p>";
echo "<p><a href='medical_records.php'>← Back to Medical Records</a></p>";
?>
