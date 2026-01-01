<?php
session_start();
include 'db_connect.php';

// 1. SECURITY
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    die("Access Denied.");
}

$message = "";
$trainer_email = isset($_GET['trainer']) ? $_GET['trainer'] : "";
$trainer_name = "";

// 2. GET TRAINER NAME (For display)
if ($trainer_email) {
    $sql = "SELECT name FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $trainer_email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $trainer_name = $row['name'];
    }
}

// 3. HANDLE BOOKING SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $renter_email = $_SESSION['user_email'];
    $t_email = $_POST['trainer_email'];
    $date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Combine Date + Time into MySQL DATETIME format (YYYY-MM-DD HH:MM:SS)
    $start_datetime = $date . ' ' . $start_time . ':00';
    $end_datetime = $date . ' ' . $end_time . ':00';

    // Basic Validation
    if (strtotime($end_datetime) <= strtotime($start_datetime)) {
        $message = "Error: End time must be after start time.";
    } else {
        $stmt = $conn->prepare("INSERT INTO training_sessions (trainer_email, renter_email, session_start, session_end) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $t_email, $renter_email, $start_datetime, $end_datetime);

        if ($stmt->execute()) {
            $message = "Success! Session Booked with " . htmlspecialchars($trainer_name);
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Training</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <form method="POST" action="">
        <h2 style="margin-top: 0;">Book Training Session</h2>
        
        <?php if($message): ?>
            <p style="text-align:center; font-weight:bold; color: <?php echo strpos($message, 'Error') !== false ? 'red' : 'green'; ?>;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <?php if ($trainer_name) { ?>
            <div style="background:#f8f9fa; padding:15px; border-left:4px solid #007bff; margin-bottom:20px;">
                <p style="margin:0;">Trainer: <b><?php echo htmlspecialchars($trainer_name); ?></b></p>
                <p style="margin:5px 0 0 0; font-size:14px; color:#666;"><?php echo $trainer_email; ?></p>
            </div>

            <input type="hidden" name="trainer_email" value="<?php echo $trainer_email; ?>">

            <label>Select Date:</label>
            <input type="date" name="session_date" required min="<?php echo date('Y-m-d'); ?>">

            <div style="display:flex; gap:10px;">
                <div style="flex:1;">
                    <label>Start Time:</label>
                    <input type="time" name="start_time" required>
                </div>
                <div style="flex:1;">
                    <label>End Time:</label>
                    <input type="time" name="end_time" required>
                </div>
            </div>

            <button type="submit">Confirm Booking</button>
            <a href="view_trainers.php" style="margin-top:15px; display:block; text-align:center;">Cancel</a>

        <?php } else { ?>
            <p style="color:red; text-align:center;">Invalid Trainer Selected.</p>
            <a href="view_trainers.php">Go Back</a>
        <?php } ?>
    </form>

</body>
</html>