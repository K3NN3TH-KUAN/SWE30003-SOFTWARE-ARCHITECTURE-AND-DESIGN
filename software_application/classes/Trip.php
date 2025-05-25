<?php
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE trip SET tripStatus = :status WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':tripID', $tripID);
        return $stmt->execute();
    }

    public function updateAvailableSeats($tripID, $seatsToBook) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        // First get current available seats
        $trip = $this->getTripByID($tripID);
        if (!$trip) return false;
        
        $newAvailableSeats = $trip['availableSeats'] - $seatsToBook;
        if ($newAvailableSeats < 0) return false;
        
        $sql = "UPDATE trip SET availableSeats = :seats WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':seats', $newAvailableSeats);
        $stmt->bindParam(':tripID', $tripID);
        
        if ($stmt->execute()) {
            // If no seats left, update status to Booked
            if ($newAvailableSeats == 0) {
                $this->updateTripStatus($tripID, 'Booked');
            }
            return true;
        }
        return false;
    }

    public function rescheduleTrip($tripID, $newDate, $newTime) {
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "UPDATE trip SET tripStatus = 'Cancelled' WHERE tripID = :tripID";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':tripID', $tripID);
        return $stmt->execute();
    }
        
    public function createTripGroup($trips) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Generate a unique group ID (using timestamp)
            $tripGroupID = time();
            
            // Create multiple trips with the same group ID
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
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
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "INSERT INTO trip_booking (saleID, tripID, accountID, bookingStatus, bookingDate) 
                VALUES (:saleID, :tripID, :accountID, 'Confirmed', NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':saleID', $saleID);
        $stmt->bindParam(':tripID', $tripID);
        $stmt->bindParam(':accountID', $accountID);
        return $stmt->execute();
    }

    public function rescheduleBooking($bookingID, $newTripID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Get the original booking
            $sql = "SELECT * FROM trip_booking WHERE bookingID = :bookingID";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':bookingID', $bookingID);
            $stmt->execute();
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Update the booking status and new trip
            $sql = "UPDATE trip_booking 
                    SET bookingStatus = 'Rescheduled', 
                        rescheduledTripID = :newTripID 
                    WHERE bookingID = :bookingID";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':newTripID', $newTripID);
            $stmt->bindParam(':bookingID', $bookingID);
            $stmt->execute();
            
            // Create notification for the user
            $notification = new Notification();
            $notification->createNotification(
                $booking['accountID'],
                "Your trip has been rescheduled. Please check your booking details.",
                'reschedule'
            );
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function getBookingDetails($bookingID) {
        require_once __DIR__ . '/Database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $sql = "SELECT b.*, t.origin, t.destination, t.tripDate, t.tripTime, 
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
        require_once __DIR__ . '/Database.php';
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
}
?>
