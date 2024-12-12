<?php
$allowedOrigins = ['http://localhost:9001', 'http://localhost']; // List all origins you want to allow
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    http_response_code(403); // Block disallowed origins
    exit();
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Supported AI services
$supported_services = [
    "openai" => "https://api.openai.com/v1/chat/completions",
    "anthropic" => "https://api.anthropic.com/v1/complete"
];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read the input from the client
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Validate that we have the required data
    if (empty($data['api_key']) || empty($data['service']) || empty($data['messages'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields: api_key, service, or messages."]);
        exit();
    }

    // Extract data
    $api_key = $data['api_key'];
    $service = $data['service'];
    $messages = $data['messages'];
    $model = isset($data['model']) ? $data['model'] : null;

    // Check if the service is supported
    if (!array_key_exists($service, $supported_services)) {
        http_response_code(400);
        echo json_encode(["error" => "Unsupported AI service: $service"]);
        exit();
    }

    // Configure the API URL
    $api_url = $supported_services[$service];

    // Build the payload for the respective service
    $payload = [];
    switch ($service) {
        case "openai":
            if (!$model) {
                $model = "gpt-4"; // Default model for OpenAI
            }
            $payload = [
                "model" => $model,
                "messages" => $messages
            ];
            break;

        case "anthropic":
            if (!$model) {
                $model = "claude-v1"; // Default model for Anthropic
            }
            $payload = [
                "prompt" => generateAnthropicPrompt($messages),
                "model" => $model,
                "max_tokens_to_sample" => 1000
            ];
            break;
    }

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    // Execute the request
    $response = curl_exec($ch);

    // Handle cURL errors
    if (curl_errno($ch)) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to communicate with $service: " . curl_error($ch)]);
        curl_close($ch);
        exit();
    }

    // Get the HTTP status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close the cURL session
    curl_close($ch);

    // Return the response from the AI service to the client
    http_response_code($http_code);
    echo $response;
} else {
    // If the request is not a POST, return an error
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed. Use POST."]);
}

/**
 * Generate the Anthropic-specific prompt format.
 *
 * @param array $messages The array of messages from the client.
 * @return string The formatted prompt for Anthropic.
 */
function generateAnthropicPrompt($messages) {
    $prompt = "";
    foreach ($messages as $message) {
        if (isset($message['role']) && isset($message['content'])) {
            $role = ucfirst($message['role']);
            $prompt .= "$role: " . $message['content'] . "\n";
        }
    }
    $prompt .= "Assistant:"; // Anthropic expects this as the last line
    return $prompt;
}

?>