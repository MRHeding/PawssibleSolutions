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

// Get medical record details
$query = "SELECT mr.*, p.name as pet_name, p.species, p.breed, p.gender, p.date_of_birth, p.weight, p.microchip_id,
         CONCAT(o.first_name, ' ', o.last_name) as owner_name,
         o.email as owner_email, o.phone as owner_phone,
         CONCAT(v.first_name, ' ', v.last_name) as vet_name,
         vd.specialization, vd.license_number,
         a.appointment_number, a.appointment_date, a.appointment_time
         FROM medical_records mr
         JOIN pets p ON mr.pet_id = p.id
         JOIN users o ON p.owner_id = o.id
         JOIN users v ON mr.created_by = v.id
         LEFT JOIN vets vd ON v.id = vd.user_id
         LEFT JOIN appointments a ON mr.appointment_id = a.id
         WHERE mr.id = :record_id";

$stmt = $db->prepare($query);
$stmt->bindParam(':record_id', $record_id);
$stmt->execute();

$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    header("Location: medical_records.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record - <?php echo htmlspecialchars($record['pet_name']); ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                font-size: 12px;
            }
            .print-header {
                border-bottom: 2px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
            }
        }
    </style>
</head>
<body class="bg-white">
    <!-- Print Controls -->
    <div class="no-print fixed top-4 right-4 z-10">
        <button onclick="window.print()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">
            <i class="fas fa-print"></i> Print
        </button>
        <button onclick="window.close()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            <i class="fas fa-times"></i> Close
        </button>
    </div>

    <div class="max-w-4xl mx-auto p-8">
        <!-- Clinic Header -->
        <div class="print-header text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Pawssible Solutions Veterinary Clinic</h1>
            <p class="text-gray-600 mt-2">Briana Catapang Tower, MCLL Highway, Guiwan Zamboanga City</p>
            <p class="text-gray-600">Phone: 09477312312 | Email: psvc.inc@gmail.com</p>
        </div>

        <!-- Medical Record Title -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">MEDICAL RECORD</h2>
            <p class="text-gray-600 mt-2">Record ID: #<?php echo $record['id']; ?></p>
        </div>

        <!-- Record Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <!-- Pet Information -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Pet Information</h3>
                <div class="space-y-2">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($record['pet_name']); ?></p>
                    <p><strong>Species:</strong> <?php echo htmlspecialchars($record['species']); ?></p>
                    <p><strong>Breed:</strong> <?php echo htmlspecialchars($record['breed']); ?></p>
                    <?php if ($record['date_of_birth']): ?>
                        <p><strong>Age:</strong> 
                            <?php 
                            $birth_date = new DateTime($record['date_of_birth']);
                            $today = new DateTime();
                            $age = $birth_date->diff($today);
                            echo $age->y . ' years, ' . $age->m . ' months';
                            ?>
                        </p>
                        <p><strong>Date of Birth:</strong> <?php echo date('F j, Y', strtotime($record['date_of_birth'])); ?></p>
                    <?php endif; ?>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($record['gender']); ?></p>
                    <?php if ($record['microchip_id']): ?>
                        <p><strong>Microchip ID:</strong> <?php echo htmlspecialchars($record['microchip_id']); ?></p>
                    <?php endif; ?>
                    <p><strong>Weight:</strong> <?php echo htmlspecialchars($record['weight']); ?> kg</p>
                </div>
            </div>

            <!-- Owner Information -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">Owner Information</h3>
                <div class="space-y-2">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($record['owner_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($record['owner_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($record['owner_phone']); ?></p>
                </div>
            </div>
        </div>

        <!-- Appointment Information -->
        <?php if ($record['appointment_number']): ?>
        <div class="bg-blue-50 p-6 rounded-lg mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Appointment Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <p><strong>Appointment #:</strong> <?php echo htmlspecialchars($record['appointment_number']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($record['appointment_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($record['appointment_time'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Medical Record Details -->
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Medical Record Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <p><strong>Record Date:</strong> <?php echo date('F j, Y', strtotime($record['record_date'])); ?></p>
                    <p><strong>Veterinarian:</strong> <?php echo htmlspecialchars($record['vet_name']); ?></p>
                    <?php if ($record['specialization']): ?>
                    <p><strong>Specialization:</strong> <?php echo htmlspecialchars($record['specialization']); ?></p>
                    <?php endif; ?>
                    <?php if ($record['license_number']): ?>
                    <p><strong>License #:</strong> <?php echo htmlspecialchars($record['license_number']); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p><strong>Record Created:</strong> <?php echo date('F j, Y g:i A', strtotime($record['created_at'])); ?></p>
                    <?php if ($record['updated_at'] && $record['updated_at'] != $record['created_at']): ?>
                    <p><strong>Last Updated:</strong> <?php echo date('F j, Y g:i A', strtotime($record['updated_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Diagnosis -->
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Diagnosis</h4>
                <div class="bg-gray-50 p-4 rounded border min-h-[100px]">
                    <?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?>
                </div>
            </div>

            <!-- Treatment -->
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Treatment</h4>
                <div class="bg-gray-50 p-4 rounded border min-h-[100px]">
                    <?php echo nl2br(htmlspecialchars($record['treatment'])); ?>
                </div>
            </div>

            <!-- Medications -->
            <?php if (!empty($record['medications'])): ?>
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Medications</h4>
                <div class="bg-gray-50 p-4 rounded border min-h-[100px]">
                    <?php echo nl2br(htmlspecialchars($record['medications'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if (!empty($record['notes'])): ?>
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-2">Additional Notes</h4>
                <div class="bg-gray-50 p-4 rounded border min-h-[100px]">
                    <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 pt-6 mt-8">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-600">Medical Record ID: #<?php echo $record['id']; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Veterinarian Signature:</p>
                    <div class="mt-4 border-b border-gray-400 w-48"></div>
                    <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($record['vet_name']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js" crossorigin="anonymous"></script>
</body>
</html>
