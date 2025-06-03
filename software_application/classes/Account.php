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

    /**
     * Registers a new account with the provided details.
     */
    public function registerAccount($accountName, $phoneNumber, $password, $email) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "INSERT INTO account (accountName, phoneNumber, password, email, accountBalance, accountStatus, accountVerifyStatus) VALUES (?, ?, ?, ?, 0, 'active', 'unverified')";
        $stmt = $db->prepare($sql);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $stmt->execute([$accountName, $phoneNumber, $hashedPassword, $email]);
    }
    
    /**
     * Authenticates a user using their username, email, or phone number and password.
     */
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
    /**
     * Logs out the current user (implementation needed).
     */
    public function logout() {}
    /**
     * Deactivates the current account (implementation needed).
     */
    public function deactivateAccount() {}
    /**
     * Updates the account details (implementation needed).
     */
    public function updateAccount() {}
    /**
     * Verifies the account (implementation needed).
     */
    public function verifyAccount() {}
    /**
     * Updates the account balance for a given account ID.
     */
    public function updateAccountBalance($accountID, $newBalance) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET accountBalance = ? WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$newBalance, $accountID]);
    }

    /**
     * Updates the account status (implementation needed).
     */
    public function updateAccountStatus() {}
    /**
     * Retrieves the account information (implementation needed).
     */
    public function viewAccountInfo() {}
    /**
     * Views the points for the account (implementation needed).
     */
    public function viewPoints() {}
    /**
     * Views the account's balance history (implementation needed).
     */
    public function viewAccountBalanceHistory() {}
    /**
     * Views the sale history for the account (implementation needed).
     */
    public function viewSaleHistory() {}
    /**
     * Views the ART schedule for the account (implementation needed).
     */
    public function viewARTSchedule() {}
    /**
     * Updates the ART schedule for the account (implementation needed).
     */
    public function updateARTSchedule() {}
    /**
     * Updates the verification status of the account (implementation needed).
     */
    public function updateVerifyStatus() {}
    /**
     * Views the merchandise stock (implementation needed).
     */
    public function viewMerchandiseStock() {}
    /**
     * Requests to redeem a reward (implementation needed).
     */
    public function requestRedeemReward() {}
    /**
     * Selects the type of reward to redeem (implementation needed).
     */
    public function selectRewardType() {}
    /**
     * Records the purchase history for the account (implementation needed).
     */
    public function recordPurchasedHistory() {}
    /**
     * Records the cancellation history for the account (implementation needed).
     */
    public function recordCancellationHistory() {}

    /**
     * Retrieves account details by account ID.
     */
    public function getAccountByID($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT * FROM account WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$accountID]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Updates the account information for a given account ID.
     */
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

    /**
     * Uploads an identity document for the account and sets verify status to pending.
     */
    public function uploadIdentity($accountID, $identityPath) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET identityDocument = ?, accountVerifyStatus = 'pending' WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$identityPath, $accountID]);
    }

    /**
     * Sets the account verification status to 'verified' for the given account ID.
     */
    public function verifyAccountStatus($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "UPDATE account SET accountVerifyStatus = 'verified' WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$accountID]);
    }

    /**
     * Gets the current balance for the given account ID.
     */
    public function getCurrentBalance($accountID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT accountBalance FROM account WHERE accountID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$accountID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['accountBalance'] : false;
    }
}
?>
