<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';
?>

<!-- Hero Section -->
<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h1 class="text-5xl font-bold mb-6">Expert Veterinary Care For Your Pet Family</h1>
            <p class="text-xl mb-8">Compassionate Care for your beloved pet with our exceptional services. Schedule appointments, access medical records, and manage your pet's health all in one place.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="schedule_appointment.php" class="bg-white text-violet-600 hover:bg-gray-100 font-bold py-3 px-8 rounded-lg transition">
                        Schedule Appointment
                    </a>
                    <a href="my_pets.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        View My Pets
                    </a>
                <?php else: ?>
                    <a href="login.php" class="bg-white text-violet-600 hover:bg-gray-100 font-bold py-3 px-8 rounded-lg transition">
                        Sign In
                    </a>
                    <a href="register.php" class="bg-violet-700 hover:bg-violet-800 text-white font-bold py-3 px-8 rounded-lg transition">
                        Register Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>  
</div>

<!-- Services Preview Section -->
<div class="container mx-auto px-4 py-16">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-800">Our Services</h2>
        <p class="text-gray-600 mt-2">Comprehensive healthcare for your beloved pets</p>
    </div>
    
    <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-48 bg-blue-500 flex items-center justify-center">
                <i class="fas fa-heartbeat text-white text-6xl"></i>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold mb-3">Preventive Care</h3>
                <p class="text-gray-600 mb-4">Regular check-ups and vaccinations to keep your pet healthy and prevent illness.</p>
                <a href="services.php#preventive" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                    Learn More
                    <svg class="w-4 h-4 ml-1" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-48 bg-green-500 flex items-center justify-center">
                <i class="fas fa-stethoscope text-white text-6xl"></i>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold mb-3">Diagnostic Services</h3>
                <p class="text-gray-600 mb-4">Advanced diagnostic tools to accurately identify your pet's health issues.</p>
                <a href="services.php#diagnostics" class="text-green-600 hover:text-green-800 inline-flex items-center">
                    Learn More
                    <svg class="w-4 h-4 ml-1" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-48 bg-red-500 flex items-center justify-center">
                <i class="fas fa-procedures text-white text-6xl"></i>
            </div>
            <div class="p-6">
                <h3 class="text-xl font-bold mb-3">Surgical Procedures</h3>
                <p class="text-gray-600 mb-4">Expert surgical care with state-of-the-art monitoring and pain management.</p>
                <a href="services.php#surgery" class="text-red-600 hover:text-red-800 inline-flex items-center">
                    Learn More
                    <svg class="w-4 h-4 ml-1" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="none">
                        <path d="M5 12h14M12 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-10">
        <a href="services.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-2 px-6 rounded-lg transition">
            View All Services
        </a>
    </div>
</div>

<!-- How It Works Section -->
<div class="bg-gray-100 py-16">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">How It Works</h2>
            <p class="text-gray-600 mt-2">Easy steps to manage your pet's healthcare</p>
        </div>
        
        <div class="flex flex-wrap justify-center">
            <div class="w-full md:w-1/3 px-4 mb-8">
                <div class="bg-white rounded-lg p-6 h-full shadow-md">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-blue-600 text-2xl font-bold">1</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-center">Create an Account</h3>
                    <p class="text-gray-600 text-center">Register for a free account to manage your pets' information and healthcare needs.</p>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 px-4 mb-8">
                <div class="bg-white rounded-lg p-6 h-full shadow-md">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-green-600 text-2xl font-bold">2</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-center">Add Your Pets</h3>
                    <p class="text-gray-600 text-center">Enter information about your pets to personalize their healthcare experience.</p>
                </div>
            </div>
            
            <div class="w-full md:w-1/3 px-4 mb-8">
                <div class="bg-white rounded-lg p-6 h-full shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <span class="text-purple-600 text-2xl font-bold">3</span>
                    </div>
                    <h3 class="text-xl font-bold mb-2 text-center">Schedule Appointments</h3>
                    <p class="text-gray-600 text-center">Easily book appointments with our veterinarians and manage your pet's visit schedule.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testimonials Section -->
<div class="container mx-auto px-4 py-16">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-bold text-gray-800">What Pet Parents Say</h2>
        <p class="text-gray-600 mt-2">Read about the experiences of our clients</p>
    </div>
    
    <div class="grid md:grid-cols-3 gap-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-4">
                <div class="text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <p class="text-gray-600 mb-6 italic">"The online appointment system makes scheduling so easy! Dr. Johnson is wonderful with my anxious rescue dog, and the staff always go above and beyond."</p>
            <div class="flex items-center">
                <img src="assets/images/client-1.png" alt="Jennifer S." class="w-10 h-10 rounded-full mr-4">
                <div>
                    <h4 class="font-semibold">Jennifer S.</h4>
                    <p class="text-sm text-gray-500">Dog owner</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-4">
                <div class="text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <p class="text-gray-600 mb-6 italic">"Being able to see my cats' medical records online is incredibly helpful. The vets explain everything thoroughly and always make my skittish cats feel comfortable."</p>
            <div class="flex items-center">
                <img src="assets/images/client-2.png" alt="Robert T." class="w-10 h-10 rounded-full mr-4">
                <div>
                    <h4 class="font-semibold">Robert T.</h4>
                    <p class="text-sm text-gray-500">Cat owner</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-4">
                <div class="text-yellow-400">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <p class="text-gray-600 mb-6 italic">"When my puppy needed emergency surgery, Dr. Smith was amazing. The follow-up care instructions in the app helped us keep track of medications and recovery milestones."</p>
            <div class="flex items-center">
                <img src="assets/images/client-3.png" alt="Maria G." class="w-10 h-10 rounded-full mr-4">
                <div>
                    <h4 class="font-semibold">Maria G.</h4>
                    <p class="text-sm text-gray-500">Puppy parent</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Call to Action Section -->
<div class="bg-blue-50 py-16">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Ready to provide the best care for your pet?</h2>
            <p class="text-xl text-gray-600 mb-8">Join many pet parents in Zamboanga City who trust Pawssible Solutions with their furry family members' health.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="schedule_appointment.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        Schedule Now
                    </a>
                <?php else: ?>
                    <a href="register.php" class="bg-violet-600 hover:bg-violet-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        Register Now
                    </a>
                    <a href="login.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-8 rounded-lg transition">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
