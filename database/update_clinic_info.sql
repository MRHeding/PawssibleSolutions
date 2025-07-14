-- Update clinic information for Pawssible Solutions Veterinary
-- Execute this SQL script to update the clinic details

UPDATE `settings` SET `setting_value` = 'Pawssible Solutions Veterinary' WHERE `setting_key` = 'clinic_name';

UPDATE `settings` SET `setting_value` = 'Briana Catapang Tower, MCLL Highway, Guiwan Zamboanga City' WHERE `setting_key` = 'clinic_address';

UPDATE `settings` SET `setting_value` = '09477312312' WHERE `setting_key` = 'clinic_phone';

UPDATE `settings` SET `setting_value` = 'psvc.inc@gmail.com' WHERE `setting_key` = 'clinic_email';

-- Optional: Update emergency phone to the same number if desired
-- UPDATE `settings` SET `setting_value` = '09477312312' WHERE `setting_key` = 'emergency_phone';

-- Verify the updates
SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email');
