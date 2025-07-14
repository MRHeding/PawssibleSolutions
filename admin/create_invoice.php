<?php
session_start();
include_once '../config/database.php';
include_once '../includes/service_price_mapper.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Cache control headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = trim($_POST['appointment_id']);
    $client_id = trim($_POST['client_id']);
    $amount = trim($_POST['amount']);
    $description = trim($_POST['description']);
    
    // Validation
    if (empty($client_id) || empty($amount) || empty($description)) {
        $error = "Please fill in all required fields.";
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = "Please enter a valid amount.";
    } else {
        try {
            $db->beginTransaction();
            
            // Insert invoice directly with the existing table structure
            $query = "INSERT INTO invoices (appointment_id, client_id, total_amount, notes, created_at) 
                     VALUES (:appointment_id, :client_id, :amount, :description, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':appointment_id', $appointment_id);
            $stmt->bindParam(':client_id', $client_id);
            $stmt->bindParam(':amount', $amount);
            $stmt->bindParam(':description', $description);
            
            if ($stmt->execute()) {
                $invoice_id = $db->lastInsertId();
                
                $db->commit();
                $_SESSION['success'] = "Invoice created successfully with ID: " . $invoice_id;
                header("Location: view_invoice.php?id=" . $invoice_id);
                exit;
            } else {
                throw new Exception("Failed to create invoice.");
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Error creating invoice: " . $e->getMessage();
        }
    }
}

// Get clients for dropdown
$clients_query = "SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.email FROM users u WHERE u.role = 'client' ORDER BY u.first_name, u.last_name";
$clients_stmt = $db->prepare($clients_query);
$clients_stmt->execute();
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get appointments for dropdown
$appointments_query = "SELECT a.id as appointment_id, a.appointment_number, a.appointment_date, a.appointment_time, 
                              a.reason as service, a.notes,
                              CONCAT(u.first_name, ' ', u.last_name) as client_name, p.name as pet_name, 
                              p.owner_id as client_id, p.species, p.breed,
                              u.email as client_email, u.phone as client_phone
                       FROM appointments a 
                       JOIN pets p ON a.pet_id = p.id 
                       JOIN users u ON p.owner_id = u.id 
                       WHERE a.status = 'completed'
                       ORDER BY a.appointment_date DESC";
$appointments_stmt = $db->prepare($appointments_query);
$appointments_stmt->execute();
$appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Add service prices to appointments data
foreach ($appointments as &$appointment) {
    $appointment['service_price'] = ServicePriceMapper::getServicePrice($appointment['service']);
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Create Invoice</h1>
        <p class="text-white text-opacity-90 mt-2">Generate a new invoice for services rendered</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <form method="POST" id="invoiceForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Client Selection -->
                <div>
                    <label for="client_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user text-purple-600 mr-2"></i>Client *
                    </label>
                    <select name="client_id" id="client_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select a client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" 
                                    <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['full_name']) . ' (' . htmlspecialchars($client['email']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Appointment Selection (Optional) -->
                <div>
                    <label for="appointment_id" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-calendar-check text-purple-600 mr-2"></i>Related Appointment (Optional)
                    </label>
                    <select name="appointment_id" id="appointment_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select an appointment</option>
                        <?php foreach ($appointments as $appointment): ?>
                            <option value="<?php echo $appointment['appointment_id']; ?>" 
                                    data-client-id="<?php echo $appointment['client_id']; ?>"
                                    data-service="<?php echo htmlspecialchars($appointment['service']); ?>"
                                    data-service-price="<?php echo $appointment['service_price']; ?>"
                                    data-pet-name="<?php echo htmlspecialchars($appointment['pet_name']); ?>"
                                    data-pet-species="<?php echo htmlspecialchars($appointment['species']); ?>"
                                    data-pet-breed="<?php echo htmlspecialchars($appointment['breed']); ?>"
                                    data-appointment-notes="<?php echo htmlspecialchars($appointment['notes']); ?>"
                                    data-appointment-time="<?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>"
                                    <?php echo (isset($_POST['appointment_id']) && $_POST['appointment_id'] == $appointment['appointment_id']) ? 'selected' : ''; ?>>
                                <?php echo $appointment['appointment_number'] . ' - ' . htmlspecialchars($appointment['client_name']) . ' (' . htmlspecialchars($appointment['pet_name']) . ') - ' . date('M d, Y', strtotime($appointment['appointment_date'])) . ' - $' . number_format($appointment['service_price'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-dollar-sign text-purple-600 mr-2"></i>Total Amount *
                    </label>
                    <div class="relative">
                        <input type="number" name="amount" id="amount" step="0.01" min="0" required
                               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <button type="button" id="servicePriceHelper" class="absolute right-2 top-2 text-gray-400 hover:text-purple-600" title="View service pricing guide">
                            <i class="fas fa-question-circle"></i>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Amount will be auto-calculated when appointment is selected</p>
                </div>
            </div>

            <!-- Appointment Details Panel (Hidden by default) -->
            <div id="appointmentDetails" class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 hidden">
                <h3 class="text-lg font-semibold text-blue-800 mb-3">
                    <i class="fas fa-info-circle mr-2"></i>Appointment Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <strong>Client:</strong> <span id="detailClientName">-</span><br>
                        <strong>Pet:</strong> <span id="detailPetName">-</span> (<span id="detailPetSpecies">-</span>)<br>
                        <strong>Breed:</strong> <span id="detailPetBreed">-</span>
                    </div>
                    <div>
                        <strong>Service:</strong> <span id="detailService">-</span><br>
                        <strong>Date & Time:</strong> <span id="detailDateTime">-</span><br>
                        <strong>Suggested Amount:</strong> $<span id="detailPrice">0.00</span>
                    </div>
                </div>
                <div class="mt-3">
                    <strong>Appointment Notes:</strong> <span id="detailNotes">-</span>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-text text-purple-600 mr-2"></i>Description *
                </label>
                <textarea name="description" id="description" rows="3" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                          placeholder="Describe the services provided..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <!-- Submit Buttons -->
            <div class="mt-8 flex justify-end space-x-4">
                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i>Create Invoice
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Service Pricing Guide Modal -->
<div id="pricingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg p-6 max-w-md w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Service Pricing Guide</h3>
                <button type="button" id="closePricingModal" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span>Wellness Exam:</span><span class="font-semibold">$500.00</span></div>
                <div class="flex justify-between"><span>Vaccination:</span><span class="font-semibold">$500.00</span></div>
                <div class="flex justify-between"><span>Sick Visit:</span><span class="font-semibold">$1,000.00</span></div>
                <div class="flex justify-between"><span>Injury Treatment:</span><span class="font-semibold">$2,000.00</span></div>
                <div class="flex justify-between"><span>Dental Care:</span><span class="font-semibold">$500.00</span></div>
                <div class="flex justify-between"><span>Surgery Consultation:</span><span class="font-semibold">$700.00</span></div>
                <div class="flex justify-between"><span>Follow-up Visit:</span><span class="font-semibold">$300.00</span></div>
                <hr class="my-2">
                <div class="flex justify-between text-gray-600"><span>Default (Other services):</span><span class="font-semibold">$500.00</span></div>
            </div>
            <p class="text-xs text-gray-500 mt-4">
                <i class="fas fa-info-circle mr-1"></i>
                Prices are automatically calculated when you select an appointment
            </p>
        </div>
    </div>
</div>

<script>
// Auto-populate fields when appointment is selected
document.getElementById('appointment_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const appointmentDetails = document.getElementById('appointmentDetails');
    
    if (this.value === '') {
        // Hide details panel if no appointment selected
        appointmentDetails.classList.add('hidden');
        clearFields();
        return;
    }
    
    // Get all data attributes
    const clientId = selectedOption.getAttribute('data-client-id');
    const service = selectedOption.getAttribute('data-service');
    const servicePrice = selectedOption.getAttribute('data-service-price');
    const petName = selectedOption.getAttribute('data-pet-name');
    const petSpecies = selectedOption.getAttribute('data-pet-species');
    const petBreed = selectedOption.getAttribute('data-pet-breed');
    const appointmentNotes = selectedOption.getAttribute('data-appointment-notes');
    const appointmentTime = selectedOption.getAttribute('data-appointment-time');
    
    // Auto-populate form fields
    if (clientId) {
        document.getElementById('client_id').value = clientId;
    }
    
    if (servicePrice) {
        document.getElementById('amount').value = parseFloat(servicePrice).toFixed(2);
    }
    
    // Generate comprehensive description
    if (service && petName) {
        let description = `Service provided for ${petName}: ${service}`;
        if (petSpecies && petBreed) {
            description += `\n\nPet Details: ${petSpecies} (${petBreed})`;
        }
        if (appointmentNotes && appointmentNotes.trim() !== '') {
            description += `\n\nAppointment Notes: ${appointmentNotes}`;
        }
        description += `\n\nAppointment Time: ${appointmentTime}`;
        document.getElementById('description').value = description;
    }
    
    // Update appointment details panel
    updateAppointmentDetailsPanel(selectedOption);
    
    // Show details panel
    appointmentDetails.classList.remove('hidden');
    
    // Show notification that fields were auto-populated
    showAutoPopulateNotification();
});

function updateAppointmentDetailsPanel(selectedOption) {
    // Get client name from the option text
    const optionText = selectedOption.textContent;
    const clientName = optionText.split(' - ')[1].split(' (')[0];
    
    // Update detail fields
    document.getElementById('detailClientName').textContent = clientName;
    document.getElementById('detailPetName').textContent = selectedOption.getAttribute('data-pet-name') || '-';
    document.getElementById('detailPetSpecies').textContent = selectedOption.getAttribute('data-pet-species') || '-';
    document.getElementById('detailPetBreed').textContent = selectedOption.getAttribute('data-pet-breed') || '-';
    document.getElementById('detailService').textContent = selectedOption.getAttribute('data-service') || '-';
    
    // Format date and time
    const appointmentDate = optionText.match(/\w{3} \d{1,2}, \d{4}/)[0];
    const appointmentTime = selectedOption.getAttribute('data-appointment-time');
    document.getElementById('detailDateTime').textContent = `${appointmentDate} at ${appointmentTime}`;
    
    document.getElementById('detailPrice').textContent = parseFloat(selectedOption.getAttribute('data-service-price') || 0).toFixed(2);
    document.getElementById('detailNotes').textContent = selectedOption.getAttribute('data-appointment-notes') || 'No notes recorded';
}

function clearFields() {
    // Clear form fields when no appointment is selected
    document.getElementById('client_id').value = '';
    document.getElementById('amount').value = '';
    document.getElementById('description').value = '';
}

// Add visual feedback for required fields
document.addEventListener('DOMContentLoaded', function() {
    const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
    
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-300');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });
        
        field.addEventListener('input', function() {
            if (this.value.trim() !== '') {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-300');
            }
        });
    });
});

// Auto-format amount input
document.getElementById('amount').addEventListener('blur', function() {
    if (this.value && !isNaN(this.value)) {
        this.value = parseFloat(this.value).toFixed(2);
    }
});

// Service pricing guide modal
document.getElementById('servicePriceHelper').addEventListener('click', function() {
    document.getElementById('pricingModal').classList.remove('hidden');
});

document.getElementById('closePricingModal').addEventListener('click', function() {
    document.getElementById('pricingModal').classList.add('hidden');
});

// Close modal when clicking outside
document.getElementById('pricingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Add notification when fields are auto-populated
function showAutoPopulateNotification() {
    // Create a temporary notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50';
    notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i>Client and service details auto-populated!';
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<?php include_once '../includes/admin_footer.php'; ?>
