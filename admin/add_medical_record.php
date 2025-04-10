<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;
$message = '';
$messageClass = '';
$pets = [];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get list of pets for dropdown if pet_id is not provided
if (empty($pet_id)) {
    $pets_query = "SELECT p.id, p.name, p.species, p.breed, u.first_name, u.last_name 
                  FROM pets p 
                  JOIN users u ON p.owner_id = u.id 
                  ORDER BY p.name";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->execute();
    $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get pet details if pet_id is provided
    $pet_query = "SELECT p.id, p.name, p.species, p.breed, u.first_name, u.last_name, u.id as owner_id
                 FROM pets p 
                 JOIN users u ON p.owner_id = u.id 
                 WHERE p.id = :pet_id";
    $pet_stmt = $db->prepare($pet_query);
    $pet_stmt->bindParam(':pet_id', $pet_id);
    $pet_stmt->execute();
    
    if ($pet_stmt->rowCount() === 0) {
        $message = "Pet not found";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
        // Reset pet_id since it's invalid
        $pet_id = 0;
        
        // Get all pets as fallback
        $pets_query = "SELECT p.id, p.name, p.species, p.breed, u.first_name, u.last_name 
                      FROM pets p 
                      JOIN users u ON p.owner_id = u.id 
                      ORDER BY p.name";
        $pets_stmt = $db->prepare($pets_query);
        $pets_stmt->execute();
        $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
    // Get form data
    $pet_id_form = intval($_POST['pet_id']);
    $record_date = $_POST['record_date'];
    $record_type = $_POST['record_type'];
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment = $_POST['treatment'] ?? '';
    $medications = $_POST['medications'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $vet_id = $_SESSION['user_id']; // Current admin as the vet
    
    // Validate input
    $errors = [];
    if (empty($pet_id_form)) $errors[] = "Please select a pet";
    if (empty($record_date)) $errors[] = "Record date is required";
    if (empty($record_type)) $errors[] = "Record type is required";
    
    if (empty($errors)) {
        try {
            // Check which columns actually exist in the medical_records table
            $columns_query = "SHOW COLUMNS FROM medical_records";
            $columns_stmt = $db->prepare($columns_query);
            $columns_stmt->execute();
            $columns = $columns_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Build the query dynamically based on existing columns
            $fields = [];
            $placeholders = [];
            $params = [];
            
            // Required fields
            if (in_array('pet_id', $columns)) {
                $fields[] = 'pet_id';
                $placeholders[] = ':pet_id';
                $params[':pet_id'] = $pet_id_form;
            }
            
            if (in_array('record_date', $columns)) {
                $fields[] = 'record_date';
                $placeholders[] = ':record_date';
                $params[':record_date'] = $record_date;
            }
            
            // Handle record_type field - which might be missing
            if (in_array('record_type', $columns)) {
                $fields[] = 'record_type';
                $placeholders[] = ':record_type';
                $params[':record_type'] = $record_type;
            } elseif (in_array('visit_type', $columns)) {
                // Try alternative field name 'visit_type' which might be used instead
                $fields[] = 'visit_type';
                $placeholders[] = ':visit_type';
                $params[':visit_type'] = $record_type;
            } elseif (in_array('type', $columns)) {
                // Or even just 'type'
                $fields[] = 'type';
                $placeholders[] = ':type';
                $params[':type'] = $record_type;
            } else {
                // If no type field is found, store it in notes
                $notes = "Type: " . $record_type . "\n\n" . $notes;
            }
            
            // Optional fields
            if (in_array('diagnosis', $columns)) {
                $fields[] = 'diagnosis';
                $placeholders[] = ':diagnosis';
                $params[':diagnosis'] = $diagnosis;
            }
            
            if (in_array('treatment', $columns)) {
                $fields[] = 'treatment';
                $placeholders[] = ':treatment';
                $params[':treatment'] = $treatment;
            }
            
            if (in_array('medications', $columns)) {
                $fields[] = 'medications';
                $placeholders[] = ':medications';
                $params[':medications'] = $medications;
            }
            
            if (in_array('notes', $columns)) {
                $fields[] = 'notes';
                $placeholders[] = ':notes';
                $params[':notes'] = $notes;
            }
            
            if (in_array('vet_id', $columns)) {
                $fields[] = 'vet_id';
                $placeholders[] = ':vet_id';
                $params[':vet_id'] = $vet_id;
            }
            
            // Handle created_by field - this is a foreign key that references users.id
            if (in_array('created_by', $columns)) {
                $fields[] = 'created_by';
                $placeholders[] = ':created_by';
                $params[':created_by'] = $_SESSION['user_id']; // The current logged-in admin
            }
            
            // Always add created_at
            if (in_array('created_at', $columns)) {
                $fields[] = 'created_at';
                $placeholders[] = 'NOW()';
            }
            
            // Construct the dynamic query
            $insert_query = "INSERT INTO medical_records (" . implode(", ", $fields) . ") 
                             VALUES (" . implode(", ", $placeholders) . ")";
            
            $stmt = $db->prepare($insert_query);
            
            // Bind parameters
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            if ($stmt->execute()) {
                $record_id = $db->lastInsertId();
                $message = "Medical record added successfully";
                $messageClass = "bg-green-100 border-green-400 text-green-700";
                
                // Redirect to view the newly created record if view page exists
                $check_file = file_exists('../admin/view_medical_record.php');
                if ($check_file) {
                    header("Location: view_medical_record.php?id=" . $record_id);
                    exit;
                }
            } else {
                $message = "Error adding medical record";
                $messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        } catch (PDOException $e) {
            // More specific error handling for foreign key constraints
            if ($e->getCode() == '42S02') {
                $message = "Medical records table does not exist. Please create the table first.";
            } elseif ($e->getCode() == '42S22') {
                // Missing column error
                $column_name = '';
                if (preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $matches)) {
                    $column_name = $matches[1];
                }
                $message = "Database schema issue: Column '{$column_name}' not found in medical_records table. Please update the database structure.";
            } elseif ($e->getCode() == '23000') {
                // Foreign key constraint error
                if (strpos($e->getMessage(), 'created_by') !== false) {
                    $message = "Foreign key constraint error: Your user ID does not exist in the users table. Please contact the system administrator.";
                } else {
                    $message = "Foreign key constraint error: " . $e->getMessage();
                }
            } else {
                $message = "Database error: " . $e->getMessage();
            }
            $messageClass = "bg-red-100 border-red-400 text-red-700";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    }
}

// Include header
include_once '../includes/admin_header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Add Medical Record</h1>
        <a href="medical_records.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
            Back to All Records
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold">New Medical Record</h2>
        </div>
        
        <div class="p-6">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-3 text-violet-700">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                            <select name="pet_id" id="pet_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select Pet</option>
                                <?php if (!empty($pet_id) && isset($pet)): ?>
                                    <option value="<?php echo $pet['id']; ?>" selected>
                                        <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species'] . ' - ' . $pet['breed']); ?>) - 
                                        Owner: <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                                    </option>
                                <?php else: ?>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?php echo $pet['id']; ?>">
                                            <?php echo htmlspecialchars($pet['name']); ?> (<?php echo htmlspecialchars($pet['species'] . ' - ' . $pet['breed']); ?>) - 
                                            Owner: <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="record_date" class="block text-gray-700 text-sm font-bold mb-2">Record Date</label>
                            <input type="date" name="record_date" id="record_date" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="record_type" class="block text-gray-700 text-sm font-bold mb-2">Record Type</label>
                            <select name="record_type" id="record_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select Type</option>
                                <option value="Check-up">Check-up</option>
                                <option value="Vaccination">Vaccination</option>
                                <option value="Surgery">Surgery</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Dental">Dental</option>
                                <option value="Laboratory">Laboratory Test</option>
                                <option value="Imaging">Imaging (X-Ray/Ultrasound)</option>
                                <option value="Prescription">Prescription</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-3 text-violet-700">Medical Details</h3>
                        
                        <div class="mb-4">
                            <label for="diagnosis" class="block text-gray-700 text-sm font-bold mb-2">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" rows="2" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="treatment" class="block text-gray-700 text-sm font-bold mb-2">Treatment</label>
                            <textarea name="treatment" id="treatment" rows="2" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="medications" class="block text-gray-700 text-sm font-bold mb-2">Medications</label>
                            <textarea name="medications" id="medications" rows="2" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 mb-6">
                    <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Additional Notes</label>
                    <textarea name="notes" id="notes" rows="4" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                </div>
                
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <div class="flex justify-end">
                        <a href="medical_records.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                            Cancel
                        </a>
                        <button type="submit" name="add_record" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Save Medical Record
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
