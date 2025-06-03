<?php
require_once __DIR__ . '/Database.php';

class Merchandise {
    public $merchandiseID;
    public $adminID;
    public $merchandiseName;
    public $merchandisePrice;
    public $merchandiseDescription;
    public $stockQuantity;
    public $quantity;
    public $merchandiseCategory;
    public $merchandiseImage;

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Manages merchandise details (implementation needed).
     */
    public function manageMerchandiseDetails() {}

    /**
     * Tracks stock levels for merchandise (implementation needed).
     */
    public function trackStockLevels() {}

    /**
     * Sets new merchandise details (implementation needed).
     */
    public function setNewMerchandise() {}

    /**
     * Maintains the purchase history for merchandise (implementation needed).
     */
    public function maintainPurchaseHistory() {}

    /**
     * Removes merchandise from the system (implementation needed).
     */
    public function removeMerchandise() {}

    /**
     * Views merchandise details (implementation needed).
     */
    public function viewMerchandise() {}

    /**
     * Reorders merchandise stock (implementation needed).
     */
    public function reorderMerchandise() {}

    /**
     * Reserves merchandise for a purchase (implementation needed).
     */
    public function reserveMerchandise() {}

    /**
     * Generates a merchandise order (implementation needed).
     */
    public function generateMerchandiseOrder() {}

    /**
     * Displays a list of merchandise (returns up to 4 items).
     */
    public function displayMerchandiseList() {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $stmt = $db->query("SELECT * FROM merchandise LIMIT 4");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }

    /**
     * Displays the stock level for merchandise (implementation needed).
     */
    public function displayMerchandiseStockLevel() {}

    /**
     * Updates the quantity of merchandise in stock.
     */
    public function updateMerchandiseQuantity($merchandiseID, $newQuantity) {
        $sql = "UPDATE merchandise SET stockQuantity = ? WHERE merchandiseID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newQuantity, $merchandiseID]);
    }

    /**
     * Displays the quantity of merchandise (implementation needed).
     */
    public function displayMerchandiseQuantity() {}

    /**
     * Updates the stock quantity after a purchase.
     */
    public function updateStockQuantity($merchandiseID, $quantityPurchased) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        // Reduce stockQuantity by quantityPurchased
        $sql = "UPDATE merchandise SET stockQuantity = stockQuantity - ? WHERE merchandiseID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$quantityPurchased, $merchandiseID]);
    }

    /**
     * Gets all merchandise records.
     */
    public function getAllMerchandise() {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT * FROM merchandise ORDER BY merchandiseID DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets merchandise details by ID.
     */
    public function getMerchandiseByID($merchandiseID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM merchandise WHERE merchandiseID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$merchandiseID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Gets a random selection of merchandise with stock available.
     */
    public function getRandomMerchandise($limit = 2) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT * FROM merchandise WHERE stockQuantity > 0 ORDER BY RAND() LIMIT :limit";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
