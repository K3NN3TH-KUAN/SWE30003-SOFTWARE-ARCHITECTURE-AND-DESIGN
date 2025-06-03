<?php
class Database {
    private $host = "localhost";
    private $db_name = "software_app_db";
    private $username = "root";
    private $password = "";
    private $conn;

    /**
     * Returns a PDO connection to the application's database.
     * 
     * @return PDO|null The PDO connection object or null on failure.
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }

    /**
     * Returns a PDO connection to the MySQL server without selecting a database.
     * 
     * @return PDO|null The PDO connection object or null on failure.
     */
    public function getConnectionWithoutDB() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        return $this->conn;
    }

    /**
     * Closes the current database connection.
     */
    public function closeConnection() {
        $this->conn = null;
    }
}
?>
