-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 02:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pet_veterinary_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `appointment_number` varchar(20) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `vet_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','no-show') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `appointment_number`, `pet_id`, `vet_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 'A20250001', 1, 1, '2025-05-31', '10:00:00', 'Annual Check-up', 'Need to update vaccinations', 'scheduled', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(2, 'A20250002', 2, 3, '2025-06-01', '14:30:00', 'Dental Cleaning', 'Has shown signs of discomfort while eating', 'scheduled', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(3, 'A20250003', 3, 2, '2025-05-30', '09:00:00', 'Limping', 'Limping on right hind leg since yesterday', 'scheduled', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(4, 'A20250004', 4, 1, '2025-06-02', '16:00:00', 'Vaccination', 'Due for annual vaccinations', 'scheduled', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(5, 'A20250005', 5, 3, '2025-05-29', '11:30:00', 'Vomiting', 'Has been vomiting since last night', 'scheduled', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(6, 'A20250006', 1, 1, '2025-04-29', '13:00:00', 'Skin Condition', 'Itchy spots on back', 'completed', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(7, 'A20250007', 2, 3, '2025-03-30', '09:30:00', 'Wellness Exam', 'Routine check-up', 'completed', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(8, 'A20250008', 3, 2, '2025-04-14', '15:00:00', 'Injured Paw', 'Cut on front left paw', 'completed', '2025-05-28 23:40:41', '2025-05-28 23:40:41');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(20) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `supplier` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `name`, `category`, `description`, `quantity`, `unit`, `unit_price`, `reorder_level`, `supplier`, `expiry_date`, `location`, `created_at`, `updated_at`) VALUES
(1, 'Amoxicillin 250mg', 'Medication', 'Antibiotic for treating bacterial infections', 120, 'tablets', 0.75, 50, 'PharmaPlus Inc.', '2024-06-30', 'Cabinet A1', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(2, 'Rimadyl 100mg', 'Medication', 'Anti-inflammatory for pain management', 85, 'tablets', 1.20, 30, 'VetMeds Supply', '2024-12-15', 'Cabinet A2', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(3, 'Flea & Tick Preventive', 'Preventive', 'Monthly preventive treatment', 45, 'doses', 8.50, 20, 'PetHealth Products', '2025-03-10', 'Cabinet B3', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(4, 'Gauze Pads 4x4', 'Medical Supply', 'Sterile gauze pads for wound dressing', 200, 'pads', 0.15, 100, 'Medical Supplies Co.', NULL, 'Storage Room 1', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(5, 'Exam Gloves Medium', 'Medical Supply', 'Latex-free examination gloves', 500, 'gloves', 0.10, 200, 'Healthcare Essentials', NULL, 'Exam Room Supply Cabinet', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(6, 'Rabies Vaccine', 'Vaccine', 'Rabies prevention vaccine', 30, 'doses', 12.75, 10, 'Veterinary Biologics', '2024-08-15', 'Refrigerator 1', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(7, 'Distemper Vaccine', 'Vaccine', 'DHPP combination vaccine', 25, 'doses', 10.50, 10, 'Veterinary Biologics', '2024-07-22', 'Refrigerator 1', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(8, 'Cat Food - Prescription Diet', 'Food', 'Kidney Care prescription cat food', 12, 'bags', 25.99, 5, 'PetNutrition Inc.', '2025-01-15', 'Food Storage Area', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(9, 'Microchips', 'Identification', 'ISO standard pet microchips', 40, 'chips', 5.25, 15, 'PetTrack Systems', NULL, 'Office Cabinet 2', '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(10, 'Surgical Sutures', 'Surgical Supply', 'Absorbable surgical sutures', 30, 'packs', 8.75, 10, 'Surgical Solutions', '2024-10-30', 'Surgery Room Cabinet', '2025-05-28 23:40:42', '2025-05-28 23:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `client_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `paid` tinyint(1) DEFAULT 0,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `record_date` date NOT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`id`, `pet_id`, `appointment_id`, `record_date`, `diagnosis`, `treatment`, `medications`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 6, '2025-04-29', 'Allergic dermatitis', 'Prescribed antihistamines and medicated shampoo', 'Benadryl 25mg once daily, Medicated shampoo twice weekly', 'Follow up in 2 weeks if condition does not improve', 2, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(2, 2, 7, '2025-03-30', 'Healthy cat, slight tartar build-up', 'Dental cleaning recommended', 'None prescribed', 'Overall good health, recommend dental cleaning in the next 3 months', 4, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(3, 3, 8, '2025-04-14', 'Minor laceration on paw pad', 'Wound cleaned and bandaged', 'Amoxicillin 250mg twice daily for 7 days', 'Keep bandage clean and dry, change every 2 days', 3, '2025-05-28 23:40:42', '2025-05-28 23:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `species` varchar(50) NOT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `gender` enum('male','female','unknown') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `microchip_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pets`
--

INSERT INTO `pets` (`id`, `owner_id`, `name`, `species`, `breed`, `gender`, `date_of_birth`, `weight`, `microchip_id`, `created_at`, `updated_at`) VALUES
(1, 5, 'Max', 'Dog', 'Golden Retriever', 'male', '2019-06-15', 32.50, 'ABC123456', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(2, 5, 'Luna', 'Cat', 'Siamese', 'female', '2020-03-10', 4.20, 'DEF789012', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(3, 6, 'Bella', 'Dog', 'Labrador Retriever', 'female', '2018-09-22', 28.70, 'GHI345678', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(4, 7, 'Charlie', 'Dog', 'Beagle', 'male', '2020-11-05', 12.30, 'JKL901234', '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(5, 7, 'Oliver', 'Cat', 'Maine Coon', 'male', '2019-12-30', 6.80, 'MNO567890', '2025-05-28 23:40:41', '2025-05-28 23:40:41');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `category`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Wellness Exam', 'Complete physical examination and health assessment', 60.00, 30, 'Preventive Care', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(2, 'Vaccination - Core', 'Essential vaccines for dogs or cats', 45.00, 15, 'Preventive Care', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(3, 'Vaccination - Non-Core', 'Additional vaccines based on risk assessment', 35.00, 15, 'Preventive Care', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(4, 'Dental Cleaning', 'Professional dental cleaning under anesthesia', 250.00, 120, 'Dental', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(5, 'Spay/Neuter - Dog', 'Surgical sterilization for dogs', 350.00, 90, 'Surgery', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(6, 'Spay/Neuter - Cat', 'Surgical sterilization for cats', 250.00, 60, 'Surgery', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(7, 'X-Ray', 'Digital radiography, per view', 120.00, 30, 'Diagnostics', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(8, 'Ultrasound', 'Abdominal or cardiac ultrasound', 200.00, 45, 'Diagnostics', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(9, 'Blood Work - Basic', 'Complete blood count and basic chemistry panel', 95.00, 20, 'Diagnostics', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(10, 'Blood Work - Comprehensive', 'Complete blood count and comprehensive chemistry panel', 165.00, 20, 'Diagnostics', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(11, 'Emergency Consultation', 'Urgent care consultation fee', 125.00, 45, 'Emergency', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(12, 'Microchipping', 'Includes microchip and registration', 45.00, 15, 'Preventive Care', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(13, 'Nail Trim', 'Trimming of nails for dogs or cats', 20.00, 15, 'Grooming', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(14, 'Anal Gland Expression', 'Manual expression of anal glands', 25.00, 15, 'Preventive Care', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(15, 'Euthanasia', 'Humane euthanasia service', 150.00, 60, 'End of Life Care', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42'),
(16, 'Allergy Testing', 'Blood test for environmental and food allergies', 250.00, 30, 'Diagnostics', 1, '2025-05-28 23:40:42', '2025-05-28 23:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'clinic_name', 'PetCare Veterinary Clinic', 'Name of the veterinary clinic', '2025-05-28 23:40:42'),
(2, 'clinic_address', '123 Pet Street, Veterinary City, VC 12345', 'Physical address of the clinic', '2025-05-28 23:40:42'),
(3, 'clinic_phone', '(123) 456-7890', 'Main phone number for the clinic', '2025-05-28 23:40:42'),
(4, 'clinic_email', 'info@petcareclinic.com', 'Main email address for the clinic', '2025-05-28 23:40:42'),
(5, 'business_hours', '{\"monday\":\"8:00-18:00\",\"tuesday\":\"8:00-18:00\",\"wednesday\":\"8:00-18:00\",\"thursday\":\"8:00-18:00\",\"friday\":\"8:00-18:00\",\"saturday\":\"9:00-15:00\",\"sunday\":\"Closed\"}', 'Regular business hours', '2025-05-28 23:40:42'),
(6, 'appointment_interval', '30', 'Default appointment duration in minutes', '2025-05-28 23:40:42'),
(7, 'emergency_phone', '(123) 456-7899', 'Emergency after-hours phone number', '2025-05-28 23:40:42'),
(8, 'max_advance_booking_days', '90', 'Maximum days in advance that appointments can be booked', '2025-05-28 23:40:42'),
(9, 'allow_online_scheduling', 'true', 'Whether clients can schedule appointments online', '2025-05-28 23:40:42'),
(10, 'default_cancellation_policy', 'Please provide at least 24 hours notice when cancelling appointments.', 'Default appointment cancellation policy', '2025-05-28 23:40:42');

-- --------------------------------------------------------

--
-- Table structure for table `time_off`
--

CREATE TABLE `time_off` (
  `id` int(11) NOT NULL,
  `vet_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('client','vet','admin') NOT NULL DEFAULT 'client',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `email`, `phone`, `role`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin', 'Admin', 'User', 'admin@petcare.com', '123-456-7890', 'admin', '2025-05-29 07:44:03', '2025-05-28 23:40:41', '2025-05-28 23:44:03'),
(2, 'sherly', 'sherly', 'Sherly', 'Admin', 'sherly@petcare.com', '123-456-7897', 'admin', '2025-05-29 07:42:08', '2025-05-28 23:40:41', '2025-05-28 23:42:08'),
(3, 'drjohnson', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Sarah', 'Johnson', 'drjohnson@petcare.com', '123-456-7891', 'vet', NULL, '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(4, 'drsmith', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Michael', 'Smith', 'drsmith@petcare.com', '123-456-7892', 'vet', NULL, '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(5, 'drpatel', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Emily', 'Patel', 'drpatel@petcare.com', '123-456-7893', 'vet', NULL, '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(6, 'jennifer', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Jennifer', 'Adams', 'jennifer@example.com', '123-456-7894', 'client', NULL, '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(7, 'robert', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Robert', 'Chen', 'robert@example.com', '123-456-7895', 'client', NULL, '2025-05-28 23:40:41', '2025-05-28 23:40:41'),
(8, 'jessica', '$2y$10$VrgsuAK.FSsgZmG/aPCUHOTJHN7KmKBgBIFtQ0BAZ.qIqfaLktHE2', 'Jessica', 'Taylor', 'jessica@example.com', '123-456-7896', 'client', NULL, '2025-05-28 23:40:41', '2025-05-28 23:40:41');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `medical_record_id` int(11) DEFAULT NULL,
  `vaccine_name` varchar(100) NOT NULL,
  `vaccination_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `administered_by` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vets`
--

CREATE TABLE `vets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `bio` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vets`
--

INSERT INTO `vets` (`id`, `user_id`, `specialization`, `license_number`, `years_of_experience`, `bio`) VALUES
(1, 2, 'General Veterinary Medicine, Surgery', 'VET12345', 15, 'Dr. Johnson has over 15 years of experience in veterinary medicine with a special interest in preventive care and soft tissue surgery.'),
(2, 3, 'Orthopedics, Canine Sports Medicine', 'VET23456', 10, 'Dr. Smith specializes in orthopedic surgery and has a particular interest in canine sports medicine and rehabilitation.'),
(3, 4, 'Feline Medicine, Dentistry', 'VET34567', 8, 'Dr. Patel focuses on feline medicine and has advanced training in dental care and minimally invasive procedures.');

-- --------------------------------------------------------

--
-- Table structure for table `working_hours`
--

CREATE TABLE `working_hours` (
  `id` int(11) NOT NULL,
  `vet_id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `working_hours`
--

INSERT INTO `working_hours` (`id`, `vet_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 1, 1, '08:00:00', '17:00:00'),
(2, 1, 2, '08:00:00', '17:00:00'),
(3, 1, 3, '08:00:00', '17:00:00'),
(4, 1, 4, '08:00:00', '17:00:00'),
(5, 1, 5, '08:00:00', '17:00:00'),
(6, 2, 1, '09:00:00', '18:00:00'),
(7, 2, 2, '09:00:00', '18:00:00'),
(8, 2, 3, '09:00:00', '18:00:00'),
(9, 2, 4, '09:00:00', '18:00:00'),
(10, 2, 5, '09:00:00', '16:00:00'),
(11, 2, 6, '10:00:00', '14:00:00'),
(12, 3, 1, '10:00:00', '19:00:00'),
(13, 3, 2, '10:00:00', '19:00:00'),
(14, 3, 3, '10:00:00', '19:00:00'),
(15, 3, 5, '10:00:00', '19:00:00'),
(16, 3, 6, '09:00:00', '15:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointments_pet` (`pet_id`),
  ADD KEY `idx_appointments_vet` (`vet_id`),
  ADD KEY `idx_appointments_date` (`appointment_date`),
  ADD KEY `idx_appointments_status` (`status`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_category` (`category`),
  ADD KEY `idx_inventory_quantity` (`quantity`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_records_pet` (`pet_id`);

--
-- Indexes for table `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pets_owner` (`owner_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `time_off`
--
ALTER TABLE `time_off`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vet_id` (`vet_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `medical_record_id` (`medical_record_id`),
  ADD KEY `administered_by` (`administered_by`),
  ADD KEY `idx_vaccinations_pet` (`pet_id`);

--
-- Indexes for table `vets`
--
ALTER TABLE `vets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `working_hours`
--
ALTER TABLE `working_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vet_id` (`vet_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `time_off`
--
ALTER TABLE `time_off`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vets`
--
ALTER TABLE `vets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `working_hours`
--
ALTER TABLE `working_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`vet_id`) REFERENCES `vets` (`id`);

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `time_off`
--
ALTER TABLE `time_off`
  ADD CONSTRAINT `time_off_ibfk_1` FOREIGN KEY (`vet_id`) REFERENCES `vets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vaccination_records_ibfk_2` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vaccination_records_ibfk_3` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vets`
--
ALTER TABLE `vets`
  ADD CONSTRAINT `vets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `working_hours`
--
ALTER TABLE `working_hours`
  ADD CONSTRAINT `working_hours_ibfk_1` FOREIGN KEY (`vet_id`) REFERENCES `vets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
