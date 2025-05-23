<?php
class Account {
    public $accountID;
    public $accountName;
    public $phoneNumber;
    public $password;
    public $email;
    public $itemCount;
    public $accountBalance;
    public $accountStatus; // ENUM('active', 'inactive')
    public $accountVerifyStatus; // ENUM('verified', 'unverified')

    public function registerAccount($accountName, $phoneNumber, $password, $email) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "INSERT INTO account (accountName, phoneNumber, password, email, accountBalance, accountStatus, accountVerifyStatus) VALUES (?, ?, ?, ?, 0, 'active', 'unverified')";
        $stmt = $db->prepare($sql);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $stmt->execute([$accountName, $phoneNumber, $hashedPassword, $email]);
    }
    
    public function login($loginInput, $password) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT * FROM account WHERE (accountName = ? OR email = ? OR phoneNumber = ?) AND accountStatus = 'active'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$loginInput, $loginInput, $loginInput]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    public function logout() {}
    public function deactivateAccount() {}
    public function updateAccount() {}
    public function verifyAccount() {}
    public function updateAccountBalance() {}
    public function updateAccountStatus() {}
    public function viewAccountInfo() {}
    public function viewPoints() {}
    public function viewAccountBalanceHistory() {}
    public function viewSaleHistory() {}
    public function viewARTSchedule() {}
    public function updateARTSchedule() {}
    public function updateVerifyStatus() {}
    public function viewMerchandiseStock() {}
    public function requestRedeemReward() {}
    public function selectRewardType() {}
    public function recordPurchasedHistory() {}
    public function recordCancellationHistory() {}

    public function getAccountByID($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT * FROM account WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateAccountInfo($accountID, $accountName, $phoneNumber, $email, $password = null) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        if ($password) {
            $sql = "UPDATE account SET accountName = ?, phoneNumber = ?, email = ?, password = ? WHERE accountID = ?";
            $stmt = $db->prepare($sql);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            return $stmt->execute([$accountName, $phoneNumber, $email, $hashedPassword, $accountID]);
        } else {
            $sql = "UPDATE account SET accountName = ?, phoneNumber = ?, email = ? WHERE accountID = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute([$accountName, $phoneNumber, $email, $accountID]);
        }
    }

    public function uploadIdentity($accountID, $identityPath) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET identityDocument = ?, accountVerifyStatus = 'pending' WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$identityPath, $accountID]);
    }

    public function verifyAccountStatus($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET accountVerifyStatus = 'verified' WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$accountID]);
    }
}
?>
