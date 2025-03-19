<?php
session_start();
include_once 'config/database.php';

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

// Get user's pets
$query = "SELECT * FROM pets WHERE owner_id = :owner_id ORDER BY name";
$stmt = $db->prepare($query);
$stmt->bindParam(':owner_id', $user_id);
$stmt->execute();

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">My Pets</h1>
            <a href="add_pet.php" class="bg-white hover:bg-gray-100 text-teal-600 font-bold py-2 px-4 rounded inline-flex items-center transition">
                <i class="fas fa-plus mr-2"></i> Add New Pet
            </a>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 notification">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
            <button class="notification-close float-right font-semibold text-green-700">&times;</button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 notification">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
            <button class="notification-close float-right font-semibold text-red-700">&times;</button>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($pet = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="bg-<?php 
                            // Change color based on species
                            switch (strtolower($pet['species'])) {
                                case 'dog':
                                    echo 'blue';
                                    break;
                                case 'cat':
                                    echo 'yellow';
                                    break;
                                case 'bird':
                                    echo 'green';
                                    break;
                                case 'rabbit':
                                    echo 'purple';
                                    break;
                                default:
                                    echo 'gray';
                            }
                        ?>-100 p-4 flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="bg-white rounded-full p-2 mr-3">
                                    <i class="fas <?php 
                                        // Change icon based on species
                                        switch (strtolower($pet['species'])) {
                                            case 'dog':
                                                echo 'fa-dog text-blue-500';
                                                break;
                                            case 'cat':
                                                echo 'fa-cat text-yellow-500';
                                                break;
                                            case 'bird':
                                                echo 'fa-dove text-green-500';
                                                break;
                                            case 'rabbit':
                                                echo 'fa-rabbit text-purple-500';
                                                break;
                                            default:
                                                echo 'fa-paw text-gray-500';
                                        }
                                    ?> text-xl"></i>
                                </div>
                                <h3 class="text-xl font-bold"><?php echo htmlspecialchars($pet['name']); ?></h3>
                            </div>
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($pet['species']); ?></span>
                        </div>
                        <div class="p-4">
                            <div class="mb-3">
                                <?php if (!empty($pet['breed'])): ?>
                                    <p><span class="font-medium">Breed:</span> <?php echo htmlspecialchars($pet['breed']); ?></p>
                                <?php endif; ?>
                                <p><span class="font-medium">Gender:</span> <?php echo ucfirst($pet['gender']); ?></p>
                                <?php if (!empty($pet['date_of_birth'])): ?>
                                    <p><span class="font-medium">Birth Date:</span> <?php echo date('M d, Y', strtotime($pet['date_of_birth'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex justify-between items-center mt-4">
                                <a href="pet_details.php?id=<?php echo $pet['id']; ?>" class="text-teal-600 hover:text-teal-800 font-medium">
                                    View Details
                                </a>
                                <div class="flex space-x-2">
                                    <a href="schedule_appointment.php?pet_id=<?php echo $pet['id']; ?>" class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-calendar-plus" title="Schedule Appointment"></i>
                                    </a>
                                    <a href="edit_pet.php?id=<?php echo $pet['id']; ?>" class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-edit" title="Edit Pet"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-10">
                <div class="text-gray-400 mb-6">
                    <i class="fas fa-paw text-6xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">No Pets Found</h3>
                <p class="text-gray-600 mb-6">You haven't added any pets to your profile yet.</p>
                <a href="add_pet.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded transition">
                    Add Your First Pet
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($stmt->rowCount() > 0): ?>
        <!-- Pet Care Tips Section -->
        <div class="mt-8 bg-blue-50 rounded-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Pet Care Tips</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="bg-blue-100 p-2 rounded-full mr-3">
                        <i class="fas fa-heartbeat text-blue-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium">Regular Check-ups</h4>
                        <p class="text-sm text-gray-600">Schedule annual wellness exams for your pets to catch health issues early.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="bg-green-100 p-2 rounded-full mr-3">
                        <i class="fas fa-apple-alt text-green-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium">Proper Nutrition</h4>
                        <p class="text-sm text-gray-600">Ensure your pet is getting a balanced diet appropriate for their age, size, and health status.</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="bg-purple-100 p-2 rounded-full mr-3">
                        <i class="fas fa-running text-purple-600"></i>
                    </div>
                    <div>
                        <h4 class="font-medium">Exercise Regularly</h4>
                        <p class="text-sm text-gray-600">Regular physical activity helps maintain your pet's weight and keeps them mentally stimulated.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
