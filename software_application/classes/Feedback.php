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

    /**
     * Creates a new feedback entry (implementation needed).
     */
    public function newFeedback() {}

    /**
     * Updates the status of feedback (implementation needed).
     */
    public function updateFeedbackStatus() {}

    /**
     * Submits feedback from a user (implementation needed).
     */
    public function submitFeedback() {}

    /**
     * Collects feedback data (implementation needed).
     */
    public function collectFeedback() {}

    /**
     * Stores feedback details in the database (implementation needed).
     */
    public function storeFeedbackDetails() {}

    /**
     * Reviews feedback as an admin (implementation needed).
     */
    public function reviewFeedback() {}
}
?>
