<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is a vet
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'vet') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

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

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$species_filter = isset($_GET['species']) ? $_GET['species'] : '';

// Build the query to get all pets the vet has had appointments with
$query = "SELECT DISTINCT p.*, 
          CONCAT(u.first_name, ' ', u.last_name) as owner_name,
          u.email as owner_email,
          u.phone as owner_phone,
          (SELECT COUNT(*) FROM appointments a WHERE a.pet_id = p.id AND a.vet_id = :vet_id) as appointment_count,
          (SELECT MAX(a.appointment_date) FROM appointments a WHERE a.pet_id = p.id AND a.vet_id = :vet_id) as last_visit
          FROM pets p
          JOIN appointments a ON p.id = a.pet_id
          JOIN users u ON p.owner_id = u.id
          WHERE a.vet_id = :vet_id ";

// Add search filter if provided
if (!empty($search)) {
    $query .= "AND (p.name LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search) ";
}

// Add species filter if provided
if (!empty($species_filter)) {
    $query .= "AND p.species = :species ";
}

$query .= "ORDER BY last_visit DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':vet_id', $vet_id);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}

if (!empty($species_filter)) {
    $stmt->bindParam(':species', $species_filter);
}

$stmt->execute();

// Get distinct species for filter dropdown
$species_query = "SELECT DISTINCT p.species FROM pets p 
                 JOIN appointments a ON p.id = a.pet_id 
                 WHERE a.vet_id = :vet_id
                 ORDER BY p.species";
$species_stmt = $db->prepare($species_query);
$species_stmt->bindParam(':vet_id', $vet_id);
$species_stmt->execute();

include_once '../includes/vet_header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">My Patients</h1>
        <p class="text-white text-opacity-90 mt-2">View and manage your patient records</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Search and Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="get" action="patients.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by pet name or owner name"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            
            <div>
                <label for="species" class="block text-sm font-medium text-gray-700 mb-1">Species</label>
                <select id="species" name="species" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">All Species</option>
                    <?php while ($species_row = $species_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo htmlspecialchars($species_row['species']); ?>" 
                                <?php echo $species_filter == $species_row['species'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($species_row['species']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="md:col-span-3 flex items-center justify-end space-x-3">
                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                    <i class="fas fa-search mr-1"></i> Search
                </button>
                <a href="patients.php" class="text-violet-600 hover:text-violet-800 py-2 px-3">Clear Filters</a>
            </div>
        </form>
    </div>
    
    <!-- Patient List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold">Patient List</h3>
            <span class="text-sm text-gray-500">
                <?php echo $stmt->rowCount(); ?> patients found
            </span>
        </div>
        
        <?php if ($stmt->rowCount() > 0): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($pet = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-violet-100 rounded-full flex items-center justify-center text-violet-600">
                                            <?php
                                            $icon = 'fa-paw';
                                            if (strtolower($pet['species']) === 'dog') {
                                                $icon = 'fa-dog';
                                            } elseif (strtolower($pet['species']) === 'cat') {
                                                $icon = 'fa-cat';
                                            } elseif (strtolower($pet['species']) === 'bird') {
                                                $icon = 'fa-dove';
                                            } elseif (strtolower($pet['species']) === 'fish') {
                                                $icon = 'fa-fish';
                                            }
                                            ?>
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($pet['name']); ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($pet['species']); ?>
                                                <?php if (!empty($pet['breed'])): ?>
                                                    - <?php echo htmlspecialchars($pet['breed']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($pet['owner_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pet['owner_email']); ?></div>
                                    <?php if (!empty($pet['owner_phone'])): ?>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($pet['owner_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($pet['age'] ?? 'N/A'); ?> years
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($pet['gender'] ?? 'Unknown'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($pet['appointment_count']); ?> visits
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php 
                                        if (!empty($pet['last_visit'])) {
                                            echo date('M d, Y', strtotime($pet['last_visit']));
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex space-x-3">
                                        <a href="view_pet.php?id=<?php echo $pet['id']; ?>" class="text-violet-600 hover:text-violet-900">
                                            <i class="fas fa-clipboard-list"></i> Records
                                        </a>
                                        <a href="create_appointment.php?pet_id=<?php echo $pet['id']; ?>" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-calendar-plus"></i> Schedule
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No patients found</h3>
                <p class="mt-1 text-sm text-gray-500">No patients match your search criteria or you haven't seen any patients yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once '../includes/vet_footer.php'; ?>
