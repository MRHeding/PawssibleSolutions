<?php
session_start();
include_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    if (($handle = fopen($file, 'r')) !== false) {
        $header = fgetcsv($handle); // skip header
        $rowCount = 0;
        while (($data = fgetcsv($handle)) !== false) {
            // Map CSV columns
            list($id, $name, $category, $description, $quantity, $unit, $unit_price, $reorder_level, $supplier, $expiry_date, $location) = array_pad($data, 11, null);
            // Check if item exists by name
            $check = $db->prepare("SELECT id FROM inventory WHERE name = :name");
            $check->bindParam(':name', $name);
            $check->execute();
            if ($check->rowCount() > 0) {
                // Update existing
                $update = $db->prepare("UPDATE inventory SET category=:category, description=:description, quantity=:quantity, unit=:unit, unit_price=:unit_price, reorder_level=:reorder_level, supplier=:supplier, expiry_date=:expiry_date, location=:location WHERE name=:name");
                $update->bindParam(':category', $category);
                $update->bindParam(':description', $description);
                $update->bindParam(':quantity', $quantity);
                $update->bindParam(':unit', $unit);
                $update->bindParam(':unit_price', $unit_price);
                $update->bindParam(':reorder_level', $reorder_level);
                $update->bindParam(':supplier', $supplier);
                $update->bindParam(':expiry_date', $expiry_date);
                $update->bindParam(':location', $location);
                $update->bindParam(':name', $name);
                $update->execute();
            } else {
                // Insert new
                $insert = $db->prepare("INSERT INTO inventory (name, category, description, quantity, unit, unit_price, reorder_level, supplier, expiry_date, location) VALUES (:name, :category, :description, :quantity, :unit, :unit_price, :reorder_level, :supplier, :expiry_date, :location)");
                $insert->bindParam(':name', $name);
                $insert->bindParam(':category', $category);
                $insert->bindParam(':description', $description);
                $insert->bindParam(':quantity', $quantity);
                $insert->bindParam(':unit', $unit);
                $insert->bindParam(':unit_price', $unit_price);
                $insert->bindParam(':reorder_level', $reorder_level);
                $insert->bindParam(':supplier', $supplier);
                $insert->bindParam(':expiry_date', $expiry_date);
                $insert->bindParam(':location', $location);
                $insert->execute();
            }
            $rowCount++;
        }
        fclose($handle);
        $success = "Import successful. $rowCount items processed.";
    } else {
        $error = 'Failed to open uploaded file.';
    }
}
?>
<?php include_once '../includes/admin_header.php'; ?>
<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Import Inventory Items</h1>
        <a href="inventory.php" class="text-white hover:text-indigo-100"><i class="fas fa-arrow-left mr-2"></i> Back to Inventory</a>
    </div>
</div>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6">
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="csv_file" class="block text-gray-700 text-sm font-bold mb-2">Select CSV File</label>
                <input type="file" name="csv_file" id="csv_file" accept=".csv" required class="block w-full text-sm text-gray-700 border border-gray-300 rounded py-2 px-3">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Import</button>
        </form>
        <div class="mt-6 text-sm text-gray-600">
            <strong>CSV Format:</strong><br>
            ID, Name, Category, Description, Quantity, Unit, Unit Price, Reorder Level, Supplier, Expiry Date, Location<br>
            (ID is ignored on import; items are matched by Name)
        </div>
    </div>
</div>
<?php include_once '../includes/admin_footer.php'; ?>
