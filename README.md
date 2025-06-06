# 🐾 PawssibleSolutions - Veterinary Clinic Management System

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)

PawssibleSolutions is a comprehensive web-based management system designed for veterinary clinics to streamline patient management, appointment scheduling, inventory tracking, and medical record-keeping. Built with modern web technologies, it provides an intuitive interface for clients, veterinarians, and administrators.

## 🚀 Features

### 👥 Client Portal
- **🔐 User Authentication**: Secure login and registration system with role-based access
- **🐕 Pet Management**: Add, edit, and manage multiple pet profiles with detailed information
- **📅 Appointment Scheduling**: Book, reschedule, and cancel appointments with real-time availability
- **📋 Medical Records**: View comprehensive pet medical history and treatment records
- **👤 Profile Management**: Update personal information, contact details, and preferences
- **📱 Responsive Design**: Access from any device with mobile-friendly interface

### 🩺 Veterinarian Portal
- **📅 Appointment Management**: View daily schedules and manage appointed consultations
- **🏥 Patient Records**: Access and update detailed patient medical information
- **💊 Treatment Tracking**: Document treatments, diagnoses, prescriptions, and follow-ups
- **⚖️ Pet Weight Tracking**: Monitor and update patient weight over time
- **📊 Patient History**: View complete treatment timeline and medical notes
- **🔔 Notifications**: Receive alerts for upcoming appointments and patient updates

### ⚙️ Administrative Features
- **👨‍⚕️ Staff Management**: Add, edit, and manage veterinarians and administrative staff
- **👥 Client Management**: Comprehensive client database with search and filtering
- **📦 Inventory Control**: Track medical supplies, equipment, and medication stock levels
- **📈 Reporting**: Generate detailed operational, financial, and statistical reports
- **📅 Appointment Oversight**: Schedule and monitor all clinic appointments across staff
- **⚙️ System Settings**: Configure clinic information, operating hours, and system preferences
- **📊 Dashboard Analytics**: Real-time insights into clinic performance and metrics

### 🤖 AI Integration
- **Smart Scheduling**: AI-powered appointment optimization and conflict resolution
- **Predictive Analytics**: Insights for inventory management and patient care
- **Automated Reminders**: Intelligent notification system for appointments and follow-ups
- **Data Analysis**: Advanced reporting with AI-driven recommendations

## 🛠️ Technology Stack

### Frontend
- **HTML5**: Semantic markup with modern web standards
- **CSS3**: Responsive design with custom styling and animations
- **JavaScript (ES6+)**: Interactive user interface and AJAX functionality
- **Bootstrap** (optional): For responsive grid system and components

### Backend
- **PHP 7.4+**: Server-side logic and API endpoints
- **PDO**: Secure database interactions with prepared statements
- **Session Management**: Secure user authentication and authorization

### Database
- **MySQL 5.7+**: Relational database with optimized queries
- **phpMyAdmin**: Database administration interface via XAMPP

### Development Environment
- **XAMPP**: Local development stack (Apache, MySQL, PHP)
- **Apache**: Web server with mod_rewrite support
- **Git**: Version control system

## ⚡ Quick Start

### Prerequisites
- **XAMPP** (Apache, MySQL, PHP 7.4+)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **Git** (optional, for cloning repository)

### 🔧 Installation Steps

1. **Download and Setup XAMPP**
   ```bash
   # Download XAMPP from https://www.apachefriends.org/
   # Install and start Apache and MySQL services
   ```

2. **Get the Project**
   ```bash
   # Option 1: Clone repository
   git clone https://github.com/yourusername/PawssibleSolutions.git
   cd c:\xampp\htdocs\PawssibleSolutions
   
   # Option 2: Download and extract to XAMPP htdocs folder
   ```

3. **Database Setup**
   - Open **phpMyAdmin**: http://localhost/phpmyadmin
   - Create new database: `pet_veterinary_system`
   - Import schema: Go to **Import** tab → Choose `database/setup.sql` → Click **Go**

4. **Configure Database Connection**
   - Verify settings in `config/database.php`:
     ```php
     private $host = "localhost";
     private $db_name = "pet_veterinary_system";
     private $username = "root";
     private $password = "";
     ```

5. **Launch Application**
   - Start Apache and MySQL in XAMPP Control Panel
   - Open browser and navigate to: http://localhost/PawssibleSolutions

### 🎯 Default Access
- **Admin Portal**: http://localhost/PawssibleSolutions/admin
- **Vet Portal**: http://localhost/PawssibleSolutions/vet
- **Client Portal**: http://localhost/PawssibleSolutions (main site)

## 👥 User Access Levels

### 🧑‍💼 Client Access
- **Registration**: Self-service registration through main portal
- **Login URL**: http://localhost/PawssibleSolutions
- **Features**: Pet management, appointment booking, medical record viewing
- **Default Role**: `client`

### 👨‍⚕️ Veterinarian Access
- **Access URL**: http://localhost/PawssibleSolutions/vet
- **Features**: Patient management, appointment handling, medical record creation
- **Role**: `vet` (assigned by administrator)
- **Credentials**: Created by admin users

### ⚙️ Administrator Access
- **Access URL**: http://localhost/PawssibleSolutions/admin
- **Features**: Full system control, user management, reporting, system settings
- **Role**: `admin` (highest privilege level)
- **Initial Setup**: Create first admin account through setup process

## 🔒 Security Features

- **Password Hashing**: Secure password storage using PHP password_hash()
- **SQL Injection Protection**: Prepared statements with PDO
- **Session Management**: Secure session handling with timeout
- **Role-Based Access Control**: Granular permissions by user type
- **Input Validation**: Server-side validation for all user inputs
- **CSRF Protection**: Cross-site request forgery prevention

## 🏗️ Project Structure

```
PawssibleSolutions/
├── 📁 admin/                    # Administrative interface
│   ├── dashboard.php           # Admin dashboard with analytics
│   ├── clients.php             # Client management
│   ├── vets.php               # Veterinarian management
│   ├── appointments.php        # Appointment oversight
│   ├── inventory.php          # Inventory management
│   ├── reports.php            # System reports
│   └── settings.php           # System configuration
├── 📁 vet/                     # Veterinarian portal
│   ├── dashboard.php          # Vet dashboard
│   ├── appointments.php       # Daily schedule
│   ├── view_appointment.php   # Appointment details
│   └── add_medical_record.php # Medical record entry
├── 📁 assets/                  # Static resources
│   ├── css/style.css          # Main stylesheet
│   ├── js/main.js             # JavaScript functionality
│   └── images/                # Image assets
├── 📁 config/                  # Configuration files
│   ├── database.php           # Database connection
│   └── setup.php              # Initial setup configuration
├── 📁 database/                # Database scripts
│   └── setup.sql              # Database schema and sample data
├── 📁 includes/                # Reusable components
│   ├── header.php             # Common header
│   ├── footer.php             # Common footer
│   ├── admin_header.php       # Admin-specific header
│   └── vet_header.php         # Vet-specific header
├── 📁 ai/                      # AI integration features
│   ├── ai.php                 # AI main interface
│   ├── api.php                # AI API endpoints
│   └── ai.js                  # AI frontend functionality
├── 📄 index.php                # Main landing page
├── 📄 login.php                # User authentication
├── 📄 register.php             # User registration
├── 📄 dashboard.php            # Client dashboard
├── 📄 my_pets.php              # Pet management
├── 📄 appointments.php         # Appointment booking
└── 📄 medical_records.php      # Medical record viewing
```

## 🚀 Usage Examples

### For Clients
1. **Register**: Create account on main page
2. **Add Pets**: Navigate to "My Pets" → "Add New Pet"
3. **Book Appointment**: Go to "Appointments" → "Schedule New"
4. **View Records**: Check "Medical Records" for pet history

### For Veterinarians
1. **Login**: Access `/vet` portal with provided credentials
2. **View Schedule**: Check daily appointments on dashboard
3. **Start Consultation**: Click "Start" on appointment
4. **Add Records**: Document treatments and diagnoses

### For Administrators
1. **System Overview**: Monitor clinic operations on admin dashboard
2. **Manage Staff**: Add/edit veterinarians and admin users
3. **Inventory**: Track supplies and set reorder alerts
4. **Reports**: Generate financial and operational reports

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to match your MySQL setup:
```php
private $host = "localhost";        // Database host
private $db_name = "pet_veterinary_system";  // Database name
private $username = "root";         // MySQL username
private $password = "";             // MySQL password (blank for XAMPP default)
```

### System Settings
Access admin panel → Settings to configure:
- Clinic information and hours
- Appointment time slots
- Email notifications
- System maintenance mode

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Verify MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Ensure database `pet_veterinary_system` exists

**Page Not Found (404)**
- Confirm Apache is running
- Check file paths and directory structure
- Verify `.htaccess` configuration

**Login Issues**
- Clear browser cache and cookies
- Check user role and permissions
- Verify database user table integrity

**AI Features Not Working**
- Check `ai/api.php` configuration
- Verify AI service endpoints
- Review browser console for JavaScript errors

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** your changes (`git commit -m 'Add some AmazingFeature'`)
4. **Push** to the branch (`git push origin feature/AmazingFeature`)
5. **Open** a Pull Request

### Development Guidelines
- Follow PSR-4 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test all features before committing
- Ensure responsive design compatibility

## 📋 Roadmap

### Upcoming Features
- [ ] **Mobile App**: Native iOS and Android applications
- [ ] **Telemedicine**: Video consultation integration
- [ ] **Payment Gateway**: Online payment processing
- [ ] **SMS Notifications**: Automated text message alerts
- [ ] **Multi-language Support**: Internationalization
- [ ] **API Documentation**: RESTful API for third-party integration
- [ ] **Advanced Reporting**: Enhanced analytics and insights
- [ ] **Cloud Storage**: Integration with cloud file storage

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### MIT License Summary
- ✅ Commercial use
- ✅ Modification
- ✅ Distribution
- ✅ Private use
- ❌ Liability
- ❌ Warranty

## 📞 Support & Contact

### Getting Help
- **Documentation**: Check this README and inline code comments
- **Issues**: Report bugs via GitHub Issues
- **Discussions**: Join community discussions for questions

### Contact Information
- **Project Maintainer**: [Your Name]
- **Email**: [your.email@example.com]
- **Website**: [https://your-website.com]
- **LinkedIn**: [Your LinkedIn Profile]

### Professional Services
For custom development, training, or enterprise support:
- **Consultation**: Available for hire
- **Custom Features**: Tailored development services
- **Training**: System administration and user training
- **Support**: Priority technical support packages

---

**Made with ❤️ for veterinary professionals worldwide**

*Last updated: May 29, 2025*

