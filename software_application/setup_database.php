<?php
$host = "localhost";
$user = "root";
$pass = "";

// Connect to MySQL server
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    // Optionally log error, but do not echo or die
    // error_log("Connection failed: " . $conn->connect_error);
    $conn->close();
    exit;
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS software_app_db";
if ($conn->query($sql) === TRUE) {
    // echo "Database created or already exists.<br>";
} else {
    // die("Error creating database: " . $conn->error);
    $conn->close();
    exit;
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
        tripID INT PRIMARY KEY AUTO_INCREMENT,
        origin VARCHAR(100) NOT NULL,
        destination VARCHAR(100) NOT NULL,
        tripDate DATE NOT NULL,
        tripTime TIME NOT NULL,
        totalAmount DECIMAL(10,2) NOT NULL,
        tripStatus ENUM('Available', 'Booked', 'Rescheduled', 'Cancelled') DEFAULT 'Available',
        maxSeats INT NOT NULL,
        availableSeats INT NOT NULL,
        tripGroupID INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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
        promotionType ENUM('Voucher', 'Promotion') DEFAULT 'Promotion',
        FOREIGN KEY (adminID) REFERENCES admin(adminID)
    )",

    // Point Redemption
    "CREATE TABLE IF NOT EXISTS point_redemption (
        redemptionID INT PRIMARY KEY AUTO_INCREMENT,
        accountID INT NOT NULL,
        itemID INT NOT NULL,
        itemType ENUM('Voucher', 'Merchandise') NOT NULL,
        pointsCost INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        redemptionDate DATE NOT NULL,
        redemptionTime TIME NOT NULL,
        isUsed TINYINT(1) DEFAULT 0,
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",

    // Sale
    "CREATE TABLE IF NOT EXISTS sale (
        saleID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        promotionID INT NULL,
        redemptionID INT NULL,
        saleDate DATE,
        saleTime TIME,
        lineOfSaleQuantity INT,
        lineOfSaleAmount FLOAT,
        totalAmountPay FLOAT,
        saleStatus ENUM('Completed', 'Incompleted', 'Rescheduled') DEFAULT 'Incompleted',
        FOREIGN KEY (accountID) REFERENCES account(accountID),
        FOREIGN KEY (promotionID) REFERENCES promotion(promotionID),
        FOREIGN KEY (redemptionID) REFERENCES point_redemption(redemptionID)
    )",

    // LineOfSale
    "CREATE TABLE IF NOT EXISTS line_of_sale (
        lineOfSaleID INT AUTO_INCREMENT PRIMARY KEY,
        saleID INT NOT NULL,
        type ENUM('Trip', 'Merchandise') NOT NULL,
        tripID INT NULL,
        merchandiseID INT NULL,
        itemQuantity INT NOT NULL,
        itemAmount FLOAT NOT NULL,
        totalAmountPerLineOfSale FLOAT NOT NULL DEFAULT 0,
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

    // Statistic (for storing most/least popular routes per date)
    "CREATE TABLE IF NOT EXISTS statistic (
        statisticID INT AUTO_INCREMENT PRIMARY KEY,
        route VARCHAR(255),
        date DATE,
        time VARCHAR(20),
        passengers INT,
        cancellations INT,
        status VARCHAR(50),
        stat_month VARCHAR(12),
        generated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",

    // TopUp
    "CREATE TABLE IF NOT EXISTS topup (
        topUpID INT AUTO_INCREMENT PRIMARY KEY,
        accountID INT,
        topUpDate DATE,
        topUpTime TIME,
        topUpAmount FLOAT,
        topUpType VARCHAR(50),
        topUpStatus ENUM('completed', 'failed') DEFAULT 'failed',
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",

    // Trip Booking
    "CREATE TABLE IF NOT EXISTS trip_booking (
        bookingID INT PRIMARY KEY AUTO_INCREMENT,
        saleID INT,
        tripID INT,
        accountID INT,
        bookingStatus ENUM('Booked', 'Rescheduled', 'Cancelled'),
        originalTripID INT,
        rescheduledTripID INT,
        bookingDate DATETIME,
        refundAmount DECIMAL(10,2) DEFAULT 0,
        refundDate DATE NULL,
        refundTime TIME NULL,
        originalBookingID INT NULL,
        FOREIGN KEY (saleID) REFERENCES sale(saleID),
        FOREIGN KEY (tripID) REFERENCES trip(tripID),
        FOREIGN KEY (accountID) REFERENCES account(accountID)
    )",
];

foreach ($table_sql as $sql) {
    if ($conn->query($sql) !== TRUE) {
        // Optionally log error, but do not echo or die
        // error_log("Error creating table: " . $conn->error);
        $conn->close();
        exit;
    }
}

// Read and parse the JSON file
$jsonFile = __DIR__ . '/dummy_data.json';
if (file_exists($jsonFile)) {
    $jsonData = json_decode(file_get_contents($jsonFile), true);
    if ($jsonData === null) {
        $conn->close();
        exit;
    }

    // Insert admin records
    foreach ($jsonData['admins'] as $admin) {
        $stmt = $conn->prepare("INSERT INTO admin (adminRole, adminName, adminPhoneNumber, adminEmail, adminPassword) VALUES (?, ?, ?, ?, ?)");
        $hashedPassword = password_hash($admin['adminPassword'], PASSWORD_DEFAULT);
        $stmt->bind_param(
            "sssss",
            $admin['adminRole'],
            $admin['adminName'],
            $admin['adminPhoneNumber'],
            $admin['adminEmail'],
            $hashedPassword
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert merchandise items
    foreach ($jsonData['merchandise'] as $item) {
        $stmt = $conn->prepare("INSERT INTO merchandise (adminID, merchandiseName, merchandisePrice, merchandiseDescription, stockQuantity, quantity, merchandiseCategory, merchandiseImage) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isdsiiss",
            $item['adminID'],
            $item['merchandiseName'],
            $item['merchandisePrice'],
            $item['merchandiseDescription'],
            $item['stockQuantity'],
            $item['quantity'],
            $item['merchandiseCategory'],
            $item['merchandiseImage']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert promotions
    foreach ($jsonData['promotions'] as $promo) {
        $stmt = $conn->prepare("INSERT INTO promotion (adminID, discountRate, startDate, expireDate, promotionQuantity, promotionType) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "idssis",
            $promo['adminID'],
            $promo['discountRate'],
            $promo['startDate'],
            $promo['expireDate'],
            $promo['promotionQuantity'],
            $promo['promotionType']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert trips
    foreach ($jsonData['trips'] as $trip) {
        $stmt = $conn->prepare("INSERT INTO trip (tripDate, tripTime, origin, destination, totalAmount, tripStatus, maxSeats, availableSeats) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ssssdsii",
            $trip['tripDate'],
            $trip['tripTime'],
            $trip['origin'],
            $trip['destination'],
            $trip['totalAmount'],
            $trip['tripStatus'],
            $trip['maxSeats'],
            $trip['availableSeats']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert accounts
    foreach ($jsonData['accounts'] as $account) {
        $stmt = $conn->prepare("INSERT INTO account (accountName, phoneNumber, password, email, accountBalance, accountStatus, accountVerifyStatus) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $hashedPassword = password_hash($account['password'], PASSWORD_DEFAULT);
        $stmt->bind_param(
            "ssssdss",
            $account['accountName'],
            $account['phoneNumber'],
            $hashedPassword,
            $account['email'],
            $account['accountBalance'],
            $account['accountStatus'],
            $account['accountVerifyStatus']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert sales
    foreach ($jsonData['sales'] as $sale) {
        $stmt = $conn->prepare("INSERT INTO sale (accountID, promotionID, redemptionID, saleDate, saleTime, lineOfSaleQuantity, lineOfSaleAmount, totalAmountPay, saleStatus) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iiissddds",
            $sale['accountID'],
            $sale['promotionID'],
            $sale['redemptionID'],
            $sale['saleDate'],
            $sale['saleTime'],
            $sale['lineOfSaleQuantity'],
            $sale['lineOfSaleAmount'],
            $sale['totalAmountPay'],
            $sale['saleStatus']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert trip bookings
    foreach ($jsonData['trip_bookings'] as $booking) {
        $stmt = $conn->prepare("INSERT INTO trip_booking (saleID, tripID, accountID, bookingStatus, originalTripID, rescheduledTripID, bookingDate, refundAmount, refundDate, refundTime, originalBookingID) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iiisssdsdsi",
            $booking['saleID'],
            $booking['tripID'],
            $booking['accountID'],
            $booking['bookingStatus'],
            $booking['originalTripID'],
            $booking['rescheduledTripID'],
            $booking['bookingDate'],
            $booking['refundAmount'],
            $booking['refundDate'],
            $booking['refundTime'],
            $booking['originalBookingID']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert line of sales
    foreach ($jsonData['line_of_sales'] as $line) {
        $stmt = $conn->prepare("INSERT INTO line_of_sale (saleID, type, tripID, merchandiseID, itemQuantity, itemAmount, totalAmountPerLineOfSale) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isiiidd",
            $line['saleID'],
            $line['type'],
            $line['tripID'],
            $line['merchandiseID'],
            $line['itemQuantity'],
            $line['itemAmount'],
            $line['totalAmountPerLineOfSale']
        );
        $stmt->execute();
        $stmt->close();
    }

    // Insert feedbacks
    foreach ($jsonData['feedbacks'] as $feedback) {
        $stmt = $conn->prepare("INSERT INTO feedback (accountID, adminID, rating, comment, feedbackStatus) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iiiss",
            $feedback['accountID'],
            $feedback['adminID'],
            $feedback['rating'],
            $feedback['comment'],
            $feedback['feedbackStatus']
        );
        $stmt->execute();
        $stmt->close();
    }
} else {
    $conn->close();
    exit;
}

$conn->close();
// No echo, no die, no output
?>
