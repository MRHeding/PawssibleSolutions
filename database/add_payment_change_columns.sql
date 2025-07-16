-- Add payment_amount and change_amount columns to invoices table
ALTER TABLE `invoices` 
ADD COLUMN `payment_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_method`,
ADD COLUMN `change_amount` DECIMAL(10,2) DEFAULT NULL AFTER `payment_amount`;
