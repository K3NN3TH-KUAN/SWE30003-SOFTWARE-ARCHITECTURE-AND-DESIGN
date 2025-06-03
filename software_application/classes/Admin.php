<?php
class Admin {
    public $adminID;
    public $adminRole; // ENUM('system admin', 'feedback coordinator', 'promotion coordinator', 'merchandise coordinator')
    public $adminName;
    public $adminPhoneNumber;
    public $adminEmail;
    public $adminPassword;

    /**
     * Edits an account (implementation needed).
     */
    public function editAccount() {}

    /**
     * Removes an account (implementation needed).
     */
    public function removeAccount() {}

    /**
     * Adds a new account (implementation needed).
     */
    public function addAccount() {}

    /**
     * Updates the status of an account (implementation needed).
     */
    public function updateAccountStatus() {}

    /**
     * Manages promotions (implementation needed).
     */
    public function managePromotion() {}

    /**
     * Assigns a role to an admin (implementation needed).
     */
    public function assignRole() {}

    /**
     * Views the merchandise stock (implementation needed).
     */
    public function viewMerchandiseStock() {}

    /**
     * Adds new merchandise (implementation needed).
     */
    public function addMerchandise() {}

    /**
     * Removes merchandise (implementation needed).
     */
    public function removeMerchandise() {}

    /**
     * Checks the quantity of merchandise (implementation needed).
     */
    public function checkMerchandiseQuantity() {}

    /**
     * Increases the quantity of merchandise (implementation needed).
     */
    public function increaseMerchandiseQuantity() {}

    /**
     * Decreases the quantity of merchandise (implementation needed).
     */
    public function decreaseMerchandiseQuantity() {}

    /**
     * Enters new stock for merchandise (implementation needed).
     */
    public function enterMerchandiseStock() {}

    /**
     * Gets the list of feedback (implementation needed).
     */
    public function getFeedbackList() {}

    /**
     * Accesses the feedback list (implementation needed).
     */
    public function accessFeedbackList() {}

    /**
     * Views the statistical report (implementation needed).
     */
    public function viewStatisticalReport() {}

    /**
     * Adjusts the ART schedule (implementation needed).
     */
    public function adjustARTSchedule() {}
}
?>
