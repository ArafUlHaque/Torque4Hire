<?php
session_start();
include 'db_connect.php';


if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'OWNER') {
    die("Access Denied.");
}

$message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['start_maintenance'])) {
    $m_id = $_POST['machine_id'];
    $start = $_POST['start_date'];
    $end = $_POST['end_date']; 
    $desc = $_POST['description'];

    $conn->begin_transaction();
    try {
        $id_check = $conn->query("SELECT MAX(maintenance_id) as max_id FROM maintenance WHERE machine_id = $m_id");
        $next_id = $id_check->fetch_assoc()['max_id'] + 1;

        $stmt = $conn->prepare("INSERT INTO maintenance (machine_id, maintenance_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $m_id, $next_id, $start, $end);
        $stmt->execute();

        $conn->query("UPDATE machinery SET status = 'MAINTENANCE' WHERE machine_id = $m_id");

        $conn->commit();
        header("Location: owner_dashboard.php?msg=Machine marked as Under Maintenance");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}


if (isset($_GET['action']) && $_GET['action'] == 'finish' && isset($_GET['id'])) {
    $m_id = $_GET['id'];
    if($conn->query("UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = $m_id")) {
        header("Location: owner_dashboard.php?msg=Machine is now Available!");
        exit();
    } else {
        $message = "Error updating status.";
    }
}


$machine = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $machine = $conn->query("SELECT * FROM machinery WHERE machine_id = $id AND owner_email = '{$_SESSION['user_email']}'")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Ticket - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form method="POST" action="">
        <h2 style="color:#d32f2f; border-color:#d32f2f;">⚠️ Service Ticket</h2>
        
        <?php if($message) echo "<p style='color:red; text-align:center; font-weight:bold;'>$message</p>"; ?>

        <?php if($machine): ?>
            <p style="text-align:center; margin-bottom:20px; font-size:1.1em;">
                Unit: <b><?php echo htmlspecialchars($machine['model_name']); ?></b>
            </p>
            
            <input type="hidden" name="machine_id" value="<?php echo $machine['machine_id']; ?>">
            
            <label>Start Date:</label>
            <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
            
            <label>Est. Finish Date:</label>
            <input type="date" name="end_date" required>

            <label>Issue Description:</label>
            <input type="text" name="description" placeholder="e.g. Hydraulic Leak" required>

            <button type="submit" name="start_maintenance" style="background:#1A1A1A; color:#FFD700; border:1px solid #FFD700;">
                INITIATE MAINTENANCE
            </button>
            
            <a href="owner_dashboard.php">CANCEL REQUEST</a>
        
        <?php else: ?>
            <p style="text-align:center;">Invalid Machine ID.</p>
            <a href="owner_dashboard.php">Back to Dashboard</a>
        <?php endif; ?>
    </form>
</body>
</html>