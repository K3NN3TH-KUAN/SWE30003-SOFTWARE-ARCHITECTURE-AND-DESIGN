<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/LineOfSale.php';

class Trip {
    private $tripID;
    private $origin;
    private $destination;
    private $tripDate;
    private $tripTime;
    private $totalAmount;
    private $tripStatus;
    private $maxSeats;
    private $availableSeats;
    private $tripGroupID;

    public function getTripByID($tripID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT tripID, origin, destination, tripDate, tripTime, totalAmount, 
                tripStatus, maxSeats, availableSeats, tripGroupID 
                FROM trip WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTripsByDateAndTime($date, $time) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT * FROM trip WHERE tripDate = :date AND tripTime = :time AND tripStatus != 'Cancelled'";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time', $time);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createNewTrip($origin, $destination, $tripDate, $tripTime, $totalAmount, $maxSeats, $tripGroupID = null) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "INSERT INTO trip (origin, destination, tripDate, tripTime, totalAmount, maxSeats, availableSeats, tripGroupID) 
                VALUES (:origin, :destination, :tripDate, :tripTime, :totalAmount, :maxSeats, :maxSeats, :tripGroupID)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':origin', $origin);
        $stmt->bindParam(':destination', $destination);
        $stmt->bindParam(':tripDate', $tripDate);
        $stmt->bindParam(':tripTime', $tripTime);
        $stmt->bindParam(':totalAmount', $totalAmount);
        $stmt->bindParam(':maxSeats', $maxSeats);
        $stmt->bindParam(':tripGroupID', $tripGroupID);
        
        return $stmt->execute();
    }

    public function updateTripStatus($tripID, $status) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE trip SET tripStatus = :status WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':tripID', $tripID);
        return $stmt->execute();
    }

    public function updateAvailableSeats($tripID, $seatsToBook) {
        $database = new Database();
        $db = $database->getConnection();
        
        $trip = $this->getTripByID($tripID);
        if (!$trip) return false;
        
        $newAvailableSeats = $trip['availableSeats'] - $seatsToBook;
        if ($newAvailableSeats < 0) return false;
        
        $sql = "UPDATE trip SET availableSeats = :seats WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':seats', $newAvailableSeats);
        $stmt->bindParam(':tripID', $tripID);
        
        if ($stmt->execute()) {
            if ($newAvailableSeats == 0) {
                $this->updateTripStatus($tripID, 'Booked');
            }
            return true;
        }
        return false;
    }

    public function rescheduleTrip($tripID, $newDate, $newTime) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE trip SET tripDate = :newDate, tripTime = :newTime, tripStatus = 'Rescheduled' 
                WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':newDate', $newDate);
        $stmt->bindParam(':newTime', $newTime);
        $stmt->bindParam(':tripID', $tripID);
        return $stmt->execute();
    }

    public function cancelTrip($tripID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE trip SET tripStatus = 'Cancelled' WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tripID', $tripID);
        return $stmt->execute();
    }
        
    public function createTripGroup($trips) {
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            $tripGroupID = time();
            
            foreach ($trips as $trip) {
                $this->createNewTrip(
                    $trip['origin'],
                    $trip['destination'],
                    $trip['tripDate'],
                    $trip['tripTime'],
                    $trip['totalAmount'],
                    $trip['maxSeats'],
                    $tripGroupID
                );
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function getTripPurchasers($tripID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT 
                    a.accountID,
                    a.accountName,
                    a.email,
                    s.saleID,
                    s.saleDate,
                    s.saleTime,
                    los.itemQuantity as seatsBooked,
                    s.totalAmountPay
                FROM trip t
                JOIN line_of_sale los ON t.tripID = los.tripID
                JOIN sale s ON los.saleID = s.saleID
                JOIN account a ON s.accountID = a.accountID
                WHERE t.tripID = :tripID
                ORDER BY s.saleDate DESC, s.saleTime DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTripSalesSummary($tripID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT 
                    COUNT(DISTINCT s.saleID) as totalBookings,
                    SUM(los.itemQuantity) as totalSeatsBooked,
                    SUM(s.totalAmountPay) as totalRevenue
                FROM trip t
                JOIN line_of_sale los ON t.tripID = los.tripID
                JOIN sale s ON los.saleID = s.saleID
                WHERE t.tripID = :tripID";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchTrips($date = null, $time = null, $origin = null, $destination = null) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT * FROM trip WHERE tripStatus = 'Available' AND availableSeats > 0";
        $params = [];
        
        if ($date) {
            $sql .= " AND tripDate = :date";
            $params[':date'] = $date;
        }
        if ($time) {
            $sql .= " AND tripTime >= :time";
            $params[':time'] = $time;
        }
        if ($origin) {
            $sql .= " AND origin = :origin";
            $params[':origin'] = $origin;
        }
        if ($destination) {
            $sql .= " AND destination = :destination";
            $params[':destination'] = $destination;
        }
        
        $sql .= " ORDER BY tripDate, tripTime";
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableTrips() {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT * FROM trip 
                WHERE tripStatus = 'Available' 
                AND availableSeats > 0 
                AND tripDate >= CURDATE()
                ORDER BY tripDate, tripTime";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createBooking($saleID, $tripID, $accountID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "INSERT INTO trip_booking (saleID, tripID, accountID, bookingStatus, bookingDate) 
                VALUES (:saleID, :tripID, :accountID, 'Booked', NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':saleID', $saleID);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->bindParam(':accountID', $accountID);
        return $stmt->execute();
    }

    public function rescheduleBooking($bookingID, $newTripID) {
        $database = new Database();
        $db = $database->getConnection();

        try {
            $db->beginTransaction();

            // 1. Get the original booking, sale, and trip info
            $stmt = $db->prepare("SELECT * FROM trip_booking WHERE bookingID = ?");
            $stmt->execute([$bookingID]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$booking) throw new Exception("Booking not found");

            $oldSaleID = $booking['saleID'];
            $accountID = $booking['accountID'];

            // Get seats and price from old line_of_sale
            $stmt = $db->prepare("SELECT * FROM line_of_sale WHERE saleID = ? AND tripID = ?");
            $stmt->execute([$oldSaleID, $booking['tripID']]);
            $oldLine = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldLine) throw new Exception("Original line_of_sale not found");

            $seats = $oldLine['itemQuantity'];
            $price = $oldLine['itemAmount'];
            $total = $oldLine['totalAmountPerLineOfSale'];

            // 2. Create a new sale for the rescheduled trip
            $stmt = $db->prepare("INSERT INTO sale (accountID, saleDate, saleTime, lineOfSaleQuantity, lineOfSaleAmount, totalAmountPay, saleStatus) VALUES (?, CURDATE(), CURTIME(), ?, ?, ?, 'Completed')");
            $stmt->execute([$accountID, $seats, $price, $total]);
            $newSaleID = $db->lastInsertId();

            // 3. Create a new line_of_sale for the new trip
            $stmt = $db->prepare("INSERT INTO line_of_sale (saleID, type, tripID, itemQuantity, itemAmount, totalAmountPerLineOfSale) VALUES (?, 'Trip', ?, ?, ?, ?)");
            $stmt->execute([$newSaleID, $newTripID, $seats, $price, $total]);

            // 4. Update the old sale status to 'Rescheduled'
            $stmt = $db->prepare("UPDATE sale SET saleStatus = 'Rescheduled' WHERE saleID = ?");
            $stmt->execute([$oldSaleID]);

            // 5. Update the old booking to 'Rescheduled'
            $stmt = $db->prepare("UPDATE trip_booking SET bookingStatus = 'Rescheduled', rescheduledTripID = ? WHERE bookingID = ?");
            $stmt->execute([$newTripID, $bookingID]);

            // 6. Create a new booking for the new trip, link to original booking
            $stmt = $db->prepare("INSERT INTO trip_booking (saleID, tripID, accountID, bookingStatus, originalTripID, rescheduledTripID, bookingDate, originalBookingID) VALUES (?, ?, ?, 'Booked', ?, NULL, NOW(), ?)");
            $stmt->execute([$newSaleID, $newTripID, $accountID, $booking['tripID'], $bookingID]);

            // --- ADD THIS: Update availableSeats for both trips ---
            $stmt = $db->prepare("UPDATE trip SET availableSeats = availableSeats + ? WHERE tripID = ?");
            $stmt->execute([$seats, $booking['tripID']]); // Increase seats in old trip

            $stmt = $db->prepare("UPDATE trip SET availableSeats = availableSeats - ? WHERE tripID = ?");
            $stmt->execute([$seats, $newTripID]); // Decrease seats in new trip
            // --- END ADD ---

            $db->commit();

            // Optionally, create a notification for the user here

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log("RESCHEDULE ERROR: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function getBookingDetails($bookingID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT b.bookingID, b.saleID, b.tripID, 
                    b.rescheduledTripID, b.accountID, 
                    b.bookingStatus, b.bookingDate,
                    b.refundAmount, b.refundDate, b.refundTime,
                    t.origin, t.destination, t.tripDate, t.tripTime, 
                    a.accountName, a.email
                FROM trip_booking b
                JOIN trip t ON b.tripID = t.tripID
                JOIN account a ON b.accountID = a.accountID
                WHERE b.bookingID = :bookingID";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':bookingID', $bookingID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserBookings($accountID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT b.*, t.origin, t.destination, t.tripDate, t.tripTime
                FROM trip_booking b
                JOIN trip t ON b.tripID = t.tripID
                WHERE b.accountID = :accountID
                ORDER BY t.tripDate DESC, t.tripTime DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':accountID', $accountID);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBookingBySaleID($saleID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT b.*, t.origin, t.destination, t.tripDate, t.tripTime
                FROM trip_booking b
                JOIN trip t ON b.tripID = t.tripID
                WHERE b.saleID = :saleID";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':saleID', $saleID);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cancelBooking($bookingID) {
        $database = new Database();
        $db = $database->getConnection();

        try {
            // Fetch booking details before starting transaction
            $booking = $this->getBookingDetails($bookingID);
            if (!$booking) {
                throw new Exception("Booking not found: $bookingID");
            }

            if ($booking['bookingStatus'] === 'Cancelled') {
                throw new Exception("Booking already cancelled.");
            }

            $saleID = $booking['saleID'];
            $tripID = $booking['tripID'];

            // Fetch sale/account info before transaction
            $saleStmt = $db->prepare("SELECT * FROM sale WHERE saleID = ?");
            $saleStmt->execute([$saleID]);
            $sale = $saleStmt->fetch(PDO::FETCH_ASSOC);
            if (!$sale) {
                throw new Exception("Sale not found for booking: $bookingID");
            }
            $accountID = $sale['accountID'];

            // Calculate refund amount before transaction
            $refundAmount = 0;
            
            // 1. Try to get the exact amount paid for the trip from line_of_sale
            $tripLineStmt = $db->prepare("SELECT totalAmountPerLineOfSale FROM line_of_sale WHERE saleID = ? AND tripID = ? AND type = 'Trip' LIMIT 1");
            $tripLineStmt->execute([$saleID, $tripID]);
            $tripLine = $tripLineStmt->fetch(PDO::FETCH_ASSOC);
            $refundAmount = $tripLine ? floatval($tripLine['totalAmountPerLineOfSale']) : 0;

            // 2. Fallback: If not found, use the sale's totalAmountPay if this was a trip-only sale
            if ($refundAmount <= 0 && floatval($sale['lineOfSaleQuantity']) == 1) {
                $refundAmount = floatval($sale['totalAmountPay']);
            }

            // 3. Never refund more than was paid for the sale
            if ($refundAmount > floatval($sale['totalAmountPay'])) {
                $refundAmount = floatval($sale['totalAmountPay']);
            }

            if ($refundAmount <= 0) {
                throw new Exception("Refund amount is zero or negative. Please check line_of_sale data and trip price.");
            }

            // Get seats booked before transaction
            $seatsBooked = $this->getTripSeatsForSale($db, $saleID, $tripID);

            // Start transaction for critical updates only
            $db->beginTransaction();

            // 1. Update booking status
            $updateBooking = $db->prepare(
                "UPDATE trip_booking 
                 SET bookingStatus = 'Cancelled', 
                     refundAmount = ?, 
                     refundDate = CURDATE(), 
                     refundTime = CURTIME() 
                 WHERE bookingID = ?"
            );
            $updateBooking->execute([$refundAmount, $bookingID]);

            // 2. Release seats
            $updateSeats = $db->prepare("UPDATE trip SET availableSeats = availableSeats + ? WHERE tripID = ?");
            $updateSeats->execute([$seatsBooked, $tripID]);

            // 3. Refund amount to account balance
            $updateAccount = $db->prepare("UPDATE account SET accountBalance = accountBalance + ? WHERE accountID = ?");
            $updateAccount->execute([$refundAmount, $accountID]);

            $db->commit();

            // Create notification after transaction is committed
            $notification = new Notification($db);
            $notification->createNotification(
                $accountID,
                "Your trip booking #$bookingID has been cancelled. RM" . number_format($refundAmount, 2) . " has been refunded to your account.",
                'booking'
            );

            return true;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("CANCEL BOOKING ERROR: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    private function getTripAmountForSale($db, $saleID, $tripID) {
        $sql = "SELECT COALESCE(SUM(totalAmountPerLineOfSale), 0) as amount 
                FROM line_of_sale 
                WHERE saleID = :saleID 
                AND tripID = :tripID
                AND type = 'Trip'";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':saleID', $saleID);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['amount'] ?? 0;
    }

    private function getTripSeatsForSale($db, $saleID, $tripID) {
        $sql = "SELECT COALESCE(SUM(itemQuantity), 0) as seats 
                FROM line_of_sale 
                WHERE saleID = :saleID 
                AND tripID = :tripID
                AND type = 'Trip'";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':saleID', $saleID);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['seats'] ?? 0;
    }

    private function getSeatsBookedForBooking($bookingID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT SUM(los.itemQuantity) as seats 
                FROM line_of_sale los
                JOIN trip_booking tb ON los.saleID = tb.saleID
                WHERE tb.bookingID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$bookingID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['seats'] ?? 0;
    }

    public function getAvailableTripsForReschedule($currentTripID) {
        $database = new Database();
        $db = $database->getConnection();
        
        $currentTrip = $this->getTripByID($currentTripID);
        if (!$currentTrip) return [];
        
        $sql = "SELECT * FROM trip 
                WHERE tripID != ?
                AND origin = ?
                AND destination = ?
                AND tripDate >= CURDATE()
                AND availableSeats > 0
                AND tripStatus = 'Available'
                ORDER BY tripDate, tripTime";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $currentTripID,
            $currentTrip['origin'],
            $currentTrip['destination']
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
