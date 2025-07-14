# PawssibleSolutions - AI Coding Assistant Instructions

## Project Overview
This is a **3-tier veterinary clinic management system** built with PHP/MySQL, featuring role-based access for Clients, Veterinarians, and Administrators. The system includes AI-powered features and runs on XAMPP for local development.

## Architecture Patterns

### Role-Based Access Control
Every page follows this authentication pattern:
```php
session_start();
include_once '../config/database.php'; // or 'config/database.php' for root level

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Role-specific access control
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
```

**Key Roles:**
- `client` - Pet owners (default registration role)
- `vet` - Veterinarians (created by admin)
- `admin` - Full system access

### Database Connection Pattern
Use the singleton Database class for all connections:
```php
$database = new Database();
$db = $database->getConnection();
```
Always use **PDO prepared statements** - no direct SQL concatenation allowed.

### Directory Structure Logic
- `/` - Client portal pages
- `/admin/` - Administrator interface
- `/vet/` - Veterinarian portal  
- `/includes/` - Reusable headers/footers/utilities
- `/config/` - Database and configuration
- `/ai/` - AI chat integration features

## Critical Development Patterns

### Header Includes
Each user tier has specific headers:
```php
// Client pages
include_once 'includes/header.php';

// Admin pages  
include_once '../includes/admin_header.php';

// Vet pages
include_once '../includes/vet_header.php';
```

### Appointment Number Generation
Use the `AppointmentNumberGenerator` class for unique appointment IDs:
```php
include_once 'includes/appointment_number_generator.php';
$generator = new AppointmentNumberGenerator($db);
$appointment_number = $generator->generateAppointmentNumber();
```
Format: `A{YEAR}{4-digit sequence}` (e.g., A20250001)

### Data Access Patterns
**Client Data Filtering:** Always filter by ownership
```php
// For client viewing their own data
$query = "SELECT ... WHERE p.owner_id = :user_id";
```

**Admin/Vet Access:** Can view all data but still use prepared statements
```php
// Admin can see everything
$query = "SELECT ... FROM table WHERE condition = :param";
```

## Security Requirements

### Password Handling
```php
// Registration/password updates
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Login verification  
if (password_verify($password, $user['password'])) {
    // Success
}
```

### Input Validation
Always sanitize and validate:
```php
$input = trim($_POST['field']);
if (empty($input)) {
    $error = "Field is required";
}
```

### Cache Busting
For appointment/status pages, add cache headers:
```php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
```

## UI/Frontend Conventions

### Styling Framework
- **Tailwind CSS** via CDN for all styling
- **Font Awesome 6.4.0** for icons
- Responsive design with mobile-first approach

### Form Patterns
Standard form structure with error/success handling:
```php
<?php if (!empty($error)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo $error; ?>
    </div>
<?php endif; ?>
```

### Page Header Pattern
All pages use gradient headers:
```html
<div class="bg-gradient-to-r from-purple-600 to-indigo-700 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Page Title</h1>
        <p class="text-white text-opacity-90 mt-2">Description</p>
    </div>
</div>
```

## AI Integration

### Chat System
The AI features use Groq API with:
- Session-based chat history (`$_SESSION['chat_history']`)
- Floating chat widget (`ai/ai.php`)
- API endpoint (`ai/api.php`) with veterinary-specific prompts
- Model: `llama-3.3-70b-versatile`

### Key AI Files
- `ai/ai.php` - Main chat interface
- `ai/api.php` - Backend API with veterinary context
- `ai/ai.js` - Frontend chat functionality

## Database Conventions

### Connection
Database: `pet_veterinary_system` (MySQL)
Credentials in `config/database.php`

### Key Tables
- `users` - All user types (role column differentiates)
- `pets` - Pet records (owner_id links to users)
- `appointments` - Appointments with unique numbers
- `medical_records` - Treatment history
- `vets` - Veterinarian details (user_id links to users)
- `inventory` - Medical supplies tracking

### Timestamp Handling
Most tables use:
```sql
`created_at` timestamp NOT NULL DEFAULT current_timestamp(),
`updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
```

## Development Workflow

### Local Environment
- **XAMPP** with Apache/MySQL/PHP 7.4+
- Access URLs:
  - Clients: `http://localhost/PawssibleSolutions`
  - Vets: `http://localhost/PawssibleSolutions/vet`
  - Admins: `http://localhost/PawssibleSolutions/admin`

### Error Handling
Use session messages for feedback:
```php
$_SESSION['success'] = "Operation completed successfully";
$_SESSION['error'] = "Something went wrong";
// Then redirect to avoid resubmission
header("Location: page.php");
```

### File Organization
- Keep role-specific files in respective directories
- Use relative paths consistently within each tier
- Include common utilities in `/includes/`

## Performance Notes
- Database queries optimized with indexes
- 40% performance improvement noted in v2.1
- Use pagination for large datasets
- Cache-busting headers where real-time data is critical

When working on this codebase, always maintain the role-based architecture, use prepared statements, and follow the established header/authentication patterns.
