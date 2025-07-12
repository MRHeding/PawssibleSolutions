<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: medical_records.php");
    exit;
}

$record_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $query = "UPDATE medical_records SET 
                  record_date = :record_date,
                  record_type = :record_type,
                  diagnosis = :diagnosis,
                  treatment = :treatment,
                  medications = :medications,
                  notes = :notes,
                  updated_at = NOW()
                  WHERE id = :record_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':record_date', $_POST['record_date']);
        $stmt->bindParam(':record_type', $_POST['record_type']);
        $stmt->bindParam(':diagnosis', $_POST['diagnosis']);
        $stmt->bindParam(':treatment', $_POST['treatment']);
        $stmt->bindParam(':medications', $_POST['medications']);
        $stmt->bindParam(':notes', $_POST['notes']);
        $stmt->bindParam(':record_id', $record_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Medical record updated successfully!";
            header("Location: medical_records.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Error updating medical record.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Get medical record details
$query = "SELECT mr.*, p.name as pet_name, p.species, p.breed,
         CONCAT(o.first_name, ' ', o.last_name) as owner_name,
         CONCAT(v.first_name, ' ', v.last_name) as vet_name
         FROM medical_records mr
         JOIN pets p ON mr.pet_id = p.id
         JOIN users o ON p.owner_id = o.id
         JOIN users v ON mr.created_by = v.id
         WHERE mr.id = :record_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':record_id', $record_id);
$stmt->execute();

$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    $_SESSION['error_message'] = "Medical record not found.";
    header("Location: medical_records.php");
    exit;
}

include_once '../includes/admin_header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Edit Medical Record</h1>
                <p class="text-sm text-gray-600 mt-2">
                    Pet: <?php echo htmlspecialchars($record['pet_name']); ?> | 
                    Owner: <?php echo htmlspecialchars($record['owner_name']); ?>
                </p>
            </div>

            <form method="POST" class="px-6 py-4 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Record Date -->
                    <div>
                        <label for="record_date" class="block text-sm font-medium text-gray-700">Record Date</label>
                        <input type="date" name="record_date" id="record_date" 
                               value="<?php echo $record['record_date']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                               required>
                    </div>

                    <!-- Reason for Visit -->
                    <div>
                        <label for="record_type" class="block text-sm font-medium text-gray-700">Reason for Visit *</label>
                        <select name="record_type" id="record_type" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                                required>
                            <option value="">Select reason</option>
                            <option value="Wellness Exam" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Wellness Exam') ? 'selected' : ''; ?>>Wellness Exam</option>
                            <option value="Vaccination" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Vaccination') ? 'selected' : ''; ?>>Vaccination</option>
                            <option value="Sick Visit" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Sick Visit') ? 'selected' : ''; ?>>Sick Visit</option>
                            <option value="Injury" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Injury') ? 'selected' : ''; ?>>Injury</option>
                            <option value="Dental Care" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Dental Care') ? 'selected' : ''; ?>>Dental Care</option>
                            <option value="Surgery Consultation" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Surgery Consultation') ? 'selected' : ''; ?>>Surgery Consultation</option>
                            <option value="Follow-up Visit" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Follow-up Visit') ? 'selected' : ''; ?>>Follow-up Visit</option>
                            <option value="Other" <?php echo (isset($record['record_type']) && $record['record_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Created By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Created By</label>
                        <p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars($record['vet_name']); ?></p>
                    </div>
                </div>

                <!-- Diagnosis -->
                <div>
                    <label for="diagnosis" class="block text-sm font-medium text-gray-700">Diagnosis</label>
                    <textarea name="diagnosis" id="diagnosis" rows="4" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                              required><?php echo htmlspecialchars($record['diagnosis']); ?></textarea>
                </div>

                <!-- Treatment -->
                <div>
                    <label for="treatment" class="block text-sm font-medium text-gray-700">Treatment</label>
                    <textarea name="treatment" id="treatment" rows="4" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                              required><?php echo htmlspecialchars($record['treatment']); ?></textarea>
                </div>

                <!-- Medications -->
                <div>
                    <label for="medications" class="block text-sm font-medium text-gray-700">Medications</label>
                    <textarea name="medications" id="medications" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo htmlspecialchars($record['medications']); ?></textarea>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
                    <textarea name="notes" id="notes" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo htmlspecialchars($record['notes']); ?></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="medical_records.php" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/admin_footer.php'; ?>
