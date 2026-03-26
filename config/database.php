<?php
class Database {
    private $host = "localhost";
    private $db_name = "learning_path";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
            
        } catch(PDOException $e) {
            // More detailed error information
            $error = "Connection failed: " . $e->getMessage() . "\n";
            $error .= "Error Code: " . $e->getCode() . "\n";
            $error .= "Trying to connect to: mysql:host={$this->host};dbname={$this->db_name}\n";
            die($error);
        }

        return $this->conn;
    }
}
?>
