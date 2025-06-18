<?php
// Overview Dashboard Report - Default view showing all key metrics
?>

<!-- Overview Dashboard -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Practice Overview Dashboard</h2>
        <div class="text-sm text-gray-600">
            <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="text-center bg-blue-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo $appointment_stats['total']; ?></div>
            <div class="text-sm text-gray-600 font-medium">Total Appointments</div>
            <div class="text-xs text-gray-500 mt-1">
                <?php echo $appointment_stats['completed']; ?> completed
            </div>
        </div>
        <div class="text-center bg-green-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-green-600 mb-2"><?php echo $new_clients_count; ?></div>
            <div class="text-sm text-gray-600 font-medium">New Clients</div>
            <div class="text-xs text-gray-500 mt-1">in selected period</div>
        </div>
        <div class="text-center bg-purple-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-purple-600 mb-2"><?php echo $new_pets_count; ?></div>
            <div class="text-sm text-gray-600 font-medium">New Pets</div>
            <div class="text-xs text-gray-500 mt-1">registered</div>
        </div>
        <div class="text-center bg-orange-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-orange-600 mb-2">
                <?php echo $appointment_stats['total'] > 0 ? number_format(($appointment_stats['completed'] / $appointment_stats['total']) * 100, 1) : 0; ?>%
            </div>
            <div class="text-sm text-gray-600 font-medium">Completion Rate</div>
            <div class="text-xs text-gray-500 mt-1">appointments completed</div>
        </div>
    </div>
</div>

<!-- Today's Summary -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Today's Summary (<?php echo date('F j, Y'); ?>)</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="text-center bg-blue-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-blue-600"><?php echo $report_of_day['appointments']['total']; ?></div>
            <div class="text-sm text-gray-600">Appointments Today</div>
            <div class="text-xs text-gray-500 mt-1">
                <?php echo $report_of_day['appointments']['completed']; ?> completed,
                <?php echo $report_of_day['appointments']['cancelled']; ?> cancelled
            </div>
        </div>
        <div class="text-center bg-green-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-green-600"><?php echo $report_of_day['new_clients']; ?></div>
            <div class="text-sm text-gray-600">New Clients</div>
        </div>
        <div class="text-center bg-purple-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-purple-600"><?php echo $report_of_day['new_pets']; ?></div>
            <div class="text-sm text-gray-600">New Pets</div>
        </div>
    </div>
</div>

<!-- Appointment Status Breakdown -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Appointment Status Breakdown</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Scheduled</span>
                <span class="font-medium text-blue-600"><?php echo $appointment_stats['scheduled']; ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $appointment_stats['total'] > 0 ? ($appointment_stats['scheduled'] / $appointment_stats['total']) * 100 : 0; ?>%"></div>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Completed</span>
                <span class="font-medium text-green-600"><?php echo $appointment_stats['completed']; ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $appointment_stats['total'] > 0 ? ($appointment_stats['completed'] / $appointment_stats['total']) * 100 : 0; ?>%"></div>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Cancelled</span>
                <span class="font-medium text-red-600"><?php echo $appointment_stats['cancelled']; ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo $appointment_stats['total'] > 0 ? ($appointment_stats['cancelled'] / $appointment_stats['total']) * 100 : 0; ?>%"></div>
            </div>
            
            <div class="flex justify-between items-center">
                <span class="text-gray-600">No-show</span>
                <span class="font-medium text-yellow-600"><?php echo $appointment_stats['no_show']; ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-yellow-600 h-2 rounded-full" style="width: <?php echo $appointment_stats['total'] > 0 ? ($appointment_stats['no_show'] / $appointment_stats['total']) * 100 : 0; ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Species Distribution -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Pet Species Distribution</h3>
        <div class="space-y-3">
            <?php 
            $total_pets = 0;
            $species_data = [];
            $species_stmt->execute(); // Re-execute for display
            while ($row = $species_stmt->fetch(PDO::FETCH_ASSOC)) {
                $total_pets += $row['count'];
                $species_data[] = $row;
            }
            
            foreach ($species_data as $data): 
                $percentage = $total_pets > 0 ? ($data['count'] / $total_pets) * 100 : 0;
            ?>
            <div>
                <div class="flex justify-between mb-1">
                    <span class="text-gray-600"><?php echo htmlspecialchars(ucfirst($data['species'])); ?></span>
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

<!-- Veterinarian Performance -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-semibold mb-4 text-gray-700">Veterinarian Performance</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Appointments</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performance</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php 
                $vet_workload_stmt->execute(); // Re-execute for display
                while ($vet = $vet_workload_stmt->fetch(PDO::FETCH_ASSOC)): 
                    $completion_rate = $vet['total_appointments'] > 0 ? 
                        ($vet['completed_appointments'] / $vet['total_appointments']) * 100 : 0;
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                        <i class="fas fa-user-md text-purple-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($vet['vet_name']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $vet['total_appointments']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $vet['completed_appointments']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo number_format($completion_rate, 1); ?>%</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="h-2 rounded-full <?php echo $completion_rate >= 90 ? 'bg-green-600' : ($completion_rate >= 70 ? 'bg-yellow-600' : 'bg-red-600'); ?>" 
                                         style="width: <?php echo $completion_rate; ?>%"></div>
                                </div>
                                <span class="text-xs text-gray-500">
                                    <?php 
                                    if ($completion_rate >= 90) echo 'Excellent';
                                    elseif ($completion_rate >= 70) echo 'Good';
                                    else echo 'Needs Improvement';
                                    ?>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>