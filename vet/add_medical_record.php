<?php
session_start();
include_once '../config/database.php';
include_once '../includes/service_price_mapper.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageClass = '';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get vet information
$vet_query = "SELECT * FROM vets WHERE user_id = :user_id";
$vet_stmt = $db->prepare($vet_query);
$vet_stmt->bindParam(':user_id', $user_id);
$vet_stmt->execute();
$vet = $vet_stmt->fetch(PDO::FETCH_ASSOC);
$vet_id = $vet['id'];

// Initialize variables
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;

// If we have an appointment_id, get the appointment details and pet_id
if ($appointment_id > 0) {
    // Verify that the appointment belongs to this vet
    $appointment_query = "SELECT a.*, p.id as pet_id, p.name as pet_name, p.species, p.breed, 
                         CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                         u.email, u.phone 
                         FROM appointments a 
                         LEFT JOIN pets p ON a.pet_id = p.id 
                         LEFT JOIN users u ON p.owner_id = u.id 
                         WHERE a.id = :appointment_id AND a.vet_id = :vet_id";
    
    $appointment_stmt = $db->prepare($appointment_query);
    $appointment_stmt->bindParam(':appointment_id', $appointment_id);
    $appointment_stmt->bindParam(':vet_id', $vet_id);
    $appointment_stmt->execute();
    
    if ($appointment_stmt->rowCount() === 0) {
        $_SESSION['error_message'] = "You do not have permission to add a medical record for this appointment.";
        header("Location: appointments.php");
        exit;
    }
    
    $appointment = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
    $pet_id = $appointment['pet_id'];
    
    // Check if a medical record already exists for this appointment
    $check_record_query = "SELECT COUNT(*) FROM medical_records WHERE appointment_id = :appointment_id";
    $check_record_stmt = $db->prepare($check_record_query);
    $check_record_stmt->bindParam(':appointment_id', $appointment_id);
    $check_record_stmt->execute();
    
    if ($check_record_stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = "A medical record already exists for this appointment.";
        header("Location: view_appointment.php?id=" . $appointment_id);
        exit;
    }
} 
// If we only have a pet_id, get the pet details
elseif ($pet_id > 0) {
    // Verify that the vet has had appointments with this pet
    $pet_check_query = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as owner_name 
                       FROM pets p 
                       JOIN users u ON p.owner_id = u.id 
                       JOIN appointments a ON p.id = a.pet_id 
                       WHERE p.id = :pet_id AND a.vet_id = :vet_id 
                       LIMIT 1";
    
    $pet_check_stmt = $db->prepare($pet_check_query);
    $pet_check_stmt->bindParam(':pet_id', $pet_id);
    $pet_check_stmt->bindParam(':vet_id', $vet_id);
    $pet_check_stmt->execute();
    
    if ($pet_check_stmt->rowCount() === 0) {
        $_SESSION['error_message'] = "You do not have permission to add a medical record for this pet.";
        header("Location: dashboard.php");
        exit;
    }
    
    $pet = $pet_check_stmt->fetch(PDO::FETCH_ASSOC);
} 
// If we have neither, show a list of pets
else {
    // Get list of pets this vet has seen
    $pets_query = "SELECT DISTINCT p.id, p.name, p.species, p.breed, CONCAT(u.first_name, ' ', u.last_name) as owner_name 
                  FROM pets p 
                  JOIN appointments a ON p.id = a.pet_id 
                  JOIN users u ON p.owner_id = u.id 
                  WHERE a.vet_id = :vet_id 
                  ORDER BY p.name";
    
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->bindParam(':vet_id', $vet_id);
    $pets_stmt->execute();
    $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no pets found, redirect to dashboard with a message
    if ($pets_stmt->rowCount() === 0) {
        $_SESSION['error_message'] = "You haven't seen any pets yet.";
        header("Location: dashboard.php");
        exit;
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
            
            // Handle appointment_id field if it exists and we have an appointment_id
            if (in_array('appointment_id', $columns) && !empty($appointment_id)) {
                $fields[] = 'appointment_id';
                $placeholders[] = ':appointment_id';
                $params[':appointment_id'] = $appointment_id;
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
            
            // Handle created_by field if it exists
            if (in_array('created_by', $columns)) {
                $fields[] = 'created_by';
                $placeholders[] = ':created_by';
                $params[':created_by'] = $user_id;
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
                
                // If this was part of an appointment, update the appointment status to completed
                if (!empty($appointment_id)) {
                    $update_status_query = "UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = :appointment_id AND vet_id = :vet_id";
                    $update_status_stmt = $db->prepare($update_status_query);
                    $update_status_stmt->bindParam(':appointment_id', $appointment_id);
                    $update_status_stmt->bindParam(':vet_id', $vet_id);
                    
                    if ($update_status_stmt->execute()) {
                        // Auto-generate invoice when appointment is marked as completed
                        try {
                            ServicePriceMapper::autoGenerateInvoice($db, $appointment_id);
                        } catch (Exception $invoiceError) {
                            // Log invoice generation error but don't fail the medical record creation
                            error_log("Invoice generation failed for appointment $appointment_id: " . $invoiceError->getMessage());
                        }
                    }
                }
                
                $_SESSION['success_message'] = "Medical record added successfully";
                
                // Redirect to view the newly created record if view page exists
                if (file_exists('view_medical_record.php')) {
                    header("Location: view_medical_record.php?id=" . $record_id);
                    exit;
                } elseif (!empty($appointment_id)) {
                    // Redirect back to the appointment
                    header("Location: view_appointment.php?id=" . $appointment_id);
                    exit;
                } else {
                    // Redirect to a pet's history page if it exists
                    if (file_exists('patient_history.php')) {
                        header("Location: patient_history.php?pet_id=" . $pet_id_form);
                        exit;
                    } else {
                        header("Location: dashboard.php");
                        exit;
                    }
                }
            } else {
                $message = "Error adding medical record";
                $messageClass = "bg-red-100 border-red-400 text-red-700";
            }
        } catch (PDOException $e) {
            // More specific error handling for foreign key constraints
            if ($e->getCode() == '42S02') {
                $message = "Medical records table does not exist. Please contact an administrator.";
            } elseif ($e->getCode() == '42S22') {
                // Missing column error
                $column_name = '';
                if (preg_match("/Unknown column '([^']+)'/", $e->getMessage(), $matches)) {
                    $column_name = $matches[1];
                }
                $message = "Database schema issue: Column '{$column_name}' not found in medical_records table. Please contact an administrator.";
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
include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Add Medical Record</h1>
            <?php if (!empty($appointment_id)): ?>
                <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="bg-white hover:bg-gray-100 text-violet-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Appointment
                </a>
            <?php else: ?>
                <a href="dashboard.php" class="bg-white hover:bg-gray-100 text-violet-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-6">
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-violet-50">
            <h2 class="text-xl font-semibold text-violet-800">New Medical Record</h2>
            <?php if (!empty($appointment_id)): ?>
                <p class="text-sm text-gray-600">
                    For Appointment on: <?php echo date('F j, Y', strtotime($appointment['appointment_date'])) . ' at ' . date('g:i a', strtotime($appointment['appointment_time'])); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="p-6">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . (!empty($appointment_id) ? '?appointment_id=' . $appointment_id : '')); ?>" method="post">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-3 text-violet-700">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                            <select name="pet_id" id="pet_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required <?php if (!empty($appointment_id)) echo 'disabled'; ?>>
                                <option value="">Select Pet</option>
                                <?php if (!empty($appointment_id)): ?>
                                    <option value="<?php echo $appointment['pet_id']; ?>" selected>
                                        <?php echo htmlspecialchars($appointment['pet_name']); ?> 
                                        (<?php echo htmlspecialchars($appointment['species'] . (!empty($appointment['breed']) ? ' - ' . $appointment['breed'] : '')); ?>) - 
                                        Owner: <?php echo htmlspecialchars($appointment['owner_name']); ?>
                                    </option>
                                    <input type="hidden" name="pet_id" value="<?php echo $appointment['pet_id']; ?>">
                                <?php elseif (!empty($pet_id) && isset($pet)): ?>
                                    <option value="<?php echo $pet['id']; ?>" selected>
                                        <?php echo htmlspecialchars($pet['name']); ?>
                                        (<?php echo htmlspecialchars($pet['species'] . (!empty($pet['breed']) ? ' - ' . $pet['breed'] : '')); ?>) - 
                                        Owner: <?php echo htmlspecialchars($pet['owner_name']); ?>
                                    </option>
                                <?php else: ?>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?php echo $pet['id']; ?>">
                                            <?php echo htmlspecialchars($pet['name']); ?>
                                            (<?php echo htmlspecialchars($pet['species'] . (!empty($pet['breed']) ? ' - ' . $pet['breed'] : '')); ?>) - 
                                            Owner: <?php echo htmlspecialchars($pet['owner_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="record_date" class="block text-gray-700 text-sm font-bold mb-2">Record Date</label>
                            <input type="date" name="record_date" id="record_date" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo !empty($appointment_id) ? $appointment['appointment_date'] : date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="record_type" class="block text-gray-700 text-sm font-bold mb-2">Record Type</label>
                            <select name="record_type" id="record_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select Type</option>
                                <option value="Check-up" <?php if (!empty($appointment) && strpos(strtolower($appointment['reason']), 'check') !== false) echo 'selected'; ?>>Check-up</option>
                                <option value="Vaccination" <?php if (!empty($appointment) && strpos(strtolower($appointment['reason']), 'vaccin') !== false) echo 'selected'; ?>>Vaccination</option>
                                <option value="Surgery" <?php if (!empty($appointment) && strpos(strtolower($appointment['reason']), 'surg') !== false) echo 'selected'; ?>>Surgery</option>
                                <option value="Emergency" <?php if (!empty($appointment) && strpos(strtolower($appointment['reason']), 'emerg') !== false) echo 'selected'; ?>>Emergency</option>
                                <option value="Dental" <?php if (!empty($appointment) && strpos(strtolower($appointment['reason']), 'dental') !== false) echo 'selected'; ?>>Dental</option>
                                <option value="Laboratory" <?php if (!empty($appointment) && (strpos(strtolower($appointment['reason']), 'lab') !== false || strpos(strtolower($appointment['reason']), 'test') !== false)) echo 'selected'; ?>>Laboratory Test</option>
                                <option value="Imaging" <?php if (!empty($appointment) && (strpos(strtolower($appointment['reason']), 'x-ray') !== false || strpos(strtolower($appointment['reason']), 'ultrasound') !== false)) echo 'selected'; ?>>Imaging (X-Ray/Ultrasound)</option>
                                <option value="Prescription" <?php if (!empty($appointment) && strpos(strtolower($appointment['reason']), 'prescript') !== false) echo 'selected'; ?>>Prescription</option>
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
                    <textarea name="notes" id="notes" rows="4" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php if (!empty($appointment) && !empty($appointment['notes'])) echo "Client Notes: " . htmlspecialchars($appointment['notes']); ?></textarea>
                </div>
                
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <div class="flex justify-end">
                        <?php if (!empty($appointment_id)): ?>
                            <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded mr-2">
                                Cancel
                            </a>
                        <?php endif; ?>
                        <button type="submit" name="add_record" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Save Medical Record
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
