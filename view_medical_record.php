<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: medical_records.php");
    exit;
}

$record_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get medical record details, ensuring it belongs to one of the user's pets
$query = "SELECT mr.*, p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth,
          a.appointment_date, a.appointment_time, a.reason as appointment_reason,
          CONCAT(u.first_name, ' ', u.last_name) as vet_name
          FROM medical_records mr
          JOIN pets p ON mr.pet_id = p.id
          LEFT JOIN appointments a ON mr.appointment_id = a.id
          LEFT JOIN vets v ON a.vet_id = v.id
          LEFT JOIN users u ON v.user_id = u.id
          WHERE mr.id = :record_id AND p.owner_id = :owner_id";
          
$stmt = $db->prepare($query);
$stmt->bindParam(':record_id', $record_id);
$stmt->bindParam(':owner_id', $user_id);
$stmt->execute();

// Check if record exists and belongs to the user
if ($stmt->rowCount() == 0) {
    header("Location: medical_records.php");
    exit;
}

$record = $stmt->fetch(PDO::FETCH_ASSOC);

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Medical Record</h1>
            <a href="medical_records.php" class="text-white hover:text-blue-100 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Records
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <div class="bg-blue-50 p-6">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">
                        <?php echo htmlspecialchars($record['pet_name']); ?>
                        <span class="text-lg font-normal text-gray-600">
                            (<?php echo htmlspecialchars($record['species']); 
                            if (!empty($record['breed'])) echo ', ' . htmlspecialchars($record['breed']); ?>)
                        </span>
                    </h2>
                    <p class="text-gray-600 mt-1">
                        <?php echo date('F d, Y', strtotime($record['record_date'])); ?>
                    </p>
                </div>
                
                <?php if (!empty($record['vet_name'])): ?>
                <div class="mt-4 md:mt-0 md:text-right">
                    <p class="text-gray-600">Attending Veterinarian:</p>
                    <p class="font-semibold">Dr. <?php echo htmlspecialchars($record['vet_name']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($record['appointment_date'])): ?>
        <div class="bg-gray-100 p-4 border-y border-gray-200">
            <h3 class="font-semibold text-gray-700">Appointment Details</h3>
            <div class="grid md:grid-cols-2 gap-4 mt-2">
                <div>
                    <span class="text-gray-500">Date:</span>
                    <span class="ml-1"><?php echo date('l, F d, Y', strtotime($record['appointment_date'])); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Time:</span>
                    <span class="ml-1"><?php echo date('h:i A', strtotime($record['appointment_time'])); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Reason for Visit:</span>
                    <span class="ml-1"><?php echo htmlspecialchars($record['appointment_reason']); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="p-6">
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-3 pb-2 border-b border-gray-200">Diagnosis</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <?php if (!empty($record['diagnosis'])): ?>
                        <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No diagnosis recorded</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-3 pb-2 border-b border-gray-200">Treatment</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <?php if (!empty($record['treatment'])): ?>
                        <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                    <?php else: ?>
                        <p class="text-gray-500 italic">No treatment recorded</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($record['medications'])): ?>
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-3 pb-2 border-b border-gray-200">Medications</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['medications'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($record['notes'])): ?>
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-3 pb-2 border-b border-gray-200">Additional Notes</h3>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-between">
                <a href="medical_records.php?pet_id=<?php echo $record['pet_id']; ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-list mr-2"></i> View All Records for <?php echo htmlspecialchars($record['pet_name']); ?>
                </a>
                <a href="pet_details.php?id=<?php echo $record['pet_id']; ?>" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-dog mr-2"></i> Go to Pet Profile
                </a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
