<?php
// Test script for automatic invoice generation
session_start();

// Set admin session for testing
$_SESSION['user_id'] = 1; // Assuming admin user ID is 1
$_SESSION['user_role'] = 'admin';

include_once 'config/database.php';
include_once 'includes/service_price_mapper.php';

$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automatic Invoice Generation Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .success { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .price { font-weight: bold; color: #28a745; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        h3 { color: #495057; border-left: 4px solid #007bff; padding-left: 10px; }
        pre { background-color: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<h2>🧪 Testing Automatic Invoice Generation System</h2>

// Test 1: Check service pricing
echo "<h3>📋 Test 1: Service Pricing Configuration</h3>";
$services = [
    'Wellness Exam',
    'Vaccination', 
    'Sick Visit',
    'Injury',
    'Dental Care',
    'Surgery Consultation',
    'Follow-up Visit'
];

echo "<table>";
echo "<tr><th>Service</th><th>Price</th></tr>";
foreach ($services as $service) {
    $price = ServicePriceMapper::getServicePrice($service);
    echo "<tr><td>$service</td><td class='price'>₱" . number_format($price, 2) . "</td></tr>";
}
echo "</table>";

// Test 2: Create invoice item
echo "<h3>🧾 Test 2: Invoice Item Creation</h3>";
$item = ServicePriceMapper::createInvoiceItem('Wellness Exam');
echo "<div class='info'>";
echo "<strong>Sample Invoice Item for 'Wellness Exam':</strong>";
echo "<pre>";
print_r($item);
echo "</pre>";
echo "</div>";

// Test 3: Find a completed appointment to test invoice generation
echo "<h3>💰 Test 3: Invoice Generation Testing</h3>";
$test_query = "SELECT id, reason, status FROM appointments WHERE status = 'completed' LIMIT 1";
$test_stmt = $db->prepare($test_query);
$test_stmt->execute();

if ($test_stmt->rowCount() > 0) {
    $test_appointment = $test_stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='info'>";
    echo "<p><strong>Found existing completed appointment:</strong></p>";
    echo "<p>Appointment ID: {$test_appointment['id']}, Reason: {$test_appointment['reason']}</p>";
    echo "</div>";
    
    // Check if invoice already exists
    $check_query = "SELECT id FROM invoices WHERE appointment_id = :appointment_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':appointment_id', $test_appointment['id']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo "<div class='warning'>⚠️ Invoice already exists for this appointment.</div>";
    } else {
        try {
            $invoice_id = ServicePriceMapper::autoGenerateInvoice($db, $test_appointment['id']);
            if ($invoice_id) {
                echo "<div class='success'>✅ Invoice generated successfully! Invoice ID: $invoice_id</div>";
                
                // Display invoice details
                $invoice_query = "SELECT i.*, ii.description, ii.unit_price, ii.quantity, ii.total_price 
                                 FROM invoices i 
                                 LEFT JOIN invoice_items ii ON i.id = ii.invoice_id 
                                 WHERE i.id = :invoice_id";
                $invoice_stmt = $db->prepare($invoice_query);
                $invoice_stmt->bindParam(':invoice_id', $invoice_id);
                $invoice_stmt->execute();
                
                echo "<h4>📄 Invoice Details:</h4>";
                echo "<table>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                
                $invoice_data = $invoice_stmt->fetch(PDO::FETCH_ASSOC);
                foreach ($invoice_data as $key => $value) {
                    $display_value = $value;
                    if (in_array($key, ['total_amount', 'unit_price', 'total_price']) && is_numeric($value)) {
                        $display_value = '₱' . number_format($value, 2);
                    }
                    echo "<tr><td><strong>$key</strong></td><td>$display_value</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ Failed to generate invoice</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
} else {
    echo "<div class='warning'>⚠️ No completed appointments found for testing. Creating a test scenario...</div>";
    
    // Create a test appointment if none exists
    echo "<h4>🏗️ Creating Test Appointment</h4>";
    
    // Check if we have any pets and clients to work with
    $client_query = "SELECT u.id as user_id, p.id as pet_id, u.first_name, u.last_name, p.name as pet_name 
                     FROM users u 
                     JOIN pets p ON u.id = p.owner_id 
                     WHERE u.role = 'client' 
                     LIMIT 1";
    $client_stmt = $db->prepare($client_query);
    $client_stmt->execute();
    
    if ($client_stmt->rowCount() > 0) {
        $client_data = $client_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get a vet
        $vet_query = "SELECT id FROM vets LIMIT 1";
        $vet_stmt = $db->prepare($vet_query);
        $vet_stmt->execute();
        
        if ($vet_stmt->rowCount() > 0) {
            $vet_id = $vet_stmt->fetchColumn();
            
            // Include appointment number generator
            include_once 'includes/appointment_number_generator.php';
            $generator = new AppointmentNumberGenerator($db);
            $appointment_number = $generator->generateAppointmentNumber();
            
            // Create test appointment
            $test_reason = 'Wellness Exam'; // Using one of our predefined services
            $appointment_date = date('Y-m-d');
            $appointment_time = '10:00:00';
            
            $create_apt_query = "INSERT INTO appointments (appointment_number, pet_id, vet_id, appointment_date, appointment_time, reason, status, created_at) 
                                VALUES (:appointment_number, :pet_id, :vet_id, :appointment_date, :appointment_time, :reason, 'scheduled', NOW())";
            
            $create_apt_stmt = $db->prepare($create_apt_query);
            $create_apt_stmt->bindParam(':appointment_number', $appointment_number);
            $create_apt_stmt->bindParam(':pet_id', $client_data['pet_id']);
            $create_apt_stmt->bindParam(':vet_id', $vet_id);
            $create_apt_stmt->bindParam(':appointment_date', $appointment_date);
            $create_apt_stmt->bindParam(':appointment_time', $appointment_time);
            $create_apt_stmt->bindParam(':reason', $test_reason);
            
            if ($create_apt_stmt->execute()) {
                $new_appointment_id = $db->lastInsertId();
                echo "<div class='success'>✅ Test appointment created successfully!</div>";
                echo "<div class='info'>";
                echo "<p><strong>📅 Appointment Details:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Appointment Number:</strong> $appointment_number</li>";
                echo "<li><strong>Client:</strong> {$client_data['first_name']} {$client_data['last_name']}</li>";
                echo "<li><strong>Pet:</strong> {$client_data['pet_name']}</li>";
                echo "<li><strong>Service:</strong> $test_reason (₱500.00)</li>";
                echo "<li><strong>Date & Time:</strong> $appointment_date at $appointment_time</li>";
                echo "</ul>";
                echo "</div>";
                
                // Now mark it as completed and test invoice generation
                echo "<h4>⚡ Testing Appointment Completion & Auto Invoice Generation</h4>";
                
                $complete_query = "UPDATE appointments SET status = 'completed' WHERE id = :appointment_id";
                $complete_stmt = $db->prepare($complete_query);
                $complete_stmt->bindParam(':appointment_id', $new_appointment_id);
                
                if ($complete_stmt->execute()) {
                    echo "<div class='success'>✅ Appointment marked as completed</div>";
                    
                    // Test automatic invoice generation
                    try {
                        $invoice_id = ServicePriceMapper::autoGenerateInvoice($db, $new_appointment_id);
                        if ($invoice_id) {
                            echo "<div class='success'>🎉 Invoice generated automatically! Invoice ID: $invoice_id</div>";
                            
                            // Display invoice details
                            $invoice_query = "SELECT i.*, ii.description, ii.unit_price, ii.quantity, ii.total_price 
                                             FROM invoices i 
                                             LEFT JOIN invoice_items ii ON i.id = ii.invoice_id 
                                             WHERE i.id = :invoice_id";
                            $invoice_stmt = $db->prepare($invoice_query);
                            $invoice_stmt->bindParam(':invoice_id', $invoice_id);
                            $invoice_stmt->execute();
                            
                            echo "<h4>🧾 Generated Invoice Details:</h4>";
                            echo "<table>";
                            echo "<tr><th>Field</th><th>Value</th></tr>";
                            
                            $invoice_data = $invoice_stmt->fetch(PDO::FETCH_ASSOC);
                            foreach ($invoice_data as $key => $value) {
                                $display_value = $value;
                                if (in_array($key, ['total_amount', 'unit_price', 'total_price']) && is_numeric($value)) {
                                    $display_value = '₱' . number_format($value, 2);
                                }
                                echo "<tr><td><strong>$key</strong></td><td>$display_value</td></tr>";
                            }
                            echo "</table>";
                            
                            echo "<div class='success'>💡 This demonstrates the complete automatic invoice generation workflow!</div>";
                        } else {
                            echo "<div class='error'>❌ Failed to generate invoice</div>";
                        }
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Invoice generation error: " . $e->getMessage() . "</div>";
                    }
                } else {
                    echo "<div class='error'>❌ Failed to mark appointment as completed</div>";
                }
            } else {
                echo "<div class='error'>❌ Failed to create test appointment</div>";
            }
        } else {
            echo "<div class='error'>❌ No veterinarians found in database</div>";
        }
    } else {
        echo "<div class='error'>❌ No clients or pets found in database</div>";
        echo "<div class='warning'>";
        echo "<p><strong>Please add some test data first:</strong></p>";
        echo "<ol>";
        echo "<li>Register a client account</li>";
        echo "<li>Add a pet for the client</li>";
        echo "<li>Create a veterinarian account</li>";
        echo "<li>Then run this test again</li>";
        echo "</ol>";
        echo "</div>";
    }
}

echo "<h3>🔄 Test 4: Complete Workflow Demonstration</h3>";
echo "<div class='info'>";
echo "<p><strong>How to test the complete automatic invoice flow:</strong></p>";
echo "<ol>";
echo "<li>Create an appointment with reason 'Wellness Exam' (₱500)</li>";
echo "<li>Mark it as 'completed' using the admin interface</li>";
echo "<li>Check if invoice is automatically generated</li>";
echo "<li>View the generated invoice in the admin reports</li>";
echo "</ol>";
echo "</div>";
?>

</body>
</html>
