<?php
/**
 * Appointment Number Generator Utility
 * Generates unique appointment numbers in the format: A{YEAR}{SEQUENCE}
 * Example: A20250001, A20250002, etc.
 */

class AppointmentNumberGenerator {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Generate a unique appointment number
     * Format: A{YEAR}{4-digit sequence}
     * @return string The generated appointment number
     */
    public function generateAppointmentNumber() {
        $year = date('Y');
        $prefix = 'A' . $year;
        
        // Get the highest sequence number for the current year
        $query = "SELECT appointment_number FROM appointments 
                 WHERE appointment_number LIKE :prefix 
                 ORDER BY appointment_number DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $search_prefix = $prefix . '%';
        $stmt->bindParam(':prefix', $search_prefix);
        $stmt->execute();
        
        $lastNumber = $stmt->fetchColumn();
        
        if ($lastNumber) {
            // Extract the sequence number from the last appointment number
            $sequence = intval(substr($lastNumber, strlen($prefix))) + 1;
        } else {
            // First appointment of the year
            $sequence = 1;
        }
        
        // Format with leading zeros (4 digits)
        $formattedSequence = str_pad($sequence, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $formattedSequence;
    }
    
    /**
     * Validate appointment number format
     * @param string $appointmentNumber
     * @return bool
     */
    public function validateAppointmentNumber($appointmentNumber) {
        // Pattern: A + 4-digit year + 4-digit sequence
        return preg_match('/^A\d{4}\d{4}$/', $appointmentNumber);
    }
    
    /**
     * Check if appointment number exists
     * @param string $appointmentNumber
     * @return bool
     */
    public function appointmentNumberExists($appointmentNumber) {
        $query = "SELECT COUNT(*) FROM appointments WHERE appointment_number = :appointment_number";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':appointment_number', $appointmentNumber);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get appointment by appointment number
     * @param string $appointmentNumber
     * @return array|false
     */
    public function getAppointmentByNumber($appointmentNumber) {
        $query = "SELECT a.*, p.name as pet_name, p.species, 
                 CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                 CONCAT(vu.first_name, ' ', vu.last_name) as vet_name
                 FROM appointments a 
                 JOIN pets p ON a.pet_id = p.id 
                 JOIN users u ON p.owner_id = u.id 
                 JOIN vets v ON a.vet_id = v.id 
                 JOIN users vu ON v.user_id = vu.id 
                 WHERE a.appointment_number = :appointment_number";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':appointment_number', $appointmentNumber);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Search appointments by appointment number (partial match)
     * @param string $searchTerm
     * @return array
     */
    public function searchByAppointmentNumber($searchTerm) {
        $query = "SELECT a.*, p.name as pet_name, p.species, 
                 CONCAT(u.first_name, ' ', u.last_name) as owner_name,
                 CONCAT(vu.first_name, ' ', vu.last_name) as vet_name
                 FROM appointments a 
                 JOIN pets p ON a.pet_id = p.id 
                 JOIN users u ON p.owner_id = u.id 
                 JOIN vets v ON a.vet_id = v.id 
                 JOIN users vu ON v.user_id = vu.id 
                 WHERE a.appointment_number LIKE :search_term
                 ORDER BY a.appointment_date DESC, a.appointment_time ASC";
        
        $stmt = $this->db->prepare($query);
        $searchPattern = '%' . $searchTerm . '%';
        $stmt->bindParam(':search_term', $searchPattern);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>