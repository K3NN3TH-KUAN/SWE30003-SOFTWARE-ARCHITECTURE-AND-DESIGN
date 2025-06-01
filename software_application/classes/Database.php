<?php
class Database {
    private $host = "localhost";
    private $db_name = "software_app_db";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 60,  // 60 seconds timeout
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION wait_timeout=60, innodb_lock_wait_timeout=60"
                )
            );
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
    // Add this method to close connection
    public function closeConnection() {
        $this->conn = null;
    }
}
?>
