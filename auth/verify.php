<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

// Check if token is provided
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /RMS/login.php?error=invalid_token');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

// Verify the token
$query = "SELECT id FROM users WHERE verification_token = ? AND is_verified = 0";
$stmt = $db->prepare($query);
$stmt->execute([$token]);

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Mark user as verified
    $update = "UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?";
    $stmt = $db->prepare($update);
    
    if ($stmt->execute([$row['id']])) {
        header('Location: /RMS/login.php?verified=1');
    } else {
        header('Location: /RMS/login.php?error=verification_failed');
    }
} else {
    header('Location: /RMS/login.php?error=invalid_token');
}

exit();
?>
