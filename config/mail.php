<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mail;
    private $config = [
        'host' => 'smtp.gmail.com', // Change to your SMTP server
        'username' => 'your-email@gmail.com', // Your email
        'password' => 'your-app-specific-password', // Use App Password if 2FA is enabled
        'port' => 587,
        'encryption' => 'tls', // tls or ssl
        'from_email' => 'noreply@yourdomain.com',
        'from_name' => 'Learning Path'
    ];

    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }

    private function configure() {
        // Server settings
        $this->mail->isSMTP();
        $this->mail->Host = $this->config['host'];
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->config['username'];
        $this->mail->Password = $this->config['password'];
        $this->mail->SMTPSecure = $this->config['encryption'];
        $this->mail->Port = $this->config['port'];
        $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
    }

    public function sendVerificationEmail($to, $username, $token) {
        try {
            $verificationUrl = "http://" . $_SERVER['HTTP_HOST'] . "/RMS/verify.php?token=" . $token;
            
            $this->mail->addAddress($to, $username);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your Email Address';
            
            $htmlContent = "
                <h2>Welcome to Learning Path, {$username}!</h2>
                <p>Thank you for registering. Please verify your email address by clicking the button below:</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{$verificationUrl}" style="background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                        Verify Email Address
                    </a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p><a href="{$verificationUrl}">{$verificationUrl}</a></p>
                <p>If you did not create an account, no further action is required.</p>
                <p>Best regards,<br>Learning Path Team</p>
            ";
            
            $this->mail->Body = $htmlContent;
            $this->mail->AltBody = "Welcome to Learning Path! Please verify your email by visiting: {$verificationUrl}";
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    public function sendPasswordResetEmail($to, $username, $token) {
        try {
            $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . "/RMS/reset-password.php?token=" . $token;
            
            $this->mail->addAddress($to, $username);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Password Reset Request';
            
            $htmlContent = "
                <h2>Password Reset Request</h2>
                <p>Hello {$username},</p>
                <p>We received a request to reset your password. Click the button below to set a new password:</p>
                <p style="text-align: center; margin: 25px 0;">
                    <a href="{$resetUrl}" style="background-color: #4e73df; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                        Reset Password
                    </a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p><a href="{$resetUrl}">{$resetUrl}</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p>Best regards,<br>Learning Path Team</p>
            ";
            
            $this->mail->Body = $htmlContent;
            $this->mail->AltBody = "To reset your password, please visit: {$resetUrl}";
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Password reset email could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
}
?>
