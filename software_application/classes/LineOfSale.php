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

    public function createNewLineOfSale() {}
    public function calculateTotalAmount() {}
    public function storeItemDetails() {}
    public function getItemDetails() {}
    public function displayItemInfo() {}
}
?>
