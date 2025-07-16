<?php
// PDF Export for Reports
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

// Simple HTML to PDF generation (you can enhance this with libraries like TCPDF or mPDF)
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Veterinary Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4F46E5; padding-bottom: 20px; }
        .clinic-name { font-size: 24px; font-weight: bold; color: #4F46E5; margin-bottom: 5px; }
        .report-title { font-size: 18px; color: #6B7280; }
        .report-date { font-size: 12px; color: #9CA3AF; margin-top: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
        .stat-card { background: #F9FAFB; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #E5E7EB; }
        .stat-number { font-size: 24px; font-weight: bold; color: #4F46E5; }
        .stat-label { font-size: 12px; color: #6B7280; margin-top: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #E5E7EB; }
        th { background-color: #F9FAFB; font-weight: bold; color: #374151; }
        .section-title { font-size: 16px; font-weight: bold; margin: 30px 0 15px 0; color: #1F2937; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 15px; }
    </style>
</head>
<body>';

$html .= '<div class="header">
    <div class="clinic-name">PawssibleSolutions Veterinary Clinic</div>
    <div class="report-title">' . ucfirst(str_replace('_', ' ', $report_type)) . ' Report</div>
    <div class="report-date">Report Period: ' . date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to)) . '</div>
    <div class="report-date">Generated on: ' . date('F j, Y \a\t g:i A') . '</div>
</div>';

switch ($report_type) {
    case 'daily_appointments':
        // Get appointments statistics
        $stats_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM appointments WHERE appointment_date BETWEEN :date_from AND :date_to";
        
        $stats_stmt = $db->prepare($stats_query);
        $stats_stmt->bindParam(':date_from', $date_from);
        $stats_stmt->bindParam(':date_to', $date_to);
        $stats_stmt->execute();
        $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        $html .= '<div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $stats['total'] . '</div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['completed'] . '</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['scheduled'] . '</div>
                <div class="stat-label">Scheduled</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $stats['cancelled'] . '</div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>';
        
        // Get appointments data - ALL APPOINTMENTS (removed LIMIT)
        $query = "SELECT 
            a.appointment_date, a.appointment_time, a.appointment_number,
            p.name as pet_name, p.species, p.breed, p.date_of_birth,
            CONCAT(owner.first_name, ' ', owner.last_name) as owner_name,
            owner.email as owner_email, owner.phone as owner_phone,
            CONCAT(vet_user.first_name, ' ', vet_user.last_name) as vet_name,
            a.reason, a.status, a.notes
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
        
        $html .= '<div class="section-title">All Appointment Details</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Appt #</th>
                    <th>Pet Details</th>
                    <th>Owner Details</th>
                    <th>Veterinarian</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>';
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $html .= '<tr>
                <td>' . date('M d, Y', strtotime($row['appointment_date'])) . '</td>
                <td>' . date('g:i A', strtotime($row['appointment_time'])) . '</td>
                <td>' . htmlspecialchars($row['appointment_number']) . '</td>
                <td>' . htmlspecialchars($row['pet_name']) . '<br><small>' . htmlspecialchars($row['species']) . ' - ' . htmlspecialchars($row['breed']) . '<br>DOB: ' . htmlspecialchars($row['date_of_birth']) . '</small></td>
                <td>' . htmlspecialchars($row['owner_name']) . '<br><small>' . htmlspecialchars($row['owner_email']) . '<br>' . htmlspecialchars($row['owner_phone']) . '</small></td>
                <td>Dr. ' . htmlspecialchars($row['vet_name']) . '</td>
                <td>' . htmlspecialchars($row['reason']) . '</td>
                <td>' . ucfirst($row['status']) . '</td>
                <td>' . htmlspecialchars($row['notes']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        break;
        
    case 'client_report':
        // Get client statistics
        $client_stats_query = "SELECT 
            COUNT(*) as total_clients,
            COUNT(CASE WHEN created_at BETWEEN :date_from AND :date_to THEN 1 END) as new_clients
            FROM users WHERE role = 'client'";
        
        $client_stats_stmt = $db->prepare($client_stats_query);
        $client_stats_stmt->bindParam(':date_from', $date_from);
        $client_stats_stmt->bindParam(':date_to', $date_to);
        $client_stats_stmt->execute();
        $client_stats = $client_stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        $html .= '<div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $client_stats['total_clients'] . '</div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $client_stats['new_clients'] . '</div>
                <div class="stat-label">New Clients</div>
            </div>
        </div>';
        
        // Get ALL clients data (removed LIMIT)
        $query = "SELECT 
            CONCAT(u.first_name, ' ', u.last_name) as client_name,
            u.email, u.phone,
            COUNT(DISTINCT p.id) as total_pets,
            COUNT(DISTINCT a.id) as total_appointments,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            MAX(a.appointment_date) as last_appointment_date,
            u.created_at
            FROM users u
            LEFT JOIN pets p ON u.id = p.owner_id
            LEFT JOIN appointments a ON p.id = a.pet_id
            WHERE u.role = 'client'
            GROUP BY u.id
            ORDER BY u.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $html .= '<div class="section-title">All Client Details</div>
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Pets</th>
                    <th>Total Appts</th>
                    <th>Completed</th>
                    <th>Last Visit</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>';
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['client_name']) . '</td>
                <td>' . htmlspecialchars($row['email']) . '</td>
                <td>' . htmlspecialchars($row['phone']) . '</td>
                <td>' . $row['total_pets'] . '</td>
                <td>' . $row['total_appointments'] . '</td>
                <td>' . $row['completed_appointments'] . '</td>
                <td>' . ($row['last_appointment_date'] ? date('M d, Y', strtotime($row['last_appointment_date'])) : 'Never') . '</td>
                <td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        break;
        
    case 'pet_report':
        // Get pet statistics
        $pet_stats_query = "SELECT 
            COUNT(*) as total_pets,
            COUNT(CASE WHEN created_at BETWEEN :date_from AND :date_to THEN 1 END) as new_pets,
            COUNT(DISTINCT species) as species_count
            FROM pets";
        
        $pet_stats_stmt = $db->prepare($pet_stats_query);
        $pet_stats_stmt->bindParam(':date_from', $date_from);
        $pet_stats_stmt->bindParam(':date_to', $date_to);
        $pet_stats_stmt->execute();
        $pet_stats = $pet_stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        $html .= '<div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $pet_stats['total_pets'] . '</div>
                <div class="stat-label">Total Pets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $pet_stats['new_pets'] . '</div>
                <div class="stat-label">New Pets</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $pet_stats['species_count'] . '</div>
                <div class="stat-label">Species Types</div>
            </div>
        </div>';
        
        // Get species breakdown
        $species_query = "SELECT species, COUNT(*) as count FROM pets GROUP BY species ORDER BY count DESC";
        $species_stmt = $db->prepare($species_query);
        $species_stmt->execute();
        
        $html .= '<div class="section-title">Species Distribution</div>
        <table>
            <thead>
                <tr>
                    <th>Species</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>';
        
        while ($species = $species_stmt->fetch(PDO::FETCH_ASSOC)) {
            $percentage = ($species['count'] / $pet_stats['total_pets']) * 100;
            $html .= '<tr>
                <td>' . htmlspecialchars(ucfirst($species['species'])) . '</td>
                <td>' . $species['count'] . '</td>
                <td>' . number_format($percentage, 1) . '%</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        break;
        
    case 'revenue_report':
        // Get ALL revenue data
        $revenue_query = "SELECT 
            i.id, i.total_amount, i.payment_amount, i.change_amount,
            i.created_at, a.appointment_number,
            CONCAT(u.first_name, ' ', u.last_name) as client_name,
            u.email as client_email, u.phone as client_phone
            FROM invoices i
            JOIN users u ON i.client_id = u.id
            LEFT JOIN appointments a ON i.appointment_id = a.id
            WHERE DATE(i.created_at) BETWEEN :date_from AND :date_to
            ORDER BY i.created_at DESC";
            
        $revenue_stmt = $db->prepare($revenue_query);
        $revenue_stmt->bindParam(':date_from', $date_from);
        $revenue_stmt->bindParam(':date_to', $date_to);
        $revenue_stmt->execute();
        
        // Get revenue statistics
        $total_revenue = 0;
        $invoice_count = 0;
        $revenue_data = [];
        
        while ($row = $revenue_stmt->fetch(PDO::FETCH_ASSOC)) {
            $revenue_data[] = $row;
            $total_revenue += $row['total_amount'];
            $invoice_count++;
        }
        
        $html .= '<div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $invoice_count . '</div>
                <div class="stat-label">Total Invoices</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱' . number_format($total_revenue, 2) . '</div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱' . ($invoice_count > 0 ? number_format($total_revenue / $invoice_count, 2) : '0.00') . '</div>
                <div class="stat-label">Average Invoice</div>
            </div>
        </div>';
        
        $html .= '<div class="section-title">All Revenue Details</div>
        <table>
            <thead>
                <tr>
                    <th>Invoice ID</th>
                    <th>Appointment #</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Change</th>
                </tr>
            </thead>
            <tbody>';
            
        foreach ($revenue_data as $row) {
            $html .= '<tr>
                <td>INV-' . str_pad($row['id'], 6, '0', STR_PAD_LEFT) . '</td>
                <td>' . htmlspecialchars($row['appointment_number']) . '</td>
                <td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>
                <td>' . htmlspecialchars($row['client_name']) . '</td>
                <td>' . htmlspecialchars($row['client_email']) . '<br><small>' . htmlspecialchars($row['client_phone']) . '</small></td>
                <td>₱' . number_format($row['total_amount'], 2) . '</td>
                <td>₱' . number_format($row['payment_amount'], 2) . '</td>
                <td>₱' . number_format($row['change_amount'], 2) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        break;
        
    case 'medical_records':
        // Get ALL medical records data
        $records_query = "SELECT 
            mr.id, mr.record_date, mr.diagnosis, mr.treatment, mr.medications, mr.notes,
            p.name as pet_name, p.species, p.breed, p.date_of_birth,
            CONCAT(owner.first_name, ' ', owner.last_name) as owner_name,
            owner.email as owner_email, owner.phone as owner_phone,
            CONCAT(vet.first_name, ' ', vet.last_name) as vet_name
            FROM medical_records mr
            JOIN pets p ON mr.pet_id = p.id
            JOIN users owner ON p.owner_id = owner.id
            JOIN users vet ON mr.created_by = vet.id
            WHERE DATE(mr.record_date) BETWEEN :date_from AND :date_to
            ORDER BY mr.record_date DESC";
            
        $records_stmt = $db->prepare($records_query);
        $records_stmt->bindParam(':date_from', $date_from);
        $records_stmt->bindParam(':date_to', $date_to);
        $records_stmt->execute();
        
        $html .= '<div class="section-title">All Medical Records</div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Pet Details</th>
                    <th>Owner</th>
                    <th>Veterinarian</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Medications</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>';
            
        while ($row = $records_stmt->fetch(PDO::FETCH_ASSOC)) {
            $html .= '<tr>
                <td>' . date('M d, Y', strtotime($row['record_date'])) . '</td>
                <td>' . htmlspecialchars($row['pet_name']) . '<br><small>' . htmlspecialchars($row['species']) . ' - ' . htmlspecialchars($row['breed']) . '<br>DOB: ' . htmlspecialchars($row['date_of_birth']) . '</small></td>
                <td>' . htmlspecialchars($row['owner_name']) . '<br><small>' . htmlspecialchars($row['owner_email']) . '</small></td>
                <td>Dr. ' . htmlspecialchars($row['vet_name']) . '</td>
                <td>' . htmlspecialchars($row['diagnosis']) . '</td>
                <td>' . htmlspecialchars($row['treatment']) . '</td>
                <td>' . htmlspecialchars($row['medications']) . '</td>
                <td>' . htmlspecialchars($row['notes']) . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        break;
        
    default: // Overview report
        // Get overview statistics
        $appt_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM appointments WHERE appointment_date BETWEEN :date_from AND :date_to";
        
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
        
        $completion_rate = $appt_stats['total'] > 0 ? 
            ($appt_stats['completed'] / $appt_stats['total']) * 100 : 0;
        
        $html .= '<div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">' . $appt_stats['total'] . '</div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $appt_stats['completed'] . '</div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . $new_clients . '</div>
                <div class="stat-label">New Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">' . number_format($completion_rate, 1) . '%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
        </div>';
        
        // Veterinarian performance
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
        
        $html .= '<div class="section-title">Veterinarian Performance</div>
        <table>
            <thead>
                <tr>
                    <th>Veterinarian</th>
                    <th>Total Appointments</th>
                    <th>Completed</th>
                    <th>Completion Rate</th>
                </tr>
            </thead>
            <tbody>';
        
        while ($vet = $vet_stmt->fetch(PDO::FETCH_ASSOC)) {
            $vet_completion_rate = $vet['total_appointments'] > 0 ? 
                ($vet['completed_appointments'] / $vet['total_appointments']) * 100 : 0;
            
            $html .= '<tr>
                <td>Dr. ' . htmlspecialchars($vet['vet_name']) . '</td>
                <td>' . $vet['total_appointments'] . '</td>
                <td>' . $vet['completed_appointments'] . '</td>
                <td>' . number_format($vet_completion_rate, 1) . '%</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        break;
}

$html .= '<div class="footer">
    <p>This report was generated by PawssibleSolutions Veterinary Management System</p>
    <p>© ' . date('Y') . ' PawssibleSolutions - All Rights Reserved</p>
</div>';

$html .= '</body></html>';

// Set headers for PDF download
$filename = $report_type . '_report_' . $date_from . '_to_' . $date_to . '.pdf';

// For basic HTML to PDF conversion, we'll use the browser's print functionality
// In a production environment, you would use libraries like TCPDF, mPDF, or Dompdf
header('Content-Type: text/html; charset=UTF-8');
header('Content-Disposition: inline; filename="' . $filename . '"');

// Add print styles and auto-print JavaScript
$html = str_replace('</head>', '
<style media="print">
    @page { margin: 1cm; }
    body { margin: 0; }
    .no-print { display: none; }
</style>
<script>
    window.onload = function() {
        // Auto-print when page loads
        window.print();
    }
</script>
</head>', $html);

echo $html;
exit;
?>