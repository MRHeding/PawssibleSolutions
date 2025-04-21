<?php
session_start();
include_once '../config/database.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get appointment ID from query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid appointment ID.';
    header('Location: appointments.php');
    exit;
}
$appointment_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Check if appointment exists
$stmt = $db->prepare('SELECT id FROM appointments WHERE id = :id');
$stmt->bindParam(':id', $appointment_id);
$stmt->execute();
if ($stmt->rowCount() === 0) {
    $_SESSION['error'] = 'Appointment not found.';
    header('Location: appointments.php');
    exit;
}

// Delete the appointment
$delete_stmt = $db->prepare('DELETE FROM appointments WHERE id = :id');
$delete_stmt->bindParam(':id', $appointment_id);
if ($delete_stmt->execute()) {
    $_SESSION['success'] = 'Appointment deleted successfully.';
} else {
    $_SESSION['error'] = 'Failed to delete appointment.';
}
header('Location: appointments.php');
exit;
