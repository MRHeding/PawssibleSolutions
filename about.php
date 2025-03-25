<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-violet-600 to-violet-700 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h1 class="text-5xl font-bold mb-6">About PetCare Clinic</h1>
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
    
    <!-- Our Team Section -->
    <div class="mb-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Meet Our Veterinary Team</h2>
            <div class="w-20 h-1 bg-teal-500 mx-auto mt-4 mb-8"></div>
            <p class="text-gray-600 max-w-2xl mx-auto">Our team of experienced veterinarians and support staff are dedicated to providing the highest standard of care for your beloved pets.</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Team Member 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/vet-1.jpg" alt="Dr. Sarah Johnson" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-1">Dr. Sarah Johnson</h3>
                    <p class="text-teal-600 mb-3">Founder & Chief Veterinarian</p>
                    <p class="text-gray-600 mb-4">Dr. Johnson has over 20 years of experience in veterinary medicine with a special interest in preventive care and soft tissue surgery.</p>
                    <p class="text-gray-500 text-sm">DVM - Cornell University</p>
                </div>
            </div>
            
            <!-- Team Member 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/vet-2.jpg" alt="Dr. Michael Chen" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-1">Dr. Michael Chen</h3>
                    <p class="text-teal-600 mb-3">Senior Veterinarian</p>
                    <p class="text-gray-600 mb-4">Dr. Chen specializes in orthopedic surgery and has a particular interest in canine sports medicine and rehabilitation.</p>
                    <p class="text-gray-500 text-sm">DVM - University of California, Davis</p>
                </div>
            </div>
            
            <!-- Team Member 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/vet-3.jpg" alt="Dr. Emily Patel" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-1">Dr. Emily Patel</h3>
                    <p class="text-teal-600 mb-3">Veterinarian</p>
                    <p class="text-gray-600 mb-4">Dr. Patel focuses on feline medicine and has advanced training in dental care and minimally invasive procedures.</p>
                    <p class="text-gray-500 text-sm">DVM - University of Pennsylvania</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-8">
            <a href="team.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-6 rounded-lg transition">
                View All Team Members
            </a>
        </div>
    </div>
    
    <!-- Our Facility -->
    <div class="mb-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Our Facility</h2>
            <div class="w-20 h-1 bg-teal-500 mx-auto mt-4 mb-8"></div>
            <p class="text-gray-600 max-w-2xl mx-auto">Our modern veterinary facility is designed with your pet's comfort in mind, featuring state-of-the-art equipment and a stress-free environment.</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/facility-1.jpg" alt="Examination Rooms" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">Comfortable Examination Rooms</h3>
                    <p class="text-gray-600">Our exam rooms are designed to be comfortable and calming for your pet, with non-slip tables, soft lighting, and temperature control.</p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/facility-2.jpg" alt="Surgical Suite" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">Advanced Surgical Suite</h3>
                    <p class="text-gray-600">Our surgical suite is equipped with the latest monitoring equipment and surgical tools for both routine and complex procedures.</p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/facility-3.jpg" alt="Diagnostic Lab" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">On-Site Diagnostic Lab</h3>
                    <p class="text-gray-600">Our in-house laboratory allows us to perform many tests and get results quickly, often while you wait.</p>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <img src="assets/images/facility-4.jpg" alt="Recovery Area" class="w-full h-64 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2">Comfortable Recovery Areas</h3>
                    <p class="text-gray-600">Pets recovering from procedures stay in our specially designed recovery areas with comfortable bedding and constant monitoring.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testimonials -->
    <div class="mb-20">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">What Pet Parents Say</h2>
            <div class="w-20 h-1 bg-teal-500 mx-auto mt-4 mb-8"></div>
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
                <p class="text-gray-600 mb-6 italic">"As a first-time pet owner, I had many questions about caring for my new kitten. The staff at PetCare Clinic have been incredibly patient and helpful. I couldn't ask for better care!"</p>
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
                <p class="text-gray-600 mb-6 italic">"I've been bringing my pets to PetCare Clinic for over 10 years. Their dedication to animal health is unmatched, and the online appointment system makes scheduling so convenient!"</p>
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
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Become a part of the PetCare family</h3>
        <p class="text-gray-600 mb-8 max-w-2xl mx-auto">We'd be honored to care for your furry, feathered, or scaled family members. Schedule an appointment today or contact us to learn more about our services.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="schedule_appointment.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition">
                Schedule an Appointment
            </a>
            <a href="contact.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition">
                Contact Us
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
