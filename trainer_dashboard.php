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
        $message = "STATUS UPDATED TO $new_status.";
    }
}

// 3. LOGIC: HANDLE PROFILE UPDATE
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
    
    $message = "PROFILE DATA UPDATED SUCCESSFULLY.";
}

// 4. GET TRAINER DETAILS & VIEW LOGIC
$trainer = $conn->query("SELECT t.*, u.name, u.phone FROM trainers t JOIN users u ON t.trainer_email = u.email WHERE t.trainer_email = '$email'")->fetch_assoc();
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';

?>

<!DOCTYPE html>
<html>
<head>
    <title>Trainer Dashboard - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* TRAINER DASHBOARD OVERRIDES */
        body { 
            display: block; 
            padding: 0; 
            /* Background Inherited (Dark Asphalt) */
        }
        
        /* Safety Yellow Navbar */
        .dashboard-header {
            background: #FFD700; 
            padding: 15px 30px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border-bottom: 5px solid #b39700;
            margin-bottom: 30px;
        }

        .dashboard-header h2 {
            margin: 0;
            margin-right: 30px;
            border: none;
            padding: 0;
            color: #1A1A1A;
            font-size: 24px;
        }

        .nav-links a {
            text-decoration: none; 
            color: #1A1A1A; 
            margin-right: 20px; 
            font-weight: 800;
            text-transform: uppercase;
            padding-bottom: 5px;
        }
        
        .nav-links a:hover, .nav-links a.active { 
            border-bottom: 3px solid #1A1A1A; 
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* White Content Cards */
        .card {
            background: white; 
            padding: 25px; 
            border-top: 5px solid #1A1A1A; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }

        .card h3 {
            margin-top: 0;
            color: #1A1A1A;
            text-transform: uppercase;
            font-weight: 800;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Status Box Specifics */
        .status-form {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 0;
            width: 100%;
            box-shadow: none;
            border: none;
            background: transparent;
        }

        .status-form select {
            width: auto;
            min-width: 200px;
            margin: 0;
            border: 2px solid #1A1A1A;
            font-weight: bold;
        }

        .status-form button {
            margin: 0;
            width: auto;
            padding: 12px 25px;
        }

        /* Industrial Table */
        table { 
            width: 100%; 
            background: white; 
            border-collapse: collapse; 
            margin-top: 20px; 
            border: 1px solid #1A1A1A;
        }
        
        th, td { 
            padding: 15px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
            color: #1A1A1A;
        }
        
        th { 
            background: #1A1A1A; 
            color: #FFD700; 
            text-transform: uppercase;
            font-size: 14px;
        }

        /* Status Indicators */
        .status-dot {
            height: 12px; width: 12px;
            display: inline-block;
            border-radius: 50%;
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <div class="dashboard-header">
        <div style="display:flex; align-items:center;">
            <h2>🛠️ Trainer Panel</h2>
            <div class="nav-links">
                <a href="?view=dashboard" class="<?= $view=='dashboard'?'active':'' ?>">Schedule</a>
                <a href="?view=profile" class="<?= $view=='profile'?'active':'' ?>">My Profile</a>
            </div>
        </div>
        <div>
            <span style="margin-right:15px; color:#1A1A1A; font-weight:bold; text-transform:uppercase;">
                ID: <?= htmlspecialchars($trainer['name']); ?>
            </span>
            <a href="logout.php" style="color:#d32f2f; font-weight:900; text-decoration:none; text-transform:uppercase;">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if($message): ?>
            <div style="background:#28a745; color:white; padding:15px; margin-bottom:20px; text-align:center; font-weight:bold; text-transform:uppercase; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if($view == 'dashboard'): ?>
            
            <div class="card" style="border-top-color: #FFD700;">
                <h3 style="border-bottom:none; margin-bottom:10px;">Current Status</h3>
                <form method="POST" class="status-form">
                    <select name="status">
                        <option value="AVAILABLE" <?= $trainer['availability'] == 'AVAILABLE' ? 'selected' : ''; ?>>🟢 Available</option>
                        <option value="BOOKED" <?= $trainer['availability'] == 'BOOKED' ? 'selected' : ''; ?>>🔴 Busy / Booked</option>
                        <option value="OFFLINE" <?= $trainer['availability'] == 'OFFLINE' ? 'selected' : ''; ?>>⚫ Offline</option>
                    </select>
                    <button type="submit" name="update_status">Update Status</button>
                </form>
            </div>

            <div class="card">
                <h3>📅 Upcoming Sessions</h3>
                <?php
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
                                    <div style="font-weight:900; font-size:1.1em;"><?= date('M d, Y', strtotime($row['session_start'])); ?></div>
                                    <small style="color:#555; font-weight:bold;">
                                        <?= date('h:i A', strtotime($row['session_start'])); ?> - 
                                        <?= date('h:i A', strtotime($row['session_end'])); ?>
                                    </small>
                                </td>
                                <td style="font-weight:600;"><?= htmlspecialchars($row['renter_name']); ?></td>
                                <td><?= $row['phone'] ? $row['phone'] : '<span style="color:#888;">N/A</span>'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="margin-top:20px; color:#555; font-style:italic;">No upcoming sessions scheduled.</p>
                <?php endif; ?>
            </div>

        <?php elseif($view == 'profile'): ?>
            
            <div class="card" style="max-width:600px; margin:0 auto;">
                <h3>Edit Trainer Profile</h3>
                <form method="POST" action="?view=profile" style="padding:0; width:auto; box-shadow:none; border:none; border-top:none;">
                    <label>Full Name:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($trainer['name']); ?>" required>

                    <label>Expertise (e.g. Crane Operator):</label>
                    <input type="text" name="expertise" value="<?= htmlspecialchars($trainer['expertise']); ?>" required>

                    <label>Phone Number:</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($trainer['phone']); ?>" required>

                    <label style="margin-top:20px; color:#d32f2f;">Change Password (Optional):</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current">

                    <button type="submit" name="update_profile" style="width:100%;">Save Changes</button>
                </form>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>