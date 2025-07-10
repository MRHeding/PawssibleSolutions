<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Check if pet ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pets.php");
    exit;
}

$pet_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $query = "UPDATE pets SET 
                  name = :name,
                  species = :species,
                  breed = :breed,
                  gender = :gender,
                  date_of_birth = :date_of_birth,
                  weight = :weight,
                  microchip_id = :microchip_id,
                  updated_at = NOW()
                  WHERE id = :pet_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':species', $_POST['species']);
        $stmt->bindParam(':breed', $_POST['breed']);
        $stmt->bindParam(':gender', $_POST['gender']);
        $stmt->bindParam(':date_of_birth', !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null);
        $stmt->bindParam(':weight', !empty($_POST['weight']) ? $_POST['weight'] : null);
        $stmt->bindParam(':microchip_id', !empty($_POST['microchip_id']) ? $_POST['microchip_id'] : null);
        $stmt->bindParam(':pet_id', $pet_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Pet information updated successfully!";
            header("Location: view_pet.php?id=" . $pet_id);
            exit;
        } else {
            $_SESSION['error_message'] = "Error updating pet information.";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Get pet details with owner information
$pet_query = "SELECT p.*, u.first_name, u.last_name, u.email 
              FROM pets p 
              JOIN users u ON p.owner_id = u.id 
              WHERE p.id = :pet_id";

$pet_stmt = $db->prepare($pet_query);
$pet_stmt->bindParam(':pet_id', $pet_id);
$pet_stmt->execute();

$pet = $pet_stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    $_SESSION['error_message'] = "Pet not found.";
    header("Location: pets.php");
    exit;
}

include_once '../includes/admin_header.php';
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit Pet Information</h1>
                        <p class="text-sm text-gray-600 mt-2">
                            Owner: <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="view_pet.php?id=<?php echo $pet['id']; ?>" 
                           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-arrow-left"></i> Back to Pet
                        </a>
                    </div>
                </div>
            </div>

            <form method="POST" class="px-6 py-4 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pet Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Pet Name</label>
                        <input type="text" name="name" id="name" 
                               value="<?php echo htmlspecialchars($pet['name']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                               required>
                    </div>

                    <!-- Species -->
                    <div>
                        <label for="species" class="block text-sm font-medium text-gray-700">Species</label>
                        <select name="species" id="species" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                                required>
                            <option value="">Select Species</option>
                            <option value="Dog" <?php echo ($pet['species'] == 'Dog') ? 'selected' : ''; ?>>Dog</option>
                            <option value="Cat" <?php echo ($pet['species'] == 'Cat') ? 'selected' : ''; ?>>Cat</option>
                            <option value="Bird" <?php echo ($pet['species'] == 'Bird') ? 'selected' : ''; ?>>Bird</option>
                            <option value="Fish" <?php echo ($pet['species'] == 'Fish') ? 'selected' : ''; ?>>Fish</option>
                            <option value="Rabbit" <?php echo ($pet['species'] == 'Rabbit') ? 'selected' : ''; ?>>Rabbit</option>
                            <option value="Hamster" <?php echo ($pet['species'] == 'Hamster') ? 'selected' : ''; ?>>Hamster</option>
                            <option value="Guinea Pig" <?php echo ($pet['species'] == 'Guinea Pig') ? 'selected' : ''; ?>>Guinea Pig</option>
                            <option value="Other" <?php echo ($pet['species'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <!-- Breed -->
                    <div>
                        <label for="breed" class="block text-sm font-medium text-gray-700">Breed</label>
                        <input type="text" name="breed" id="breed" 
                               value="<?php echo htmlspecialchars($pet['breed']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                               placeholder="e.g., Labrador, Persian, etc.">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                        <select name="gender" id="gender" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                                required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo ($pet['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo ($pet['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="unknown" <?php echo ($pet['gender'] == 'unknown') ? 'selected' : ''; ?>>Unknown</option>
                        </select>
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" 
                               value="<?php echo $pet['date_of_birth']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <!-- Weight -->
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Weight (kg)</label>
                        <input type="number" name="weight" id="weight" step="0.01" 
                               value="<?php echo $pet['weight']; ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="e.g., 5.5">
                    </div>

                    <!-- Microchip ID -->
                    <div>
                        <label for="microchip_id" class="block text-sm font-medium text-gray-700">Microchip ID</label>
                        <input type="text" name="microchip_id" id="microchip_id" 
                               value="<?php echo htmlspecialchars($pet['microchip_id']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                               placeholder="e.g., 123456789012345">
                    </div>

                    <!-- Owner Information (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Owner</label>
                        <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-sm text-gray-500">
                            <?php echo htmlspecialchars($pet['first_name'] . ' ' . $pet['last_name']); ?>
                            <small class="block text-xs text-gray-400 mt-1">
                                <?php echo htmlspecialchars($pet['email']); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Information Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>All fields marked with * are required</li>
                                    <li>Weight should be entered in kilograms (kg)</li>
                                    <li>Microchip ID is typically a 15-digit number</li>
                                    <li>Owner information cannot be changed from this form</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="view_pet.php?id=<?php echo $pet['id']; ?>" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Update Pet Information
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-update breed suggestions based on species
document.getElementById('species').addEventListener('change', function() {
    const species = this.value;
    const breedField = document.getElementById('breed');
    
    // Clear current breed
    breedField.value = '';
    
    // Update placeholder based on species
    switch(species) {
        case 'Dog':
            breedField.placeholder = 'e.g., Labrador, Golden Retriever, German Shepherd';
            break;
        case 'Cat':
            breedField.placeholder = 'e.g., Persian, Siamese, Maine Coon';
            break;
        case 'Bird':
            breedField.placeholder = 'e.g., Canary, Parakeet, Cockatiel';
            break;
        case 'Fish':
            breedField.placeholder = 'e.g., Goldfish, Betta, Guppy';
            break;
        case 'Rabbit':
            breedField.placeholder = 'e.g., Holland Lop, Lionhead, Mini Rex';
            break;
        default:
            breedField.placeholder = 'Enter breed or type';
    }
});
</script>

<?php include_once '../includes/admin_footer.php'; ?>
