</main>
    <footer class="bg-gray-800 text-white py-10 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <img src="assets/images/logo.png" alt="PetCare Clinic" class="h-10 mb-3">
                    <p class="text-gray-300 mb-4">Providing quality veterinary care for your beloved pets</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
                        <li><a href="services.php" class="text-gray-300 hover:text-white transition">Services</a></li>
                        <li><a href="about.php" class="text-gray-300 hover:text-white transition">About Us</a></li>
                        <li><a href="contact.php" class="text-gray-300 hover:text-white transition">Contact Us</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Services</h4>
                    <ul class="space-y-2">
                        <li><a href="services.php#wellness" class="text-gray-300 hover:text-white transition">Consultations</a></li>
                        <li><a href="services.php#vaccinations" class="text-gray-300 hover:text-white transition">Vaccinations</a></li>
                        <li><a href="services.php#dental" class="text-gray-300 hover:text-white transition">Deworming</a></li>
                        <li><a href="services.php#surgery" class="text-gray-300 hover:text-white transition">Surgery</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact Info</h4>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2 text-violet-400"></i>
                            <span>Briana Catapang Tower<br>MCLL Highway,Guiwan Zamboanga City</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-2 text-violet-400"></i>
                            <span>09477312312</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-violet-400"></i>
                            <span>psvc.inc@gmail.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p>&copy; <?php echo date('Y'); ?> Pawssible Solutions Veterinary Clinic. All rights reserved.</p>
                <p class="text-sm text-gray-400 mt-2">
                    <a href="privacy.php" class="hover:text-white">Privacy Policy</a> | 
                    <a href="terms.php" class="hover:text-white">Terms of Service</a>
                </p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Close notification messages when the X button is clicked
        document.addEventListener('DOMContentLoaded', function() {
            const notificationCloseButtons = document.querySelectorAll('.notification-close');
            
            notificationCloseButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const notification = this.closest('.notification');
                    if (notification) {
                        notification.classList.add('opacity-0');
                        setTimeout(() => {
                            notification.style.display = 'none';
                        }, 300);
                    }
                });
            });
            
            // User menu toggle
            const userMenuButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            
            if (userMenuButton && userMenu) {
                userMenuButton.addEventListener('click', function() {
                    userMenu.classList.toggle('hidden');
                });
                
                // Click outside to close
                document.addEventListener('click', function(event) {
                    if (!userMenuButton.contains(event.target) && !userMenu.contains(event.target)) {
                        userMenu.classList.add('hidden');
                    }
                });
            }
            
            // Mobile menu toggle
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
        });
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
