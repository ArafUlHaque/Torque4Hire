<?php
session_start();
include 'db_connect.php';

//security check for only LOGGEDIN REMTERS
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    die("Access Denied.");
}

$message = "";
$machine = null;

//machin details fetch
if (isset($_GET['id'])) {
    $machine_id = $_GET['id'];
    
    $sql = "SELECT m.*, c.category_name, o.company_name 
            FROM machinery m
            JOIN machine_categories c ON m.category_id = c.category_id
            JOIN owners o ON m.owner_email = o.owner_email
            WHERE m.machine_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();
    $machine = $stmt->get_result()->fetch_assoc();
}

//rent form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $renter_email = $_SESSION['user_email'];
    $machine_id = $_POST['machine_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $daily_rate = $_POST['daily_rate'];

    //rental duration calc(inside joke)
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    //check if end date after start
    if ($end < $start) {
        $message = "Error: End date cannot be before start date!";
    } 
    else {
        // Calculate days and total cost
        $interval = $start->diff($end);
        $days = $interval->days + 1; // +1 because renting Jan 1 to Jan 1 is 1 day
        $total_cost = $days * $daily_rate;

        // Insert into Database
        $insert_sql = "INSERT INTO rentals (renter_email, machine_id, start_date, end_date, total_cost, rental_status) 
                       VALUES (?, ?, ?, ?, ?, 'REQUESTED')";
        
        $stmt2 = $conn->prepare($insert_sql);
        $stmt2->bind_param("sissd", $renter_email, $machine_id, $start_date, $end_date, $total_cost);

        if ($stmt2->execute()) {
            $message = "Success! Rental Requested. Total: $" . $total_cost;
            // Mark machine as RENTED
            $conn->query("UPDATE machinery SET status = 'RENTED' WHERE machine_id = $machine_id");
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rent Machine</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Add some specific styling for the receipt box */
        .machine-info {
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            margin-bottom: 20px;
            border-left: 5px solid #007bff;
        }
    </style>
</head>
<body>

<form method="POST" action="">
    <h2 style="margin-top: 0;">Confirm Rental</h2>
    
    <?php if($message): ?>
        <p style="color: <?php echo strpos($message, 'Error') !== false ? 'red' : 'green'; ?>; text-align: center; font-weight: bold;">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <?php if ($machine) { ?>
        <div class="machine-info">
            <h3 style="margin-top:0"><?php echo htmlspecialchars($machine['model_name']); ?></h3>
            <p>Category: <b><?php echo htmlspecialchars($machine['category_name']); ?></b></p>
            <p>Owner: <?php echo htmlspecialchars($machine['company_name']); ?></p>
            <p>Rate: <b style="color:green;">$<?php echo $machine['daily_rate']; ?> / day</b></p>
        </div>

        <input type="hidden" name="machine_id" value="<?php echo $machine['machine_id']; ?>">
        <input type="hidden" name="daily_rate" value="<?php echo $machine['daily_rate']; ?>">

        <label>Start Date:</label>
        <input type="date" name="start_date" required>

        <label>End Date:</label>
        <input type="date" name="end_date" required>

        <button type="submit">Confirm & Request</button>
        <a href="view_machinery.php" style="text-align:center; display:block; margin-top:10px;">Cancel</a>

    <?php } else { ?>
        <p style="text-align:center; color:red;">Invalid Machine Selected.</p>
        <a href="view_machinery.php" style="text-align:center; display:block;">Go Back</a>
    <?php } ?>
</form>

</body>
</html>