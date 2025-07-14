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
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
$message = '';
$messageClass = '';
$pets = [];
$appointment_info = null;

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// If appointment_id is provided, get pet information from the appointment
if (!empty($appointment_id)) {
    $appointment_query = "SELECT a.id as appointment_id, a.appointment_date, a.reason,
                         p.id as id, p.name as pet_name, p.species, p.breed, 
                         u.first_name, u.last_name, u.id as owner_id
                         FROM appointments a
                         JOIN pets p ON a.pet_id = p.id 
                         JOIN users u ON p.owner_id = u.id 
                         WHERE a.id = :appointment_id";
    $appointment_stmt = $db->prepare($appointment_query);
    $appointment_stmt->bindParam(':appointment_id', $appointment_id);
    $appointment_stmt->execute();
    
    if ($appointment_stmt->rowCount() > 0) {
        $appointment_info = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if a medical record already exists for this appointment
        $existing_record_query = "SELECT id FROM medical_records WHERE appointment_id = :appointment_id";
        $existing_record_stmt = $db->prepare($existing_record_query);
        $existing_record_stmt->bindParam(':appointment_id', $appointment_id);
        $existing_record_stmt->execute();
        
        if ($existing_record_stmt->rowCount() > 0) {
            // Medical record already exists, redirect to view it
            $existing_record = $existing_record_stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['info_message'] = "A medical record already exists for this appointment. You can view it below.";
            header("Location: ../view_medical_record.php?id=" . $existing_record['id']);
            exit;
        }
        
        $pet_id = $appointment_info['id']; // Set pet_id from appointment
        $pet = $appointment_info; // Use appointment info as pet info
        
        // Ensure consistent field structure for the pet data
        $pet['pet_id'] = $pet['id']; // Add pet_id field for consistency
        if (!isset($pet['name']) && isset($pet['pet_name'])) {
            $pet['name'] = $pet['pet_name']; // Add name field for consistency
        }
        
        // Auto-select record type based on appointment reason
        $suggested_record_type = '';
        if (!empty($appointment_info['reason'])) {
            $reason = strtolower(trim($appointment_info['reason']));
            
            // Map appointment reasons to record types
            if (strpos($reason, 'wellness') !== false || strpos($reason, 'checkup') !== false || strpos($reason, 'check-up') !== false || strpos($reason, 'routine') !== false) {
                $suggested_record_type = 'Wellness Exam';
            } elseif (strpos($reason, 'vaccination') !== false || strpos($reason, 'vaccine') !== false || strpos($reason, 'shots') !== false || strpos($reason, 'immunization') !== false) {
                $suggested_record_type = 'Vaccination';
            } elseif (strpos($reason, 'sick') !== false || strpos($reason, 'illness') !== false || strpos($reason, 'not feeling well') !== false || strpos($reason, 'symptoms') !== false) {
                $suggested_record_type = 'Sick Visit';
            } elseif (strpos($reason, 'injury') !== false || strpos($reason, 'injured') !== false || strpos($reason, 'hurt') !== false || strpos($reason, 'wound') !== false || strpos($reason, 'accident') !== false) {
                $suggested_record_type = 'Injury';
            } elseif (strpos($reason, 'dental') !== false || strpos($reason, 'teeth') !== false || strpos($reason, 'tooth') !== false || strpos($reason, 'cleaning') !== false) {
                $suggested_record_type = 'Dental Care';
            } elseif (strpos($reason, 'surgery') !== false || strpos($reason, 'operation') !== false || strpos($reason, 'surgical') !== false) {
                $suggested_record_type = 'Surgery Consultation';
            } elseif (strpos($reason, 'follow') !== false || strpos($reason, 'followup') !== false || strpos($reason, 'follow-up') !== false || strpos($reason, 'recheck') !== false) {
                $suggested_record_type = 'Follow-up Visit';
            } else {
                $suggested_record_type = 'Other';
            }
        }
    } else {
        $message = "Appointment not found";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
        $appointment_id = 0; // Reset appointment_id since it's invalid
    }
}

// Debug: Show data structure when appointment_id is provided
if (!empty($appointment_id) && isset($appointment_info)) {
    // Ensure consistent field names for the pet data
    if (isset($appointment_info['id']) && !isset($appointment_info['pet_id'])) {
        $appointment_info['pet_id'] = $appointment_info['id'];
    }
    if (!isset($appointment_info['name']) && isset($appointment_info['pet_name'])) {
        $appointment_info['name'] = $appointment_info['pet_name'];
    }
}

// Get list of pets for dropdown if neither pet_id nor appointment_id is provided, or if they're invalid
if (empty($pet_id)) {
    $pets_query = "SELECT p.id, p.name, p.species, p.breed, u.first_name, u.last_name 
                  FROM pets p 
                  JOIN users u ON p.owner_id = u.id 
                  ORDER BY p.name";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->execute();
    $pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (empty($appointment_info)) {
    // Get pet details if pet_id is provided directly (not from appointment)
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
    $appointment_id_form = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $record_date = $_POST['record_date'];
    $record_type = $_POST['record_type'];
    $reason_for_visit = $_POST['reason_for_visit'] ?? '';
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
            
            // Handle reason_for_visit field if it exists
            if (in_array('reason_for_visit', $columns)) {
                $fields[] = 'reason_for_visit';
                $placeholders[] = ':reason_for_visit';
                $params[':reason_for_visit'] = $reason_for_visit;
            } elseif (in_array('reason', $columns)) {
                // Try alternative field name 'reason'
                $fields[] = 'reason';
                $placeholders[] = ':reason';
                $params[':reason'] = $reason_for_visit;
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
            
            // Handle appointment_id field if it exists and we have an appointment
            if (in_array('appointment_id', $columns) && !empty($appointment_id_form)) {
                $fields[] = 'appointment_id';
                $placeholders[] = ':appointment_id';
                $params[':appointment_id'] = $appointment_id_form;
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
                
                // Redirect based on where we came from
                if (!empty($appointment_id_form)) {
                    // Go back to appointment view if we came from an appointment
                    header("Location: view_appointment.php?id=" . $appointment_id_form);
                    exit;
                } else {
                    // Redirect to view the newly created record if view page exists
                    $check_file = file_exists('../view_medical_record.php');
                    if ($check_file) {
                        header("Location: ../view_medical_record.php?id=" . $record_id);
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
        <div class="flex gap-2">
            <?php if (!empty($appointment_id)): ?>
                <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded">
                    View Appointment
                </a>
            <?php endif; ?>
            <a href="medical_records.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded">
                Back to All Records
            </a>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="<?php echo $messageClass; ?> px-4 py-3 rounded mb-4 border">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($appointment_info)): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-2">
                <i class="fas fa-calendar-check mr-2"></i>Appointment Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="font-medium text-blue-700">Appointment Date:</span>
                    <p><?php echo date('M d, Y', strtotime($appointment_info['appointment_date'])); ?></p>
                </div>
                <div>
                    <span class="font-medium text-blue-700">Pet:</span>
                    <p><?php echo htmlspecialchars($appointment_info['pet_name']); ?> (<?php echo htmlspecialchars($appointment_info['species']); ?>)</p>
                </div>
                <div>
                    <span class="font-medium text-blue-700">Reason for Visit:</span>
                    <p><?php echo htmlspecialchars($appointment_info['reason']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-xl font-semibold">New Medical Record</h2>
        </div>
        
        <div class="p-6">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <?php if (!empty($appointment_id)): ?>
                    <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-3 text-violet-700">Basic Information</h3>
                        
                        <div class="mb-4">
                            <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                            <select name="pet_id" id="pet_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required <?php echo !empty($appointment_info) ? 'readonly style="background-color: #f9f9f9;"' : ''; ?>>
                                <option value="">Select Pet</option>
                                <?php if (!empty($pet_id) && isset($pet)): ?>
                                    <option value="<?php echo $pet['id']; ?>" selected>
                                        <?php echo htmlspecialchars($pet['pet_name'] ?? $pet['name']); ?> (<?php echo htmlspecialchars($pet['species'] . ' - ' . $pet['breed']); ?>) - 
                                        Owner: <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                                    </option>
                                    <?php if (empty($appointment_info)): ?>
                                        <?php foreach ($pets as $other_pet): ?>
                                            <?php if ($other_pet['id'] != $pet['id']): ?>
                                                <option value="<?php echo $other_pet['id']; ?>">
                                                    <?php echo htmlspecialchars($other_pet['name']); ?> (<?php echo htmlspecialchars($other_pet['species'] . ' - ' . $other_pet['breed']); ?>) - 
                                                    Owner: <?php echo htmlspecialchars($other_pet['first_name'] . ' ' . $other_pet['last_name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php foreach ($pets as $pet_option): ?>
                                        <option value="<?php echo $pet_option['id']; ?>">
                                            <?php echo htmlspecialchars($pet_option['name']); ?> (<?php echo htmlspecialchars($pet_option['species'] . ' - ' . $pet_option['breed']); ?>) - 
                                            Owner: <?php echo htmlspecialchars($pet_option['first_name'] . ' ' . $pet_option['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (!empty($appointment_info)): ?>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>Pet is automatically selected from the appointment
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="record_date" class="block text-gray-700 text-sm font-bold mb-2">Record Date</label>
                            <input type="date" name="record_date" id="record_date" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="record_type" class="block text-gray-700 text-sm font-bold mb-2">Record Type *</label>
                            <select name="record_type" id="record_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <option value="">Select record type</option>
                                <option value="Wellness Exam" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Wellness Exam') ? 'selected' : ''; ?>>Wellness Exam</option>
                                <option value="Vaccination" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                                <option value="Sick Visit" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Sick Visit') ? 'selected' : ''; ?>>Sick Visit</option>
                                <option value="Injury" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Injury') ? 'selected' : ''; ?>>Injury</option>
                                <option value="Dental Care" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Dental Care') ? 'selected' : ''; ?>>Dental Care</option>
                                <option value="Surgery Consultation" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Surgery Consultation') ? 'selected' : ''; ?>>Surgery Consultation</option>
                                <option value="Follow-up Visit" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Follow-up Visit') ? 'selected' : ''; ?>>Follow-up Visit</option>
                                <option value="Other" <?php echo (isset($suggested_record_type) && $suggested_record_type === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <?php if (!empty($appointment_info) && !empty($suggested_record_type)): ?>
                                <p class="text-sm text-gray-600 mt-1">
                                    <i class="fas fa-info-circle mr-1"></i>Auto-selected based on appointment reason. You can change if needed.
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-3 text-violet-700">Medical Details</h3>
                        
                        <div class="mb-4">
                            <label for="diagnosis" class="block text-gray-700 text-sm font-bold mb-2">Diagnosis</label>
                            <textarea name="diagnosis" id="diagnosis" rows="2" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter diagnosis after examination..."></textarea>
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
