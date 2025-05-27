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
        saleStatus ENUM('Completed', 'Incompleted') DEFAULT 'Incompleted',
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
        FOREIGN KEY (saleID) REFERENCES sale(saleID),
        FOREIGN KEY (tripID) REFERENCES trip(tripID),
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

// Insert 8 dummy merchandise items for Kuching ART
$dummyMerchandise = [
    [
        'merchandiseName' => 'ART Kuching T-shirt',
        'merchandisePrice' => 39.90,
        'merchandiseDescription' => 'Official ART Kuching branded cotton T-shirt. Comfortable and stylish for daily wear.',
        'stockQuantity' => 100,
        'quantity' => 0,
        'merchandiseCategory' => 'Apparel',
        'merchandiseImage' => 'tshirt.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Cap',
        'merchandisePrice' => 25.00,
        'merchandiseDescription' => 'Keep cool and show your support with this ART Kuching cap.',
        'stockQuantity' => 80,
        'quantity' => 0,
        'merchandiseCategory' => 'Apparel',
        'merchandiseImage' => 'cap.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Water Bottle',
        'merchandisePrice' => 18.50,
        'merchandiseDescription' => 'Stay hydrated with this eco-friendly ART Kuching water bottle.',
        'stockQuantity' => 120,
        'quantity' => 0,
        'merchandiseCategory' => 'Accessories',
        'merchandiseImage' => 'bottle.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Lanyard',
        'merchandisePrice' => 8.00,
        'merchandiseDescription' => 'Perfect for your ART card or keys. Durable and stylish.',
        'stockQuantity' => 200,
        'quantity' => 0,
        'merchandiseCategory' => 'Accessories',
        'merchandiseImage' => 'lanyard.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Tote Bag',
        'merchandisePrice' => 15.00,
        'merchandiseDescription' => 'Reusable tote bag with ART Kuching logo. Great for shopping or daily use.',
        'stockQuantity' => 90,
        'quantity' => 0,
        'merchandiseCategory' => 'Bags',
        'merchandiseImage' => 'totebag.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Keychain',
        'merchandisePrice' => 5.00,
        'merchandiseDescription' => 'Cute ART Kuching train keychain for your keys or bag.',
        'stockQuantity' => 300,
        'quantity' => 0,
        'merchandiseCategory' => 'Souvenir',
        'merchandiseImage' => 'keychain.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Mug',
        'merchandisePrice' => 22.00,
        'merchandiseDescription' => 'Enjoy your drinks in this exclusive ART Kuching mug.',
        'stockQuantity' => 60,
        'quantity' => 0,
        'merchandiseCategory' => 'Homeware',
        'merchandiseImage' => 'mug.png'
    ],
    [
        'merchandiseName' => 'ART Kuching Mini Model',
        'merchandisePrice' => 49.90,
        'merchandiseDescription' => 'Collectible mini model of the ART Kuching train.',
        'stockQuantity' => 40,
        'quantity' => 0,
        'merchandiseCategory' => 'Collectibles',
        'merchandiseImage' => 'model.png'
    ]
];

foreach ($dummyMerchandise as $item) {
    $stmt = $conn->prepare("INSERT INTO merchandise (adminID, merchandiseName, merchandisePrice, merchandiseDescription, stockQuantity, quantity, merchandiseCategory, merchandiseImage) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sdsiiss",
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

// After your merchandise dummy data, add this:
$dummyPromotions = [
    [
        'discountRate' => 10,
        'startDate' => '2024-03-01',
        'expireDate' => '2026-06-30',
        'promotionQuantity' => 10,
        'promotionType' => 'Promotion'
    ],
    [
        'discountRate' => 15,
        'startDate' => '2024-03-01',
        'expireDate' => '2026-05-31',
        'promotionQuantity' => 10,
        'promotionType' => 'Voucher'
    ],
    [
        'discountRate' => 20,
        'startDate' => '2024-03-01',
        'expireDate' => '2026-07-31',
        'promotionQuantity' => 10,
        'promotionType' => 'Promotion'
    ],
    [
        'discountRate' => 25,
        'startDate' => '2024-03-01',
        'expireDate' => '2026-08-31',
        'promotionQuantity' => 10,
        'promotionType' => 'Voucher'
    ],
    [
        'discountRate' => 30,
        'startDate' => '2024-03-01',
        'expireDate' => '2026-09-30',
        'promotionQuantity' => 10,
        'promotionType' => 'Promotion'
    ],
    [
        'discountRate' => 35,
        'startDate' => '2024-03-01',
        'expireDate' => '2026-10-30',
        'promotionQuantity' => 10,
        'promotionType' => 'Voucher'
    ],
];

// Insert dummy promotions
foreach ($dummyPromotions as $promo) {
    $stmt = $conn->prepare("INSERT INTO promotion (adminID, discountRate, startDate, expireDate, promotionQuantity, promotionType) 
                           VALUES (NULL, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "dssis",
        $promo['discountRate'],
        $promo['startDate'],
        $promo['expireDate'],
        $promo['promotionQuantity'],
        $promo['promotionType']
    );
    $stmt->execute();
    $stmt->close();
}

$dummyTrips = [
    [
        'tripDate' => '2025-07-01',
        'tripTime' => '08:00:00',
        'origin' => 'Kuching Sentral',
        'destination' => 'Pending',
        'totalAmount' => 5.00,
        'tripStatus' => 'Available',
        'maxSeats' => 40,
        'availableSeats' => 40
    ],
    [
        'tripDate' => '2025-07-01',
        'tripTime' => '09:00:00',
        'origin' => 'Pending',
        'destination' => 'Kuching Sentral',
        'totalAmount' => 5.00,
        'tripStatus' => 'Available',
        'maxSeats' => 35,
        'availableSeats' => 35
    ],
    [
        'tripDate' => '2025-07-01',
        'tripTime' => '10:00:00',
        'origin' => 'Kuching Sentral',
        'destination' => 'Samarahan',
        'totalAmount' => 7.00,
        'tripStatus' => 'Available',
        'maxSeats' => 30,
        'availableSeats' => 30
    ],
    [
        'tripDate' => '2025-07-01',
        'tripTime' => '11:00:00',
        'origin' => 'Samarahan',
        'destination' => 'Kuching Sentral',
        'totalAmount' => 7.00,
        'tripStatus' => 'Available',
        'maxSeats' => 25,
        'availableSeats' => 25
    ],
    [
        'tripDate' => '2025-07-02',
        'tripTime' => '08:30:00',
        'origin' => 'Kuching Sentral',
        'destination' => 'Pending',
        'totalAmount' => 5.00,
        'tripStatus' => 'Available',
        'maxSeats' => 40,
        'availableSeats' => 40
    ],
    [
        'tripDate' => '2025-07-02',
        'tripTime' => '09:30:00',
        'origin' => 'Pending',
        'destination' => 'Kuching Sentral',
        'totalAmount' => 5.00,
        'tripStatus' => 'Available',
        'maxSeats' => 38,
        'availableSeats' => 38
    ],
    [
        'tripDate' => '2025-07-02',
        'tripTime' => '10:30:00',
        'origin' => 'Kuching Sentral',
        'destination' => 'Samarahan',
        'totalAmount' => 7.00,
        'tripStatus' => 'Available',
        'maxSeats' => 36,
        'availableSeats' => 36
    ],
    [
        'tripDate' => '2025-07-02',
        'tripTime' => '11:30:00',
        'origin' => 'Samarahan',
        'destination' => 'Kuching Sentral',
        'totalAmount' => 7.00,
        'tripStatus' => 'Available',
        'maxSeats' => 34,
        'availableSeats' => 34
    ],
    [
        'tripDate' => '2025-07-03',
        'tripTime' => '08:00:00',
        'origin' => 'Kuching Sentral',
        'destination' => 'Pending',
        'totalAmount' => 5.00,
        'tripStatus' => 'Available',
        'maxSeats' => 40,
        'availableSeats' => 40
    ],
    [
        'tripDate' => '2025-07-03',
        'tripTime' => '09:00:00',
        'origin' => 'Pending',
        'destination' => 'Kuching Sentral',
        'totalAmount' => 5.00,
        'tripStatus' => 'Available',
        'maxSeats' => 40,
        'availableSeats' => 40
    ],
];

foreach ($dummyTrips as $trip) {
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

$conn->close();
echo "Setup complete.";
?>
