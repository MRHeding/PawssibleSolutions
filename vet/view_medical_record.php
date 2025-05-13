<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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
$record = null;
$message = '';
$messageClass = '';

// Check if record ID is provided directly or via appointment_id
$record_id = null;
$appointment_id = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $record_id = intval($_GET['id']);
} elseif (isset($_GET['appointment_id']) && !empty($_GET['appointment_id'])) {
    $appointment_id = intval($_GET['appointment_id']);
    
    // Get the medical record ID from the appointment_id
    $record_query = "SELECT id FROM medical_records WHERE appointment_id = :appointment_id";
    $record_stmt = $db->prepare($record_query);
    $record_stmt->bindParam(':appointment_id', $appointment_id);
    $record_stmt->execute();
    
    if ($record_stmt->rowCount() > 0) {
        $record_id = $record_stmt->fetchColumn();
    } else {
        $message = "No medical record found for this appointment";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    }
}

if ($record_id) {
    // Get medical record details, ensuring the vet has permission to view it
    // First check if the record is linked to an appointment this vet handled
    $query = "SELECT mr.*, 
              p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth,
              a.appointment_date, a.appointment_time, a.reason as appointment_reason, a.status as appointment_status,
              CONCAT(u.first_name, ' ', u.last_name) as vet_name,
              o.first_name as owner_first_name, o.last_name as owner_last_name, o.email as owner_email, o.phone as owner_phone
              FROM medical_records mr
              JOIN pets p ON mr.pet_id = p.id
              LEFT JOIN appointments a ON mr.appointment_id = a.id
              LEFT JOIN vets v ON (a.vet_id = v.id OR mr.vet_id = v.id)
              LEFT JOIN users u ON v.user_id = u.id
              LEFT JOIN users o ON p.owner_id = o.id
              WHERE mr.id = :record_id AND (a.vet_id = :vet_id OR mr.vet_id = :vet_id2)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':record_id', $record_id);
    $stmt->bindParam(':vet_id', $vet_id);
    $stmt->bindParam(':vet_id2', $vet_id);
    $stmt->execute();
    
    // If not found, try to see if this vet has ever treated this pet
    if ($stmt->rowCount() == 0) {
        $query = "SELECT mr.*, 
                  p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth,
                  a.appointment_date, a.appointment_time, a.reason as appointment_reason, a.status as appointment_status,
                  CONCAT(u.first_name, ' ', u.last_name) as vet_name,
                  o.first_name as owner_first_name, o.last_name as owner_last_name, o.email as owner_email, o.phone as owner_phone
                  FROM medical_records mr
                  JOIN pets p ON mr.pet_id = p.id
                  LEFT JOIN appointments a ON mr.appointment_id = a.id
                  LEFT JOIN vets v ON (a.vet_id = v.id OR mr.vet_id = v.id)
                  LEFT JOIN users u ON v.user_id = u.id
                  LEFT JOIN users o ON p.owner_id = o.id
                  LEFT JOIN appointments a2 ON (p.id = a2.pet_id AND a2.vet_id = :vet_id)
                  WHERE mr.id = :record_id AND a2.id IS NOT NULL";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':vet_id', $vet_id);
        $stmt->execute();
    }
    
    // Check if record exists and vet has permission to view it
    if ($stmt->rowCount() == 0) {
        $message = "You do not have permission to view this medical record";
        $messageClass = "bg-red-100 border-red-400 text-red-700";
    } else {
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Include header
include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Medical Record</h1>
            <?php if ($appointment_id): ?>
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
    
    <?php if ($record): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-violet-50">
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
                            Record Date: <?php echo date('F d, Y', strtotime($record['record_date'])); ?>
                        </p>
                    </div>
                    
                    <div class="mt-4 md:mt-0 md:text-right">
                        <p class="text-gray-600">Record ID: #<?php echo $record['id']; ?></p>
                        <?php if (!empty($record['vet_name']) && $record['vet_name'] != $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name']): ?>
                            <p class="font-semibold">Created by: Dr. <?php echo htmlspecialchars($record['vet_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Patient Information</h3>
                    <p class="mb-2"><span class="font-semibold">Pet Name:</span> <?php echo htmlspecialchars($record['pet_name']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Species:</span> <?php echo htmlspecialchars($record['species']); ?></p>
                    <?php if (!empty($record['breed'])): ?>
                        <p class="mb-2"><span class="font-semibold">Breed:</span> <?php echo htmlspecialchars($record['breed']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($record['gender'])): ?>
                        <p class="mb-2"><span class="font-semibold">Gender:</span> <?php echo htmlspecialchars($record['gender']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($record['date_of_birth'])): ?>
                        <p class="mb-2">
                            <span class="font-semibold">Age:</span> 
                            <?php 
                                $dob = new DateTime($record['date_of_birth']);
                                $now = new DateTime();
                                $age = $now->diff($dob);
                                echo $age->y . " years, " . $age->m . " months";
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Owner Information</h3>
                    <p class="mb-2"><span class="font-semibold">Name:</span> <?php echo htmlspecialchars($record['owner_first_name'] . ' ' . $record['owner_last_name']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Email:</span> <?php echo htmlspecialchars($record['owner_email']); ?></p>
                    <p class="mb-2"><span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($record['owner_phone']); ?></p>
                </div>
            </div>
            
            <?php if (!empty($record['appointment_date'])): ?>
                <div class="bg-gray-100 p-6 border-y border-gray-200">
                    <h3 class="text-lg font-semibold mb-3 text-violet-700">Appointment Details</h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <p class="mb-2"><span class="font-semibold">Date:</span> <?php echo date('l, F d, Y', strtotime($record['appointment_date'])); ?></p>
                            <p class="mb-2"><span class="font-semibold">Time:</span> <?php echo date('h:i A', strtotime($record['appointment_time'])); ?></p>
                        </div>
                        <div>
                            <p class="mb-2"><span class="font-semibold">Reason for Visit:</span> <?php echo htmlspecialchars($record['appointment_reason']); ?></p>
                            <p class="mb-2">
                                <span class="font-semibold">Status:</span> 
                                <span class="<?php 
                                    $statusColor = 'text-gray-700';
                                    if ($record['appointment_status'] === 'scheduled') $statusColor = 'text-blue-600';
                                    if ($record['appointment_status'] === 'in progress') $statusColor = 'text-orange-600';
                                    if ($record['appointment_status'] === 'completed') $statusColor = 'text-green-600';
                                    if ($record['appointment_status'] === 'cancelled') $statusColor = 'text-red-600';
                                    if ($record['appointment_status'] === 'no-show') $statusColor = 'text-yellow-600';
                                    echo $statusColor;
                                ?> font-semibold">
                                    <?php echo ucfirst(htmlspecialchars($record['appointment_status'])); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold mb-3 pb-2 border-b border-gray-200 text-violet-700">Diagnosis</h3>
                        <div class="bg-gray-50 p-4 rounded">
                            <?php if (!empty($record['diagnosis'])): ?>
                                <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></p>
                            <?php else: ?>
                                <p class="text-gray-500 italic">No diagnosis recorded</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-3 pb-2 border-b border-gray-200 text-violet-700">Treatment</h3>
                        <div class="bg-gray-50 p-4 rounded">
                            <?php if (!empty($record['treatment'])): ?>
                                <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['treatment'])); ?></p>
                            <?php else: ?>
                                <p class="text-gray-500 italic">No treatment recorded</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($record['medications'])): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-3 pb-2 border-b border-gray-200 text-violet-700">Medications</h3>
                        <div class="bg-gray-50 p-4 rounded">
                            <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['medications'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($record['notes'])): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-3 pb-2 border-b border-gray-200 text-violet-700">Additional Notes</h3>
                        <div class="bg-gray-50 p-4 rounded">
                            <p class="whitespace-pre-line"><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-wrap gap-3">
                    <?php if (!empty($appointment_id)): ?>
                        <a href="view_appointment.php?id=<?php echo $appointment_id; ?>" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            <i class="fas fa-calendar-alt mr-2"></i> Return to Appointment
                        </a>
                    <?php endif; ?>
                    
                    <a href="edit_medical_record.php?id=<?php echo $record['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-edit mr-2"></i> Edit Record
                    </a>
                    
                    <a href="patient_history.php?pet_id=<?php echo $record['pet_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-history mr-2"></i> View Patient History
                    </a>
                    
                    <a href="#" onclick="window.print(); return false;" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        <i class="fas fa-print mr-2"></i> Print Record
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (empty($message)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <p>No medical record found. Please check the record ID or appointment ID.</p>
            <div class="mt-4">
                <a href="dashboard.php" class="text-violet-600 hover:text-violet-800">
                    <i class="fas fa-arrow-left mr-2"></i> Return to Dashboard
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        header, .bg-gradient-to-r, .flex.justify-between, .mt-8, footer, button, .no-print {
            display: none !important;
        }
        
        body {
            background-color: white;
            font-size: 12pt;
        }
        
        .container {
            width: 100%;
            max-width: 100%;
            padding: 0;
            margin: 0;
        }
        
        .rounded-lg, .rounded, .shadow-md {
            border-radius: 0 !important;
            box-shadow: none !important;
        }
        
        .p-6, .px-6, .py-4 {
            padding: 0.5cm !important;
        }
        
        h1 {
            font-size: 18pt;
            margin-bottom: 0.5cm;
        }
        
        h2 {
            font-size: 16pt;
        }
        
        h3 {
            font-size: 14pt;
        }
        
        .bg-violet-50, .bg-gray-50, .bg-gray-100 {
            background-color: white !important;
        }
    }
</style>

<?php include_once '../includes/vet_footer.php'; ?>
