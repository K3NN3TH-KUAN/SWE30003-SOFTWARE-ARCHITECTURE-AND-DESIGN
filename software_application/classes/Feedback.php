<?php
class Feedback {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public $feedbackID;
    public $accountID;
    public $adminID;
    public $rating;
    public $comment;
    public $feedbackStatus;

    public function newFeedback() {}
    public function updateFeedbackStatus() {}
    public function submitFeedback() {}
    public function collectFeedback() {}
    public function storeFeedbackDetails() {}
    public function reviewFeedback() {}
}
?>
