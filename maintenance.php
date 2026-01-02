<?php
session_start();
include 'db_connect.php';

// 1. SECURITY: Owners Only
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'OWNER') {
    die("Access Denied.");
}

$message = "";

// 2. HANDLE "START MAINTENANCE"
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_maintenance'])) {
    $m_id = $_POST['machine_id'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date']; // Expected end date
    $desc = $_POST['description'];

    $conn->begin_transaction();
    try {
        // A. Insert into Maintenance Table
        // Use a subquery to generate a new maintenance_id for this specific machine
        $id_check = $conn->query("SELECT MAX(maintenance_id) as max_id FROM maintenance WHERE machine_id = $m_id");
        $next_id = $id_check->fetch_assoc()['max_id'] + 1;

        $stmt = $conn->prepare("INSERT INTO maintenance (machine_id, maintenance_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $m_id, $next_id, $start, $end);
        $stmt->execute();

        // B. Mark Machine as Unavailable
        $conn->query("UPDATE machinery SET status = 'MAINTENANCE' WHERE machine_id = $m_id");

        $conn->commit();
        header("Location: owner_dashboard.php?msg=Machine marked as Under Maintenance");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// 3. HANDLE "FINISH MAINTENANCE" (Called via URL link)
if (isset($_GET['action']) && $_GET['action'] == 'finish' && isset($_GET['id'])) {
    $m_id = $_GET['id'];
    
    // Set status back to AVAILABLE
    if($conn->query("UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = $m_id")) {
        header("Location: owner_dashboard.php?msg=Machine is now Available!");
        exit();
    } else {
        $message = "Error updating status.";
    }
}

// 4. FETCH MACHINE DETAILS (For the Form)
$machine = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $machine = $conn->query("SELECT * FROM machinery WHERE machine_id = $id AND owner_email = '{$_SESSION['user_email']}'")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Report</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form method="POST" action="">
        <h2 style="margin-top:0;">Report Maintenance</h2>
        <?php if($message) echo "<p style='color:red;'>$message</p>"; ?>

        <?php if($machine): ?>
            <p>Machine: <b><?php echo htmlspecialchars($machine['model_name']); ?></b></p>
            
            <input type="hidden" name="machine_id" value="<?php echo $machine['machine_id']; ?>">
            
            <label>Start Date:</label>
            <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
            
            <label>Expected Finish Date:</label>
            <input type="date" name="end_date" required>

            <label>Issue Description:</label>
            <input type="text" name="description" placeholder="e.g. Engine Overheating" required>

            <button type="submit" name="start_maintenance" style="background:#ffc107; color:black;">Mark as Maintenance</button>
            <a href="owner_dashboard.php" style="display:block; text-align:center; margin-top:10px;">Cancel</a>
        
        <?php else: ?>
            <p>Invalid Machine.</p>
        <?php endif; ?>
    </form>
</body>
</html>