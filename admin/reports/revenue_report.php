<?php
// Revenue Report - Financial overview with automatic invoice integration
?>

<!-- Revenue Report -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Revenue Report</h2>
        <div class="text-sm text-gray-600">
            <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
        </div>
    </div>

    <?php
    // Revenue data queries
    // 1. Total Revenue from Invoices
    $total_revenue_query = "SELECT 
                           COUNT(*) as total_invoices,
                           SUM(total_amount) as total_revenue,
                           AVG(total_amount) as avg_invoice_amount
                           FROM invoices 
                           WHERE DATE(created_at) BETWEEN :date_from AND :date_to";
    $revenue_stmt = $db->prepare($total_revenue_query);
    $revenue_stmt->bindParam(':date_from', $date_from);
    $revenue_stmt->bindParam(':date_to', $date_to);
    $revenue_stmt->execute();
    $revenue_data = $revenue_stmt->fetch(PDO::FETCH_ASSOC);

    // Default values if no data
    $revenue_data = $revenue_data ?: [
        'total_invoices' => 0,
        'total_revenue' => 0.00,
        'avg_invoice_amount' => 0.00
    ];

    // 2. Revenue by Service Type (from invoice items)
    $service_revenue_query = "SELECT 
                             ii.description as service,
                             COUNT(*) as service_count,
                             SUM(ii.total_price) as service_revenue
                             FROM invoice_items ii
                             JOIN invoices i ON ii.invoice_id = i.id
                             WHERE DATE(i.created_at) BETWEEN :date_from AND :date_to
                             GROUP BY ii.description
                             ORDER BY service_revenue DESC";
    $service_stmt = $db->prepare($service_revenue_query);
    $service_stmt->bindParam(':date_from', $date_from);
    $service_stmt->bindParam(':date_to', $date_to);
    $service_stmt->execute();
    $service_revenue_data = $service_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Monthly Revenue Trend
    $monthly_revenue_query = "SELECT 
                             MONTH(created_at) as month,
                             YEAR(created_at) as year,
                             COUNT(*) as invoice_count,
                             SUM(total_amount) as monthly_revenue
                             FROM invoices
                             WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                             GROUP BY YEAR(created_at), MONTH(created_at)
                             ORDER BY year, month";
    $monthly_stmt = $db->prepare($monthly_revenue_query);
    $monthly_stmt->bindParam(':date_from', $date_from);
    $monthly_stmt->bindParam(':date_to', $date_to);
    $monthly_stmt->execute();
    $monthly_revenue_data = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Payment Status Overview (if payments table exists)
    $payment_status_query = "SELECT 
                            CASE 
                                WHEN p.id IS NOT NULL THEN 'Paid'
                                ELSE 'Unpaid'
                            END as payment_status,
                            COUNT(*) as count,
                            SUM(i.total_amount) as amount
                            FROM invoices i
                            LEFT JOIN payments p ON i.id = p.invoice_id
                            WHERE DATE(i.created_at) BETWEEN :date_from AND :date_to
                            GROUP BY payment_status";
    try {
        $payment_stmt = $db->prepare($payment_status_query);
        $payment_stmt->bindParam(':date_from', $date_from);
        $payment_stmt->bindParam(':date_to', $date_to);
        $payment_stmt->execute();
        $payment_status_data = $payment_stmt->fetchAll(PDO::FETCH_ASSOC);
        $payments_table_exists = true;
    } catch (PDOException $e) {
        $payment_status_data = [];
        $payments_table_exists = false;
    }

    // 5. Top Revenue Generating Clients
    $top_clients_query = "SELECT 
                         CONCAT(u.first_name, ' ', u.last_name) as client_name,
                         u.email,
                         COUNT(i.id) as invoice_count,
                         SUM(i.total_amount) as total_spent
                         FROM invoices i
                         JOIN users u ON i.client_id = u.id
                         WHERE DATE(i.created_at) BETWEEN :date_from AND :date_to
                         GROUP BY i.client_id, u.first_name, u.last_name, u.email
                         ORDER BY total_spent DESC
                         LIMIT 10";
    $top_clients_stmt = $db->prepare($top_clients_query);
    $top_clients_stmt->bindParam(':date_from', $date_from);
    $top_clients_stmt->bindParam(':date_to', $date_to);
    $top_clients_stmt->execute();
    $top_clients_data = $top_clients_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <!-- Revenue Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="text-center bg-green-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-green-600 mb-2">₱<?php echo number_format($revenue_data['total_revenue'] ?? 0, 2); ?></div>
            <div class="text-sm text-gray-600 font-medium">Total Revenue</div>
            <div class="text-xs text-gray-500 mt-1">in selected period</div>
        </div>
        <div class="text-center bg-blue-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-blue-600 mb-2"><?php echo number_format($revenue_data['total_invoices'] ?? 0); ?></div>
            <div class="text-sm text-gray-600 font-medium">Total Invoices</div>
            <div class="text-xs text-gray-500 mt-1">generated</div>
        </div>
        <div class="text-center bg-purple-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-purple-600 mb-2">₱<?php echo number_format($revenue_data['avg_invoice_amount'] ?? 0, 2); ?></div>
            <div class="text-sm text-gray-600 font-medium">Average Invoice</div>
            <div class="text-xs text-gray-500 mt-1">per transaction</div>
        </div>
        <div class="text-center bg-orange-50 p-6 rounded-lg">
            <div class="text-4xl font-bold text-orange-600 mb-2">
                <?php 
                $completed_appointments = $appointment_stats['completed'] ?? 0;
                $invoiced_rate = ($completed_appointments > 0) ? 
                    number_format((($revenue_data['total_invoices'] ?? 0) / $completed_appointments) * 100, 1) : 0;
                echo $invoiced_rate;
                ?>%
            </div>
            <div class="text-sm text-gray-600 font-medium">Invoice Rate</div>
            <div class="text-xs text-gray-500 mt-1">of completed appointments</div>
        </div>
    </div>

    <!-- Revenue by Service Type -->
    <?php if (!empty($service_revenue_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Revenue by Service Type</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($service_revenue_data as $service): ?>
                        <?php 
                        $percentage = ($revenue_data['total_revenue'] > 0) ? 
                            (($service['service_revenue'] / $revenue_data['total_revenue']) * 100) : 0;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($service['service']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($service['service_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                ₱<?php echo number_format($service['service_revenue'], 2); ?>
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

    <!-- Payment Status Overview -->
    <?php if ($payments_table_exists && !empty($payment_status_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Status Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($payment_status_data as $status): ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-medium text-gray-800"><?php echo $status['payment_status']; ?></h4>
                            <p class="text-sm text-gray-600"><?php echo number_format($status['count']); ?> invoices</p>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold <?php echo $status['payment_status'] === 'Paid' ? 'text-green-600' : 'text-red-600'; ?>">
                                ₱<?php echo number_format($status['amount'], 2); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Monthly Revenue Trend -->
    <?php if (!empty($monthly_revenue_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Monthly Revenue Trend</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoices</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg per Invoice</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($monthly_revenue_data as $month_data): ?>
                        <?php 
                        $month_names = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $avg_per_invoice = ($month_data['invoice_count'] > 0) ? 
                            ($month_data['monthly_revenue'] / $month_data['invoice_count']) : 0;
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo $month_names[$month_data['month']] . ' ' . $month_data['year']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($month_data['invoice_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                ₱<?php echo number_format($month_data['monthly_revenue'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ₱<?php echo number_format($avg_per_invoice, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Revenue Generating Clients -->
    <?php if (!empty($top_clients_data)): ?>
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Revenue Generating Clients</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoices</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($top_clients_data as $index => $client): ?>
                        <tr class="<?php echo $index < 3 ? 'bg-yellow-50' : ''; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php if ($index < 3): ?>
                                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($client['client_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($client['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($client['invoice_count']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                ₱<?php echo number_format($client['total_spent'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Data Message -->
    <?php if (empty($service_revenue_data) && empty($monthly_revenue_data) && ($revenue_data['total_revenue'] ?? 0) == 0): ?>
    <div class="text-center py-8">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <i class="fas fa-chart-line text-4xl text-yellow-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Revenue Data Found</h3>
            <p class="text-gray-600 mb-4">No invoices have been generated for the selected date range.</p>
            <div class="text-sm text-gray-500">
                <p>💡 Revenue will appear here as appointments are completed and invoices are automatically generated.</p>
                <p class="mt-2">The automatic invoice system creates invoices when appointments are marked as "completed".</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
