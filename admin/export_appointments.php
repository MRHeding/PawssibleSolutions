<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if user is admin or staff
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'staff')) {
    header("Location: ../index.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get the same filter values as in appointments.php
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$vet_filter = isset($_GET['vet_id']) ? $_GET['vet_id'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the same appointments query as in appointments.php
$query = "SELECT a.*, p.name as pet_name, p.species, 
         CONCAT(o.first_name, ' ', o.last_name) as owner_name,
         CONCAT(v.first_name, ' ', v.last_name) as vet_name,
         o.email as owner_email, o.phone as owner_phone
         FROM appointments a 
         JOIN pets p ON a.pet_id = p.id 
         JOIN users o ON p.owner_id = o.id
         JOIN vets vt ON a.vet_id = vt.id 
         JOIN users v ON vt.user_id = v.id";

// Add filters (same logic as appointments.php)
if (!empty($status_filter)) {
    $query .= " WHERE a.status = :status";
} else {
    $query .= " WHERE 1=1"; // Placeholder for additional filters
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = :date";
}

if (!empty($vet_filter)) {
    $query .= " AND a.vet_id = :vet_id";
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE :search OR o.first_name LIKE :search OR o.last_name LIKE :search 
               OR v.first_name LIKE :search OR v.last_name LIKE :search OR a.reason LIKE :search)";
}

// Add sorting
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

// Prepare and execute the query
$stmt = $db->prepare($query);

// Bind filter parameters if they exist (same logic as appointments.php)
if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}
if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}
if (!empty($vet_filter)) {
    $stmt->bindParam(':vet_id', $vet_filter);
}
if (!empty($search)) {
    $search_param = '%' . $search . '%';
    $stmt->bindParam(':search', $search_param);
}

$stmt->execute();

// Generate filename with current date and applied filters
$filename = 'appointments_export_' . date('Y-m-d_H-i-s');
if (!empty($status_filter)) {
    $filename .= '_' . $status_filter;
}
if (!empty($date_filter)) {
    $filename .= '_' . $date_filter;
}
$filename .= '.csv';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create file pointer for output
$output = fopen('php://output', 'w');

// CSV column headers
$headers = [
    'Appointment Number',
    'Date',
    'Time',
    'Pet Name',
    'Pet Species',
    'Owner Name',
    'Owner Email',
    'Owner Phone',
    'Veterinarian',
    'Reason',
    'Status',
    'Notes',
    'Created At'
];

// Write headers to CSV
fputcsv($output, $headers);

// Write data rows
while ($appointment = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $row = [
        $appointment['appointment_number'],
        date('Y-m-d', strtotime($appointment['appointment_date'])),
        date('H:i', strtotime($appointment['appointment_time'])),
        $appointment['pet_name'],
        $appointment['species'],
        $appointment['owner_name'],
        $appointment['owner_email'],
        $appointment['owner_phone'],
        'Dr. ' . $appointment['vet_name'],
        $appointment['reason'],
        ucfirst($appointment['status']),
        $appointment['notes'] ?? '',
        $appointment['created_at'] ?? ''
    ];
    
    fputcsv($output, $row);
}

// Close the file pointer
fclose($output);
exit;
?>
