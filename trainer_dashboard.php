<?php
session_start();
include 'db_connect.php';

// 1. SECURITY: Trainers Only
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'TRAINER') {
    die("ACCESS DENIED. TRAINERS ONLY.");
}

$email = $_SESSION['user_email'];
$message = "";

// 2. LOGIC: HANDLE STATUS UPDATE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE trainers SET availability = ? WHERE trainer_email = ?");
    $stmt->bind_param("ss", $new_status, $email);
    if ($stmt->execute()) {
        $message = "Status updated to $new_status.";
    }
}

// 3. LOGIC: HANDLE PROFILE UPDATE (New Feature)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $expert = $_POST['expertise'];
    $pass = $_POST['password'];

    // Update Users Table (Name, Phone, Password)
    $stmt_u = $conn->prepare("UPDATE users SET name=?, phone=? WHERE email=?");
    $stmt_u->bind_param("sss", $name, $phone, $email);
    $stmt_u->execute();

    // Update Trainers Table (Expertise)
    $stmt_t = $conn->prepare("UPDATE trainers SET expertise=? WHERE trainer_email=?");
    $stmt_t->bind_param("ss", $expert, $email);
    $stmt_t->execute();

    // Update Password if provided
    if (!empty($pass)) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt_p = $conn->prepare("UPDATE users SET password_hash=? WHERE email=?");
        $stmt_p->bind_param("ss", $hashed, $email);
        $stmt_p->execute();
    }
    
    $message = "Profile Updated Successfully!";
}

// 4. GET TRAINER DETAILS & VIEW LOGIC
$trainer = $conn->query("SELECT t.*, u.name, u.phone FROM trainers t JOIN users u ON t.trainer_email = u.email WHERE t.trainer_email = '$email'")->fetch_assoc();
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Trainer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; padding: 20px; background: #f4f4f9; font-family: sans-serif; }
        
        /* Navbar Styling */
        .dashboard-header {
            background: white; padding: 15px 30px; border-radius: 8px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 25px;
        }
        .nav-links a {
            text-decoration: none; color: #555; margin-right: 20px; font-weight: 500;
            padding-bottom: 5px;
        }
        .nav-links a.active { color: #007bff; border-bottom: 2px solid #007bff; }
        .nav-links a:hover { color: #007bff; }

        /* Status Box */
        .status-box {
            background: white; padding: 20px; border-radius: 8px;
            display: inline-block; min-width: 350px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Table */
        table { width: 100%; background: white; border-collapse: collapse; margin-top: 20px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #007bff; color: white; }
        
        /* Forms */
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px; margin: 8px 0 15px;
            border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        button { cursor: pointer; padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 4px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <div style="display:flex; align-items:center;">
            <h2 style="margin:0; margin-right: 30px;">Trainer Dashboard</h2>
            <div class="nav-links">
                <a href="?view=dashboard" class="<?= $view=='dashboard'?'active':'' ?>">📅 Schedule</a>
                <a href="?view=profile" class="<?= $view=='profile'?'active':'' ?>">👤 My Profile</a>
            </div>
        </div>
        <div>
            <span style="margin-right:15px; color:#666;">Hi, <b><?= htmlspecialchars($trainer['name']); ?></b></span>
            <a href="logout.php" style="color:red; font-weight:bold; text-decoration:none;">Logout</a>
        </div>
    </div>

    <?php if($message): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:6px; margin-bottom:20px; text-align:center;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if($view == 'dashboard'): ?>
        
        <div class="status-box">
            <form method="POST">
                <label><b>Current Availability:</b></label>
                <div style="display:flex; gap:10px; margin-top:10px;">
                    <select name="status" style="padding:8px; border-radius:4px; border:1px solid #ddd;">
                        <option value="AVAILABLE" <?= $trainer['availability'] == 'AVAILABLE' ? 'selected' : ''; ?>>🟢 Available</option>
                        <option value="BOOKED" <?= $trainer['availability'] == 'BOOKED' ? 'selected' : ''; ?>>🔴 Busy / Booked</option>
                        <option value="OFFLINE" <?= $trainer['availability'] == 'OFFLINE' ? 'selected' : ''; ?>>⚪ Offline</option>
                    </select>
                    <button type="submit" name="update_status">Update</button>
                </div>
            </form>
        </div>

        <h3 style="margin-top:30px;">📆 Upcoming Sessions</h3>
        <?php
            // FIXED: Changed table from 'bookings' to 'training_sessions' to match your DB
            $sql = "SELECT ts.*, u.name as renter_name, u.phone 
                    FROM training_sessions ts
                    JOIN users u ON ts.renter_email = u.email
                    WHERE ts.trainer_email = ? 
                    ORDER BY ts.session_start ASC";
            $stmt_sess = $conn->prepare($sql);
            $stmt_sess->bind_param("s", $email);
            $stmt_sess->execute();
            $sessions = $stmt_sess->get_result();
        ?>

        <?php if($sessions->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Student Name</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $sessions->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="font-weight:bold;"><?= date('M d, Y', strtotime($row['session_start'])); ?></div>
                            <small style="color:#666;">
                                <?= date('h:i A', strtotime($row['session_start'])); ?> - 
                                <?= date('h:i A', strtotime($row['session_end'])); ?>
                            </small>
                        </td>
                        <td><?= htmlspecialchars($row['renter_name']); ?></td>
                        <td><?= $row['phone'] ? $row['phone'] : 'No phone listed'; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="margin-top:20px; color:#666;">No upcoming sessions found.</p>
        <?php endif; ?>

    <?php elseif($view == 'profile'): ?>
        
        <div class="status-box" style="display:block; max-width:600px; margin:0 auto;">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Edit Profile</h3>
            <form method="POST" action="?view=profile">
                <label>Full Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($trainer['name']); ?>" required>

                <label>Expertise (e.g. Crane Operator):</label>
                <input type="text" name="expertise" value="<?= htmlspecialchars($trainer['expertise']); ?>" required>

                <label>Phone Number:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($trainer['phone']); ?>" required>

                <label style="margin-top:10px; color:#dc3545;">Change Password:</label>
                <input type="password" name="password" placeholder="Leave blank to keep current password">

                <button type="submit" name="update_profile" style="width:100%; margin-top:10px;">Save Changes</button>
            </form>
        </div>

    <?php endif; ?>

</body>
</html>