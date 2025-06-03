<?php
/**
 * Class for handling line of sale records (items in a sale).
 */
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

    /**
     * Constructor. Initializes the database connection.
     */
    public function __construct() {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Creates a new line of sale record for a trip or merchandise.
     * @param int $saleID The sale ID.
     * @param string $type 'Trip' or 'Merchandise'.
     * @param int $itemID The tripID or merchandiseID.
     * @param int $itemQuantity Number of items.
     * @param float $itemAmount Price per item.
     * @param float $totalAmountPerLineOfSale Total for this line.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Calculates the total amount for a line of sale.
     * @param int $itemQuantity
     * @param float $itemAmount
     * @return float
     */
    public function calculateTotalAmount($itemQuantity, $itemAmount) {
        return $itemQuantity * $itemAmount;
    }

    /**
     * Stores item details for a line of sale (implementation needed).
     */
    public function storeItemDetails() {}

    /**
     * Gets item details for a line of sale (implementation needed).
     */
    public function getItemDetails() {}

    /**
     * Displays item information (implementation needed).
     */
    public function displayItemInfo() {}

    /**
     * Gets all line of sale records for a specific sale ID.
     * @param int $saleID
     * @return array
     */
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

    /**
     * Gets a line of sale record by its ID.
     * @param int $lineOfSaleID
     * @return array|false
     */
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
