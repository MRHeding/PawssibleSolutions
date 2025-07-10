<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h1 class="text-5xl font-bold mb-6">About Pawssible Solutions</h1>
            <p class="text-xl">Passionate about animal health and wellness</p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-16">
    <!-- Our Story Section -->
    <div class="max-w-4xl mx-auto mb-20 text-center">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Our Story</h2>
            <div class="w-20 h-1 bg-violet-500 mx-auto mt-4 mb-8"></div>
        </div>
        
        <div class="prose prose-lg max-w-none mx-auto">
            <p>To assist clients' pets in living long, happy, and healthy lives. An excellent connection with the veterinarian is essential for your pet's well-being. Everyone at Pawssible Solution Veterinary Clinic is dedicated to providing professional, compassionate, and personalized service. Pawssible Solution Veterinary Clinic takes pleasure in adhering to the best veterinary medicine standards. We have a full-service clinic with cutting-edge veterinary medical technologies.</p>
            
            <p class="my-6">We care for our patients as if they were our own pets, and we strive to provide customers with the treatment they envision and deserve.</p>
            
            <p>We adopt an individualized approach to each of our patients' long-term care and are committed to giving our clients with enough knowledge to make proper decisions on the health care of their animal companions.</p>
        </div>
    </div>
    
    
    <!-- Testimonials -->
    <div class="mb-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">What Pet Parents Say</h2>
            <div class="w-20 h-1 bg-violet-500 mx-auto mt-4 mb-8"></div>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
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
                <p class="text-gray-600 mb-6 italic">"Dr. Johnson and her team are absolutely amazing! They treated our dog Max with such care and compassion during his recent surgery. The follow-up care was also exceptional."</p>
                <div class="flex items-center">
                    <img src="assets/images/client-1.jpg" alt="Jennifer S." class="w-10 h-10 rounded-full mr-4">
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
                <p class="text-gray-600 mb-6 italic">"As a first-time pet owner, I had many questions about caring for my new kitten. The staff at Pawssible Solutions have been incredibly patient and helpful. I couldn't ask for better care!"</p>
                <div class="flex items-center">
                    <img src="assets/images/client-2.jpg" alt="Marcus T." class="w-10 h-10 rounded-full mr-4">
                    <div>
                        <h4 class="font-semibold">Marcus T.</h4>
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
                <p class="text-gray-600 mb-6 italic">"I've been bringing my pets to Pawssible Solutions for over 10 years. Their dedication to animal health is unmatched, and the online appointment system makes scheduling so convenient!"</p>
                <div class="flex items-center">
                    <img src="assets/images/client-3.jpg" alt="Laura M." class="w-10 h-10 rounded-full mr-4">
                    <div>
                        <h4 class="font-semibold">Laura M.</h4>
                        <p class="text-sm text-gray-500">Multiple pets owner</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="bg-blue-50 rounded-xl p-8 text-center">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Become a part of the Pawssible Solutions family</h3>
        <p class="text-gray-600 mb-8 max-w-2xl mx-auto">We'd be honored to care for your furry, feathered, or scaled family members. Schedule an appointment today or contact us to learn more about our services.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="schedule_appointment.php" class="bg-violet-600 hover:bg-yellow-700 text-white font-bold py-3 px-6 rounded-lg transition">
                Schedule an Appointment
            </a>
            <a href="contact.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition">
                Contact Us
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
