<?php
// Client Report
$client_report_query = "SELECT 
    u.*, 
    COUNT(DISTINCT p.id) as total_pets,
    COUNT(DISTINCT a.id) as total_appointments,
    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
    MAX(a.appointment_date) as last_appointment_date,
    MIN(a.appointment_date) as first_appointment_date
    FROM users u
    LEFT JOIN pets p ON u.id = p.owner_id
    LEFT JOIN appointments a ON p.id = a.pet_id AND a.appointment_date BETWEEN :date_from AND :date_to
    WHERE u.role = 'client'
    AND u.created_at BETWEEN :user_date_from AND :user_date_to
    GROUP BY u.id
    ORDER BY u.created_at DESC";

$client_report_stmt = $db->prepare($client_report_query);
$client_report_stmt->bindParam(':date_from', $date_from);
$client_report_stmt->bindParam(':date_to', $date_to);
$client_report_stmt->bindParam(':user_date_from', $date_from);
$client_report_stmt->bindParam(':user_date_to', $date_to);
$client_report_stmt->execute();

// Client statistics
$client_stats_query = "SELECT 
    COUNT(*) as total_clients,
    COUNT(CASE WHEN created_at BETWEEN :date_from AND :date_to THEN 1 END) as new_clients,
    COUNT(CASE WHEN last_login IS NOT NULL THEN 1 END) as active_clients
    FROM users 
    WHERE role = 'client'";

$client_stats_stmt = $db->prepare($client_stats_query);
$client_stats_stmt->bindParam(':date_from', $date_from);
$client_stats_stmt->bindParam(':date_to', $date_to);
$client_stats_stmt->execute();
$client_stats = $client_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Top clients by appointments
$top_clients_query = "SELECT 
    u.first_name, u.last_name, u.email,
    COUNT(a.id) as appointment_count
    FROM users u
    JOIN pets p ON u.id = p.owner_id
    JOIN appointments a ON p.id = a.pet_id
    WHERE u.role = 'client' 
    AND a.appointment_date BETWEEN :date_from AND :date_to
    GROUP BY u.id
    HAVING appointment_count > 0
    ORDER BY appointment_count DESC
    LIMIT 10";

$top_clients_stmt = $db->prepare($top_clients_query);
$top_clients_stmt->bindParam(':date_from', $date_from);
$top_clients_stmt->bindParam(':date_to', $date_to);
$top_clients_stmt->execute();
?>

<!-- Client Report Header -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Client Report</h2>
        <div class="text-sm text-gray-600">
            <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <!-- Client Statistics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="text-center bg-blue-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-blue-600"><?php echo $client_stats['total_clients']; ?></div>
            <div class="text-sm text-gray-600">Total Clients</div>
        </div>
        <div class="text-center bg-green-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-green-600"><?php echo $client_stats['new_clients']; ?></div>
            <div class="text-sm text-gray-600">New Clients (Period)</div>
        </div>
        <div class="text-center bg-purple-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-purple-600"><?php echo $client_stats['active_clients']; ?></div>
            <div class="text-sm text-gray-600">Active Clients</div>
        </div>
    </div>
</div>

<!-- Top Clients by Appointments -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Top Clients by Appointments</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointments</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $rank = 1;
                while ($top_client = $top_clients_stmt->fetch(PDO::FETCH_ASSOC)): 
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">#<?php echo $rank; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($top_client['first_name'] . ' ' . $top_client['last_name']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($top_client['email']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $top_client['appointment_count']; ?></div>
                        </td>
                    </tr>
                <?php 
                $rank++;
                endwhile; 
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Detailed Client List -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Client Details</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pets</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointments</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($client = $client_report_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo strtoupper(substr($client['first_name'], 0, 1) . substr($client['last_name'], 0, 1)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?php echo $client['id']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($client['email']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($client['phone']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $client['total_pets']; ?></div>
                            <div class="text-xs text-gray-500">registered pets</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $client['total_appointments']; ?></div>
                            <div class="text-xs text-gray-500">
                                <?php echo $client['completed_appointments']; ?> completed
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($client['created_at'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($client['last_appointment_date']): ?>
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($client['last_appointment_date'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="text-sm text-gray-400">No visits</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>