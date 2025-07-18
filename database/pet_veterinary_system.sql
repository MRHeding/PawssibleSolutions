-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2025 at 02:26 AM
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
(1, 'A20250001', 1, 6, '2025-07-17', '14:00:00', 'Wellness Exam', 'May pet is tired all the time and not eating his favorite food', 'completed', '2025-07-16 13:03:15', '2025-07-16 13:05:51'),
(2, 'A20250002', 1, 6, '2025-07-18', '13:00:00', 'Check-up', 'Be on time po salamat', 'scheduled', '2025-07-16 13:06:33', '2025-07-16 13:06:33');

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
(1, 'Whiskas', 'Food', 'Cat Food', 9, 'pack', 150.00, 2, 'SM Supermall', '2028-06-16', 'Cabinet A1', '2025-07-16 13:17:10', '2025-07-17 00:24:42'),
(2, 'Amoxcillin', 'Medicine', 'Antibiotics', 9, 'tablets', 10.00, 2, 'SM Supermall', '2027-10-12', 'Cabinet A2', '2025-07-16 13:18:08', '2025-07-17 00:24:42');

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
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `appointment_id`, `client_id`, `total_amount`, `paid`, `payment_date`, `payment_method`, `payment_amount`, `change_amount`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 22, 660.00, 1, '2025-07-17 08:25:06', 'cash', 1000.00, 340.00, 'Auto-generated invoice for appointment A20250001 - Service: Wellness Exam', '2025-07-16 13:05:51', '2025-07-17 00:25:06');

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
(2, 1, NULL, 'Wellness Exam', 1, 500.00, 500.00),
(3, 1, NULL, 'Whiskas (pack)', 1, 150.00, 150.00),
(4, 1, NULL, 'Amoxcillin (tablets)', 1, 10.00, 10.00);

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
(1, 1, 1, '2025-07-16', 'Wellness Exam', 'Bacterial Infections', 'Antibiotics', 'Amoxicillin', 'Always pakainin sa oras ang pusa', 1, '2025-07-16 13:07:26', '2025-07-16 13:07:26');

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
(1, 22, 'Molly', 'Cat', 'Other', 'male', '2022-11-16', 2.00, NULL, '2025-07-16 13:02:14', '2025-07-16 13:02:14');

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
(1, 'clinic_name', 'Pawssible Solutions Veterinary', 'Name of the veterinary clinic', '2025-07-13 06:13:52'),
(2, 'clinic_address', 'Briana Catapang Tower, MCLL Highway, Guiwan Zamboanga City', 'Physical address of the clinic', '2025-07-13 06:13:52'),
(3, 'clinic_phone', '09477312312', 'Main phone number for the clinic', '2025-07-13 06:13:52'),
(4, 'clinic_email', 'psvc.inc@gmail.com', 'Main email address for the clinic', '2025-07-13 06:13:52'),
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
(1, 'admin', 'admin', 'Admin', 'User', 'admin@petcare.com', '123-456-7890', 'admin', '2025-07-16 21:04:34', '2025-03-25 15:25:55', '2025-07-16 13:04:34'),
(19, 'msantos', '$2y$10$JuQ0bkwVjGu77uAEAJA1OOX9p9gNO.JLwufcyNo6YKREdRwu8/DNG', 'Maria', 'Santos', 'santosmaria@yahoo.com', '09918195432', 'vet', '2025-07-16 21:03:56', '2025-07-16 12:55:43', '2025-07-16 13:03:56'),
(20, 'abrillantes', '$2y$10$ZBPYi/Z9yQxRJGxmrNTLgO.cNn1oVSaac.3PldczBTslcvFgLTUV.', 'Andrea', 'Brillantes', 'brillantesandrea20@gmail.com', '09918195433', 'vet', NULL, '2025-07-16 12:58:36', '2025-07-16 12:58:36'),
(21, 'jrizal', '$2y$10$7xd65pHQL69gSZ4Z3iH4EuuDENe1CcFBlGbJ5pF5Mra5mdoIAmoK2', 'Jose', 'Rizal', 'joserizal1889@outlook.com', '09918195434', 'vet', NULL, '2025-07-16 13:00:25', '2025-07-16 13:00:25'),
(22, 'ken', '$2y$10$liMz1pKvirffXdxOCm1Cw.AwORW/BtPCIu8SLbHYiSRN4ElBnJRIW', 'ken', 'ken', 'kenken@gmail.com', '09918195435', 'client', '2025-07-16 21:01:23', '2025-07-16 13:01:15', '2025-07-16 13:01:23');

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
(5, 17, 'Medicine', '9248WS', 13, 'Hello World'),
(6, 19, 'Medicine', 'VL1990', 2, 'Pet lover veterinarian'),
(7, 20, 'Surgery', 'VL2000', 5, 'Hello Hi!'),
(8, 21, 'Dental', 'VL1889', 10, 'Professional Veterinarian at your service');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `vets`
--
ALTER TABLE `vets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
