<?php
class Trip {
    public $tripID;
    public $accountID;
    public $tripDate;
    public $tripTime;
    public $origin;
    public $destination;
    public $totalAmount;
    public $tripStatus;

    public function generateTicketQR() {}
    public function bookTrip() {}
    public function rescheduleTrip() {}
    public function cancelTrip() {}
    public function refund() {}
    public function enableVoicePrompt() {}
    public function updateTripStatus() {}
    public function checkSeatAvailability() {}
    public function confirmTrip() {}
    public function confirmCancellation() {}
}
?>
