<?php
session_start();
include_once 'config/database.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // In a real application, you would process the form data here
        // This could include sending an email or storing the message in a database
        
        // For demonstration purposes, we'll just show a success message
        $success = "Thank you for your message! Our team will contact you shortly.";
        
        // Reset form fields after successful submission
        $name = $email = $phone = $subject = $message = "";
    }
}

include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-20">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto text-center text-white">
            <h1 class="text-5xl font-bold mb-6">Contact Us</h1>
            <p class="text-xl">We're here to help with all your pet care needs</p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-16">
    <div class="grid md:grid-cols-2 gap-10 max-w-5xl mx-auto">
        <!-- Contact Information -->
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-6">Get in Touch</h2>
            <p class="text-gray-600 mb-8">Have questions about our services or want to schedule an appointment? Reach out to us through any of the following methods, or fill out the contact form.</p>
            
            <div class="space-y-6">
                <div class="flex items-start">
                    <div class="bg-teal-100 p-3 rounded-full mr-4">
                        <i class="fas fa-map-marker-alt text-teal-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Location</h3>
                        <p class="text-gray-600">123 Pet Street<br>Veterinary City, VC 12345</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <i class="fas fa-phone-alt text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Phone</h3>
                        <p class="text-gray-600">+1 (123) 456-7890</p>
                        <p class="text-gray-500 text-sm mt-1">For emergencies after hours: +1 (123) 456-7899</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-purple-100 p-3 rounded-full mr-4">
                        <i class="fas fa-envelope text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Email</h3>
                        <p class="text-gray-600">info@petcareclinic.com</p>
                        <p class="text-gray-500 text-sm mt-1">For appointments: appointments@petcareclinic.com</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Hours of Operation</h3>
                        <table class="text-gray-600">
                            <tr>
                                <td class="pr-6">Monday - Friday:</td>
                                <td>8:00 AM - 6:00 PM</td>
                            </tr>
                            <tr>
                                <td class="pr-6">Saturday:</td>
                                <td>9:00 AM - 4:00 PM</td>
                            </tr>
                            <tr>
                                <td class="pr-6">Sunday:</td>
                                <td>Closed</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="mt-8">
                <h3 class="font-semibold text-gray-800 mb-3">Follow Us</h3>
                <div class="flex space-x-4">
                    <a href="#" class="bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700 transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="bg-blue-400 text-white p-3 rounded-full hover:bg-blue-500 transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="bg-gradient-to-r from-purple-600 to-pink-500 text-white p-3 rounded-full hover:from-purple-700 hover:to-pink-600 transition">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="bg-red-600 text-white p-3 rounded-full hover:bg-red-700 transition">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Send Us a Message</h2>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Your Name *</label>
                    <input type="text" name="name" id="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email Address *</label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>
                
                <div class="mb-4">
                    <label for="phone" class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                    <p class="text-xs text-gray-500 mt-1">Optional, but helpful if we need to call you</p>
                </div>
                
                <div class="mb-4">
                    <label for="subject" class="block text-gray-700 text-sm font-bold mb-2">Subject</label>
                    <select name="subject" id="subject" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="General Inquiry" <?php echo (isset($subject) && $subject == 'General Inquiry') ? 'selected' : ''; ?>>General Inquiry</option>
                        <option value="Appointment Request" <?php echo (isset($subject) && $subject == 'Appointment Request') ? 'selected' : ''; ?>>Appointment Request</option>
                        <option value="Service Question" <?php echo (isset($subject) && $subject == 'Service Question') ? 'selected' : ''; ?>>Service Question</option>
                        <option value="Feedback" <?php echo (isset($subject) && $subject == 'Feedback') ? 'selected' : ''; ?>>Feedback</option>
                        <option value="Other" <?php echo (isset($subject) && $subject == 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label for="message" class="block text-gray-700 text-sm font-bold mb-2">Your Message *</label>
                    <textarea name="message" id="message" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    Send Message
                </button>
            </form>
        </div>
    </div>
    
    <!-- Map Section -->
    <div class="mt-16">
        <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Find Us</h2>
        <div class="w-full h-96 bg-gray-200 rounded-lg shadow-md overflow-hidden">
            <!-- This is where you would embed a Google Maps iframe -->
            <div class="w-full h-full flex items-center justify-center bg-gray-300">
                <p class="text-gray-600">Map would be embedded here in a real application</p>
            </div>
            
            <!-- Example of a Google Maps embed: -->
            <!-- <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3..." width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe> -->
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
