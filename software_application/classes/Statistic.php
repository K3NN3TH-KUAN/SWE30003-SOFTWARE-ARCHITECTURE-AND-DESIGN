<?php
class Statistic {
    public $statisticID;
    public $adminID;
    public $mostPopularTrip;
    public $leastPopularTrip;
    public $creationDate;
    public $creationTime;
    public $totalAccount;
    public $ticketSales;
    public $tripCancellation;

    public function generateReport() {}
    public function summariseTripPopularity() {}
    public function generateStatsVisualisation() {}
    public function getPurchasedHistory() {}
    public function getCancellationHistory() {}
    public function analysePurchasedTrend() {}
    public function analyseTripCancellation() {}
}
?>
