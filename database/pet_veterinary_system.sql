-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 12, 2025 at 03:30 PM
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
  `appointment_number` varchar(20) NOT NULL DEFAULT '',
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
(13, 'A20250001', 4, 5, '2025-07-12', '11:00:00', 'Wellness Exam', '', 'completed', '2025-07-12 02:55:24', '2025-07-12 02:56:12');

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

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `appointment_id`, `client_id`, `total_amount`, `paid`, `payment_date`, `payment_method`, `notes`, `created_at`, `updated_at`) VALUES
(3, 13, 18, 250.00, 0, NULL, NULL, '', '2025-07-12 04:13:35', '2025-07-12 04:13:35');

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

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `service_id`, `description`, `quantity`, `unit_price`, `total_price`) VALUES
(3, 3, 4, 'Dental Cleaning', 1, 250.00, 250.00);

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `record_date` date NOT NULL,
  `record_type` varchar(50) DEFAULT NULL,
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

INSERT INTO `medical_records` (`id`, `pet_id`, `appointment_id`, `record_date`, `record_type`, `diagnosis`, `treatment`, `medications`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 4, 13, '2025-07-12', 'Vaccination', 'Low Immune System', 'Booster', 'Cat Boost', 'Always feed your cat', 1, '2025-07-12 03:15:00', '2025-07-12 03:22:59'),
(7, 4, NULL, '2025-07-12', 'Sick Visit', 'Numb Limb', 'Anti bacterial treatment and Mefenamic', 'Amoxicillin', 'Always keep your cat indoor while the injury is not healing yet', 1, '2025-07-12 04:05:19', '2025-07-12 04:08:54');

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
(4, 18, 'Molly', 'Cat', 'Other', 'male', '2025-07-09', 5.00, NULL, '2025-07-09 01:30:27', '2025-07-09 01:30:27');

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
(1, 'Wellness Exam', 'Complete physical examination and health assessment', 60.00, 30, 'Preventive Care', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(2, 'Vaccination - Core', 'Essential vaccines for dogs or cats', 45.00, 15, 'Preventive Care', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(3, 'Vaccination - Non-Core', 'Additional vaccines based on risk assessment', 35.00, 15, 'Preventive Care', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(4, 'Dental Cleaning', 'Professional dental cleaning under anesthesia', 250.00, 120, 'Dental', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(5, 'Spay/Neuter - Dog', 'Surgical sterilization for dogs', 350.00, 90, 'Surgery', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(6, 'Spay/Neuter - Cat', 'Surgical sterilization for cats', 250.00, 60, 'Surgery', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(7, 'X-Ray', 'Digital radiography, per view', 120.00, 30, 'Diagnostics', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(8, 'Ultrasound', 'Abdominal or cardiac ultrasound', 200.00, 45, 'Diagnostics', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(9, 'Blood Work - Basic', 'Complete blood count and basic chemistry panel', 95.00, 20, 'Diagnostics', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(10, 'Blood Work - Comprehensive', 'Complete blood count and comprehensive chemistry panel', 165.00, 20, 'Diagnostics', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(11, 'Emergency Consultation', 'Urgent care consultation fee', 125.00, 45, 'Emergency', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(12, 'Microchipping', 'Includes microchip and registration', 45.00, 15, 'Preventive Care', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(13, 'Nail Trim', 'Trimming of nails for dogs or cats', 20.00, 15, 'Grooming', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(14, 'Anal Gland Expression', 'Manual expression of anal glands', 25.00, 15, 'Preventive Care', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(15, 'Euthanasia', 'Humane euthanasia service', 150.00, 60, 'End of Life Care', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55'),
(16, 'Allergy Testing', 'Blood test for environmental and food allergies', 250.00, 30, 'Diagnostics', 1, '2025-03-25 15:25:55', '2025-03-25 15:25:55');

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
(1, 'clinic_name', 'PetCare Veterinary Clinic', 'Name of the veterinary clinic', '2025-03-25 15:25:55'),
(2, 'clinic_address', '123 Pet Street, Veterinary City, VC 12345', 'Physical address of the clinic', '2025-03-25 15:25:55'),
(3, 'clinic_phone', '(123) 456-7890', 'Main phone number for the clinic', '2025-03-25 15:25:55'),
(4, 'clinic_email', 'info@petcareclinic.com', 'Main email address for the clinic', '2025-03-25 15:25:55'),
(5, 'business_hours', '{\"monday\":\"8:00-18:00\",\"tuesday\":\"8:00-18:00\",\"wednesday\":\"8:00-18:00\",\"thursday\":\"8:00-18:00\",\"friday\":\"8:00-18:00\",\"saturday\":\"9:00-15:00\",\"sunday\":\"Closed\"}', 'Regular business hours', '2025-03-25 15:25:55'),
(6, 'appointment_interval', '30', 'Default appointment duration in minutes', '2025-03-25 15:25:55'),
(7, 'emergency_phone', '(123) 456-7899', 'Emergency after-hours phone number', '2025-03-25 15:25:55'),
(8, 'max_advance_booking_days', '90', 'Maximum days in advance that appointments can be booked', '2025-03-25 15:25:55'),
(9, 'allow_online_scheduling', 'true', 'Whether clients can schedule appointments online', '2025-03-25 15:25:55'),
(10, 'default_cancellation_policy', 'Please provide at least 24 hours notice when cancelling appointments.', 'Default appointment cancellation policy', '2025-03-25 15:25:55');

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
(1, 'admin', 'admin', 'Admin', 'User', 'admin@petcare.com', '123-456-7890', 'admin', '2025-07-12 21:18:17', '2025-03-25 15:25:55', '2025-07-12 13:18:17'),
(2, 'sherly', 'sherly', 'Sherly', 'Admin', 'sherly@petcare.com', '123-456-7897', 'admin', '2025-06-18 22:38:42', '2025-03-25 15:25:55', '2025-06-18 14:38:42'),
(17, 'jmahamud', '$2y$10$SQgBLqcmpQdBJmTOx4ThjuOLvCYD18UXghH8JYu40TJgJ.kwdkNR6', 'Julsiya', 'Mahamud', 'rrasheed121099@gmail.com', '09918195487', 'vet', '2025-06-18 23:20:35', '2025-06-18 15:20:23', '2025-06-18 15:20:35'),
(18, 'rasheed', '$2y$10$fqJGX1S4eP2FsGHdXxAu7.VeK9qWojzQXYeL/xa6dK2DaJcVsOG1u', 'Rasheed', 'Heding', 'rasheed121099@gmail.com', '09918195487', 'client', '2025-07-12 11:13:04', '2025-06-18 17:20:21', '2025-07-12 03:13:04');

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
(5, 17, 'Medicine', '9248WS', 13, 'Hello World');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_appointment_number` (`appointment_number`),
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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `vets`
--
ALTER TABLE `vets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `vets`
--
ALTER TABLE `vets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for table `vets`
--
ALTER TABLE `vets`
  ADD CONSTRAINT `vets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
