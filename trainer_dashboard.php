<?php
session_start();
include 'db_connect.php';

// 1. SECURITY: Trainers Only
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'TRAINER') {
    die("ACCESS DENIED. TRAINERS ONLY.");
}

$email = $_SESSION['user_email'];
$message = "";

// 2. HANDLE STATUS UPDATE (Restoring the lost trainer feature)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE trainers SET availability = ? WHERE trainer_email = ?");
    $stmt->bind_param("ss", $new_status, $email);
    if ($stmt->execute()) {
        $message = "Your status has been updated to $new_status.";
    }
}

// 3. GET TRAINER DETAILS
$trainer = $conn->query("SELECT * FROM trainers WHERE trainer_email = '$email'")->fetch_assoc();

// 4. GET UPCOMING SESSIONS
// We use a JOIN to show the Renter's Name instead of just their email
$sql = "SELECT b.*, u.name as renter_name, u.phone 
        FROM bookings b
        JOIN users u ON b.renter_email = u.email
        WHERE b.trainer_email = ? 
        ORDER BY b.start_time ASC";
$stmt_sess = $conn->prepare($sql);
$stmt_sess->bind_param("s", $email);
$stmt_sess->execute();
$sessions = $stmt_sess->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trainer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; padding: 20px; background: #f4f4f9; }
        .dashboard-header {
            background: white; padding: 20px; border-radius: 8px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .status-box {
            background: white; padding: 20px; border-radius: 8px;
            display: inline-block; min-width: 350px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <div>
            <h2 style="margin:0;">Trainer Dashboard</h2>
            <p>Welcome, <b><?php echo $_SESSION['user_name']; ?></b> (<?php echo htmlspecialchars($trainer['expertise']); ?>)</p>
        </div>
        <a href="logout.php" style="color:red; font-weight:bold;">Logout</a>
    </div>

    <div class="status-box">
        <form method="POST">
            <label><b>Update My Availability:</b></label>
            <div style="display:flex; gap:10px; margin-top:10px;">
                <select name="status">
                    <option value="AVAILABLE" <?php if($trainer['availability'] == 'AVAILABLE') echo 'selected'; ?>>🟢 Available</option>
                    <option value="BOOKED" <?php if($trainer['availability'] == 'BOOKED') echo 'selected'; ?>>🔴 Busy</option>
                    <option value="OFFLINE" <?php if($trainer['availability'] == 'OFFLINE') echo 'selected'; ?>>⚪ Offline</option>
                </select>
                <button type="submit" name="update_status">Update Status</button>
            </div>
            <?php if($message) echo "<p style='color:green; font-weight:bold;'>$message</p>"; ?>
        </form>
    </div>

    <h3>📅 My Training Schedule</h3>
    <table>
        <thead>
            <tr>
                <th>Time Slot</th>
                <th>Student Name</th>
                <th>Contact info</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $sessions->fetch_assoc()): ?>
            <tr>
                <td>
                    <b><?php echo date('M d', strtotime($row['start_time'])); ?></b><br>
                    <?php echo date('h:i A', strtotime($row['start_time'])); ?> - <?php echo date('h:i A', strtotime($row['end_time'])); ?>
                </td>
                <td><?php echo htmlspecialchars($row['renter_name']); ?></td>
                <td><?php echo $row['phone'] ? $row['phone'] : $row['renter_email']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</body>
</html>