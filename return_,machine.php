<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    die("Access Denied.");
}

if (isset($_GET['rental_id']) && isset($_GET['machine_id'])) {
    $rental_id = $_GET['rental_id'];
    $machine_id = $_GET['machine_id'];

    $conn->begin_transaction();

    try {
        $sql1 = "UPDATE rentals SET rental_status = 'COMPLETED' WHERE rental_id = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("i", $rental_id);
        $stmt1->execute();

        $sql2 = "UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $machine_id);
        $stmt2->execute();

        $conn->commit();
        
        header("Location: my_rentals.php?msg=Machine Returned Successfully!");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error returning machine: " . $e->getMessage();
    }
} else {
    echo "Invalid Request.";
}
?>