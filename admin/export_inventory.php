<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=inventory_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// CSV column headers
fputcsv($output, [
    'ID', 'Name', 'Category', 'Description', 'Quantity', 'Unit', 'Unit Price', 'Reorder Level', 'Supplier', 'Expiry Date', 'Location', 'Created At', 'Updated At'
]);

$query = "SELECT * FROM inventory ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['category'],
        $row['description'],
        $row['quantity'],
        $row['unit'],
        $row['unit_price'],
        $row['reorder_level'],
        $row['supplier'],
        $row['expiry_date'],
        $row['location'],
        $row['created_at'],
        $row['updated_at']
    ]);
}
fclose($output);
exit;
