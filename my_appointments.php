<?php
session_start();
include_once 'config/database.php';

// Add cache-busting headers to ensure fresh appointment status
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Clear session messages after storing them
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get user's pets for filter
$pets_query = "SELECT id, name FROM pets WHERE owner_id = :owner_id ORDER BY name";
$pets_stmt = $db->prepare($pets_query);
$pets_stmt->bindParam(':owner_id', $user_id);
$pets_stmt->execute();
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// Set default filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$pet_filter = isset($_GET['pet_id']) ? $_GET['pet_id'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build the query with filters
$query = "SELECT a.*, p.name as pet_name, p.species,
         CONCAT(u.first_name, ' ', u.last_name) as vet_name
         FROM appointments a
         JOIN pets p ON a.pet_id = p.id
         JOIN vets v ON a.vet_id = v.id
         JOIN users u ON v.user_id = u.id
         WHERE p.owner_id = :user_id";

if (!empty($status_filter)) {
    $query .= " AND a.status = :status";
}

if (!empty($pet_filter)) {
    $query .= " AND p.id = :pet_id";
}

if (!empty($date_filter)) {
    $query .= " AND a.appointment_date = :date";
}

$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);

if (!empty($status_filter)) {
    $stmt->bindParam(':status', $status_filter);
}

if (!empty($pet_filter)) {
    $stmt->bindParam(':pet_id', $pet_filter);
}

if (!empty($date_filter)) {
    $stmt->bindParam(':date', $date_filter);
}

$stmt->execute();

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">My Appointments</h1>
            <a href="schedule_appointment.php" class="bg-white hover:bg-gray-100 text-violet-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                <i class="fas fa-plus mr-2"></i> Schedule New
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($success_message)): ?>
        <!-- Success Modal Popup -->
        <div id="successModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Appointment Scheduled!</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            <?php echo $success_message; ?>
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button id="closeSuccessModal" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <!-- Error Modal Popup -->
        <div id="errorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Error</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            <?php echo $error_message; ?>
                        </p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button id="closeErrorModal" class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Filter Appointments</h3>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="status" class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                <select id="status" name="status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="no-show" <?php echo $status_filter === 'no-show' ? 'selected' : ''; ?>>No-Show</option>
                </select>
            </div>
            
            <?php if (count($pets) > 1): ?>
                <div>
                    <label for="pet_id" class="block text-gray-700 text-sm font-bold mb-2">Pet</label>
                    <select id="pet_id" name="pet_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
                        <option value="">All Pets</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['id']; ?>" <?php echo $pet_filter == $pet['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div>
                <label for="date" class="block text-gray-700 text-sm font-bold mb-2">Date</label>
                <input type="date" id="date" name="date" value="<?php echo $date_filter; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" onchange="this.form.submit()">
            </div>
        </form>
        
        <?php if (!empty($status_filter) || !empty($pet_filter) || !empty($date_filter)): ?>
            <div class="mt-4 flex justify-end">
                <a href="my_appointments.php" class="text-sm text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times-circle mr-1"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Appointments List -->
    <?php if ($stmt->rowCount() > 0): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointment #</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($appointment = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['appointment_number']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['pet_name']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($appointment['species']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">Dr. <?php echo htmlspecialchars($appointment['vet_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['reason']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        switch($appointment['status']) {
                                            case 'scheduled':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            case 'completed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'no-show':
                                                echo 'bg-orange-100 text-orange-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($appointment['status'] === 'scheduled'): ?>
                                        <?php if (strtotime($appointment['appointment_date']) >= strtotime(date('Y-m-d'))): ?>
                                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="cancel_appointment.php?id=<?php echo $appointment['id']; ?>" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif ($appointment['status'] === 'completed'): ?>
                                        <a href="view_medical_record.php?appointment_id=<?php echo $appointment['id']; ?>" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-file-medical"></i> View Record
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-calendar-times text-6xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">No Appointments Found</h3>
            <p class="text-gray-600 mb-6">
                <?php if (!empty($status_filter) || !empty($pet_filter) || !empty($date_filter)): ?>
                    No appointments match your current filters. Try changing your filter criteria.
                <?php else: ?>
                    You haven't scheduled any appointments yet.
                <?php endif; ?>
            </p>
            <a href="schedule_appointment.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-6 rounded transition">
                Schedule an Appointment
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Appointment Guidelines -->
    <div class="mt-8 bg-blue-50 p-6 rounded-lg shadow-md">
        <h3 class="text-xl font-semibold mb-4">Appointment Guidelines</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-800 mb-2">Before Your Visit</h4>
                <ul class="space-y-1 text-gray-600">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Arrive 10 minutes before your scheduled appointment time</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Keep dogs on leashes and cats in carriers</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                        <span>Bring any previous medical records if this is your first visit</span>
                    </li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-800 mb-2">Cancellation Policy</h4>
                <ul class="space-y-1 text-gray-600">
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Please notify us at least 24 hours in advance to cancel or reschedule</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Late cancellations (less than 24 hours) may be subject to a fee</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                        <span>Emergency situations will be handled on a case-by-case basis</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
// Modal close functionality
document.addEventListener('DOMContentLoaded', function() {
    // Success modal close button
    const closeSuccessModal = document.getElementById('closeSuccessModal');
    const successModal = document.getElementById('successModal');
    
    if (closeSuccessModal && successModal) {
        closeSuccessModal.addEventListener('click', function() {
            successModal.style.display = 'none';
        });
        
        // Close modal when clicking outside of it
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                successModal.style.display = 'none';
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && successModal.style.display !== 'none') {
                successModal.style.display = 'none';
            }
        });
    }
    
    // Error modal close button
    const closeErrorModal = document.getElementById('closeErrorModal');
    const errorModal = document.getElementById('errorModal');
    
    if (closeErrorModal && errorModal) {
        closeErrorModal.addEventListener('click', function() {
            errorModal.style.display = 'none';
        });
        
        // Close modal when clicking outside of it
        errorModal.addEventListener('click', function(e) {
            if (e.target === errorModal) {
                errorModal.style.display = 'none';
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && errorModal.style.display !== 'none') {
                errorModal.style.display = 'none';
            }
        });
    }
});
</script>
