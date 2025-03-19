-- Drop existing database if it exists
DROP DATABASE IF EXISTS pet_veterinary_system;

-- Create database
CREATE DATABASE pet_veterinary_system;

-- Use the database
USE pet_veterinary_system;

-- Users table (for clients, vets, admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    role ENUM('client', 'vet', 'admin') NOT NULL DEFAULT 'client',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Veterinarians table
CREATE TABLE vets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialization VARCHAR(100),
    license_number VARCHAR(50),
    years_of_experience INT,
    bio TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pets table
CREATE TABLE pets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    species VARCHAR(50) NOT NULL,
    breed VARCHAR(50),
    gender ENUM('male', 'female', 'unknown') NOT NULL,
    date_of_birth DATE,
    weight DECIMAL(5,2),
    microchip_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    vet_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason VARCHAR(100) NOT NULL,
    notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled', 'no-show') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE RESTRICT
);

-- Medical Records table
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    appointment_id INT,
    record_date DATE NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    medications TEXT,
    notes TEXT,
    created_by INT NOT NULL, -- ID of veterinarian user who created this record
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Vaccination Records table
CREATE TABLE vaccination_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    medical_record_id INT,
    vaccine_name VARCHAR(100) NOT NULL,
    vaccination_date DATE NOT NULL,
    valid_until DATE,
    administered_by INT NOT NULL, -- ID of veterinarian user
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE SET NULL,
    FOREIGN KEY (administered_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT, -- in minutes
    category VARCHAR(50),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Invoices table
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT,
    client_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_date DATETIME,
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Invoice Items table
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    service_id INT,
    description VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- Working Hours table
CREATE TABLE working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vet_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 0 = Sunday, 1 = Monday, etc.
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE CASCADE
);

-- Time Off table
CREATE TABLE time_off (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vet_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason VARCHAR(255),
    approved BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE CASCADE
);

-- Settings table
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    reorder_level INT NOT NULL DEFAULT 10,
    supplier VARCHAR(100),
    expiry_date DATE,
    location VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO users (username, password, first_name, last_name, email, phone, role) VALUES 
('admin', '$2y$10$rWJMnvfgOMXzeUD8Os.IFuRjshI3xRb0sH0O/qoQJJsLMXndXfwme', 'Admin', 'User', 'admin@petcare.com', '123-456-7890', 'admin'),
('sherly', '$2y$10$NuyKfzwCUyaiYjyD9mV42.UbKmCpPXYdgegrEoYVpOa9iRDKrCCS2', 'Sherly', 'Admin', 'sherly@petcare.com', '123-456-7897', 'admin');
-- Password for admin: admin123
-- Password for sherly: sherly^10

-- Insert some sample veterinary users
INSERT INTO users (username, password, first_name, last_name, email, phone, role) VALUES 
('drjohnson', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Sarah', 'Johnson', 'drjohnson@petcare.com', '123-456-7891', 'vet'),
('drsmith', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Michael', 'Smith', 'drsmith@petcare.com', '123-456-7892', 'vet'),
('drpatel', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Emily', 'Patel', 'drpatel@petcare.com', '123-456-7893', 'vet');
-- Password: password123

-- Insert vet details
INSERT INTO vets (user_id, specialization, license_number, years_of_experience, bio) VALUES 
(2, 'General Veterinary Medicine, Surgery', 'VET12345', 15, 'Dr. Johnson has over 15 years of experience in veterinary medicine with a special interest in preventive care and soft tissue surgery.'),
(3, 'Orthopedics, Canine Sports Medicine', 'VET23456', 10, 'Dr. Smith specializes in orthopedic surgery and has a particular interest in canine sports medicine and rehabilitation.'),
(4, 'Feline Medicine, Dentistry', 'VET34567', 8, 'Dr. Patel focuses on feline medicine and has advanced training in dental care and minimally invasive procedures.');

-- Insert sample client users
INSERT INTO users (username, password, first_name, last_name, email, phone, role) VALUES 
('jennifer', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Jennifer', 'Adams', 'jennifer@example.com', '123-456-7894', 'client'),
('robert', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Robert', 'Chen', 'robert@example.com', '123-456-7895', 'client'),
('jessica', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Jessica', 'Taylor', 'jessica@example.com', '123-456-7896', 'client');
-- Password: password123

-- Insert sample pets
INSERT INTO pets (owner_id, name, species, breed, gender, date_of_birth, weight, microchip_id) VALUES 
(5, 'Max', 'Dog', 'Golden Retriever', 'male', '2019-06-15', 32.5, 'ABC123456'),
(5, 'Luna', 'Cat', 'Siamese', 'female', '2020-03-10', 4.2, 'DEF789012'),
(6, 'Bella', 'Dog', 'Labrador Retriever', 'female', '2018-09-22', 28.7, 'GHI345678'),
(7, 'Charlie', 'Dog', 'Beagle', 'male', '2020-11-05', 12.3, 'JKL901234'),
(7, 'Oliver', 'Cat', 'Maine Coon', 'male', '2019-12-30', 6.8, 'MNO567890');

-- Insert sample appointments
INSERT INTO appointments (pet_id, vet_id, appointment_date, appointment_time, reason, notes, status) VALUES 
(1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'Annual Check-up', 'Need to update vaccinations', 'scheduled'),
(2, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:30:00', 'Dental Cleaning', 'Has shown signs of discomfort while eating', 'scheduled'),
(3, 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 'Limping', 'Limping on right hind leg since yesterday', 'scheduled'),
(4, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:00:00', 'Vaccination', 'Due for annual vaccinations', 'scheduled'),
(5, 3, CURDATE(), '11:30:00', 'Vomiting', 'Has been vomiting since last night', 'scheduled'),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), '13:00:00', 'Skin Condition', 'Itchy spots on back', 'completed'),
(2, 3, DATE_SUB(CURDATE(), INTERVAL 60 DAY), '09:30:00', 'Wellness Exam', 'Routine check-up', 'completed'),
(3, 2, DATE_SUB(CURDATE(), INTERVAL 45 DAY), '15:00:00', 'Injured Paw', 'Cut on front left paw', 'completed');

-- Insert sample medical records for completed appointments
INSERT INTO medical_records (pet_id, appointment_id, record_date, diagnosis, treatment, medications, notes, created_by) VALUES 
(1, 6, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Allergic dermatitis', 'Prescribed antihistamines and medicated shampoo', 'Benadryl 25mg once daily, Medicated shampoo twice weekly', 'Follow up in 2 weeks if condition does not improve', 2),
(2, 7, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'Healthy cat, slight tartar build-up', 'Dental cleaning recommended', 'None prescribed', 'Overall good health, recommend dental cleaning in the next 3 months', 4),
(3, 8, DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'Minor laceration on paw pad', 'Wound cleaned and bandaged', 'Amoxicillin 250mg twice daily for 7 days', 'Keep bandage clean and dry, change every 2 days', 3);

-- Insert sample services
INSERT INTO services (name, description, price, duration, category, active) VALUES
('Wellness Exam', 'Complete physical examination and health assessment', 60.00, 30, 'Preventive Care', true),
('Vaccination - Core', 'Essential vaccines for dogs or cats', 45.00, 15, 'Preventive Care', true),
('Vaccination - Non-Core', 'Additional vaccines based on risk assessment', 35.00, 15, 'Preventive Care', true),
('Dental Cleaning', 'Professional dental cleaning under anesthesia', 250.00, 120, 'Dental', true),
('Spay/Neuter - Dog', 'Surgical sterilization for dogs', 350.00, 90, 'Surgery', true),
('Spay/Neuter - Cat', 'Surgical sterilization for cats', 250.00, 60, 'Surgery', true),
('X-Ray', 'Digital radiography, per view', 120.00, 30, 'Diagnostics', true),
('Ultrasound', 'Abdominal or cardiac ultrasound', 200.00, 45, 'Diagnostics', true),
('Blood Work - Basic', 'Complete blood count and basic chemistry panel', 95.00, 20, 'Diagnostics', true),
('Blood Work - Comprehensive', 'Complete blood count and comprehensive chemistry panel', 165.00, 20, 'Diagnostics', true),
('Emergency Consultation', 'Urgent care consultation fee', 125.00, 45, 'Emergency', true),
('Microchipping', 'Includes microchip and registration', 45.00, 15, 'Preventive Care', true),
('Nail Trim', 'Trimming of nails for dogs or cats', 20.00, 15, 'Grooming', true),
('Anal Gland Expression', 'Manual expression of anal glands', 25.00, 15, 'Preventive Care', true),
('Euthanasia', 'Humane euthanasia service', 150.00, 60, 'End of Life Care', true),
('Allergy Testing', 'Blood test for environmental and food allergies', 250.00, 30, 'Diagnostics', true);

-- Insert sample working hours for vets
INSERT INTO working_hours (vet_id, day_of_week, start_time, end_time) VALUES
-- Dr. Johnson (vet_id 1)
(1, 1, '08:00:00', '17:00:00'), -- Monday
(1, 2, '08:00:00', '17:00:00'), -- Tuesday
(1, 3, '08:00:00', '17:00:00'), -- Wednesday
(1, 4, '08:00:00', '17:00:00'), -- Thursday
(1, 5, '08:00:00', '17:00:00'), -- Friday
-- Dr. Smith (vet_id 2)
(2, 1, '09:00:00', '18:00:00'), -- Monday
(2, 2, '09:00:00', '18:00:00'), -- Tuesday
(2, 3, '09:00:00', '18:00:00'), -- Wednesday
(2, 4, '09:00:00', '18:00:00'), -- Thursday
(2, 5, '09:00:00', '16:00:00'), -- Friday
(2, 6, '10:00:00', '14:00:00'), -- Saturday
-- Dr. Patel (vet_id 3)
(3, 1, '10:00:00', '19:00:00'), -- Monday
(3, 2, '10:00:00', '19:00:00'), -- Tuesday
(3, 3, '10:00:00', '19:00:00'), -- Wednesday
(3, 5, '10:00:00', '19:00:00'), -- Friday
(3, 6, '09:00:00', '15:00:00'); -- Saturday

-- Insert system settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('clinic_name', 'PetCare Veterinary Clinic', 'Name of the veterinary clinic'),
('clinic_address', '123 Pet Street, Veterinary City, VC 12345', 'Physical address of the clinic'),
('clinic_phone', '(123) 456-7890', 'Main phone number for the clinic'),
('clinic_email', 'info@petcareclinic.com', 'Main email address for the clinic'),
('business_hours', '{"monday":"8:00-18:00","tuesday":"8:00-18:00","wednesday":"8:00-18:00","thursday":"8:00-18:00","friday":"8:00-18:00","saturday":"9:00-15:00","sunday":"Closed"}', 'Regular business hours'),
('appointment_interval', '30', 'Default appointment duration in minutes'),
('emergency_phone', '(123) 456-7899', 'Emergency after-hours phone number'),
('max_advance_booking_days', '90', 'Maximum days in advance that appointments can be booked'),
('allow_online_scheduling', 'true', 'Whether clients can schedule appointments online'),
('default_cancellation_policy', 'Please provide at least 24 hours notice when cancelling appointments.', 'Default appointment cancellation policy');

-- Insert sample inventory items
INSERT INTO inventory (name, category, description, quantity, unit, unit_price, reorder_level, supplier, expiry_date, location) VALUES
('Amoxicillin 250mg', 'Medication', 'Antibiotic for treating bacterial infections', 120, 'tablets', 0.75, 50, 'PharmaPlus Inc.', '2024-06-30', 'Cabinet A1'),
('Rimadyl 100mg', 'Medication', 'Anti-inflammatory for pain management', 85, 'tablets', 1.20, 30, 'VetMeds Supply', '2024-12-15', 'Cabinet A2'),
('Flea & Tick Preventive', 'Preventive', 'Monthly preventive treatment', 45, 'doses', 8.50, 20, 'PetHealth Products', '2025-03-10', 'Cabinet B3'),
('Gauze Pads 4x4', 'Medical Supply', 'Sterile gauze pads for wound dressing', 200, 'pads', 0.15, 100, 'Medical Supplies Co.', NULL, 'Storage Room 1'),
('Exam Gloves Medium', 'Medical Supply', 'Latex-free examination gloves', 500, 'gloves', 0.10, 200, 'Healthcare Essentials', NULL, 'Exam Room Supply Cabinet'),
('Rabies Vaccine', 'Vaccine', 'Rabies prevention vaccine', 30, 'doses', 12.75, 10, 'Veterinary Biologics', '2024-08-15', 'Refrigerator 1'),
('Distemper Vaccine', 'Vaccine', 'DHPP combination vaccine', 25, 'doses', 10.50, 10, 'Veterinary Biologics', '2024-07-22', 'Refrigerator 1'),
('Cat Food - Prescription Diet', 'Food', 'Kidney Care prescription cat food', 12, 'bags', 25.99, 5, 'PetNutrition Inc.', '2025-01-15', 'Food Storage Area'),
('Microchips', 'Identification', 'ISO standard pet microchips', 40, 'chips', 5.25, 15, 'PetTrack Systems', NULL, 'Office Cabinet 2'),
('Surgical Sutures', 'Surgical Supply', 'Absorbable surgical sutures', 30, 'packs', 8.75, 10, 'Surgical Solutions', '2024-10-30', 'Surgery Room Cabinet');

-- Create indexes for improved query performance
ALTER TABLE users ADD INDEX idx_users_role (role);
ALTER TABLE pets ADD INDEX idx_pets_owner (owner_id);
ALTER TABLE appointments ADD INDEX idx_appointments_pet (pet_id);
ALTER TABLE appointments ADD INDEX idx_appointments_vet (vet_id);
ALTER TABLE appointments ADD INDEX idx_appointments_date (appointment_date);
ALTER TABLE appointments ADD INDEX idx_appointments_status (status);
ALTER TABLE medical_records ADD INDEX idx_records_pet (pet_id);
ALTER TABLE vaccination_records ADD INDEX idx_vaccinations_pet (pet_id);
ALTER TABLE inventory ADD INDEX idx_inventory_category (category);
ALTER TABLE inventory ADD INDEX idx_inventory_quantity (quantity);
