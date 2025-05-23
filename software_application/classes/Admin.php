<?php
class Admin {
    public $adminID;
    public $adminRole; // ENUM('system admin', 'feedback coordinator', 'promotion coordinator', 'merchandise coordinator')
    public $adminName;
    public $adminPhoneNumber;
    public $adminEmail;
    public $adminPassword;

    public function editAccount() {}
    public function removeAccount() {}
    public function addAccount() {}
    public function updateAccountStatus() {}
    public function managePromotion() {}
    public function assignRole() {}
    public function viewMerchandiseStock() {}
    public function addMerchandise() {}
    public function removeMerchandise() {}
    public function checkMerchandiseQuantity() {}
    public function increaseMerchandiseQuantity() {}
    public function decreaseMerchandiseQuantity() {}
    public function enterMerchandiseStock() {}
    public function getFeedbackList() {}
    public function accessFeedbackList() {}
    public function viewStatisticalReport() {}
    public function adjustARTSchedule() {}
}
?>
