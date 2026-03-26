<?php
// Include required files
require_once '../config/database.php';
require_once '../models/User.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object
$user = new User($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'POST':
        // Create a new user (Register)
        $data = json_decode(file_get_contents("php://input"));
        
        if (!empty($data->username) && !empty($data->email) && !empty($data->password)) {
            $user->username = $data->username;
            $user->email = $data->email;
            $user->password = $data->password;
            
            // Check if email already exists
            if ($user->emailExists()) {
                http_response_code(400);
                echo json_encode(["message" => "Email already exists."]);
                exit();
            }
            
            // Create the user
            if ($user->create()) {
                http_response_code(201);
                echo json_encode([
                    "message" => "User created successfully.",
                    "user_id" => $user->id,
                    "username" => $user->username,
                    "email" => $user->email
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to create user."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Unable to create user. Data is incomplete."]);
        }
        break;
        
    case 'GET':
        // Login user
        $email = isset($_GET['email']) ? $_GET['email'] : '';
        $password = isset($_GET['password']) ? $_GET['password'] : '';
        
        if (!empty($email) && !empty($password)) {
            $user->email = $email;
            
            if ($user->emailExists() && password_verify($password, $user->password)) {
                // Get user details
                $user->getById($user->id);
                
                // Create token (in a real app, use JWT or similar)
                $token = bin2hex(random_bytes(32));
                
                http_response_code(200);
                echo json_encode([
                    "message" => "Login successful.",
                    "token" => $token,
                    "user" => [
                        "id" => $user->id,
                        "username" => $user->username,
                        "email" => $user->email
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Login failed. Invalid email or password."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Email and password are required."]);
        }
        break;
        
    case 'PUT':
        // Update user profile
        $data = json_decode(file_get_contents("php://input"));
        
        // Get user ID from URL or token in a real app
        $user_id = isset($uri[3]) ? $uri[3] : null;
        
        if ($user_id) {
            $user->id = $user_id;
            
            // Get existing user data
            if ($user->getById($user_id)) {
                // Update user data
                $user->username = !empty($data->username) ? $data->username : $user->username;
                $user->email = !empty($data->email) ? $data->email : $user->email;
                
                // Update password if provided
                if (!empty($data->password)) {
                    $user->password = password_hash($data->password, PASSWORD_BCRYPT);
                }
                
                if ($user->update()) {
                    http_response_code(200);
                    echo json_encode([
                        "message" => "User updated successfully.",
                        "user" => [
                            "id" => $user->id,
                            "username" => $user->username,
                            "email" => $user->email
                        ]
                    ]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Unable to update user."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "User not found."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "User ID is required."]);
        }
        break;
        
    case 'DELETE':
        // Delete user account
        $user_id = isset($uri[3]) ? $uri[3] : null;
        
        if ($user_id) {
            $user->id = $user_id;
            
            if ($user->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "User deleted successfully."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Unable to delete user."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "User ID is required."]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Method not allowed."]);
        break;
}
?>
