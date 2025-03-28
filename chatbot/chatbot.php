<?php
session_start(); // Start a session for multi-turn conversations

// Include the backend logic for API calls
require 'api.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = sanitizeInput($_POST['message']);
    if (!empty($userMessage)) {
        // Store the user's message in the session
        $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userMessage, 'timestamp' => date('H:i')];

        // Get the AI's response
        $chatResponse = getChatResponse($userMessage, $_SESSION['chat_history']);
        $_SESSION['chat_history'][] = ['role' => 'bot', 'content' => $chatResponse, 'timestamp' => date('H:i')];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinary Chatbot</title>
    <!-- Include Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #chat-container {
            display: none;
        }
        #chat-container.open {
            display: flex;
        }
        .message {
            opacity: 0;
            transform: translateY(10px);
            animation: fadeIn 0.3s ease-in-out forwards;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .typing-indicator span {
            animation: typingDot 1.4s infinite ease-in-out;
            background-color: #6D28D9;
            border-radius: 50%;
            display: inline-block;
            height: 7px;
            margin-right: 2px;
            width: 7px;
        }
        .typing-indicator span:nth-child(1) { animation-delay: 0s; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingDot {
            0% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 0.6; }
        }
    </style>
</head>
<body class="bg-transparent">
    <!-- Floating Chat Button -->
    <div id="chat-button" class="fixed bottom-5 right-5 bg-violet-600 hover:bg-violet-700 text-white px-5 py-3 rounded-full cursor-pointer shadow-lg transition-all z-50">
        <span class="flex items-center">💬 Pawssible Chat</span>
    </div>

    <!-- Chat Container -->
    <div id="chat-container" class="flex flex-col fixed bottom-20 right-5 w-[350px] h-[500px] bg-white rounded-lg shadow-2xl overflow-hidden z-50 border border-gray-200">
        <div class="bg-gradient-to-r from-violet-600 to-violet-700 py-4 px-5 text-white font-bold flex justify-between items-center">
            🐾 Pawssible Clinic Assistant
            <button id="close-button" class="text-2xl hover:text-gray-200 transition-colors">×</button>
        </div>
        
        <div id="chat-window" class="flex-1 p-4 overflow-y-auto flex flex-col gap-4 bg-gray-50">
            <!-- Welcome message -->
            <div class="message flex items-start gap-2 max-w-[90%]">
                <div class="w-8 h-8 rounded-full bg-violet-600 text-white flex items-center justify-center flex-shrink-0 mt-1">
                    <span class="text-sm">🐾</span>
                </div>
                <div class="bg-white p-3 rounded-lg shadow-sm">
                    <div class="text-gray-800">Hello! I'm your veterinary assistant. How can I help you today?</div>
                </div>
            </div>

            <?php if (!empty($_SESSION['chat_history'])): ?>
                <?php foreach ($_SESSION['chat_history'] as $message): ?>
                    <?php if ($message['role'] === 'user'): ?>
                    <div class="message flex items-start gap-2 max-w-[90%] self-end">
                        <div class="bg-violet-600 p-3 rounded-lg shadow-sm text-white">
                            <div><?php echo nl2br($message['content']); ?></div>
                            <div class="text-xs text-gray-200 mt-1 text-right"><?php echo $message['timestamp']; ?></div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-sm">👤</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="message flex items-start gap-2 max-w-[90%]">
                        <div class="w-8 h-8 rounded-full bg-violet-600 text-white flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-sm">🐾</span>
                        </div>
                        <div class="bg-white p-3 rounded-lg shadow-sm">
                            <div class="text-gray-800"><?php echo nl2br($message['content']); ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?php echo $message['timestamp']; ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <form id="chat-form" class="flex gap-2 p-3 border-t border-gray-200 bg-white">
            <input type="text" id="message" name="message" placeholder="Type your message..." required
                   class="flex-1 px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white p-2 rounded-full transition-colors w-10 h-10 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get elements
            const chatButton = document.getElementById('chat-button');
            const chatContainer = document.getElementById('chat-container');
            const closeButton = document.getElementById('close-button');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message');
            const chatWindow = document.getElementById('chat-window');
            
            // Toggle chat container visibility
            chatButton.addEventListener('click', function() {
                chatContainer.classList.add('open');
                // Make sure the most recent messages are visible
                setTimeout(() => {
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                }, 100);
            });
            
            // Close chat container
            closeButton.addEventListener('click', function() {
                chatContainer.classList.remove('open');
                console.log('Chat closed');
            });
            
            // Handle form submission
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const userMessage = messageInput.value.trim();
                
                if (userMessage) {
                    // Add user's message to the chat window
                    const userMessageElement = document.createElement('div');
                    userMessageElement.className = 'message flex items-start gap-2 max-w-[90%] self-end';
                    userMessageElement.innerHTML = `
                        <div class="bg-violet-600 p-3 rounded-lg shadow-sm text-white">
                            <div>${userMessage}</div>
                            <div class="text-xs text-gray-200 mt-1 text-right">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-sm">👤</span>
                        </div>
                    `;
                    chatWindow.appendChild(userMessageElement);
                    
                    // Show typing indicator
                    const typingElement = document.createElement('div');
                    typingElement.className = 'message flex items-start gap-2 max-w-[90%]';
                    typingElement.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-violet-600 text-white flex items-center justify-center flex-shrink-0 mt-1">
                            <span class="text-sm">🐾</span>
                        </div>
                        <div class="bg-white p-3 rounded-lg shadow-sm">
                            <div class="typing-indicator">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    `;
                    chatWindow.appendChild(typingElement);
                    
                    // Scroll to the bottom of the chat window
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                    
                    // Send the message to the server using AJAX
                    fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `message=${encodeURIComponent(userMessage)}`,
                    })
                    .then((response) => response.text())
                    .then((data) => {
                        // Remove typing indicator
                        chatWindow.removeChild(typingElement);
                        
                        // Add bot's response to the chat window
                        const botMessageElement = document.createElement('div');
                        botMessageElement.className = 'message flex items-start gap-2 max-w-[90%]';
                        botMessageElement.innerHTML = `
                            <div class="w-8 h-8 rounded-full bg-violet-600 text-white flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-sm">🐾</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm">
                                <div class="text-gray-800">${data}</div>
                                <div class="text-xs text-gray-500 mt-1">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                            </div>
                        `;
                        chatWindow.appendChild(botMessageElement);
                        
                        // Scroll to the bottom of the chat window
                        chatWindow.scrollTop = chatWindow.scrollHeight;
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        chatWindow.removeChild(typingElement);
                        
                        const errorMessageElement = document.createElement('div');
                        errorMessageElement.className = 'message flex items-start gap-2 max-w-[90%]';
                        errorMessageElement.innerHTML = `
                            <div class="w-8 h-8 rounded-full bg-violet-600 text-white flex items-center justify-center flex-shrink-0 mt-1">
                                <span class="text-sm">🐾</span>
                            </div>
                            <div class="bg-white p-3 rounded-lg shadow-sm">
                                <div class="text-red-500">Error: Unable to fetch response.</div>
                                <div class="text-xs text-gray-500 mt-1">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                            </div>
                        `;
                        chatWindow.appendChild(errorMessageElement);
                    });
                    
                    // Clear the input field
                    messageInput.value = '';
                }
            });
        });
    </script>
</body>
</html>