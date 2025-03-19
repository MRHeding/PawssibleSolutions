<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare Veterinary Clinic</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <header class="bg-white shadow-md">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <img src="assets/images/logo.png" alt="PetCare Clinic Logo" class="h-10 mr-3">
                        <span class="text-xl font-bold text-teal-600">PetCare Clinic</span>
                    </a>
                    
                    <div class="hidden md:flex items-center space-x-6 ml-10">
                        <a href="index.php" class="text-gray-700 hover:text-teal-600 transition">Home</a>
                        <a href="services.php" class="text-gray-700 hover:text-teal-600 transition">Services</a>
                        <a href="about.php" class="text-gray-700 hover:text-teal-600 transition">About Us</a>
                        <a href="contact.php" class="text-gray-700 hover:text-teal-600 transition">Contact</a>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative">
                            <button type="button" class="flex items-center space-x-2 text-gray-700 hover:text-teal-600 focus:outline-none" id="user-menu-button">
                                <span class="font-medium"><?php echo $_SESSION['first_name']; ?></span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <!-- Dropdown menu, hidden by default -->
                            <div class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10" id="user-menu">
                                <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                <a href="my_pets.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Pets</a>
                                <a href="my_appointments.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Appointments</a>
                                <a href="medical_records.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Medical Records</a>
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-teal-600 transition mr-4">Sign In</a>
                        <a href="register.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">Sign Up</a>
                    <?php endif; ?>
                    
                    <!-- Mobile menu button -->
                    <button type="button" class="md:hidden ml-4 text-gray-700 hover:text-teal-600 focus:outline-none" id="mobile-menu-button">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Mobile menu, hidden by default -->
            <div class="hidden md:hidden mt-3" id="mobile-menu">
                <div class="space-y-1 px-2 pt-2 pb-3">
                    <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Home</a>
                    <a href="services.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Services</a>
                    <a href="about.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">About Us</a>
                    <a href="contact.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Contact</a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Dashboard</a>
                        <a href="my_pets.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">My Pets</a>
                        <a href="my_appointments.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">My Appointments</a>
                        <a href="medical_records.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Medical Records</a>
                        <a href="profile.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Profile</a>
                        <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Sign out</a>
                    <?php else: ?>
                        <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Sign In</a>
                        <a href="register.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-teal-50 hover:text-teal-700">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    <main class="flex-grow">
