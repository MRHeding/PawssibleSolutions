<?php
/**
 * Service Price Mapping Utility
 * Maps appointment reasons to standardized service prices
 */

class ServicePriceMapper {
    
    // Service price mapping based on your specified prices
    private static $service_prices = [
        'Wellness Exam' => 500.00,
        'Vaccination' => 500.00,
        'Sick Visit' => 1000.00,
        'Injury' => 2000.00,
        'Dental Care' => 500.00,
        'Dental' => 500.00, // Alternative name
        'Surgery Consultation' => 700.00,
        'Surgery' => 700.00, // Alternative name
        'Follow-up Visit' => 300.00,
        'Follow-up' => 300.00, // Alternative name
        // Default fallback prices for other services
        'Check-up' => 500.00,
        'Illness' => 1000.00,
        'Other' => 500.00
    ];
    
    /**
     * Get price for a specific service
     * @param string $service_name The service/reason name
     * @return float The price for the service
     */
    public static function getServicePrice($service_name) {
        // Normalize service name (remove extra spaces, convert to proper case)
        $normalized_name = trim($service_name);
        
        // Check for exact match first
        if (isset(self::$service_prices[$normalized_name])) {
            return self::$service_prices[$normalized_name];
        }
        
        // Check for partial matches (case-insensitive)
        foreach (self::$service_prices as $key => $price) {
            if (stripos($normalized_name, $key) !== false || stripos($key, $normalized_name) !== false) {
                return $price;
            }
        }
        
        // Default price if no match found
        return 500.00;
    }
    
    /**
     * Get all available services with prices
     * @return array Array of services with their prices
     */
    public static function getAllServices() {
        return self::$service_prices;
    }
    
    /**
     * Create invoice item data structure for a service
     * @param string $service_name The service name
     * @param int $quantity The quantity (default 1)
     * @return array Invoice item data
     */
    public static function createInvoiceItem($service_name, $quantity = 1) {
        $unit_price = self::getServicePrice($service_name);
        $total_price = $unit_price * $quantity;
        
        return [
            'description' => $service_name,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'total_price' => $total_price
        ];
    }
    
    /**
     * Auto-generate invoice for completed appointment
     * @param PDO $db Database connection
     * @param int $appointment_id Appointment ID
     * @return int|false Invoice ID on success, false on failure
     */
    public static function autoGenerateInvoice($db, $appointment_id) {
        try {
            // Get appointment details
            $apt_query = "SELECT a.*, p.owner_id, a.reason 
                         FROM appointments a 
                         JOIN pets p ON a.pet_id = p.id 
                         WHERE a.id = :appointment_id AND a.status = 'completed'";
            $apt_stmt = $db->prepare($apt_query);
            $apt_stmt->bindParam(':appointment_id', $appointment_id);
            $apt_stmt->execute();
            
            $appointment = $apt_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$appointment) {
                return false;
            }
            
            // Check if invoice already exists
            $check_query = "SELECT id FROM invoices WHERE appointment_id = :appointment_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':appointment_id', $appointment_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                // Invoice already exists
                return $check_stmt->fetchColumn();
            }
            
            // Create invoice item based on appointment reason
            $invoice_item = self::createInvoiceItem($appointment['reason']);
            
            $db->beginTransaction();
            
            // Create invoice
            $invoice_query = "INSERT INTO invoices (appointment_id, client_id, total_amount, notes, created_at) 
                             VALUES (:appointment_id, :client_id, :total_amount, :notes, NOW())";
            $invoice_stmt = $db->prepare($invoice_query);
            $invoice_stmt->bindParam(':appointment_id', $appointment_id);
            $invoice_stmt->bindParam(':client_id', $appointment['owner_id']);
            $invoice_stmt->bindParam(':total_amount', $invoice_item['total_price']);
            
            $notes = "Auto-generated invoice for appointment " . $appointment['appointment_number'] . 
                    " - Service: " . $appointment['reason'];
            $invoice_stmt->bindParam(':notes', $notes);
            $invoice_stmt->execute();
            
            $invoice_id = $db->lastInsertId();
            
            // Add invoice item
            $item_query = "INSERT INTO invoice_items (invoice_id, service_id, description, quantity, unit_price, total_price) 
                          VALUES (:invoice_id, NULL, :description, :quantity, :unit_price, :total_price)";
            $item_stmt = $db->prepare($item_query);
            $item_stmt->bindParam(':invoice_id', $invoice_id);
            $item_stmt->bindParam(':description', $invoice_item['description']);
            $item_stmt->bindParam(':quantity', $invoice_item['quantity']);
            $item_stmt->bindParam(':unit_price', $invoice_item['unit_price']);
            $item_stmt->bindParam(':total_price', $invoice_item['total_price']);
            $item_stmt->execute();
            
            $db->commit();
            
            return $invoice_id;
            
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }
}
?>
