</main>
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p>&copy; <?php echo date("Y"); ?> Pawssible Solutions Veterinary Clinic. All rights reserved.</p>
                <p class="text-sm mt-2">Admin Portal v1.0</p>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Toggle user dropdown menu
        document.getElementById('user-menu-button').addEventListener('click', function() {
            document.getElementById('user-menu').classList.toggle('hidden');
        });
        
        // Handle clicks outside to close the user dropdown
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const userMenuButton = document.getElementById('user-menu-button');
            
            if (!userMenu.classList.contains('hidden') && 
                !userMenuButton.contains(event.target) && 
                !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
