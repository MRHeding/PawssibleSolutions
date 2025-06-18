<?php
// Daily Appointments Report
$daily_appointments_query = "SELECT 
    a.*, 
    p.name as pet_name, 
    p.species,
    CONCAT(owner.first_name, ' ', owner.last_name) as owner_name,
    CONCAT(vet_user.first_name, ' ', vet_user.last_name) as vet_name,
    owner.phone as owner_phone,
    owner.email as owner_email
    FROM appointments a
    JOIN pets p ON a.pet_id = p.id
    JOIN users owner ON p.owner_id = owner.id
    JOIN vets v ON a.vet_id = v.id
    JOIN users vet_user ON v.user_id = vet_user.id
    WHERE a.appointment_date BETWEEN :date_from AND :date_to
    ORDER BY a.appointment_date ASC, a.appointment_time ASC";

$daily_appointments_stmt = $db->prepare($daily_appointments_query);
$daily_appointments_stmt->bindParam(':date_from', $date_from);
$daily_appointments_stmt->bindParam(':date_to', $date_to);
$daily_appointments_stmt->execute();

// Group appointments by date
$appointments_by_date = [];
while ($appointment = $daily_appointments_stmt->fetch(PDO::FETCH_ASSOC)) {
    $date = $appointment['appointment_date'];
    if (!isset($appointments_by_date[$date])) {
        $appointments_by_date[$date] = [];
    }
    $appointments_by_date[$date][] = $appointment;
}

// Daily statistics
$daily_stats_query = "SELECT 
    appointment_date,
    COUNT(*) as total_appointments,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show
    FROM appointments 
    WHERE appointment_date BETWEEN :date_from AND :date_to
    GROUP BY appointment_date
    ORDER BY appointment_date ASC";

$daily_stats_stmt = $db->prepare($daily_stats_query);
$daily_stats_stmt->bindParam(':date_from', $date_from);
$daily_stats_stmt->bindParam(':date_to', $date_to);
$daily_stats_stmt->execute();
?>

<!-- Daily Appointments Report Header -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Daily Appointments Report</h2>
        <div class="text-sm text-gray-600">
            <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <!-- Daily Statistics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php 
        $total_all = $completed_all = $scheduled_all = $cancelled_all = $no_show_all = 0;
        $daily_stats_stmt->execute(); // Re-execute for summary
        while ($stat = $daily_stats_stmt->fetch(PDO::FETCH_ASSOC)) {
            $total_all += $stat['total_appointments'];
            $completed_all += $stat['completed'];
            $scheduled_all += $stat['scheduled'];
            $cancelled_all += $stat['cancelled'];
            $no_show_all += $stat['no_show'];
        }
        ?>
        <div class="text-center bg-blue-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-blue-600"><?php echo $total_all; ?></div>
            <div class="text-sm text-gray-600">Total Appointments</div>
        </div>
        <div class="text-center bg-green-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-green-600"><?php echo $completed_all; ?></div>
            <div class="text-sm text-gray-600">Completed</div>
        </div>
        <div class="text-center bg-yellow-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-yellow-600"><?php echo $scheduled_all; ?></div>
            <div class="text-sm text-gray-600">Scheduled</div>
        </div>
        <div class="text-center bg-red-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-red-600"><?php echo $cancelled_all + $no_show_all; ?></div>
            <div class="text-sm text-gray-600">Cancelled/No-Show</div>
        </div>
    </div>
</div>

<!-- Daily Breakdown -->
<?php if (!empty($appointments_by_date)): ?>
    <?php foreach ($appointments_by_date as $date => $appointments): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">
                    <?php echo date('l, F j, Y', strtotime($date)); ?>
                </h3>
                <div class="text-sm text-gray-600">
                    <?php echo count($appointments); ?> appointments
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet & Owner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($appointments as $appointment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        #<?php echo $appointment['appointment_number']; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($appointment['pet_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($appointment['species']); ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Owner: <?php echo htmlspecialchars($appointment['owner_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Dr. <?php echo htmlspecialchars($appointment['vet_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($appointment['reason']); ?>
                                    </div>
                                    <?php if (!empty($appointment['notes'])): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo htmlspecialchars(substr($appointment['notes'], 0, 50)); ?>...
                                        </div>
                                    <?php endif; ?>
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
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            default:
                                                echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-900">
                                        <?php echo htmlspecialchars($appointment['owner_phone']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($appointment['owner_email']); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <div class="text-gray-400 mb-4">
            <i class="fas fa-calendar-times text-6xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-700 mb-2">No Appointments Found</h3>
        <p class="text-gray-600">No appointments scheduled for the selected date range.</p>
    </div>
<?php endif; ?>