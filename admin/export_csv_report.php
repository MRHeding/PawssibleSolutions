<?php
// CSV Export for Reports
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Set filename based on report type and date
$filename = $report_type . '_report_' . $date_from . '_to_' . $date_to . '.csv';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create file pointer
$output = fopen('php://output', 'w');

switch ($report_type) {
    case 'daily_appointments':
        // CSV headers for appointments
        fputcsv($output, [
            'Date', 'Time', 'Appointment Number', 'Pet Name', 'Species', 'Owner Name', 
            'Owner Email', 'Owner Phone', 'Veterinarian', 'Reason', 'Status', 'Notes'
        ]);
        
        // Get appointments data
        $query = "SELECT 
            a.*, 
            p.name as pet_name, 
            p.species,
            CONCAT(owner.first_name, ' ', owner.last_name) as owner_name,
            owner.email as owner_email,
            owner.phone as owner_phone,
            CONCAT(vet_user.first_name, ' ', vet_user.last_name) as vet_name
            FROM appointments a
            JOIN pets p ON a.pet_id = p.id
            JOIN users owner ON p.owner_id = owner.id
            JOIN vets v ON a.vet_id = v.id
            JOIN users vet_user ON v.user_id = vet_user.id
            WHERE a.appointment_date BETWEEN :date_from AND :date_to
            ORDER BY a.appointment_date ASC, a.appointment_time ASC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':date_from', $date_from);
        $stmt->bindParam(':date_to', $date_to);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['appointment_date'],
                $row['appointment_time'],
                $row['appointment_number'],
                $row['pet_name'],
                $row['species'],
                $row['owner_name'],
                $row['owner_email'],
                $row['owner_phone'],
                'Dr. ' . $row['vet_name'],
                $row['reason'],
                ucfirst($row['status']),
                $row['notes']
            ]);
        }
        break;
        
    case 'client_report':
        // CSV headers for clients
        fputcsv($output, [
            'Client ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Address',
            'Registration Date', 'Total Pets', 'Total Appointments', 'Completed Appointments',
            'Last Appointment Date'
        ]);
        
        // Get clients data
        $query = "SELECT 
            u.*, 
            COUNT(DISTINCT p.id) as total_pets,
            COUNT(DISTINCT a.id) as total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            MAX(a.appointment_date) as last_appointment_date
            FROM users u
            LEFT JOIN pets p ON u.id = p.owner_id
            LEFT JOIN appointments a ON p.id = a.pet_id
            WHERE u.role = 'client'
            GROUP BY u.id
            ORDER BY u.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['phone'],
                $row['address'],
                $row['created_at'],
                $row['total_pets'],
                $row['total_appointments'],
                $row['completed_appointments'],
                $row['last_appointment_date']
            ]);
        }
        break;
        
    case 'pet_report':
        // CSV headers for pets
        fputcsv($output, [
            'Pet ID', 'Pet Name', 'Species', 'Breed', 'Gender', 'Date of Birth', 'Weight',
            'Microchip ID', 'Owner Name', 'Owner Email', 'Owner Phone', 'Registration Date',
            'Total Appointments', 'Completed Appointments', 'Medical Records Count'
        ]);
        
        // Get pets data
        $query = "SELECT 
            p.*, 
            CONCAT(u.first_name, ' ', u.last_name) as owner_name,
            u.email as owner_email,
            u.phone as owner_phone,
            COUNT(DISTINCT a.id) as total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            COUNT(DISTINCT mr.id) as medical_records_count
            FROM pets p
            JOIN users u ON p.owner_id = u.id
            LEFT JOIN appointments a ON p.id = a.pet_id
            LEFT JOIN medical_records mr ON p.id = mr.pet_id
            GROUP BY p.id
            ORDER BY p.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['species'],
                $row['breed'],
                $row['gender'],
                $row['date_of_birth'],
                $row['weight'],
                $row['microchip_id'],
                $row['owner_name'],
                $row['owner_email'],
                $row['owner_phone'],
                $row['created_at'],
                $row['total_appointments'],
                $row['completed_appointments'],
                $row['medical_records_count']
            ]);
        }
        break;
        
    default: // Overview report
        // CSV headers for overview
        fputcsv($output, [
            'Report Period', 'Total Appointments', 'Scheduled', 'Completed', 'Cancelled', 'No-Show',
            'New Clients', 'New Pets', 'Completion Rate (%)'
        ]);
        
        // Get overview statistics
        $appt_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show
            FROM appointments 
            WHERE appointment_date BETWEEN :date_from AND :date_to";
        
        $appt_stmt = $db->prepare($appt_query);
        $appt_stmt->bindParam(':date_from', $date_from);
        $appt_stmt->bindParam(':date_to', $date_to);
        $appt_stmt->execute();
        $appt_stats = $appt_stmt->fetch(PDO::FETCH_ASSOC);
        
        $clients_query = "SELECT COUNT(*) as count FROM users WHERE role = 'client' AND created_at BETWEEN :date_from AND :date_to";
        $clients_stmt = $db->prepare($clients_query);
        $clients_stmt->bindParam(':date_from', $date_from);
        $clients_stmt->bindParam(':date_to', $date_to);
        $clients_stmt->execute();
        $new_clients = $clients_stmt->fetchColumn();
        
        $pets_query = "SELECT COUNT(*) as count FROM pets WHERE created_at BETWEEN :date_from AND :date_to";
        $pets_stmt = $db->prepare($pets_query);
        $pets_stmt->bindParam(':date_from', $date_from);
        $pets_stmt->bindParam(':date_to', $date_to);
        $pets_stmt->execute();
        $new_pets = $pets_stmt->fetchColumn();
        
        $completion_rate = $appt_stats['total'] > 0 ? 
            ($appt_stats['completed'] / $appt_stats['total']) * 100 : 0;
        
        fputcsv($output, [
            $date_from . ' to ' . $date_to,
            $appt_stats['total'],
            $appt_stats['scheduled'],
            $appt_stats['completed'],
            $appt_stats['cancelled'],
            $appt_stats['no_show'],
            $new_clients,
            $new_pets,
            number_format($completion_rate, 2)
        ]);
        
        // Add veterinarian performance data
        fputcsv($output, []); // Empty row
        fputcsv($output, ['Veterinarian Performance']);
        fputcsv($output, ['Veterinarian', 'Total Appointments', 'Completed', 'Completion Rate (%)']);
        
        $vet_query = "SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as vet_name,
            COUNT(a.id) as total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
            FROM vets v
            JOIN users u ON v.user_id = u.id
            LEFT JOIN appointments a ON v.id = a.vet_id AND a.appointment_date BETWEEN :date_from AND :date_to
            GROUP BY v.id
            ORDER BY total_appointments DESC";
        
        $vet_stmt = $db->prepare($vet_query);
        $vet_stmt->bindParam(':date_from', $date_from);
        $vet_stmt->bindParam(':date_to', $date_to);
        $vet_stmt->execute();
        
        while ($vet = $vet_stmt->fetch(PDO::FETCH_ASSOC)) {
            $vet_completion_rate = $vet['total_appointments'] > 0 ? 
                ($vet['completed_appointments'] / $vet['total_appointments']) * 100 : 0;
            
            fputcsv($output, [
                'Dr. ' . $vet['vet_name'],
                $vet['total_appointments'],
                $vet['completed_appointments'],
                number_format($vet_completion_rate, 2)
            ]);
        }
        break;
}

fclose($output);
exit;
?>