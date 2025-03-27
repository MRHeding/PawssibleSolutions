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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name']);
    $species = trim($_POST['species']);
    $breed = trim($_POST['breed']);
    $gender = $_POST['gender'];
    $date_of_birth = !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : NULL;
    $weight = !empty($_POST['weight']) ? $_POST['weight'] : NULL;
    $microchip_id = !empty($_POST['microchip_id']) ? trim($_POST['microchip_id']) : NULL;
    
    // Validation
    if (empty($name) || empty($species) || empty($gender)) {
        $error = "Pet name, species, and gender are required fields";
    } else {
        // Insert the pet
        $query = "INSERT INTO pets (owner_id, name, species, breed, gender, date_of_birth, weight, microchip_id) 
                 VALUES (:owner_id, :name, :species, :breed, :gender, :date_of_birth, :weight, :microchip_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':owner_id', $user_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':species', $species);
        $stmt->bindParam(':breed', $breed);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':date_of_birth', $date_of_birth);
        $stmt->bindParam(':weight', $weight);
        $stmt->bindParam(':microchip_id', $microchip_id);
        
        try {
            if ($stmt->execute()) {
                $pet_id = $db->lastInsertId();
                $success = "Pet added successfully!";
                
                // Redirect to pet details page after a delay
                header("refresh:2;url=pet_details.php?id=" . $pet_id);
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

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Add a New Pet</h1>
            <a href="my_pets.php" class="text-white hover:text-blue-100 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to My Pets
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
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" data-validate="true">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Pet Name *</label>
                <input type="text" name="name" id="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="species" class="block text-gray-700 text-sm font-bold mb-2">Species *</label>
                    <select name="species" id="species" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select Species</option>
                        <option value="Dog">Dog</option>
                        <option value="Cat">Cat</option>
                        <option value="Bird">Bird</option>
                        <option value="Rabbit">Rabbit</option>
                        <option value="Guinea Pig">Guinea Pig</option>
                        <option value="Hamster">Hamster</option>
                        <option value="Reptile">Reptile</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label for="breed" class="block text-gray-700 text-sm font-bold mb-2">Breed</label>
                    <input type="text" name="breed" id="breed" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="gender" class="block text-gray-700 text-sm font-bold mb-2">Gender *</label>
                    <select name="gender" id="gender" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="unknown">Unknown</option>
                    </select>
                </div>
                
                <div>
                    <label for="date_of_birth" class="block text-gray-700 text-sm font-bold mb-2">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" max="<?php echo date('Y-m-d'); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="weight" class="block text-gray-700 text-sm font-bold mb-2">Weight (kg)</label>
                    <input type="number" name="weight" id="weight" step="0.01" min="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div>
                    <label for="microchip_id" class="block text-gray-700 text-sm font-bold mb-2">Microchip ID</label>
                    <input type="text" name="microchip_id" id="microchip_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <a href="my_pets.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                    Cancel
                </a>
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition">
                    Add Pet
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for species-specific behavior -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const speciesField = document.getElementById('species');
        const breedField = document.getElementById('breed');
        
        speciesField.addEventListener('change', function() {
            // You could preload breed options based on species
            if (this.value === 'Dog') {
                breedField.placeholder = 'E.g., Labrador Retriever, German Shepherd';
            } else if (this.value === 'Cat') {
                breedField.placeholder = 'E.g., Siamese, Maine Coon';
            } else {
                breedField.placeholder = '';
            }
        });
    });
</script>

<?php include_once 'includes/footer.php'; ?>
