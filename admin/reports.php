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
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    include_once 'export_pdf_report.php';
    exit;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    include_once 'export_csv_report.php';
    exit;
}

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

// 7. Report of the Day (today's summary)
$today = date('Y-m-d');
$report_of_day = [];

// Appointments today
$today_appt_query = "SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled, SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show FROM appointments WHERE appointment_date = :today";
$today_appt_stmt = $db->prepare($today_appt_query);
$today_appt_stmt->bindParam(':today', $today);
$today_appt_stmt->execute();
$report_of_day['appointments'] = $today_appt_stmt->fetch(PDO::FETCH_ASSOC);

// New clients today
$today_clients_query = "SELECT COUNT(*) as count FROM users WHERE role = 'client' AND DATE(created_at) = :today";
$today_clients_stmt = $db->prepare($today_clients_query);
$today_clients_stmt->bindParam(':today', $today);
$today_clients_stmt->execute();
$report_of_day['new_clients'] = $today_clients_stmt->fetchColumn();

// New pets today
$today_pets_query = "SELECT COUNT(*) as count FROM pets WHERE DATE(created_at) = :today";
$today_pets_stmt = $db->prepare($today_pets_query);
$today_pets_stmt->bindParam(':today', $today);
$today_pets_stmt->execute();
$report_of_day['new_pets'] = $today_pets_stmt->fetchColumn();

include_once '../includes/admin_header.php';
?>

<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-white">Reports & Analytics</h1>
                <p class="text-white text-opacity-90 mt-2">Comprehensive veterinary practice reports</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="exportToPDF()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-file-pdf mr-2"></i> Export PDF
                </button>
                <button onclick="exportToCSV()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded inline-flex items-center transition">
                    <i class="fas fa-file-csv mr-2"></i> Export CSV
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Enhanced Filter Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-lg font-medium mb-4">Report Generator</h2>
        <form action="" method="get" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label for="report_type" class="block text-gray-700 text-sm font-bold mb-2">Report Type</label>
                <select id="report_type" name="report_type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview Dashboard</option>
                    <option value="daily_appointments" <?php echo $report_type === 'daily_appointments' ? 'selected' : ''; ?>>Daily Appointments</option>
                    <option value="client_report" <?php echo $report_type === 'client_report' ? 'selected' : ''; ?>>Client Report</option>
                    <option value="pet_report" <?php echo $report_type === 'pet_report' ? 'selected' : ''; ?>>Pet Report</option>
                    <option value="revenue_report" <?php echo $report_type === 'revenue_report' ? 'selected' : ''; ?>>Revenue Report</option>
                    <option value="medical_records" <?php echo $report_type === 'medical_records' ? 'selected' : ''; ?>>Medical Records</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-gray-700 text-sm font-bold mb-2">Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label for="date_to" class="block text-gray-700 text-sm font-bold mb-2">Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded w-full focus:outline-none focus:shadow-outline">
                    <i class="fas fa-chart-bar mr-2"></i>Generate Report
                </button>
            </div>
            <div>
                <button type="button" onclick="generateQuickReport('today')" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full focus:outline-none focus:shadow-outline">
                    <i class="fas fa-calendar-day mr-2"></i>Today's Report
                </button>
            </div>
        </form>
        
        <!-- Quick Date Filters -->
        <div class="mt-4 flex flex-wrap gap-2">
            <button onclick="setDateRange('today')" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-sm">Today</button>
            <button onclick="setDateRange('yesterday')" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-sm">Yesterday</button>
            <button onclick="setDateRange('this_week')" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-sm">This Week</button>
            <button onclick="setDateRange('this_month')" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-sm">This Month</button>
            <button onclick="setDateRange('last_month')" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-sm">Last Month</button>
            <button onclick="setDateRange('this_year')" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded text-sm">This Year</button>
        </div>
    </div>

    <!-- Report Content Based on Type -->
    <?php
    switch ($report_type) {
        case 'daily_appointments':
            include 'reports/daily_appointments.php';
            break;
        case 'client_report':
            include 'reports/client_report.php';
            break;
        case 'pet_report':
            include 'reports/pet_report.php';
            break;
        case 'revenue_report':
            include 'reports/revenue_report.php';
            break;
        case 'medical_records':
            include 'reports/medical_records_report.php';
            break;
        default:
            include 'reports/overview_dashboard.php';
    }
    ?>
</div>

<script>
// Quick date range functions
function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;
    
    switch(range) {
        case 'today':
            startDate = endDate = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = endDate = yesterday.toISOString().split('T')[0];
            break;
        case 'this_week':
            const thisWeekStart = new Date(today);
            thisWeekStart.setDate(today.getDate() - today.getDay());
            startDate = thisWeekStart.toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'this_month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            startDate = lastMonth.toISOString().split('T')[0];
            endDate = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
            break;
        case 'this_year':
            startDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
            endDate = today.toISOString().split('T')[0];
            break;
    }
    
    document.getElementById('date_from').value = startDate;
    document.getElementById('date_to').value = endDate;
}

function generateQuickReport(type) {
    setDateRange(type);
    document.querySelector('form').submit();
}

function exportToPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.open('?' + params.toString(), '_blank');
}

function exportToCSV() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.open('?' + params.toString(), '_blank');
}
</script>

<?php include_once '../includes/admin_footer.php'; ?>
