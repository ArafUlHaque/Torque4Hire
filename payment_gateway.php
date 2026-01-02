<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['rental_id'])) {
    die("Error: No rental ID provided.");
}

$rental_id = $_GET['rental_id'];
$stmt = $conn->prepare("SELECT total_cost, rental_status FROM rentals WHERE rental_id = ? AND renter_email = ?");
$stmt->bind_param("is", $rental_id, $_SESSION['user_email']);
$stmt->execute();
$result = $stmt->get_result();
$rental = $result->fetch_assoc();

if (!$rental) {
    die("Error: Invalid rental record.");
}

$real_amount = $rental['total_cost']; 

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    
    
    $conn->begin_transaction();
    try {
        $update_stmt = $conn->prepare("UPDATE rentals SET rental_status = 'CONFIRMED' WHERE rental_id = ?");
        $update_stmt->bind_param("i", $rental_id);
        $update_stmt->execute();

        $conn->commit();
        echo "<script>
            alert('Payment of $$real_amount Successful! Your rental is now active.');
            window.location.href='renter_dashboard.php?view=rentals';
        </script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Payment Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure Payment - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; background: #121212; color: white; padding: 50px; font-family: sans-serif; }
        .payment-card { max-width: 400px; margin: 0 auto; background: #1f1f1f; padding: 30px; border-radius: 8px; border: 1px solid #333; }
        .price-display { font-size: 24px; color: #28a745; margin: 20px 0; text-align: center; font-weight: bold; }
        input { width: 100%; padding: 10px; margin: 10px 0; background: #2d2d2d; border: 1px solid #444; color: white; box-sizing: border-box; }
        .btn-pay { width: 100%; background: #28a745; color: white; padding: 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="payment-card">
        <h2 style="text-align: center;">Secure Checkout</h2>
        <p style="text-align: center; color: #888;">Rental Reference: #<?php echo $rental_id; ?></p>
        
        <div class="price-display">
            Amount Due: $<?php echo number_format($real_amount, 2); ?>
        </div>

        <form method="POST">
            <label>Cardholder Name</label>
            <input type="text" placeholder="John Doe" required>
            
            <label>Card Number</label>
            <input type="text" placeholder="1234 5678 9101 1121" maxlength="16" required>
            
            <div style="display:flex; gap:10px;">
                <input type="text" placeholder="MM/YY" maxlength="5" required>
                <input type="text" placeholder="CVV" maxlength="3" required>
            </div>

            <button type="submit" name="process_payment" class="btn-pay">Pay $<?php echo number_format($real_amount, 2); ?></button>
            <a href="renter_dashboard.php?view=rentals" style="display:block; text-align:center; color:#888; margin-top:15px; text-decoration:none;">Cancel and Go Back</a>
        </form>
    </div>

</body>
</html>