<?php
session_start();
require_once '../classes/Merchandise.php';
require_once '../classes/Database.php';
$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['accountID'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['merchandiseID'])) {
    $merchandiseID = $_POST['merchandiseID'];
    $merchandise = new Merchandise($db);
    $item = $merchandise->getMerchandiseByID($merchandiseID);

    if ($item && $item['stockQuantity'] > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if item already exists in cart
        $itemExists = false;
        foreach ($_SESSION['cart'] as &$cartItem) {
            if ($cartItem['merchandiseID'] == $merchandiseID) {
                $cartItem['quantity']++;
                $itemExists = true;
                break;
            }
        }

        // If item doesn't exist, add it to cart
        if (!$itemExists) {
            $_SESSION['cart'][] = [
                'merchandiseID' => $item['merchandiseID'],
                'merchandiseName' => $item['merchandiseName'],
                'merchandisePrice' => $item['merchandisePrice'],
                'merchandiseImage' => $item['merchandiseImage'],
                'merchandiseCategory' => $item['merchandiseCategory'],
                'quantity' => 1
            ];
        }

        header('Location: cart.php');
        exit();
    }
}

header('Location: merchandise.php');
exit();