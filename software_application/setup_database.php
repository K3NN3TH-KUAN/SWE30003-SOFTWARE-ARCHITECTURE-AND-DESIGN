<?php
$host = "localhost";
$user = "root";
$pass = "";

// Connect to MySQL server
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS software_app_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db("software_app_db");

// Create tables
$table_sql = [

    // Accounts
    "CREATE TABLE IF NOT EXISTS account (
        accountID INT AUTO_INCREMENT PRIMARY KEY,
        accountName VARCHAR(100) NOT NULL,
        phoneNumber VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        accountBalance FLOAT DEFAULT 0,
        accountStatus ENUM('active', 'inactive') DEFAULT 'active',
        accountVerifyStatus ENUM('verified', 'unverified', 'pending') DEFAULT 'unverified',
        identityDocument VARCHAR(255) NULL
    )",

    // Admins
    "CREATE TABLE IF NOT EXISTS admin (
        adminID INT AUTO_INCREMENT PRIMARY KEY,
        adminRole ENUM('system admin', 'feedback coordinator', 'promotion coordinator', 'merchandise coordinator') NOT NULL,
        adminName VARCHAR(100) NOT NULL,
        adminPhoneNumber VARCHAR(20) NOT NULL,
        adminEmail VARCHAR(100) NOT NULL,
        adminPassword VARCHAR(255) NOT NULL
    )",    

    // Feedback
    "CREATE TABLE IF NOT EXISTS feedback (
        feedbackID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        adminID INT NULL,
        rating INT,
        comment TEXT,
        feedbackStatus ENUM('pending', 'resolved') DEFAULT 'pending',
        FOREIGN KEY (accountID) REFERENCES account(accountID),
        FOREIGN KEY (adminID) REFERENCES admin(adminID)
    )",

     // Trip
     "CREATE TABLE IF NOT EXISTS trip (
        tripID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        tripDate DATE,
        tripTime TIME,
        origin VARCHAR(100),
        destination VARCHAR(100),
        totalAmount FLOAT,
        tripStatus VARCHAR(20),
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",

    // Merchandise
    "CREATE TABLE IF NOT EXISTS merchandise (
        merchandiseID INT AUTO_INCREMENT PRIMARY KEY,
        adminID INT,
        merchandiseName VARCHAR(100),
        merchandisePrice FLOAT,
        merchandiseDescription TEXT,
        stockQuantity INT,
        quantity INT,
        merchandiseCategory VARCHAR(50),
        merchandiseImage VARCHAR(255),
        FOREIGN KEY (adminID) REFERENCES admin(adminID)
    )",

    // Promotion
    "CREATE TABLE IF NOT EXISTS promotion (
        promotionID INT AUTO_INCREMENT PRIMARY KEY,
        adminID INT,
        discountRate FLOAT,
        startDate DATE,
        expireDate DATE,
        promotionQuantity INT,
        FOREIGN KEY (adminID) REFERENCES admin(adminID)
    )",

    // Sale
    "CREATE TABLE IF NOT EXISTS sale (
        saleID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        promotionID INT NULL,
        saleDate DATE,
        saleTime TIME,
        lineOfSaleQuantity INT,
        lineOfSaleAmount FLOAT,
        totalAmountPay FLOAT,
        saleStatus ENUM('Completed', 'Incompleted') DEFAULT 'Incompleted',
        FOREIGN KEY (accountID) REFERENCES account(accountID),
        FOREIGN KEY (promotionID) REFERENCES promotion(promotionID)
    )",

    // LineOfSale
    "CREATE TABLE IF NOT EXISTS line_of_sale (
        lineOfSaleID INT AUTO_INCREMENT PRIMARY KEY,
        saleID INT,
        type ENUM('Trip', 'Merchandise') NOT NULL,
        itemID INT,
        tripID INT NULL,
        merchandiseID INT NULL,
        itemQuantity INT,
        itemAmount FLOAT,
        totalAmountPerLineOfSale FLOAT,
        FOREIGN KEY (saleID) REFERENCES sale(saleID),
        FOREIGN KEY (tripID) REFERENCES trip(tripID),
        FOREIGN KEY (merchandiseID) REFERENCES merchandise(merchandiseID)
    )",

    // Notification
    "CREATE TABLE IF NOT EXISTS notification (
        notificationID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        messageContent TEXT,
        notificationType ENUM('booking', 'promotion', 'payment', 'feedback') NOT NULL,
        notificationDateTime DATETIME,
        notificationStatus ENUM('read', 'unread') DEFAULT 'unread',
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",

    // Points
    "CREATE TABLE IF NOT EXISTS point (
        pointID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        pointBalance INT,
        totalPointEarned INT,
        pointRedeemed INT,
        pointQuantity INT,
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",

    // Statistic
    "CREATE TABLE IF NOT EXISTS statistic (
        statisticID INT AUTO_INCREMENT PRIMARY KEY,
        adminID INT null,
        mostPopularTrip VARCHAR(100),
        leastPopularTrip VARCHAR(100),
        creationDate DATE,
        creationTime TIME,
        totalAccount INT,
        ticketSales INT,
        tripCancellation INT,
        FOREIGN KEY (adminID) REFERENCES admin(adminID)
    )",

    // TopUp
    "CREATE TABLE IF NOT EXISTS topup (
        topUpID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        topUpDate DATE,
        topUpTime TIME,
        topUpAmount FLOAT,
        topUpStatus ENUM('completed', 'failed') DEFAULT 'failed',
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",
];

foreach ($table_sql as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Table created or already exists.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

$conn->close();
echo "Setup complete.";
?>
