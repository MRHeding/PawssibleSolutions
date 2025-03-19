<?php
// This script sets up the database and creates necessary tables

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$db_name = "pet_veterinary_system";

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    $conn->exec($sql);
    
    echo "Database created or already exists<br>";
    
    // Select the database
    $conn->exec("USE $db_name");
    
    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        role ENUM('admin', 'vet', 'staff', 'client') NOT NULL DEFAULT 'client',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "Users table created<br>";
    
    // Create vets table
    $sql = "CREATE TABLE IF NOT EXISTS vets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        specialization VARCHAR(100),
        bio TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Vets table created<br>";
    
    // Create pets table
    $sql = "CREATE TABLE IF NOT EXISTS pets (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        owner_id INT(11) NOT NULL,
        name VARCHAR(50) NOT NULL,
        species VARCHAR(50) NOT NULL,
        breed VARCHAR(50),
        gender ENUM('male', 'female', 'unknown') NOT NULL,
        date_of_birth DATE,
        weight DECIMAL(5,2),
        microchip_id VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "Pets table created<br>";
    
    // Create appointments table
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        pet_id INT(11) NOT NULL,
        vet_id INT(11) NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        reason VARCHAR(100) NOT NULL,
        notes TEXT,
        status ENUM('scheduled', 'completed', 'cancelled', 'no-show') NOT NULL DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
        FOREIGN KEY (vet_id) REFERENCES vets(id)
    )";
    $conn->exec($sql);
    echo "Appointments table created<br>";
    
    // Create medical_records table
    $sql = "CREATE TABLE IF NOT EXISTS medical_records (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        pet_id INT(11) NOT NULL,
        appointment_id INT(11),
        record_date DATE NOT NULL,
        diagnosis TEXT,
        treatment TEXT,
        medications TEXT,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
    )";
    $conn->exec($sql);
    echo "Medical records table created<br>";
    
    // Create admin user if doesn't exist
    $check_admin = "SELECT id FROM users WHERE username = 'admin'";
    $stmt = $conn->prepare($check_admin);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        $insert_admin = "INSERT INTO users (username, password, email, first_name, last_name, phone, role) 
                        VALUES ('admin', :password, 'admin@petcareclinic.com', 'Admin', 'User', '123-456-7890', 'admin')";
        $stmt = $conn->prepare($insert_admin);
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        echo "Admin user created<br>";
        
        // Create vet users and their vet records
        $vet_data = [
            [
                'username' => 'drjohnson',
                'password' => password_hash('johnson123', PASSWORD_DEFAULT),
                'email' => 'sarah.johnson@petcareclinic.com',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'phone' => '555-123-4567',
                'specialization' => 'General Practice, Surgery'
            ],
            [
                'username' => 'drchen',
                'password' => password_hash('chen123', PASSWORD_DEFAULT),
                'email' => 'michael.chen@petcareclinic.com',
                'first_name' => 'Michael',
                'last_name' => 'Chen',
                'phone' => '555-234-5678',
                'specialization' => 'Orthopedics'
            ],
            [
                'username' => 'drpatel',
                'password' => password_hash('patel123', PASSWORD_DEFAULT),
                'email' => 'emily.patel@petcareclinic.com',
                'first_name' => 'Emily',
                'last_name' => 'Patel',
                'phone' => '555-345-6789',
                'specialization' => 'Feline Medicine, Dentistry'
            ]
        ];
        
        foreach ($vet_data as $vet) {
            // Insert user
            $insert_user = "INSERT INTO users (username, password, email, first_name, last_name, phone, role) 
                           VALUES (:username, :password, :email, :first_name, :last_name, :phone, 'vet')";
            $stmt = $conn->prepare($insert_user);
            $stmt->bindParam(':username', $vet['username']);
            $stmt->bindParam(':password', $vet['password']);
            $stmt->bindParam(':email', $vet['email']);
            $stmt->bindParam(':first_name', $vet['first_name']);
            $stmt->bindParam(':last_name', $vet['last_name']);
            $stmt->bindParam(':phone', $vet['phone']);
            $stmt->execute();
            
            $user_id = $conn->lastInsertId();
            
            // Insert vet
            $insert_vet = "INSERT INTO vets (user_id, specialization) 
                          VALUES (:user_id, :specialization)";
            $stmt = $conn->prepare($insert_vet);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':specialization', $vet['specialization']);
            $stmt->execute();
        }
        echo "Sample vet users created<br>";
        
        // Create sample client user
        $client_password = password_hash("client123", PASSWORD_DEFAULT);
        $insert_client = "INSERT INTO users (username, password, email, first_name, last_name, phone, role) 
                         VALUES ('client', :password, 'client@example.com', 'John', 'Doe', '555-987-6543', 'client')";
        $stmt = $conn->prepare($insert_client);
        $stmt->bindParam(':password', $client_password);
        $stmt->execute();
        
        $client_id = $conn->lastInsertId();
        echo "Sample client user created<br>";
        
        // Add sample pets for the client
        $pets = [
            [
                'name' => 'Max',
                'species' => 'Dog',
                'breed' => 'Golden Retriever',
                'gender' => 'male',
                'date_of_birth' => '2020-06-15',
                'weight' => 28.5
            ],
            [
                'name' => 'Luna',
                'species' => 'Cat',
                'breed' => 'Siamese',
                'gender' => 'female',
                'date_of_birth' => '2021-03-10',
                'weight' => 4.2
            ]
        ];
        
        foreach ($pets as $pet) {
            $insert_pet = "INSERT INTO pets (owner_id, name, species, breed, gender, date_of_birth, weight) 
                          VALUES (:owner_id, :name, :species, :breed, :gender, :date_of_birth, :weight)";
            $stmt = $conn->prepare($insert_pet);
            $stmt->bindParam(':owner_id', $client_id);
            $stmt->bindParam(':name', $pet['name']);
            $stmt->bindParam(':species', $pet['species']);
            $stmt->bindParam(':breed', $pet['breed']);
            $stmt->bindParam(':gender', $pet['gender']);
            $stmt->bindParam(':date_of_birth', $pet['date_of_birth']);
            $stmt->bindParam(':weight', $pet['weight']);
            $stmt->execute();
        }
        echo "Sample pets created<br>";
    } else {
        echo "Admin user already exists<br>";
    }
    
    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='../index.php'>Go to Homepage</a></p>";
    
} catch(PDOException $e) {
    echo "<br>Error: " . $e->getMessage();
}

// Close connection
$conn = null;
?>
