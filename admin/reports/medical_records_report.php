<?php
// Medical Records Report - Comprehensive medical records analysis
?>

<!-- Medical Records Report -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Medical Records Report</h2>
        <div class="text-sm text-gray-600">
            <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <?php
    // Medical Records data queries
    // 1. Total Medical Records Statistics
    $records_stats_query = "SELECT 
                           COUNT(*) as total_records,
                           COUNT(DISTINCT pet_id) as unique_pets_treated,
                           COUNT(DISTINCT created_by) as active_vets
                           FROM medical_records 
                           WHERE DATE(record_date) BETWEEN :date_from AND :date_to";
    $records_stmt = $db->prepare($records_stats_query);
    $records_stmt->bindParam(':date_from', $date_from);
    $records_stmt->bindParam(':date_to', $date_to);
    $records_stmt->execute();
    $records_data = $records_stmt->fetch(PDO::FETCH_ASSOC);

    // Default values if no data
    $records_data = $records_data ?: [
        'total_records' => 0,
        'unique_pets_treated' => 0,
        'active_vets' => 0
    ];

    // 2. Records by Type (if record_type field exists)
    $type_query = "SHOW COLUMNS FROM medical_records LIKE 'record_type'";
    $type_check = $db->prepare($type_query);
    $type_check->execute();
    $has_record_type = $type_check->rowCount() > 0;

    $records_by_type = [];
    if ($has_record_type) {
        $type_stats_query = "SELECT 
                            record_type,
                            COUNT(*) as count
                            FROM medical_records 
                            WHERE DATE(record_date) BETWEEN :date_from AND :date_to
                            GROUP BY record_type
                            ORDER BY count DESC";
        $type_stmt = $db->prepare($type_stats_query);
        $type_stmt->bindParam(':date_from', $date_from);
        $type_stmt->bindParam(':date_to', $date_to);
        $type_stmt->execute();
        $records_by_type = $type_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Most Active Veterinarians
    $vet_activity_query = "SELECT 
                          CONCAT(u.first_name, ' ', u.last_name) as vet_name,
                          v.specialization,
                          COUNT(mr.id) as records_count
                          FROM medical_records mr
                          JOIN users u ON mr.created_by = u.id
                          LEFT JOIN vets v ON u.id = v.user_id
                          WHERE DATE(mr.record_date) BETWEEN :date_from AND :date_to
                          AND u.role IN ('vet', 'admin')
                          GROUP BY mr.created_by, u.first_name, u.last_name, v.specialization
                          ORDER BY records_count DESC";
    $vet_activity_stmt = $db->prepare($vet_activity_query);
    $vet_activity_stmt->bindParam(':date_from', $date_from);
    $vet_activity_stmt->bindParam(':date_to', $date_to);
    $vet_activity_stmt->execute();
    $vet_activity_data = $vet_activity_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Species Treatment Statistics
    $species_treatment_query = "SELECT 
                               p.species,
                               COUNT(mr.id) as treatment_count,
                               COUNT(DISTINCT mr.pet_id) as unique_pets
                               FROM medical_records mr
                               JOIN pets p ON mr.pet_id = p.id
                               WHERE DATE(mr.record_date) BETWEEN :date_from AND :date_to
                               GROUP BY p.species
                               ORDER BY treatment_count DESC";
    $species_treatment_stmt = $db->prepare($species_treatment_query);
    $species_treatment_stmt->bindParam(':date_from', $date_from);
    $species_treatment_stmt->bindParam(':date_to', $date_to);
    $species_treatment_stmt->execute();
    $species_treatment_data = $species_treatment_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Recent Medical Records Summary
    $recent_records_query = "SELECT 
                            mr.id,
                            mr.record_date,
                            mr.diagnosis,
                            mr.treatment,
                            p.name as pet_name,
                            p.species,
                            CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                            CONCAT(vu.first_name, ' ', vu.last_name) as vet_name
                            FROM medical_records mr
                            JOIN pets p ON mr.pet_id = p.id
                            JOIN users u ON p.owner_id = u.id
                            JOIN users vu ON mr.created_by = vu.id
                            WHERE DATE(mr.record_date) BETWEEN :date_from AND :date_to
                            ORDER BY mr.record_date DESC
                            LIMIT 20";
    $recent_records_stmt = $db->prepare($recent_records_query);
    $recent_records_stmt->bindParam(':date_from', $date_from);
    $recent_records_stmt->bindParam(':date_to', $date_to);
    $recent_records_stmt->execute();
    $recent_records_data = $recent_records_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Daily Records Count
    $daily_records_query = "SELECT 
                           DATE(record_date) as record_day,
                           COUNT(*) as daily_count
                           FROM medical_records
                           WHERE DATE(record_date) BETWEEN :date_from AND :date_to
                           GROUP BY DATE(record_date)
                           ORDER BY record_day DESC";
    $daily_records_stmt = $db->prepare($daily_records_query);
    $daily_records_stmt->bindParam(':date_from', $date_from);
    $daily_records_stmt->bindParam(':date_to', $date_to);
    $daily_records_stmt->execute();
    $daily_records_data = $daily_records_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!-- Medical Records Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="text-center bg-blue-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo number_format($records_data['total_records']); ?></div>
            <div class="text-sm text-gray-600 font-medium">Total Records</div>
            <div class="text-xs text-gray-500 mt-1">in selected period</div>
        </div>
        <div class="text-center bg-green-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-green-600 mb-2"><?php echo number_format($records_data['unique_pets_treated']); ?></div>
            <div class="text-sm text-gray-600 font-medium">Pets Treated</div>
            <div class="text-xs text-gray-500 mt-1">unique patients</div>
        </div>
        <div class="text-center bg-purple-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-purple-600 mb-2"><?php echo number_format($records_data['active_vets']); ?></div>
            <div class="text-sm text-gray-600 font-medium">Active Vets</div>
            <div class="text-xs text-gray-500 mt-1">providing care</div>
        </div>
        <div class="text-center bg-orange-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-orange-600 mb-2">
                <?php 
                $avg_per_day = count($daily_records_data) > 0 ? 
                    number_format($records_data['total_records'] / count($daily_records_data), 1) : 0;
                echo $avg_per_day;
                ?>
            </div>
            <div class="text-sm text-gray-600 font-medium">Avg Per Day</div>
            <div class="text-xs text-gray-500 mt-1">records created</div>
        </div>
    </div>

    <!-- Records by Type -->
    <?php if ($has_record_type && !empty($records_by_type)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Records by Type</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($records_by_type as $type): ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-800"><?php echo number_format($type['count']); ?></div>
                        <div class="text-sm text-gray-600 capitalize"><?php echo htmlspecialchars($type['record_type']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Veterinarian Activity -->
    <?php if (!empty($vet_activity_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Veterinarian Activity</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($vet_activity_data as $vet): ?>
                        <?php 
                        $percentage = ($records_data['total_records'] > 0) ? 
                            (($vet['records_count'] / $records_data['total_records']) * 100) : 0;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($vet['vet_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($vet['specialization'] ?? 'General Practice'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                <?php echo number_format($vet['records_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($percentage, 1); ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Species Treatment Statistics -->
    <?php if (!empty($species_treatment_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Treatment by Species</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Treatments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unique Pets</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Treatments per Pet</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($species_treatment_data as $species): ?>
                        <?php 
                        $avg_treatments = ($species['unique_pets'] > 0) ? 
                            ($species['treatment_count'] / $species['unique_pets']) : 0;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 capitalize">
                                <?php echo htmlspecialchars($species['species']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                <?php echo number_format($species['treatment_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($species['unique_pets']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($avg_treatments, 1); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Daily Activity Chart -->
    <?php if (!empty($daily_records_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Daily Activity</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Records Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity Level</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($daily_records_data as $day): ?>
                        <?php 
                        $activity_level = '';
                        $activity_color = '';
                        if ($day['daily_count'] >= 10) {
                            $activity_level = 'Very High';
                            $activity_color = 'text-red-600';
                        } elseif ($day['daily_count'] >= 5) {
                            $activity_level = 'High';
                            $activity_color = 'text-orange-600';
                        } elseif ($day['daily_count'] >= 2) {
                            $activity_level = 'Medium';
                            $activity_color = 'text-yellow-600';
                        } else {
                            $activity_level = 'Low';
                            $activity_color = 'text-green-600';
                        }
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo date('M d, Y', strtotime($day['record_day'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                <?php echo number_format($day['daily_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $activity_color; ?>">
                                <?php echo $activity_level; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Medical Records -->
    <?php if (!empty($recent_records_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Medical Records (Latest 20)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pet</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Veterinarian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Diagnosis</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recent_records_data as $record): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y', strtotime($record['record_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($record['pet_name']); ?>
                                <div class="text-xs text-gray-500 capitalize"><?php echo htmlspecialchars($record['species']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['owner_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($record['vet_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="max-w-xs">
                                    <?php echo htmlspecialchars(substr($record['diagnosis'], 0, 100)); ?>
                                    <?php if (strlen($record['diagnosis']) > 100): ?>...<?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Data Message -->
    <?php if ($records_data['total_records'] == 0): ?>
    <div class="text-center py-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <i class="fas fa-notes-medical text-4xl text-yellow-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Medical Records Found</h3>
            <p class="text-gray-600 mb-4">No medical records have been created for the selected date range.</p>
            <div class="text-sm text-gray-500">
                <p>Medical records will appear here as veterinarians document treatments and diagnoses.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
