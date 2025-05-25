<?php
class LineOfSale {
    public $lineOfSaleID;
    public $saleID;
    public $type; // ENUM('Trip', 'Merchandise')
    public $itemID;
    public $tripID;
    public $merchandiseID;
    public $itemQuantity;
    public $itemAmount;
    public $totalAmountPerLineOfSale;
    private $conn;

    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createNewLineOfSale($saleID, $type, $itemID, $itemQuantity, $itemAmount, $totalAmountPerLineOfSale) {
        try {
            if ($type === 'Trip') {
                $sql = "INSERT INTO line_of_sale (saleID, type, tripID, itemQuantity, itemAmount, totalAmountPerLineOfSale)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([
                    $saleID,
                    $type,
                    $itemID, // tripID
                    $itemQuantity,
                    $itemAmount,
                    $totalAmountPerLineOfSale
                ]);
            } else if ($type === 'Merchandise') {
                $sql = "INSERT INTO line_of_sale (saleID, type, merchandiseID, itemQuantity, itemAmount, totalAmountPerLineOfSale)
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sql);
                return $stmt->execute([
                    $saleID,
                    $type,
                    $itemID, // merchandiseID
                    $itemQuantity,
                    $itemAmount,
                    $totalAmountPerLineOfSale
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error in createNewLineOfSale: " . $e->getMessage());
            return false;
        }
    }

    public function calculateTotalAmount($itemQuantity, $itemAmount) {
        return $itemQuantity * $itemAmount;
    }

    public function storeItemDetails() {}
    public function getItemDetails() {}
    public function displayItemInfo() {}

    public function getLineOfSaleBySaleID($saleID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM line_of_sale WHERE saleID = :saleID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':saleID', $saleID);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLineOfSaleByID($lineOfSaleID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();

        $sql = "SELECT * FROM line_of_sale WHERE lineOfSaleID = :lineOfSaleID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':lineOfSaleID', $lineOfSaleID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
