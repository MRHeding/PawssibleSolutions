<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = '';
$success_msg = '';

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

// Check if coming from an appointment
$appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : null;
$appointment = null;
$pet = null;

if ($appointment_id) {
    // Get appointment details with pet information
    $appointment_query = "SELECT a.*, 
                         p.id as pet_id, p.name as pet_name, p.species, p.breed, p.gender,
                         CONCAT(u.first_name, ' ', u.last_name) as owner_name
                         FROM appointments a 
                         JOIN pets p ON a.pet_id = p.id
                         JOIN users u ON p.owner_id = u.id
                         WHERE a.id = :appointment_id AND a.vet_id = :vet_id";
    $appointment_stmt = $db->prepare($appointment_query);
    $appointment_stmt->bindParam(':appointment_id', $appointment_id);
    $appointment_stmt->bindParam(':vet_id', $vet_id);
    $appointment_stmt->execute();
    
    if ($appointment_stmt->rowCount() > 0) {
        $appointment = $appointment_stmt->fetch(PDO::FETCH_ASSOC);
        $pet_id = $appointment['pet_id'];
        $pet = [
            'id' => $appointment['pet_id'],
            'name' => $appointment['pet_name'],
            'species' => $appointment['species'],
            'breed' => $appointment['breed'],
            'gender' => $appointment['gender'],
            'owner_name' => $appointment['owner_name']
        ];
    } else {
        // Invalid appointment ID
        header("Location: appointments.php");
        exit;
    }
} else {
    // Not coming from an appointment, determine pet_id if provided
    $pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : null;
    
    if ($pet_id) {
        // Get pet details
        $pet_query = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as owner_name
                     FROM pets p 
                     JOIN users u ON p.owner_id = u.id
                     WHERE p.id = :pet_id";
        $pet_stmt = $db->prepare($pet_query);
        $pet_stmt->bindParam(':pet_id', $pet_id);
        $pet_stmt->execute();
        
        if ($pet_stmt->rowCount() > 0) {
            $pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Invalid pet ID
            header("Location: patients.php");
            exit;
        }
    }
}

// Get list of pets for select dropdown if needed
$pets_list = [];
if (!$pet_id) {
    $pets_query = "SELECT DISTINCT p.id, p.name, p.species, p.breed, 
                  CONCAT(u.first_name, ' ', u.last_name) as owner_name
                  FROM pets p
                  JOIN users u ON p.owner_id = u.id
                  JOIN appointments a ON p.id = a.pet_id
                  WHERE a.vet_id = :vet_id
                  ORDER BY p.name";
    $pets_stmt = $db->prepare($pets_query);
    $pets_stmt->bindParam(':vet_id', $vet_id);
    $pets_stmt->execute();
    $pets_list = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (empty($_POST['pet_id']) || empty($_POST['record_date']) || empty($_POST['diagnosis']) || empty($_POST['treatment'])) {
        $error_msg = "Please fill in all required fields.";
    } else {
        try {
            $db->beginTransaction();
            
            $record_date = $_POST['record_date'];
            $diagnosis = $_POST['diagnosis'];
            $treatment = $_POST['treatment'];
            $medications = $_POST['medications'] ?? null;
            $notes = $_POST['notes'] ?? null;
            $pet_id = $_POST['pet_id'];
            $appointment_id = isset($_POST['appointment_id']) && !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : null;
            
            // Insert the medical record
            $query = "INSERT INTO medical_records (pet_id, appointment_id, record_date, diagnosis, treatment, medications, notes, created_by) 
                      VALUES (:pet_id, :appointment_id, :record_date, :diagnosis, :treatment, :medications, :notes, :created_by)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':pet_id', $pet_id);
            $stmt->bindParam(':appointment_id', $appointment_id);
            $stmt->bindParam(':record_date', $record_date);
            $stmt->bindParam(':diagnosis', $diagnosis);
            $stmt->bindParam(':treatment', $treatment);
            $stmt->bindParam(':medications', $medications);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':created_by', $user_id);
            
            $stmt->execute();
            $record_id = $db->lastInsertId();
            
            // If this is linked to an appointment, update the appointment status to completed if not already
            if ($appointment_id) {
                $update_query = "UPDATE appointments SET status = 'completed' WHERE id = :appointment_id AND status = 'scheduled'";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':appointment_id', $appointment_id);
                $update_stmt->execute();
            }
            
            $db->commit();
            $success_msg = "Medical record created successfully!";
            
            // Redirect to the record view page
            header("Location: view_record.php?id=" . $record_id . "&success=created");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $error_msg = "Database error: " . $e->getMessage();
        }
    }
}

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Create Medical Record</h1>
                <p class="text-white text-opacity-90 mt-2">
                    <?php if ($pet): ?>
                        For <?php echo htmlspecialchars($pet['name']); ?> 
                        (<?php echo htmlspecialchars($pet['species']); ?>
                        <?php if (!empty($pet['breed'])): ?> - <?php echo htmlspecialchars($pet['breed']); ?><?php endif; ?>)
                    <?php else: ?>
                        New patient record
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <?php if ($appointment_id): ?>
                    <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Appointment
                    </a>
                <?php elseif ($pet_id): ?>
                    <a href="view_pet.php?id=<?php echo $pet_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Pet Records
                    </a>
                <?php else: ?>
                    <a href="records.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-violet-600 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Records
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($error_msg)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="font-bold">Error:</span> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form method="post" action="">
            <!-- Hidden fields -->
            <?php if ($appointment_id): ?>
                <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
            <?php endif; ?>
            
            <!-- Pet Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="pet_id">Patient</label>
                <?php if ($pet_id): ?>
                    <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">
                    <div class="flex items-center p-3 bg-gray-50 rounded-md">
                        <div class="h-10 w-10 bg-violet-100 rounded-full flex items-center justify-center text-violet-600 mr-3">
                            <?php 
                            $icon = 'fa-paw';
                            if (isset($pet['species'])) {
                                if (strtolower($pet['species']) === 'dog') $icon = 'fa-dog';
                                elseif (strtolower($pet['species']) === 'cat') $icon = 'fa-cat';
                                elseif (strtolower($pet['species']) === 'bird') $icon = 'fa-dove';
                                elseif (strtolower($pet['species']) === 'fish') $icon = 'fa-fish';
                            }
                            ?>
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($pet['name']); ?></p>
                            <p class="text-sm text-gray-600">
                                Owner: <?php echo htmlspecialchars($pet['owner_name']); ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <select id="pet_id" name="pet_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                        <option value="">Select a patient</option>
                        <?php foreach ($pets_list as $pet_item): ?>
                            <option value="<?php echo $pet_item['id']; ?>">
                                <?php echo htmlspecialchars($pet_item['name']); ?> (<?php echo htmlspecialchars($pet_item['species']); ?>)
                                - Owner: <?php echo htmlspecialchars($pet_item['owner_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <!-- Record date -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="record_date">Record Date</label>
                <input type="date" id="record_date" name="record_date" required 
                       value="<?php echo isset($appointment) ? $appointment['appointment_date'] : date('Y-m-d'); ?>" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
            </div>
            
            <!-- Diagnosis -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="diagnosis">Diagnosis</label>
                <input type="text" id="diagnosis" name="diagnosis" required
                       placeholder="Primary diagnosis or assessment" 
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                <p class="mt-1 text-sm text-gray-500">Enter the primary clinical findings or diagnosis</p>
            </div>
            
            <!-- Treatment -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="treatment">Treatment</label>
                <textarea id="treatment" name="treatment" rows="4" required
                          placeholder="Treatment provided or recommended" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"></textarea>
                <p class="mt-1 text-sm text-gray-500">Describe the treatment provided during this visit or recommended going forward</p>
            </div>
            
            <!-- Medications -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="medications">Medications</label>
                <textarea id="medications" name="medications" rows="3"
                          placeholder="Prescribed medications (optional)" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"></textarea>
                <p class="mt-1 text-sm text-gray-500">List any medications prescribed, including dosage instructions</p>
            </div>
            
            <!-- Notes -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" rows="3"
                          placeholder="Additional notes (optional)" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"></textarea>
                <p class="mt-1 text-sm text-gray-500">Any additional notes, observations, or follow-up instructions</p>
            </div>
            
            <!-- Action buttons -->
            <div class="flex justify-end space-x-3">
                <?php if ($appointment_id): ?>
                    <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        Cancel
                    </a>
                <?php else: ?>
                    <a href="records.php" class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                        Cancel
                    </a>
                <?php endif; ?>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-violet-600 hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500">
                    <i class="fas fa-save mr-2"></i> Save Record
                </button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
