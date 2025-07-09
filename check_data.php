<?php
include_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Check current date
echo 'Current Date: ' . date('Y-m-d') . PHP_EOL;

// Check appointment dates in the database
$query = 'SELECT appointment_date, COUNT(*) as count FROM appointments GROUP BY appointment_date ORDER BY appointment_date';
$stmt = $db->prepare($query);
$stmt->execute();
echo 'Appointment dates in database:' . PHP_EOL;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['appointment_date'] . ': ' . $row['count'] . ' appointments' . PHP_EOL;
}

// Check default date range being used in reports
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
echo PHP_EOL . 'Default report date range:' . PHP_EOL;
echo 'From: ' . $current_month_start . PHP_EOL;
echo 'To: ' . $current_month_end . PHP_EOL;

// Check appointments in current month
$query = 'SELECT COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN ? AND ?';
$stmt = $db->prepare($query);
$stmt->execute([$current_month_start, $current_month_end]);
$count = $stmt->fetchColumn();
echo 'Appointments in current month: ' . $count . PHP_EOL;

// Check appointments by status
$query = 'SELECT status, COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY status';
$stmt = $db->prepare($query);
$stmt->execute([$current_month_start, $current_month_end]);
echo PHP_EOL . 'Appointments by status in current month:' . PHP_EOL;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['status'] . ': ' . $row['count'] . PHP_EOL;
}

// Check today's date filtering
$today = date('Y-m-d');
$query = 'SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ?';
$stmt = $db->prepare($query);
$stmt->execute([$today]);
$today_count = $stmt->fetchColumn();
echo PHP_EOL . 'Appointments today (' . $today . '): ' . $today_count . PHP_EOL;

// Check if there are any future appointments that might be included incorrectly
$query = 'SELECT appointment_date, COUNT(*) as count FROM appointments WHERE appointment_date > ? GROUP BY appointment_date ORDER BY appointment_date LIMIT 5';
$stmt = $db->prepare($query);
$stmt->execute([$today]);
echo PHP_EOL . 'Future appointments:' . PHP_EOL;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['appointment_date'] . ': ' . $row['count'] . ' appointments' . PHP_EOL;
}
?>
