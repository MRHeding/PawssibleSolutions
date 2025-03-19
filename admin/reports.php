<?php
session_start();
include_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Set default date ranges
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$current_year_start = date('Y-01-01');
$current_year_end = date('Y-12-31');

// Filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $current_month_start;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_month_end;
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'appointments';

// 1. Appointment Statistics
$appointment_stats_query = "SELECT 
                          COUNT(*) as total,
                          SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                          SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                          SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                          SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show
                          FROM appointments 
                          WHERE appointment_date BETWEEN :date_from AND :date_to";
$appt_stats_stmt = $db->prepare($appointment_stats_query);
$appt_stats_stmt->bindParam(':date_from', $date_from);
$appt_stats_stmt->bindParam(':date_to', $date_to);
$appt_stats_stmt->execute();
$appointment_stats = $appt_stats_stmt->fetch(PDO::FETCH_ASSOC);

// 2. New Clients Report
$new_clients_query = "SELECT COUNT(*) as count 
                    FROM users 
                    WHERE role = 'client' 
                    AND created_at BETWEEN :date_from AND :date_to";
$new_clients_stmt = $db->prepare($new_clients_query);
$new_clients_stmt->bindParam(':date_from', $date_from);
$new_clients_stmt->bindParam(':date_to', $date_to);
$new_clients_stmt->execute();
$new_clients_count = $new_clients_stmt->fetchColumn();

// 3. New Pets Report
$new_pets_query = "SELECT COUNT(*) as count 
                 FROM pets 
                 WHERE created_at BETWEEN :date_from AND :date_to";
$new_pets_stmt = $db->prepare($new_pets_query);
$new_pets_stmt->bindParam(':date_from', $date_from);
$new_pets_stmt->bindParam(':date_to', $date_to);
$new_pets_stmt->execute();
$new_pets_count = $new_pets_stmt->fetchColumn();

// 4. Vet Workload Report
$vet_workload_query = "SELECT 
                      v.id,
                      CONCAT(u.first_name, ' ', u.last_name) as vet_name,
                      COUNT(a.id) as total_appointments,
                      SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
                      FROM vets v
                      JOIN users u ON v.user_id = u.id
                      LEFT JOIN appointments a ON v.id = a.vet_id AND a.appointment_date BETWEEN :date_from AND :date_to
                      GROUP BY v.id
                      ORDER BY total_appointments DESC";
$vet_workload_stmt = $db->prepare($vet_workload_query);
$vet_workload_stmt->bindParam(':date_from', $date_from);
$vet_workload_stmt->bindParam(':date_to', $date_to);
$vet_workload_stmt->execute();

// 5. Species Distribution
$species_query = "SELECT species, COUNT(*) as count 
                FROM pets 
                GROUP BY species 
                ORDER BY count DESC";
$species_stmt = $db->prepare($species_query);
$species_stmt->execute();

// 6. Monthly Appointment Trend for the current year
$monthly_trend_query = "SELECT 
                      MONTH(appointment_date) as month,
                      COUNT(*) as total
                      FROM appointments
                      WHERE appointment_date BETWEEN :year_start AND :year_end
                      GROUP BY MONTH(appointment_date)
                      ORDER BY month";
$monthly_trend_stmt = $db->prepare($monthly_trend_query);
$monthly_trend_stmt->bindParam(':year_start', $current_year_start);
$monthly_trend_stmt->bindParam(':year_end', $current_year_end);
$monthly_trend_stmt->execute();
$monthly_trend_data = [];
while ($row = $monthly_trend_stmt->fetch(PDO::FETCH_ASSOC)) {
    $month_name = date('F', mktime(0, 0, 0, $row['month'], 1));
    $monthly_trend_data[$month_name] = $row['total'];
}

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Reports & Analytics</h1>
        <p class="text-white text-opacity-90 mt-2">View and export clinic performance metrics</p>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Filter Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-medium mb-4">Filter Options</h2>
        <form action="" method="get" class="flex flex-col md:flex-row gap-4 items-end">
            <div>
                <label for="report_type" class="block text-gray-700 text-sm font-bold mb-2">Report Type</label>
                <select id="report_type" name="report_type" class="shadow border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="appointments" <?php echo $report_type === 'appointments' ? 'selected' : ''; ?>>Appointments</option>
                    <option value="clients" <?php echo $report_type === 'clients' ? 'selected' : ''; ?>>Clients</option>
                    <option value="pets" <?php echo $report_type === 'pets' ? 'selected' : ''; ?>>Pets</option>
                    <option value="vets" <?php echo $report_type === 'vets' ? 'selected' : ''; ?>>Veterinarians</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-gray-700 text-sm font-bold mb-2">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="date_to" class="block text-gray-700 text-sm font-bold mb-2">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Generate Report
                </button>
            </div>
            <div class="ml-auto">
                <button type="button" onclick="exportReport()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    <i class="fas fa-file-export mr-2"></i> Export Report
                </button>
            </div>
        </form>
    </div>
    
    <!-- Report Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Appointment Summary</h3>
            <div class="text-center">
                <div class="text-3xl font-bold mb-4"><?php echo $appointment_stats['total']; ?></div>
                <p class="text-gray-500">Total Appointments</p>
            </div>
            <div class="mt-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Scheduled</span>
                    <span class="font-medium"><?php echo $appointment_stats['scheduled']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Completed</span>
                    <span class="font-medium"><?php echo $appointment_stats['completed']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Cancelled</span>
                    <span class="font-medium"><?php echo $appointment_stats['cancelled']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">No-show</span>
                    <span class="font-medium"><?php echo $appointment_stats['no_show']; ?></span>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">New Registrations</h3>
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold mb-4"><?php echo $new_clients_count; ?></div>
                    <p class="text-gray-500">New Clients</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-4"><?php echo $new_pets_count; ?></div>
                    <p class="text-gray-500">New Pets</p>
                </div>
            </div>
            <div class="mt-4 text-center">
                <a href="clients.php" class="text-indigo-600 hover:text-indigo-800 text-sm">
                    View All Clients <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Pet Species Distribution</h3>
            <div class="space-y-3">
                <?php 
                $total_pets = 0;
                $species_data = [];
                while ($row = $species_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $total_pets += $row['count'];
                    $species_data[] = $row;
                }
                
                foreach ($species_data as $data): 
                    $percentage = ($data['count'] / $total_pets) * 100;
                ?>
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-gray-600"><?php echo htmlspecialchars($data['species']); ?></span>
                        <span class="font-medium"><?php echo $data['count']; ?> (<?php echo number_format($percentage, 1); ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Monthly Trends Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Monthly Appointment Trends (<?php echo date('Y'); ?>)</h3>
        <div style="height: 300px;">
            <canvas id="monthlyTrendChart"></canvas>
        </div>
    </div>
    
    <!-- Vet Workload Report -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Veterinarian Workload</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Appointments</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($vet = $vet_workload_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($vet['vet_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $vet['total_appointments']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $vet['completed_appointments']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $completion_rate = $vet['total_appointments'] > 0 ? 
                                    ($vet['completed_appointments'] / $vet['total_appointments']) * 100 : 0; 
                                ?>
                                <div class="text-sm text-gray-900"><?php echo number_format($completion_rate, 1); ?>%</div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Monthly trend chart
const ctx = document.getElementById('monthlyTrendChart').getContext('2d');

// Convert PHP array to JavaScript
const monthlyData = {
    labels: [
        <?php 
        $months = array('January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December');
        foreach ($months as $month) {
            echo "'$month', ";
        }
        ?>
    ],
    datasets: [{
        label: 'Appointments',
        data: [
            <?php 
            foreach ($months as $month) {
                echo isset($monthly_trend_data[$month]) ? $monthly_trend_data[$month] : 0;
                echo ", ";
            }
            ?>
        ],
        backgroundColor: 'rgba(79, 70, 229, 0.2)',
        borderColor: 'rgba(79, 70, 229, 1)',
        borderWidth: 2,
        pointBackgroundColor: 'rgba(79, 70, 229, 1)',
        tension: 0.4
    }]
};

new Chart(ctx, {
    type: 'line',
    data: monthlyData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});

// Export report function
function exportReport() {
    // In a real application, you'd implement proper export functionality
    // This is just a placeholder
    alert('Report export functionality would generate CSV/PDF here');
}
</script>

<?php include_once '../includes/admin_footer.php'; ?>
