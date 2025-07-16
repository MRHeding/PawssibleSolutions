<?php
include_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Existing services in database:\n";
$query = 'SELECT id, name, price FROM services ORDER BY id';
$stmt = $db->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($services as $service) {
    echo "ID: " . $service['id'] . " - Name: " . $service['name'] . " - Price: " . $service['price'] . "\n";
}

echo "\nChecking invoice_items table structure:\n";
$query = 'DESCRIBE invoice_items';
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo $column['Field'] . " - " . $column['Type'] . " - NULL: " . $column['Null'] . " - Key: " . $column['Key'] . "\n";
}
?>
