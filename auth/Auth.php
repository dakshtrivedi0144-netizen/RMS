<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class Auth {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    public function register($username, $email, $password, $university_name = '') {
        // Check if email already exists
        $this->user->email = $email;
        if ($this->user->emailExists()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }

        // Check if username already exists
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Username already taken.'];
        }

        // Validate password strength
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        if (!preg_match("/[A-Z]/", $password)) {
            return ['success' => false, 'message' => 'Password must contain at least one uppercase letter.'];
        }

        if (!preg_match("/[0-9]/", $password)) {
            return ['success' => false, 'message' => 'Password must contain at least one number.'];
        }

        // Create user
        $this->user->username = $username;
        $this->user->email = $email;
        $this->user->password = $password;
        
        // Make university name optional
        $this->user->university_name = $university_name;

        if ($this->user->create()) {
            // Skip email verification for now
            // $this->sendVerificationEmail($this->user->email, $this->user->verification_token);
            
            return [
                'success' => true, 
                'message' => 'Registration successful! You can now log in.'
            ];
        }

        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }

    public function login($email, $password) {
        $this->user->email = $email;

        if (!$this->user->emailExists()) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (!password_verify($password, $this->user->password)) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        if (!$this->user->is_verified) {
            return ['success' => false, 'message' => 'Please verify your email before logging in.'];
        }

        // Start session and set user data
        $this->startUserSession();

        return [
            'success' => true, 
            'message' => 'Login successful!',
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'university' => $this->user->university_name
            ]
        ];
    }

    private function startUserSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $this->user->id;
        $_SESSION['username'] = $this->user->username;
        $_SESSION['email'] = $this->user->email;
        $_SESSION['university'] = $this->user->university_name;
        $_SESSION['logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }

    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset(
            $_SESSION['logged_in'], 
            $_SESSION['user_id'], 
            $_SESSION['ip_address'],
            $_SESSION['user_agent'],
            $_SESSION['last_activity']
        ) && 
        $_SESSION['logged_in'] === true &&
        $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR'] &&
        $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'] &&
        (time() - $_SESSION['last_activity']) < 1800; // 30 minutes session
    }

    public function logout() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        return true;
    }

    public function verifyEmail($token) {
        $query = "SELECT id FROM " . $this->user->table_name . " WHERE verification_token = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $update = "UPDATE " . $this->user->table_name . " SET is_verified = 1, verification_token = NULL WHERE id = ?";
            $stmt = $this->db->prepare($update);
            
            if ($stmt->execute([$row['id']])) {
                return ['success' => true, 'message' => 'Email verified successfully!'];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid or expired verification token.'];
    }

    private function sendVerificationEmail($email, $token) {
        // In a real application, implement email sending functionality
        // For now, we'll just log the verification link
        $verificationUrl = "http://" . $_SERVER['HTTP_HOST'] . "/RMS/auth/verify.php?token=" . $token;
        error_log("Verification URL for $email: $verificationUrl");
        
        // In production, use a library like PHPMailer to send emails
        // mail($email, "Verify your email", "Click here to verify your email: " . $verificationUrl);
    }
}
?>
