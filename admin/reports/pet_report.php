<?php
// Pet Report
$pet_report_query = "SELECT 
    p.*, 
    CONCAT(u.first_name, ' ', u.last_name) as owner_name,
    u.email as owner_email,
    u.phone as owner_phone,
    COUNT(DISTINCT a.id) as total_appointments,
    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
    MAX(a.appointment_date) as last_appointment_date,
    COUNT(DISTINCT mr.id) as medical_records_count
    FROM pets p
    JOIN users u ON p.owner_id = u.id
    LEFT JOIN appointments a ON p.id = a.pet_id AND a.appointment_date BETWEEN :date_from AND :date_to
    LEFT JOIN medical_records mr ON p.id = mr.pet_id
    WHERE p.created_at BETWEEN :pet_date_from AND :pet_date_to
    GROUP BY p.id
    ORDER BY p.created_at DESC";

$pet_report_stmt = $db->prepare($pet_report_query);
$pet_report_stmt->bindParam(':date_from', $date_from);
$pet_report_stmt->bindParam(':date_to', $date_to);
$pet_report_stmt->bindParam(':pet_date_from', $date_from);
$pet_report_stmt->bindParam(':pet_date_to', $date_to);
$pet_report_stmt->execute();

// Pet statistics
$pet_stats_query = "SELECT 
    COUNT(*) as total_pets,
    COUNT(CASE WHEN created_at BETWEEN :date_from AND :date_to THEN 1 END) as new_pets,
    COUNT(DISTINCT species) as species_count
    FROM pets";

$pet_stats_stmt = $db->prepare($pet_stats_query);
$pet_stats_stmt->bindParam(':date_from', $date_from);
$pet_stats_stmt->bindParam(':date_to', $date_to);
$pet_stats_stmt->execute();
$pet_stats = $pet_stats_stmt->fetch(PDO::FETCH_ASSOC);

// Species breakdown
$species_breakdown_query = "SELECT 
    species, 
    COUNT(*) as count,
    AVG(DATEDIFF(CURDATE(), date_of_birth) / 365.25) as avg_age
    FROM pets 
    WHERE date_of_birth IS NOT NULL
    GROUP BY species 
    ORDER BY count DESC";

$species_breakdown_stmt = $db->prepare($species_breakdown_query);
$species_breakdown_stmt->execute();

// Age distribution
$age_distribution_query = "SELECT 
    CASE 
        WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 < 1 THEN 'Under 1 year'
        WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 BETWEEN 1 AND 3 THEN '1-3 years'
        WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 BETWEEN 4 AND 7 THEN '4-7 years'
        WHEN DATEDIFF(CURDATE(), date_of_birth) / 365.25 BETWEEN 8 AND 12 THEN '8-12 years'
        ELSE 'Over 12 years'
    END as age_group,
    COUNT(*) as count
    FROM pets 
    WHERE date_of_birth IS NOT NULL
    GROUP BY age_group
    ORDER BY 
        CASE age_group
            WHEN 'Under 1 year' THEN 1
            WHEN '1-3 years' THEN 2
            WHEN '4-7 years' THEN 3
            WHEN '8-12 years' THEN 4
            WHEN 'Over 12 years' THEN 5
        END";

$age_distribution_stmt = $db->prepare($age_distribution_query);
$age_distribution_stmt->execute();
?>

<!-- Pet Report Header -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Pet Report</h2>
        <div class="text-sm text-gray-600">
            <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <!-- Pet Statistics Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="text-center bg-blue-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-blue-600"><?php echo $pet_stats['total_pets']; ?></div>
            <div class="text-sm text-gray-600">Total Pets</div>
        </div>
        <div class="text-center bg-green-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-green-600"><?php echo $pet_stats['new_pets']; ?></div>
            <div class="text-sm text-gray-600">New Pets (Period)</div>
        </div>
        <div class="text-center bg-purple-50 p-4 rounded-lg">
            <div class="text-3xl font-bold text-purple-600"><?php echo $pet_stats['species_count']; ?></div>
            <div class="text-sm text-gray-600">Species Types</div>
        </div>
    </div>
</div>

<!-- Species Breakdown and Age Distribution -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Species Breakdown -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Species Breakdown</h3>
        <div class="space-y-4">
            <?php while ($species = $species_breakdown_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="flex justify-between items-center">
                    <div>
                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(ucfirst($species['species'])); ?></span>
                        <div class="text-xs text-gray-500">Avg age: <?php echo number_format($species['avg_age'], 1); ?> years</div>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold text-blue-600"><?php echo $species['count']; ?></span>
                        <div class="text-xs text-gray-500">
                            <?php echo number_format(($species['count'] / $pet_stats['total_pets']) * 100, 1); ?>%
                        </div>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($species['count'] / $pet_stats['total_pets']) * 100; ?>%"></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Age Distribution -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Age Distribution</h3>
        <div class="space-y-4">
            <?php 
            $total_age_pets = 0;
            $age_data = [];
            while ($age = $age_distribution_stmt->fetch(PDO::FETCH_ASSOC)) {
                $total_age_pets += $age['count'];
                $age_data[] = $age;
            }
            
            foreach ($age_data as $age_group): 
            ?>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-900"><?php echo $age_group['age_group']; ?></span>
                    <div class="text-right">
                        <span class="text-lg font-bold text-green-600"><?php echo $age_group['count']; ?></span>
                        <div class="text-xs text-gray-500">
                            <?php echo number_format(($age_group['count'] / $total_age_pets) * 100, 1); ?>%
                        </div>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($age_group['count'] / $total_age_pets) * 100; ?>%"></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Detailed Pet List -->
<div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-xl font-semibold text-gray-800 mb-4">Pet Details</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age/Weight</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Appointments</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medical Records</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Visit</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($pet = $pet_report_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center">
                                        <i class="fas fa-paw text-orange-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($pet['name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars(ucfirst($pet['species'])); ?>
                                        <?php if (!empty($pet['breed'])): ?>
                                            • <?php echo htmlspecialchars($pet['breed']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        <?php echo ucfirst($pet['gender']); ?>
                                        <?php if (!empty($pet['microchip_id'])): ?>
                                            • Chip: <?php echo htmlspecialchars($pet['microchip_id']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($pet['owner_name']); ?>
                            </div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($pet['owner_email']); ?></div>
                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($pet['owner_phone']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($pet['date_of_birth']): ?>
                                <div class="text-sm text-gray-900">
                                    <?php 
                                    $age = floor((time() - strtotime($pet['date_of_birth'])) / (365.25 * 24 * 3600));
                                    echo $age . ' years old';
                                    ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    Born: <?php echo date('M Y', strtotime($pet['date_of_birth'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="text-sm text-gray-400">Age unknown</div>
                            <?php endif; ?>
                            <?php if ($pet['weight']): ?>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo $pet['weight']; ?> kg
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $pet['total_appointments']; ?></div>
                            <div class="text-xs text-gray-500">
                                <?php echo $pet['completed_appointments']; ?> completed
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $pet['medical_records_count']; ?></div>
                            <div class="text-xs text-gray-500">records</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($pet['created_at'])); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($pet['last_appointment_date']): ?>
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($pet['last_appointment_date'])); ?>
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