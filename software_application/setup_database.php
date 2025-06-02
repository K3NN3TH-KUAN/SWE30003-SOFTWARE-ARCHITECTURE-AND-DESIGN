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
    if ($conn->query($sql) === TRUE) {
        echo "Table created or already exists.<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Add this at the beginning of your file after the database connection
$jsonData = json_decode(file_get_contents('dummy_data.json'), true);

// Then replace your existing data arrays with the JSON data
$dummyMerchandise = $jsonData['merchandise'];
$dummyPromotions = $jsonData['promotions'];
$dummyTrips = $jsonData['trips'];
$adminData = $jsonData['admins'];
$accountData = $jsonData['accounts'];

// First, insert admin records
foreach ($adminData as $admin) {
    $stmt = $conn->prepare("INSERT INTO admin (adminRole, adminName, adminPhoneNumber, adminEmail, adminPassword) VALUES (?, ?, ?, ?, ?)");
    $hashedPassword = password_hash('admin', PASSWORD_DEFAULT);
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

// Then insert merchandise items
foreach ($dummyMerchandise as $item) {
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

// Insert dummy promotions
foreach ($dummyPromotions as $promo) {
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

// For accounts, you'll need to hash the passwords before inserting
foreach ($accountData as $account) {
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

// Insert sales data
$salesData = $jsonData['sales'];
foreach ($salesData as $sale) {
    $promotionID = $sale['promotionID'] ?? null;
    $redemptionID = $sale['redemptionID'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO sale (accountID, promotionID, redemptionID, saleDate, saleTime, lineOfSaleQuantity, lineOfSaleAmount, totalAmountPay, saleStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "iiissddds",
        $sale['accountID'],
        $promotionID,
        $redemptionID,
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
$tripBookings = $jsonData['trip_bookings'];
foreach ($tripBookings as $booking) {
    $originalTripID = $booking['originalTripID'] ?? null;
    $rescheduledTripID = $booking['rescheduledTripID'] ?? null;
    $refundDate = $booking['refundDate'] ?? null;
    $refundTime = $booking['refundTime'] ?? null;
    $originalBookingID = $booking['originalBookingID'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO trip_booking (saleID, tripID, accountID, bookingStatus, originalTripID, rescheduledTripID, bookingDate, refundAmount, refundDate, refundTime, originalBookingID) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "iiisssdsdsi",
        $booking['saleID'],
        $booking['tripID'],
        $booking['accountID'],
        $booking['bookingStatus'],
        $originalTripID,
        $rescheduledTripID,
        $booking['bookingDate'],
        $booking['refundAmount'],
        $refundDate,
        $refundTime,
        $originalBookingID
    );
    $stmt->execute();
    $stmt->close();
}

// Update trip available seats
foreach ($tripBookings as $booking) {
    $stmt = $conn->prepare("UPDATE trip SET availableSeats = availableSeats - 1 WHERE tripID = ?");
    $stmt->bind_param("i", $booking['tripID']);
    $stmt->execute();
    $stmt->close();
}

// Insert line of sales
$lineOfSales = $jsonData['line_of_sales'];
foreach ($lineOfSales as $line) {
    $merchandiseID = $line['merchandiseID'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO line_of_sale (saleID, type, tripID, merchandiseID, itemQuantity, itemAmount, totalAmountPerLineOfSale) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isiiidd",
        $line['saleID'],
        $line['type'],
        $line['tripID'],
        $merchandiseID,
        $line['itemQuantity'],
        $line['itemAmount'],
        $line['totalAmountPerLineOfSale']
    );
    $stmt->execute();
    $stmt->close();
}

// Insert feedback data
$feedbacks = $jsonData['feedbacks'];
foreach ($feedbacks as $feedback) {
    $adminID = $feedback['adminID'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO feedback (accountID, adminID, rating, comment, feedbackStatus) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "iiiss",
        $feedback['accountID'],
        $adminID,
        $feedback['rating'],
        $feedback['comment'],
        $feedback['feedbackStatus']
    );
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo "Setup complete.";
?>
