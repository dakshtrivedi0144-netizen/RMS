<?php
// Simple AI route handler
header('Content-Type: application/json');

// Mock AI response function
function getAIResponse($prompt) {
    // In a real app, you would call an AI API here
    $responses = [
        "What programming language should I learn first?" => "I recommend starting with Python as it has a simple syntax and is widely used in various fields.",
        "How do I get started with web development?" => "Start with HTML, CSS, and JavaScript. Then learn a frontend framework like React or Vue, and a backend technology like Node.js or PHP.",
        "What's the best way to learn machine learning?" => "Begin with Python, then learn libraries like NumPy, Pandas, and Scikit-learn. Take online courses and work on projects to apply your knowledge.",
        "default" => "I'm here to help with your learning path. Could you provide more details about what you'd like to learn?"
    ];
    
    return $responses[$prompt] ?? $responses['default'];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $prompt = $data['prompt'] ?? '';
    
    if (!empty($prompt)) {
        $response = [
            'success' => true,
            'response' => getAIResponse($prompt),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } else {
        $response = [
            'success' => false,
            'error' => 'No prompt provided',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        http_response_code(400);
    }
    
    echo json_encode($response);
    exit;
}

// Handle GET requests
$prompt = $_GET['q'] ?? '';
if (!empty($prompt)) {
    $response = [
        'success' => true,
        'response' => getAIResponse($prompt),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    echo json_encode($response);
    exit;
}

// Default response
http_response_code(400);
echo json_encode([
    'success' => false,
    'error' => 'Invalid request',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
