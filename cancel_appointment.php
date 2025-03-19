<?php
session_start();
include_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Check if appointment ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: my_appointments.php");
    exit;
}

$appointment_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if the appointment belongs to one of the user's pets and is still scheduled
$appointment_check = "SELECT a.*, p.name as pet_name, p.owner_id 
                     FROM appointments a 
                     JOIN pets p ON a.pet_id = p.id 
                     WHERE a.id = :appointment_id AND p.owner_id = :owner_id 
                     AND a.status = 'scheduled'";
$check_stmt = $db->prepare($appointment_check);
$check_stmt->bindParam(':appointment_id', $appointment_id);
$check_stmt->bindParam(':owner_id', $user_id);
$check_stmt->execute();

if ($check_stmt->rowCount() == 0) {
    header("Location: my_appointments.php");
    exit;
}

$appointment = $check_stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission for cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $confirm = isset($_POST['confirm']) ? $_POST['confirm'] : '';
    
    if ($confirm !== 'yes') {
        $error = "You must confirm the cancellation.";
    } else {
        // Update appointment status to cancelled
        $query = "UPDATE appointments SET status = 'cancelled' WHERE id = :appointment_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':appointment_id', $appointment_id);
        
        try {
            if ($stmt->execute()) {
                $success = "Appointment cancelled successfully!";
                // Redirect to appointments page after a delay
                header("refresh:2;url=my_appointments.php");
            } else {
                $error = "Something went wrong. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Cancel Appointment</h1>
            <a href="my_appointments.php" class="text-white hover:text-blue-100 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Appointments
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
                <p class="mt-2">Redirecting to appointments page...</p>
            </div>
        <?php else: ?>
            <div class="text-center mb-6">
                <div class="text-red-500 text-5xl mb-4">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-700 mb-4">Cancel Appointment</h2>
                <p class="text-gray-600 mb-2">Are you sure you want to cancel this appointment?</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Pet</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['pet_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Date & Time</p>
                        <p class="font-medium">
                            <?php echo date('l, M d, Y', strtotime($appointment['appointment_date'])); ?><br>
                            <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Reason</p>
                        <p class="font-medium"><?php echo htmlspecialchars($appointment['reason']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Status</p>
                        <p class="font-medium">
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Scheduled</span>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($appointment['notes'])): ?>
                    <div class="mt-4">
                        <p class="text-gray-500 text-sm">Notes</p>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $appointment_id); ?>" method="post">
                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="confirm" id="confirm" value="yes" class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded">
                        <label for="confirm" class="ml-2 block text-gray-700">
                            I confirm that I want to cancel this appointment
                        </label>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mt-6">
                    <a href="my_appointments.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Go Back
                    </a>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Cancel Appointment
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 pt-6 border-t border-gray-200 text-center text-sm text-gray-500">
            <p>Note: Appointments cancelled less than 24 hours before the scheduled time may incur a cancellation fee according to our policy.</p>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
