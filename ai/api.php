<?php
//session_start(); // Start a session for multi-turn conversations

// Configuration
$GROQ_API_KEY = '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'; // Replace with your actual API key
$API_URL = "https://api.groq.com/openai/v1/chat/completions";

// Function to send a request to the Groq API
function sendChatRequest($message, $chatHistory, $apiKey, $apiUrl) {
    // Define the system message to set the AI's purpose
    $systemMessage = "You are a helpful assistant for a veterinary clinic called 'Pawssible Solutions'. 
    Your clinic offers the following services: 
    - Vaccination (routine vaccines for pets including rabies, distemper, and parvo)
    - Deworming (intestinal parasite prevention and treatment)
    - Consultation (general health check-ups, nutrition advice, behavior concerns)
    - Surgery (spay/neuter, dental procedures, emergency surgeries)
    
    Provide friendly, concise information about these services, including general pricing when asked.
    For appointments, advise clients to call the clinic directly.
    If asked about emergencies, emphasize that immediate veterinary attention is crucial.
    Politely redirect any questions unrelated to veterinary care by stating you can only assist with topics related to pet healthcare services.";

    // Prepare the messages array
    $messages = [
        [
            "role" => "system",
            "content" => $systemMessage
        ]
    ];

    // Add chat history to the messages
    foreach ($chatHistory as $chat) {
        $messages[] = [
            "role" => $chat['role'],
            "content" => $chat['content']
        ];
    }

    // Add the user's latest message
    $messages[] = [
        "role" => "user",
        "content" => $message
    ];

    $data = [
        "messages" => $messages,
        "model" => "llama-3.3-70b-versatile"
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode !== 200) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("API request failed with HTTP code: $httpCode. Error: $error");
    }

    curl_close($ch);
    return json_decode($response, true);
}

// Function to sanitize user input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to remove asterisks from the response
function removeAsterisks($text) {
    return str_replace('*', '', $text);
}

// Handle incoming POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = sanitizeInput($_POST['message']);

    if (!empty($userMessage)) {
        try {
            // Get the chat history from the session
            $chatHistory = $_SESSION['chat_history'] ?? [];

            // Get the AI's response
            $apiResponse = sendChatRequest($userMessage, $chatHistory, $GROQ_API_KEY, $API_URL);
            if (isset($apiResponse['choices'][0]['message']['content'])) {
                // Remove asterisks from the AI's response
                $cleanedResponse = removeAsterisks($apiResponse['choices'][0]['message']['content']);
                echo $cleanedResponse;
            } else {
                echo "Error: Unexpected API response.";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Please enter a message.";
    }
}
?>