<?php
class Sale {
    public $saleID;
    public $accountID;
    public $promotionID;
    public $saleDate;
    public $saleTime;
    public $lineOfSaleQuantity;
    public $lineOfSaleAmount;
    public $totalAmountPay;
    public $saleStatus;

    public function initiateNewSale() {}
    public function generateReceipt() {}
    public function showTotalAmount() {}
    public function activatePromotion() {}
    public function checkoutPromotion() {}
    public function earnRewardPoint() {}
    public function handleLineOfSale() {}
}
?>
