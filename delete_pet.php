<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if pet ID is provided
if (!isset($_POST['pet_id']) || empty($_POST['pet_id'])) {
    header("Location: my_pets.php");
    exit;
}

$pet_id = $_POST['pet_id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Verify ownership before deletion
$check_query = "SELECT id FROM pets WHERE id = :pet_id AND owner_id = :owner_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':pet_id', $pet_id);
$check_stmt->bindParam(':owner_id', $user_id);
$check_stmt->execute();

if ($check_stmt->rowCount() > 0) {
    try {
        // Delete the pet (cascade will delete related records due to foreign key constraints)
        $delete_query = "DELETE FROM pets WHERE id = :pet_id AND owner_id = :owner_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(':pet_id', $pet_id);
        $delete_stmt->bindParam(':owner_id', $user_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_message'] = "Pet deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to delete pet.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "You do not have permission to delete this pet.";
}

// Redirect back to pets list
header("Location: my_pets.php");
exit;
