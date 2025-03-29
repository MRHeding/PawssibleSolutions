<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

// Check if form was submitted properly
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['pet_id']) || !isset($_POST['weight'])) {
    header("Location: patients.php");
    exit;
}

$pet_id = intval($_POST['pet_id']);
$weight = floatval($_POST['weight']);
$user_id = $_SESSION['user_id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get vet information
$vet_query = "SELECT * FROM vets WHERE user_id = :user_id";
$vet_stmt = $db->prepare($vet_query);
$vet_stmt->bindParam(':user_id', $user_id);
$vet_stmt->execute();
$vet = $vet_stmt->fetch(PDO::FETCH_ASSOC);
$vet_id = $vet['id'];

// Verify this vet has access to this pet
$access_check_query = "SELECT COUNT(*) as count FROM appointments 
                      WHERE pet_id = :pet_id AND vet_id = :vet_id";
$access_check_stmt = $db->prepare($access_check_query);
$access_check_stmt->bindParam(':pet_id', $pet_id);
$access_check_stmt->bindParam(':vet_id', $vet_id);
$access_check_stmt->execute();
$access_result = $access_check_stmt->fetch(PDO::FETCH_ASSOC);

// If the vet has never seen this pet, redirect
if ($access_result['count'] == 0) {
    header("Location: patients.php");
    exit;
}

// Update the pet's weight
$update_query = "UPDATE pets SET weight = :weight, updated_at = NOW() WHERE id = :pet_id";
$update_stmt = $db->prepare($update_query);
$update_stmt->bindParam(':weight', $weight);
$update_stmt->bindParam(':pet_id', $pet_id);

if ($update_stmt->execute()) {
    header("Location: view_pet.php?id=" . $pet_id . "&success=weight_updated");
} else {
    header("Location: view_pet.php?id=" . $pet_id . "&error=update_failed");
}
exit;
