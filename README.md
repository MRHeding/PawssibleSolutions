# PawssibleSolutions - Veterinary Clinic Management System

PawssibleSolutions is a comprehensive web-based management system designed for veterinary clinics to streamline patient management, appointment scheduling, inventory tracking, and medical record-keeping.

## Features

### Client Features
- **User Authentication**: Secure login and registration system
- **Pet Management**: Add, edit, and manage pet profiles
- **Appointment Scheduling**: Book, reschedule, and cancel appointments
- **Medical Records**: View pet medical history and treatment records
- **Profile Management**: Update personal information and preferences

### Veterinarian Features
- **Appointment Management**: View and manage scheduled appointments
- **Patient Records**: Access and update patient medical information
- **Treatment Tracking**: Document treatments, diagnoses, and prescriptions
- **Pet Weight Tracking**: Update and monitor patient weight

### Administrative Features
- **Staff Management**: Add and manage veterinarians and admin staff
- **Client Management**: View and edit client information
- **Inventory Control**: Track medical supplies and equipment
- **Reporting**: Generate operational and financial reports
- **Appointment Oversight**: Schedule and monitor all clinic appointments

### AI Integration
- AI-assisted features for smarter clinic management (accessible via ai/ai.php)

## Technology Stack
- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL (via XAMPP)

## Setup Instructions

### Prerequisites
- XAMPP (Apache, MySQL, PHP)
- Web browser

### Installation Steps
1. Clone or download this repository to your XAMPP htdocs folder
2. Start Apache and MySQL services in XAMPP control panel
3. Set up the database:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `pawssible_solutions`
   - Import the database schema from `database/setup.sql`
4. Configure database connection:
   - Check `config/database.php` to ensure database credentials are correct
5. Access the application:
   - Open your browser and navigate to `http://localhost/PawssibleSolutions`

## User Access Levels
- **Clients**: Register and log in through the main portal
- **Veterinarians**: Access the vet portal through `/vet` directory
- **Administrators**: Access the admin portal through `/admin` directory

## Directory Structure
- `admin/`: Administrative interface files
- `vet/`: Veterinarian interface files
- `assets/`: Static resources (CSS, JS, images)
- `config/`: Configuration files
- `database/`: Database setup scripts
- `includes/`: Reusable components (headers, footers)
- `uploads/`: User-uploaded content
- `ai/`: AI feature integration

## License
[Your License Information]

## Support
For issues or questions, please contact [Your Contact Information]

