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
            alert('PAYMENT AUTHORIZED: $$real_amount. TRANSACTION COMPLETE.');
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
       
        .price-box {
            background: #1A1A1A;
            color: #FFD700;
            padding: 15px;
            text-align: center;
            font-size: 24px;
            font-weight: 800;
            margin: 15px 0;
            border: 2px solid #FFD700;
            text-transform: uppercase;
        }

        .ref-text {
            text-align: center;
            color: #666;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .card-row {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

    <form method="POST">
        <h2>💳 Checkout</h2>
        
        <div class="ref-text">Ref ID: #<?php echo $rental_id; ?></div>
        
        <div class="price-box">
            $<?php echo number_format($real_amount, 2); ?>
        </div>

        <label>Cardholder Name</label>
        <input type="text" placeholder="NAME ON CARD" required style="text-transform:uppercase;">
        
        <label>Card Number</label>
        <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" required>
        
        <div class="card-row">
            <div style="flex:1">
                <label>Expiry</label>
                <input type="text" placeholder="MM/YY" maxlength="5" required style="text-align:center;">
            </div>
            <div style="flex:1">
                <label>CVV</label>
                <input type="text" placeholder="123" maxlength="3" required style="text-align:center;">
            </div>
        </div>

        <button type="submit" name="process_payment">AUTHORIZE PAYMENT</button>
        
        <a href="renter_dashboard.php?view=rentals" style="color:#d32f2f;">CANCEL TRANSACTION</a>
    </form>

</body>
</html>