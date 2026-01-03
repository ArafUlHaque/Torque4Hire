<?php
// 1. SESSION & DATABASE (Fixed to prevent the Notice error)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

// 2. SECURITY: Renters Only
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    header("Location: login.php");
    exit();
}

$renter_email = $_SESSION['user_email'];
$message = "";
if(isset($_GET['msg'])) $message = $_GET['msg']; // Catching messages from payment_gateway.php

// 3. LOGIC HANDLERS (POST & GET Requests)

// A. Handle Rental Submission (From Machinery View)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_rent'])) {
    $machine_id = $_POST['machine_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $daily_rate = $_POST['daily_rate'];

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    if ($end < $start) {
        $message = "Error: End date cannot be before start date!";
    } else {
        $interval = $start->diff($end);
        $days = $interval->days + 1; 
        $total_cost = $days * $daily_rate;

        // Transaction to ensure both rental and machine status update
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO rentals (renter_email, machine_id, start_date, end_date, total_cost, rental_status) VALUES (?, ?, ?, ?, ?, 'REQUESTED')");
            $stmt->bind_param("sissd", $renter_email, $machine_id, $start_date, $end_date, $total_cost);
            $stmt->execute();

            $conn->query("UPDATE machinery SET status = 'RENTED' WHERE machine_id = $machine_id");
            $conn->commit();
            $message = "Rental Requested Successfully! Total Estimated Cost: $" . $total_cost;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// B. Handle Return Machine (WITH PENALTY CHECK LOGIC)
if (isset($_GET['action']) && $_GET['action'] == 'return' && isset($_GET['rental_id'])) {
    $rental_id = $_GET['rental_id'];
    $machine_id = $_GET['machine_id'];

    $conn->begin_transaction();
    try {
        $q = $conn->query("SELECT r.end_date, m.daily_rate FROM rentals r JOIN machinery m ON r.machine_id = m.machine_id WHERE r.rental_id = $rental_id");
        $rental = $q->fetch_assoc();
        
        $end_date = new DateTime($rental['end_date']);
        $today = new DateTime(); 
        $end_date->setTime(0, 0, 0);
        $today->setTime(0, 0, 0);
        
        $penalty_msg = "";
        if ($today > $end_date) {
            $interval = $end_date->diff($today);
            $late_days = $interval->days;
            $penalty_amount = $late_days * $rental['daily_rate'];
            $reason = "Returned " . $late_days . " days late";
            $p_stmt = $conn->prepare("INSERT INTO penalties (rental_id, penalty_amount, reason, penalty_status) VALUES (?, ?, ?, 'PENDING')");
            $p_stmt->bind_param("ids", $rental_id, $penalty_amount, $reason);
            $p_stmt->execute();
            $penalty_msg = " Note: Late fee of $$penalty_amount applied.";
        }

        $conn->query("UPDATE rentals SET rental_status = 'COMPLETED' WHERE rental_id = $rental_id");
        
        $conn->query("UPDATE machinery SET rental_count = rental_count + 1 WHERE machine_id = $machine_id");
        $check = $conn->query("SELECT rental_count FROM machinery WHERE machine_id = $machine_id")->fetch_assoc();
        
        if ($check['rental_count'] >= 5) {
            $conn->query("UPDATE machinery SET status = 'SERVICE_REQUIRED' WHERE machine_id = $machine_id");
            $penalty_msg .= " Machine flagged for scheduled maintenance.";
        } else {
            $conn->query("UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = $machine_id");
        }

        $conn->commit();
        $message = "Machine Returned Successfully!" . $penalty_msg;
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// C. Handle Trainer Booking (HOURLY REALISM & INSTANT LICENSE)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_trainer'])) {
    $trainer_email = $_POST['trainer_email'];
    $session_date = $_POST['session_date'];
    $start_time = $session_date . " " . $_POST['start_time'];
    $end_time = $session_date . " " . $_POST['end_time'];

    // 1. Conflict Check: Trainer double-booking
    $check_sql = "SELECT * FROM training_sessions WHERE trainer_email = ? AND ((session_start < ? AND session_end > ?))";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("sss", $trainer_email, $end_time, $start_time);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $message = "Error: This trainer is already booked for this time slot!";
    } else {
        // 2. Insert Booking
        $sql_book = "INSERT INTO training_sessions (trainer_email, renter_email, session_start, session_end) VALUES (?, ?, ?, ?)";
        $stmt_book = $conn->prepare($sql_book);
        $stmt_book->bind_param("ssss", $trainer_email, $renter_email, $start_time, $end_time);

        if ($stmt_book->execute()) {
            // 3. Instant License: Updating the 'renters' table
            $generated_license = "HM-" . rand(100000, 999999);
            $update_sql = "UPDATE renters SET license_no = ? WHERE renter_email = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("ss", $generated_license, $renter_email);
            if ($stmt_update->execute()) {
                $message = "Success! Training Booked. Your new License: $generated_license. Machinery unlocked!";
            }
        }
    }
}

// D. HANDLE PROFILE UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = $_POST['password'];

    // Update Basic Info
    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE email=?");
    $stmt->bind_param("ssss", $name, $phone, $address, $renter_email);
    $stmt->execute();

    // Update Password (only if provided)
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt_pw = $conn->prepare("UPDATE users SET password_hash=? WHERE email=?");
        $stmt_pw->bind_param("ss", $hashed, $renter_email);
        $stmt_pw->execute();
    }
    
    $message = "Profile Updated Successfully!";
}

// 4. DATA FETCHING FOR VIEWS
$view = isset($_GET['view']) ? $_GET['view'] : 'machines';

// Check if user is qualified
$check_q = $conn->query("SELECT license_no FROM renters WHERE renter_email = '$renter_email'");
$r_info = $check_q->fetch_assoc();
$is_qualified = !empty($r_info['license_no']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Renter Dashboard - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; padding: 0; background: #121212; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #1f1f1f; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; }
        .nav-tabs a { color: #888; text-decoration: none; margin-left: 20px; font-weight: 500; padding: 5px 0; }
        .nav-tabs a.active { color: #007bff; border-bottom: 2px solid #007bff; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .grid-box { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: #1f1f1f; padding: 20px; border-radius: 8px; border: 1px solid #333; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        table { width: 100%; border-collapse: collapse; background: #1f1f1f; margin-top: 20px; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; border-bottom: 1px solid #333; text-align: left; }
        th { background: #2d2d2d; color: #007bff; }
        .btn { padding: 10px 15px; border-radius: 4px; text-decoration: none; display: inline-block; font-weight: bold; text-align: center; border: none; cursor: pointer; }
        .btn-blue { background: #007bff; color: white; }
        .btn-yellow { background: #ffc107; color: black; }
        .btn-red { background: #dc3545; color: white; }
        .btn-green { background: #28a745; color: white; }
        .license-badge { background: #007bff; color: white; padding: 5px 10px; border-radius: 4px; font-size: 14px; margin-left: 10px; }
    </style>

    <script>
        // HOURLY BOOKING VALIDATION
        function restrictEndTime() {
            const startInput = document.getElementById('start_time');
            const endInput = document.getElementById('end_time');
            if (startInput.value) {
                let [hours, minutes] = startInput.value.split(':');
                let minHour = parseInt(hours) + 1;
                let minEndTime = (minHour < 10 ? '0' + minHour : minHour) + ':' + minutes;
                endInput.min = minEndTime;
                if (endInput.value < minEndTime) { endInput.value = minEndTime; }
            }
        }
    </script>
</head>
<body>

    <div class="nav-tabs">
    <a href="?view=machines">🚜 Browse</a>
    <a href="?view=rentals">📜 History</a>
    <a href="?view=trainers">👨‍🏫 Trainers</a>
    <a href="?view=profile" class="<?php echo $view=='profile'?'active':''; ?>">👤 Profile</a>
    
    <a href="logout.php" style="color:#ff6b6b;">Logout</a>
</div>

    <div class="container">
        <?php if($message): ?>
            <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <b><?= $message ?></b>
            </div>
        <?php endif; ?>

        <?php if($view == 'machines'):
            $today = date('Y-m-d'); 
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sql = "SELECT m.*, c.category_name, o.company_name FROM machinery m 
                    JOIN machine_categories c ON m.category_id = c.category_id 
                    JOIN owners o ON m.owner_email = o.owner_email 
                    WHERE m.status = 'AVAILABLE' AND (m.expected_return_date IS NULL OR m.expected_return_date <= '$today')";
                    
            if ($search) $sql .= " AND (m.model_name LIKE '%$search%' OR c.category_name LIKE '%$search%')";
            $machines = $conn->query($sql);
        ?>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3>Available Machinery</h3>
                <form method="GET" style="display:flex; gap:10px; background:transparent; padding:0; border:none; box-shadow:none; width:auto;">
                    <input type="hidden" name="view" value="machines">
                    <input type="text" name="search" placeholder="Search machines..." value="<?= htmlspecialchars($search) ?>" style="padding:8px; border-radius:4px; background:#2d2d2d; color:white; border:1px solid #444;">
                    <button type="submit" class="btn btn-blue" style="padding:8px 15px;">🔍</button>
                </form>
            </div>
            <div class="grid-box">
                <?php while($row = $machines->fetch_assoc()): ?>
                    <div class="card">
                        <small style="color:#007bff; font-weight:bold;"><?= htmlspecialchars($row['category_name']) ?></small>
                        <h4 style="margin:10px 0;"><?= htmlspecialchars($row['model_name']) ?></h4>
                        <p style="font-size:14px; color:#aaa;">Provider: <?= htmlspecialchars($row['company_name']) ?></p>
                        <h3 style="color:#28a745;">$<?= $row['daily_rate'] ?> <small>/ day</small></h3>
                        <?php if($is_qualified): ?>
                            <a href="?view=rent_form&id=<?= $row['machine_id'] ?>" class="btn btn-blue" style="width:90%; margin-top:10px;">Rent Now</a>
                        <?php else: ?>
                            <a href="?view=trainers" class="btn btn-yellow" style="width:90%; margin-top:10px;">Training Required</a>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php elseif($view == 'rent_form' && isset($_GET['id'])): 
            $m_id = $_GET['id'];
            $machine = $conn->query("SELECT * FROM machinery WHERE machine_id = $m_id")->fetch_assoc();
        ?>
            <div style="max-width:500px; margin:0 auto;" class="card">
                <h3>Confirm Rental: <?= htmlspecialchars($machine['model_name']) ?></h3>
                <form method="POST" action="?view=rentals">
                    <input type="hidden" name="machine_id" value="<?= $m_id ?>">
                    <input type="hidden" name="daily_rate" value="<?= $machine['daily_rate'] ?>">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>" style="width:100%; margin:10px 0; padding:10px; background:#2d2d2d; color:white; border:1px solid #444;">
                    <label>End Date:</label>
                    <input type="date" name="end_date" required min="<?= date('Y-m-d') ?>" style="width:100%; margin:10px 0; padding:10px; background:#2d2d2d; color:white; border:1px solid #444;">
                    <button type="submit" name="confirm_rent" class="btn btn-blue" style="width:100%; margin-top:20px;">Confirm Request</button>
                </form>
            </div>

        <?php elseif($view == 'rentals'): 
            $rentals = $conn->query("SELECT r.*, m.model_name, o.company_name FROM rentals r 
                                   JOIN machinery m ON r.machine_id = m.machine_id 
                                   JOIN owners o ON m.owner_email = o.owner_email 
                                   WHERE r.renter_email = '$renter_email' ORDER BY r.rental_id DESC");
        ?>
            <h3>My Rental History</h3>
            <table>
                <thead><tr><th>Machine</th><th>Dates</th><th>Cost</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($row = $rentals->fetch_assoc()): $s = $row['rental_status']; ?>
                    <tr>
                        <td><b><?= htmlspecialchars($row['model_name']) ?></b><br><small><?= htmlspecialchars($row['company_name']) ?></small></td>
                        <td><?= $row['start_date'] ?> to <?= $row['end_date'] ?></td>
                        <td>$<?= $row['total_cost'] ?></td>
                        <td><b><?= $s ?></b></td>
                        <td>
                            <?php if($s == 'PAYMENT_PENDING' || $s == 'REQUESTED'): ?>
                                <a href="payment_gateway.php?rental_id=<?php echo $row['rental_id']; ?>" 
                                style="background:#28a745; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; font-weight:bold; font-size:12px;">
                                Pay Now
                            </a>
                            <?php elseif($s == 'CONFIRMED'): ?>
                                <a href="?view=rentals&action=return&rental_id=<?php echo $row['rental_id']; ?>&machine_id=<?php echo $row['machine_id']; ?>" 
                                    onclick="return confirm('Return this machine?')" 
                                    style="background:#dc3545; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px;">
                                    Return
                                </a>
                            <?php else: ?>
                                <span style="color:#888;">Waiting...</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        <?php elseif($view == 'trainers'): 
            $trainers = $conn->query("SELECT t.*, u.name FROM trainers t JOIN users u ON t.trainer_email = u.email");
        ?>
            <h3>Expert Trainers</h3>
            <div class="grid-box">
                <?php while($row = $trainers->fetch_assoc()): ?>
                    <div class="card">
                        <h4><?= htmlspecialchars($row['name']) ?></h4>
                        <p>Expertise: <b><?= htmlspecialchars($row['expertise']) ?></b></p>
                        <p>Status: <span style="color:<?= $row['availability']=='AVAILABLE'?'#28a745':'#ffc107' ?>"><?= $row['availability'] ?></span></p>
                        <?php if($row['availability'] == 'AVAILABLE'): ?>
                            <a href="?view=book_trainer_form&email=<?= $row['trainer_email'] ?>" class="btn btn-blue" style="width:100%;">Book Session</a>
                        <?php else: ?>
                            <button disabled class="btn" style="width:100%; background:#444;">Unavailable</button>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php elseif($view == 'book_trainer_form'): ?>
            <div style="max-width:500px; margin:0 auto;" class="card">
                <h3>Book Training (1 Hour Minimum)</h3>
                <form method="POST" action="?view=trainers">
                    <input type="hidden" name="trainer_email" value="<?= $_GET['email'] ?>">
                    
                    <label>Session Date:</label>
                    <input type="date" name="session_date" required min="<?= date('Y-m-d') ?>" style="width:100%; margin-bottom:15px; padding:10px; background:#2d2d2d; color:white; border:1px solid #444;">
                    
                    <div style="display:flex; gap:10px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label>Start Time:</label>
                            <input type="time" id="start_time" name="start_time" required onchange="restrictEndTime()" style="width:100%; padding:10px; background:#2d2d2d; color:white;">
                        </div>
                        <div style="flex:1;">
                            <label>End Time:</label>
                            <input type="time" id="end_time" name="end_time" required style="width:100%; padding:10px; background:#2d2d2d; color:white;">
                        </div>
                    </div>
                    
                    <button type="submit" name="book_trainer" class="btn btn-blue" style="width:100%; padding:12px;">Confirm Training Session</button>
                </form>
            </div>

        <?php elseif($view == 'profile'): 
            // Fetch latest user data
            $user = $conn->query("SELECT * FROM users WHERE email = '$renter_email'")->fetch_assoc();
        ?>
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <h3>Edit My Profile</h3>
                <form method="POST" action="?view=profile">
                    <label>Full Name:</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required style="width:100%; padding:10px; margin-bottom:10px; background:#2d2d2d; color:white; border:1px solid #444;">

                    <label>Phone Number:</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="017..." required style="width:100%; padding:10px; margin-bottom:10px; background:#2d2d2d; color:white; border:1px solid #444;">

                    <label>Address:</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" placeholder="City, Area" style="width:100%; padding:10px; margin-bottom:10px; background:#2d2d2d; color:white; border:1px solid #444;">

                    <label style="margin-top:20px; color:#ff6b6b; display:block;">Change Password (Leave blank to keep current):</label>
                    <input type="password" name="password" placeholder="New Password" style="width:100%; padding:10px; margin-bottom:10px; background:#2d2d2d; color:white; border:1px solid #444;">

                    <button type="submit" name="update_profile" class="btn btn-blue" style="width:100%; padding:12px; margin-top:20px;">Save Changes</button>
                </form>
            </div>

        <?php endif; ?>
    </div>   
</body>
</html>