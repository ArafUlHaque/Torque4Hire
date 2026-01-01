<?php
session_start();
include 'db_connect.php';

// 1. SECURITY CHECK (Trainers Only)
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'TRAINER') {
    die("ACCESS DENIED. TRAINERS ONLY.");
}

$email = $_SESSION['user_email'];
$message = "";

// 2. HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE trainers SET availability = ? WHERE trainer_email = ?");
    $stmt->bind_param("ss", $new_status, $email);
    if ($stmt->execute()) {
        $message = "Status updated to " . $new_status;
    }
}

// 3. GET TRAINER DETAILS
$trainer = $conn->query("SELECT * FROM trainers WHERE trainer_email = '$email'")->fetch_assoc();

// 4. GET UPCOMING SESSIONS
// Join 'training_sessions' with 'users' to get the Renter's Name
$sql = "SELECT s.*, u.name as renter_name, u.phone 
        FROM training_sessions s
        JOIN users u ON s.renter_email = u.email
        WHERE s.trainer_email = ? 
        ORDER BY s.session_start ASC";
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
        body { display: block; padding: 20px; }
        .dashboard-header {
            background: white; padding: 20px; border-radius: 8px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px;
        }
        .status-box {
            background: #e9ecef; padding: 15px; border-radius: 8px;
            display: inline-block; min-width: 300px;
        }
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <div>
            <h2 style="margin:0;">Trainer Dashboard</h2>
            <p style="margin:5px 0 0 0; color:#666;">Welcome, <b><?php echo $_SESSION['user_name']; ?></b></p>
            <p style="margin:0; font-size:14px; color:#007bff;"><?php echo htmlspecialchars($trainer['expertise']); ?></p>
        </div>
        <a href="logout.php" style="color:red; font-weight:bold;">Logout</a>
    </div>

    <div class="status-box">
        <form method="POST" style="padding:0; box-shadow:none; width:auto; background:transparent;">
            <label><b>Current Availability:</b></label>
            <div style="display:flex; gap:10px;">
                <select name="status" style="margin:0;">
                    <option value="AVAILABLE" <?php if($trainer['availability'] == 'AVAILABLE') echo 'selected'; ?>>🟢 Available</option>
                    <option value="BOOKED" <?php if($trainer['availability'] == 'BOOKED') echo 'selected'; ?>>🔴 Booked / Busy</option>
                    <option value="OFFLINE" <?php if($trainer['availability'] == 'OFFLINE') echo 'selected'; ?>>⚪ Offline (On Leave)</option>
                </select>
                <button type="submit" name="update_status" style="margin:0; width:100px;">Update</button>
            </div>
            <?php if($message) echo "<p style='color:green; margin-top:5px;'>$message</p>"; ?>
        </form>
    </div>

    <h3>📅 Upcoming Training Sessions</h3>
    
    <?php if ($sessions->num_rows > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Student (Renter)</th>
                    <th>Contact</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $sessions->fetch_assoc()) { ?>
                <tr>
                    <td>
                        <?php echo date('M d, Y', strtotime($row['session_start'])); ?><br>
                        <small><?php echo date('h:i A', strtotime($row['session_start'])); ?> - <?php echo date('h:i A', strtotime($row['session_end'])); ?></small>
                    </td>
                    <td><b><?php echo htmlspecialchars($row['renter_name']); ?></b></td>
                    <td><?php echo $row['phone'] ? $row['phone'] : $row['renter_email']; ?></td>
                    <td><span style="background:#d4edda; color:#155724; padding:2px 6px; border-radius:4px; font-size:12px;">CONFIRMED</span></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p style="background:white; padding:20px; border-radius:8px;">No upcoming sessions found.</p>
    <?php } ?>

</body>
</html>