<?php
/**
 * Database Migration: Add appointment_number field to appointments table
 * Run this script once to update your existing database
 */

require_once '../config/database.php';
require_once '../includes/appointment_number_generator.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting appointment number migration...\n";
    
    // Check if appointment_number column already exists
    $checkColumn = "SHOW COLUMNS FROM appointments LIKE 'appointment_number'";
    $stmt = $db->prepare($checkColumn);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Add the appointment_number column
        echo "Adding appointment_number column...\n";
        $addColumn = "ALTER TABLE appointments ADD COLUMN appointment_number VARCHAR(20) NOT NULL DEFAULT '' AFTER id";
        $db->exec($addColumn);
        
        // Add unique index for appointment_number
        echo "Adding unique index for appointment_number...\n";
        $addIndex = "ALTER TABLE appointments ADD UNIQUE KEY idx_appointment_number (appointment_number)";
        $db->exec($addIndex);
        
        echo "Column added successfully!\n";
    } else {
        echo "appointment_number column already exists.\n";
    }
    
    // Generate appointment numbers for existing appointments
    echo "Generating appointment numbers for existing appointments...\n";
    
    $generator = new AppointmentNumberGenerator($db);
    
    // Get all appointments without appointment numbers
    $getAppointments = "SELECT id, created_at FROM appointments WHERE appointment_number = '' OR appointment_number IS NULL ORDER BY created_at ASC";
    $stmt = $db->prepare($getAppointments);
    $stmt->execute();
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($appointments as $appointment) {
        $appointmentNumber = $generator->generateAppointmentNumber();
        
        $updateQuery = "UPDATE appointments SET appointment_number = :appointment_number WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':appointment_number', $appointmentNumber);
        $updateStmt->bindParam(':id', $appointment['id']);
        $updateStmt->execute();
        
        $count++;
        echo "Generated appointment number {$appointmentNumber} for appointment ID {$appointment['id']}\n";
    }
    
    echo "Migration completed successfully! Generated {$count} appointment numbers.\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>