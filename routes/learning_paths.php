<?php
// Include required files
require_once '../config/database.php';
require_once '../models/LearningPath.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize learning path object
$learningPath = new LearningPath($db);

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = isset($uri[3]) ? $uri[3] : '';
$id = isset($uri[4]) ? $uri[4] : null;

// Handle different request methods
switch ($method) {
    case 'GET':
        // Get all learning paths for a user or a specific learning path
        if ($id) {
            // Get single learning path
            if ($learningPath->getById($id)) {
                http_response_code(200);
                echo json_encode([
                    'id' => $learningPath->id,
                    'user_id' => $learningPath->user_id,
                    'title' => $learningPath->title,
                    'description' => $learningPath->description,
                    'category' => $learningPath->category,
                    'difficulty' => $learningPath->difficulty,
                    'topics' => $learningPath->topics,
                    'created_at' => $learningPath->created_at,
                    'updated_at' => $learningPath->updated_at
                ]);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Learning path not found."]);
            }
        } else {
            // Get all learning paths for a user with optional filters
            $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
            $filters = [
                'category' => isset($_GET['category']) ? $_GET['category'] : null,
                'difficulty' => isset($_GET['difficulty']) ? $_GET['difficulty'] : null
            ];
            
            if ($user_id) {
                $learning_paths = $learningPath->getAllByUser($user_id, $filters);
                http_response_code(200);
                echo json_encode($learning_paths);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "User ID is required."]);
            }
        }
        break;
        
    case 'POST':
        // Create a new learning path
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->user_id) && !empty($data->title)) {
            $learningPath->user_id = $data->user_id;
            $learningPath->title = $data->title;
            $learningPath->description = $data->description ?? '';
            $learningPath->category = $data->category ?? null;
            $learningPath->difficulty = $data->difficulty ?? 'beginner';
            $learningPath->topics = $data->topics ?? [];
            
            if ($learningPath->create()) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Learning path created successfully.",
                    "id" => $learningPath->id
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to create learning path."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Unable to create learning path. User ID and title are required."]);
        }
        break;
        
    case 'PUT':
        // Update a learning path
        $data = json_decode(file_get_contents("php://input"));
        
        if ($id) {
            // Get existing learning path
            if ($learningPath->getById($id)) {
                // Update learning path data
                $learningPath->title = $data->title ?? $learningPath->title;
                $learningPath->description = $data->description ?? $learningPath->description;
                $learningPath->category = $data->category ?? $learningPath->category;
                $learningPath->difficulty = $data->difficulty ?? $learningPath->difficulty;
                $learningPath->topics = $data->topics ?? $learningPath->topics;
                
                if ($learningPath->update()) {
                    http_response_code(200);
                    echo json_encode(["message" => "Learning path updated successfully."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Unable to update learning path."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Learning path not found."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Learning path ID is required."]);
        }
        break;
        
    case 'DELETE':
        // Delete a learning path
        if ($id) {
            $learningPath->id = $id;
            
            // In a real app, verify that the user owns this learning path
            // $learningPath->user_id = $current_user_id;
            
            if ($learningPath->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Learning path deleted successfully."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to delete learning path."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Learning path ID is required."]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed."]);
        break;
}
?>
