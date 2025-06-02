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

    public function manageMerchandiseDetails() {}
    public function trackStockLevels() {}
    public function setNewMerchandise() {}
    public function maintainPurchaseHistory() {}
    public function removeMerchandise() {}
    public function viewMerchandise() {}
    public function reorderMerchandise() {}
    public function reserveMerchandise() {}
    public function generateMerchandiseOrder() {}
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
    public function displayMerchandiseStockLevel() {}
    public function updateMerchandiseQuantity($merchandiseID, $newQuantity) {
        $sql = "UPDATE merchandise SET stockQuantity = ? WHERE merchandiseID = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newQuantity, $merchandiseID]);
    }
    public function displayMerchandiseQuantity() {}
    public function updateStockQuantity($merchandiseID, $quantityPurchased) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        // Reduce stockQuantity by quantityPurchased
        $sql = "UPDATE merchandise SET stockQuantity = stockQuantity - ? WHERE merchandiseID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$quantityPurchased, $merchandiseID]);
    }
    public function getAllMerchandise() {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT * FROM merchandise ORDER BY merchandiseID DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getMerchandiseByID($merchandiseID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM merchandise WHERE merchandiseID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$merchandiseID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
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
