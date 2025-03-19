<?php
session_start();
include_once 'config/database.php';
include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h1 class="text-5xl font-bold mb-6">Our Veterinary Services</h1>
            <p class="text-xl">We provide comprehensive care for your beloved pets</p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-16">
    <!-- Main Services Section -->
    <div class="mb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Comprehensive Pet Care</h2>
            <p class="text-gray-600 mt-2">Our clinic offers a wide range of services to keep your pets healthy and happy</p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Wellness Exams -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-blue-500 flex items-center justify-center">
                    <i class="fas fa-heartbeat text-white text-6xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-3">Wellness Examinations</h3>
                    <p class="text-gray-600 mb-4">Regular check-ups to maintain your pet's health and detect potential issues early.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Comprehensive physical exams</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Age-appropriate screenings</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Personalized health plans</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Vaccinations -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-green-500 flex items-center justify-center">
                    <i class="fas fa-syringe text-white text-6xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-3">Vaccinations</h3>
                    <p class="text-gray-600 mb-4">Protect your pet from common and dangerous diseases with our vaccination programs.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Core and non-core vaccines</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Customized vaccination schedules</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Vaccine titer testing</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Dental Care -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-purple-500 flex items-center justify-center">
                    <i class="fas fa-tooth text-white text-6xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-3">Dental Care</h3>
                    <p class="text-gray-600 mb-4">Maintain your pet's oral health with our comprehensive dental services.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Professional teeth cleaning</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Digital dental X-rays</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Extractions and oral surgery</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Surgery -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-red-500 flex items-center justify-center">
                    <i class="fas fa-procedures text-white text-6xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-3">Surgical Services</h3>
                    <p class="text-gray-600 mb-4">Our skilled veterinary surgeons provide a wide range of surgical procedures.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Spay and neuter procedures</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Soft tissue surgeries</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Advanced monitoring during procedures</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Diagnostics -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-yellow-500 flex items-center justify-center">
                    <i class="fas fa-microscope text-white text-6xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-3">Diagnostic Services</h3>
                    <p class="text-gray-600 mb-4">Advanced diagnostics to help identify and treat your pet's health issues.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Digital X-ray imaging</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Ultrasound examinations</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Laboratory testing</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Emergency Care -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-orange-500 flex items-center justify-center">
                    <i class="fas fa-ambulance text-white text-6xl"></i>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-3">Emergency & Critical Care</h3>
                    <p class="text-gray-600 mb-4">We're here when your pet needs urgent medical attention.</p>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Emergency treatment</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>Critical care monitoring</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span>After-hours support</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Service Packages -->
    <div class="mb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Pet Care Packages</h2>
            <p class="text-gray-600 mt-2">Choose a package that suits your pet's needs</p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <!-- Basic Package -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-blue-500">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-center mb-2">Basic Care</h3>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold">$99</span>
                        <span class="text-gray-600">/year</span>
                    </div>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Annual wellness exam</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Core vaccinations</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Basic health screening</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>10% off additional services</span>
                        </li>
                    </ul>
                    <div class="text-center">
                        <a href="contact.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full inline-block transition">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Standard Package -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-teal-500 transform scale-105">
                <div class="bg-teal-500 text-white text-center py-2 text-sm font-bold">
                    MOST POPULAR
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-center mb-2">Standard Care</h3>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold">$199</span>
                        <span class="text-gray-600">/year</span>
                    </div>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Two wellness exams</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>All recommended vaccinations</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Comprehensive blood work</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Dental assessment</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>15% off additional services</span>
                        </li>
                    </ul>
                    <div class="text-center">
                        <a href="contact.php" class="bg-teal-500 hover:bg-teal-600 text-white font-bold py-2 px-4 rounded-full inline-block transition">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Premium Package -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden border-t-4 border-purple-500">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-center mb-2">Premium Care</h3>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold">$299</span>
                        <span class="text-gray-600">/year</span>
                    </div>
                    <ul class="space-y-3 mb-6">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Quarterly wellness exams</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>All vaccinations & boosters</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Advanced diagnostic panel</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Dental cleaning</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>Unlimited consultations</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span>25% off additional services</span>
                        </li>
                    </ul>
                    <div class="text-center">
                        <a href="contact.php" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded-full inline-block transition">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Specialty Services -->
    <div>
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800">Specialty Services</h2>
            <p class="text-gray-600 mt-2">Advanced care options for pets with specific needs</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-start">
                    <div class="bg-indigo-100 p-3 rounded-full mr-4">
                        <i class="fas fa-paw text-indigo-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Dermatology</h3>
                        <p class="text-gray-600 mb-3">Expert treatment for skin conditions, allergies, ear infections, and other dermatological issues that can cause discomfort for your pet.</p>
                        <a href="contact.php" class="text-indigo-600 hover:text-indigo-800 font-medium inline-flex items-center">
                            Schedule Consultation
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-start">
                    <div class="bg-pink-100 p-3 rounded-full mr-4">
                        <i class="fas fa-bone text-pink-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Orthopedics</h3>
                        <p class="text-gray-600 mb-3">Specialized care for joint issues, fractures, ligament injuries, and other musculoskeletal problems to keep your pet mobile and pain-free.</p>
                        <a href="contact.php" class="text-pink-600 hover:text-pink-800 font-medium inline-flex items-center">
                            Schedule Consultation
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-start">
                    <div class="bg-amber-100 p-3 rounded-full mr-4">
                        <i class="fas fa-brain text-amber-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Neurology</h3>
                        <p class="text-gray-600 mb-3">Advanced diagnosis and treatment of neurological conditions affecting your pet's brain, spinal cord, nerves, and muscles.</p>
                        <a href="contact.php" class="text-amber-600 hover:text-amber-800 font-medium inline-flex items-center">
                            Schedule Consultation
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-start">
                    <div class="bg-emerald-100 p-3 rounded-full mr-4">
                        <i class="fas fa-stethoscope text-emerald-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold mb-2">Cardiology</h3>
                        <p class="text-gray-600 mb-3">Specialized care for pets with heart conditions, including diagnostic services, treatment plans, and ongoing management strategies.</p>
                        <a href="contact.php" class="text-emerald-600 hover:text-emerald-800 font-medium inline-flex items-center">
                            Schedule Consultation
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="mt-16 bg-blue-50 rounded-xl p-8 text-center">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Ready to schedule your pet's appointment?</h3>
        <p class="text-gray-600 mb-8 max-w-2xl mx-auto">Our team of experienced veterinarians is ready to help your pet live a healthy and happy life. Book an appointment today and give your pet the care they deserve.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="schedule_appointment.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition">
                Schedule Appointment
            </a>
            <a href="contact.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition">
                Contact Us
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
