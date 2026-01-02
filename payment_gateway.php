<?php
// 1. SILENT SESSION START
// Only start a session if one isn't already active. This fixes the "Notice" error.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php';

// 2. SECURITY CHECK
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    die("Access Denied.");
}

$message = "";
$rental_id = isset($_GET['rental_id']) ? $_GET['rental_id'] : 0;
$amount = isset($_GET['amount']) ? $_GET['amount'] : 0;

// 3. HANDLE PAYMENT
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $r_id = $_POST['rental_id'];
    $amt = $_POST['amount'];
    $method = "Credit Card"; 

    // Generate Payment ID
    $id_query = $conn->query("SELECT MAX(payment_id) as max_id FROM payments WHERE rental_id = $r_id");
    $row = $id_query->fetch_assoc();
    $next_id = $row['max_id'] + 1;

    $conn->begin_transaction();
    try {
        // A. Insert Payment Record
        $stmt = $conn->prepare("INSERT INTO payments (rental_id, payment_id, amount, method, status) VALUES (?, ?, ?, ?, 'COMPLETED')");
        $stmt->bind_param("iids", $r_id, $next_id, $amt, $method);
        $stmt->execute();

        // B. Update Rental Status
        $stmt2 = $conn->prepare("UPDATE rentals SET rental_status = 'CONFIRMED' WHERE rental_id = ?");
        $stmt2->bind_param("i", $r_id);
        $stmt2->execute();

        $conn->commit();
        
        // Success Redirect
        header("Location: renter_dashboard.php?view=rentals&msg=Payment Successful! Machine Confirmed.");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Payment Failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure Checkout - Torque4Hire</title>
    <style>
        body { 
            background-color: #f4f7f6; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0;
        }
        
        .payment-card { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            width: 380px; 
            text-align: center;
        }

        h2 { color: #333; margin-bottom: 5px; }
        
        .amount-display { 
            font-size: 36px; 
            color: #28a745; 
            font-weight: bold; 
            margin: 15px 0 25px 0; 
        }

        /* Form Layout */
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 6px; 
            font-size: 16px; 
            margin-bottom: 15px;
            box-sizing: border-box; /* Fixes width issues */
            transition: border 0.2s;
        }

        input:focus {
            border-color: #28a745;
            outline: none;
        }

        .row {
            display: flex;
            gap: 15px;
        }

        button { 
            width: 100%; 
            background: #28a745; 
            color: white; 
            padding: 14px; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: bold;
            cursor: pointer; 
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(40, 167, 69, 0.2);
        }
        
        button:hover { background: #218838; }

        .cancel-link {
            display: block; 
            margin-top: 20px; 
            color: #999; 
            text-decoration: none;
            font-size: 14px;
        }
        .cancel-link:hover { color: #666; }
    </style>
</head>
<body>

    <div class="payment-card">
        <h2>Secure Checkout</h2>
        <p style="color:#888; margin:0;">Rental ID: #<?php echo $rental_id; ?></p>
        
        <div class="amount-display">$<?php echo number_format($amount, 2); ?></div>
        
        <?php if($message) echo "<p style='color:red; background:#ffecec; padding:10px; border-radius:4px;'>$message</p>"; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="rental_id" value="<?php echo $rental_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">

            <label>Card Number</label>
            <input type="text" placeholder="0000 0000 0000 0000" maxlength="19" required>

            <div class="row">
                <div style="flex: 1;">
                    <label>Expiry Date</label>
                    <input type="text" placeholder="MM/YY" maxlength="5" required>
                </div>
                <div style="flex: 1;">
                    <label>CVV</label>
                    <input type="password" placeholder="123" maxlength="3" required>
                </div>
            </div>

            <button type="submit">Pay Now</button>
            <a href="renter_dashboard.php?view=rentals" class="cancel-link">Cancel Payment</a>
        </form>
    </div>

</body>
</html>