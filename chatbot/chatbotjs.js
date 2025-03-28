// Toggle chat container visibility
document.getElementById('chat-button').addEventListener('click', function () {
    const chatContainer = document.getElementById('chat-container');
    chatContainer.classList.toggle('open');
});

// Close chat container
document.getElementById('close-button').addEventListener('click', function () {
    const chatContainer = document.getElementById('chat-container');
    chatContainer.classList.remove('open');
});

// Handle form submission
document.getElementById('chat-form').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent form submission

    const messageInput = document.getElementById('message');
    const chatWindow = document.getElementById('chat-window');
    const userMessage = messageInput.value.trim();

    if (userMessage) {
        // Add user's message to the chat window
        const userMessageElement = document.createElement('div');
        userMessageElement.className = 'message user-message';
        userMessageElement.innerHTML = `
            <div class="text">${userMessage}</div>
            <div class="timestamp">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
        `;
        chatWindow.appendChild(userMessageElement);

        // Show loading spinner
        const loadingSpinner = document.createElement('div');
        loadingSpinner.className = 'loading-spinner';
        loadingSpinner.innerHTML = '<div class="text">🐾 Bot is typing...</div>';
        chatWindow.appendChild(loadingSpinner);

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
                // Remove loading spinner
                chatWindow.removeChild(loadingSpinner);

                // Add bot's response to the chat window
                const botMessageElement = document.createElement('div');
                botMessageElement.className = 'message bot-message';
                botMessageElement.innerHTML = `
                    <div class="text">${data}</div>
                    <div class="timestamp">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                `;
                chatWindow.appendChild(botMessageElement);

                // Scroll to the bottom of the chat window
                chatWindow.scrollTop = chatWindow.scrollHeight;
            })
            .catch((error) => {
                console.error('Error:', error);
                chatWindow.removeChild(loadingSpinner);
                const errorMessageElement = document.createElement('div');
                errorMessageElement.className = 'message bot-message';
                errorMessageElement.innerHTML = `
                    <div class="text">Error: Unable to fetch response.</div>
                    <div class="timestamp">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                `;
                chatWindow.appendChild(errorMessageElement);
            });

        // Clear the input field
        messageInput.value = '';
    }
});