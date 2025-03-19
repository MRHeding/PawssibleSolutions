<?php
session_start();
include_once 'includes/header.php';
?>

<div class="bg-gradient-to-r from-blue-500 to-teal-400 py-10">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-white">Server Error</h1>
    </div>
</div>

<div class="container mx-auto px-4 py-16">
    <div class="max-w-lg mx-auto text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-7xl text-red-500"></i>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-4">Something Went Wrong</h2>
        <p class="text-lg text-gray-600 mb-8">We're experiencing some technical difficulties. Our team has been notified and is working on the issue. Please try again later.</p>
        
        <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
            <a href="index.php" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition text-center">
                Go to Homepage
            </a>
            <a href="contact.php" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-lg transition text-center">
                Contact Support
            </a>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
