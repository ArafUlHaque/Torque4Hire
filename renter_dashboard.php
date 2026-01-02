<?php
session_start();
include 'db_connect.php';

// 1. SECURITY: Renters Only
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    header("Location: login.php");
    exit();
}

$renter_email = $_SESSION['user_email'];
$message = "";

// 2. LOGIC HANDLERS (POST Requests)

// A. Handle Rental Submission
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

        // Transaction
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO rentals (renter_email, machine_id, start_date, end_date, total_cost, rental_status) VALUES (?, ?, ?, ?, ?, 'REQUESTED')");
            $stmt->bind_param("sissd", $renter_email, $machine_id, $start_date, $end_date, $total_cost);
            $stmt->execute();

            $conn->query("UPDATE machinery SET status = 'RENTED' WHERE machine_id = $machine_id");
            $conn->commit();
            $message = "Rental Requested Successfully! Cost: $" . $total_cost;
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// B. Handle Return Machine (WITH PENALTY CHECK)
if (isset($_GET['action']) && $_GET['action'] == 'return' && isset($_GET['rental_id'])) {
    $rental_id = $_GET['rental_id'];
    $machine_id = $_GET['machine_id'];

    $conn->begin_transaction();
    try {
        // 1. GET RENTAL DETAILS to check dates
        $q = $conn->query("SELECT r.end_date, m.daily_rate 
                           FROM rentals r 
                           JOIN machinery m ON r.machine_id = m.machine_id 
                           WHERE r.rental_id = $rental_id");
        $rental = $q->fetch_assoc();
        
        $end_date = new DateTime($rental['end_date']);
        $today = new DateTime(); // Current date/time
        
        // Reset time to midnight to compare just the dates
        $end_date->setTime(0, 0, 0);
        $today->setTime(0, 0, 0);
        
        $penalty_msg = "";

        // 2. CHECK IF LATE
        if ($today > $end_date) {
            $interval = $end_date->diff($today);
            $late_days = $interval->days;
            $penalty_amount = $late_days * $rental['daily_rate'];
            
            // Insert Penalty
            $reason = "Returned " . $late_days . " days late";
            $p_stmt = $conn->prepare("INSERT INTO penalties (rental_id, penalty_amount, reason, penalty_status) VALUES (?, ?, ?, 'PENDING')");
            $p_stmt->bind_param("ids", $rental_id, $penalty_amount, $reason);
            $p_stmt->execute();
            
            $penalty_msg = " Note: Late fee of $$penalty_amount applied ($late_days days overdue).";
        }

        // 3. COMPLETE RETURN
        $conn->query("UPDATE rentals SET rental_status = 'COMPLETED' WHERE rental_id = $rental_id");
        $conn->query("UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = $machine_id");
        
        $conn->commit();
        $message = "Machine Returned Successfully!" . $penalty_msg;
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

// C. Handle Trainer Booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_trainer'])) {
    $t_email = $_POST['trainer_email'];
    $date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    $start_dt = $date . ' ' . $start_time . ':00';
    $end_dt = $date . ' ' . $end_time . ':00';

    if (strtotime($end_dt) <= strtotime($start_dt)) {
        $message = "Error: End time must be after start time.";
    } else {
        $stmt = $conn->prepare("INSERT INTO training_sessions (trainer_email, renter_email, session_start, session_end) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $t_email, $renter_email, $start_dt, $end_dt);
        if ($stmt->execute()) {
            $message = "Session Booked Successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// 3. DETERMINE CURRENT VIEW
// Default view is 'machines'
$view = isset($_GET['view']) ? $_GET['view'] : 'machines';

// Check License Qualification
$check_q = $conn->query("SELECT license_no FROM renters WHERE renter_email = '$renter_email'");
$r_info = $check_q->fetch_assoc();
$is_qualified = !empty($r_info['license_no']);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Renter Dashboard - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; padding: 0; background: #f8f9fa; }
        
        /* Navbar */
        .navbar {
            background: #343a40; color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .nav-tabs a {
            color: #adb5bd; text-decoration: none; margin-left: 20px;
            padding-bottom: 5px; font-weight: 500;
        }
        .nav-tabs a:hover, .nav-tabs a.active {
            color: white; border-bottom: 2px solid #007bff;
        }

        /* Container */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

        /* Grid Layouts */
        .grid-box { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #e9ecef; }
    </style>
</head>
<body>

    <div class="navbar">
        <h2 style="margin:0;">Torque4Hire</h2>
        <div class="nav-tabs">
            <a href="?view=machines" class="<?php echo $view=='machines'?'active':''; ?>">🚜 Browse Machines</a>
            <a href="?view=rentals" class="<?php echo $view=='rentals'?'active':''; ?>">🧾 My Rentals</a>
            <a href="?view=trainers" class="<?php echo $view=='trainers'?'active':''; ?>">👨‍🏫 Find Trainers</a>
            <a href="logout.php" style="color:#ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="container">
        
        <?php if($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if($view == 'machines') { 
            
            // 1. CHECK IF SEARCH WAS TYPED
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $sql = "SELECT m.*, c.category_name, o.company_name 
                    FROM machinery m 
                    JOIN machine_categories c ON m.category_id = c.category_id 
                    JOIN owners o ON m.owner_email = o.owner_email 
                    WHERE m.status = 'AVAILABLE'";

            // If user typed something, add a filter
            if ($search) {
                $sql .= " AND (m.model_name LIKE '%$search%' OR c.category_name LIKE '%$search%')";
            }
            
            $machines = $conn->query($sql);
        ?>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Available Machinery</h3>
                
                <form method="GET" style="display:flex; gap:5px; padding:0; box-shadow:none; background:transparent; width:auto;">
                    <input type="hidden" name="view" value="machines">
                    <input type="text" name="search" placeholder="Search machines..." value="<?php echo htmlspecialchars($search); ?>" style="padding:8px; width:200px;">
                    <button type="submit" style="margin:0; padding:8px 15px;">🔍</button>
                    <?php if($search): ?>
                        <a href="?view=machines" style="padding:8px; color:red; text-decoration:none;">✖</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="grid-box">
                <?php while($row = $machines->fetch_assoc()) { ?>
                    <div class="card">
                        <span style="background:#e2e6ea; padding:4px 8px; border-radius:4px; font-size:12px; font-weight:bold;">
                            <?php echo htmlspecialchars($row['category_name']); ?>
                        </span>
                        <h4><?php echo htmlspecialchars($row['model_name']); ?></h4>
                        <p style="color:#666; font-size:14px;">Owner: <?php echo htmlspecialchars($row['company_name']); ?></p>
                        <h3 style="color:#28a745; margin:10px 0;">$<?php echo $row['daily_rate']; ?> <small>/day</small></h3>
                        
                        <?php if($is_qualified): ?>
                            <a href="?view=rent_form&id=<?php echo $row['machine_id']; ?>" 
                               style="display:block; background:#007bff; color:white; text-align:center; padding:10px; border-radius:4px; text-decoration:none;">
                               Rent Now
                            </a>
                        <?php else: ?>
                            <a href="?view=trainers" style="display:block; background:#ffc107; color:black; text-align:center; padding:10px; border-radius:4px; text-decoration:none; font-weight:bold;">
                                Training Required
                            </a>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>


        <?php } elseif($view == 'rent_form' && isset($_GET['id'])) { 
            $m_id = $_GET['id'];
            $machine = $conn->query("SELECT * FROM machinery WHERE machine_id = $m_id")->fetch_assoc();
        ?>
            <div style="max-width:500px; margin:0 auto;" class="card">
                <h3>Confirm Rental: <?php echo htmlspecialchars($machine['model_name']); ?></h3>
                <form method="POST" action="?view=rentals">
                    <input type="hidden" name="machine_id" value="<?php echo $m_id; ?>">
                    <input type="hidden" name="daily_rate" value="<?php echo $machine['daily_rate']; ?>">
                    
                    <label>Start Date:</label>
                    <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                    
                    <label>End Date:</label>
                    <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                    
                    <button type="submit" name="confirm_rent" style="width:100%; margin-top:20px;">Confirm Request</button>
                    <a href="?view=machines" style="display:block; text-align:center; margin-top:10px;">Cancel</a>
                </form>
            </div>


        <?php } elseif($view == 'rentals') { 
            $rentals = $conn->query("SELECT r.*, m.model_name, m.daily_rate, o.company_name FROM rentals r JOIN machinery m ON r.machine_id = m.machine_id JOIN owners o ON m.owner_email = o.owner_email WHERE r.renter_email = '$renter_email' ORDER BY r.rental_id DESC");
        ?>
            <h3>My Rental History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Machine</th>
                        <th>Dates</th>
                        <th>Total Cost</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $rentals->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <b><?php echo htmlspecialchars($row['model_name']); ?></b><br>
                            <small><?php echo htmlspecialchars($row['company_name']); ?></small>
                        </td>
                        <td><?php echo $row['start_date']; ?> to <?php echo $row['end_date']; ?></td>
                        <td>$<?php echo $row['total_cost']; ?></td>
                        <td>
                            <?php if($row['rental_status']=='COMPLETED') echo '<span style="color:green; font-weight:bold;">Completed</span>'; 
                                  else echo '<span style="color:orange; font-weight:bold;">Active</span>'; ?>
                        </td>
                        <td>
                            <?php if($row['rental_status'] != 'COMPLETED'): ?>
                                <a href="?view=rentals&action=return&rental_id=<?php echo $row['rental_id']; ?>&machine_id=<?php echo $row['machine_id']; ?>" 
                                   onclick="return confirm('Return this machine?')"
                                   style="background:#dc3545; color:white; padding:5px 10px; border-radius:4px; text-decoration:none; font-size:12px;">
                                   Return
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>


        <?php } elseif($view == 'trainers') { 
            $trainers = $conn->query("SELECT t.*, u.name FROM trainers t JOIN users u ON t.trainer_email = u.email");
        ?>
            <h3>Expert Trainers</h3>
            <div class="grid-box">
                <?php while($row = $trainers->fetch_assoc()) { ?>
                    <div class="card">
                        <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                        <p>Expertise: <b><?php echo htmlspecialchars($row['expertise']); ?></b></p>
                        <p>Status: <?php echo $row['availability']; ?></p>
                        
                        <?php if($row['availability'] == 'AVAILABLE'): ?>
                            <a href="?view=book_trainer_form&email=<?php echo $row['trainer_email']; ?>" 
                               style="display:block; background:#17a2b8; color:white; text-align:center; padding:10px; border-radius:4px; text-decoration:none;">
                               Book Session
                            </a>
                        <?php else: ?>
                            <button disabled style="width:100%; background:#ccc;">Unavailable</button>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>


        <?php } elseif($view == 'book_trainer_form' && isset($_GET['email'])) { 
            $t_email = $_GET['email'];
        ?>
            <div style="max-width:500px; margin:0 auto;" class="card">
                <h3>Book Training Session</h3>
                <form method="POST" action="?view=trainers">
                    <input type="hidden" name="trainer_email" value="<?php echo $t_email; ?>">
                    
                    <label>Date:</label>
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
                    
                    <button type="submit" name="book_trainer" style="width:100%; margin-top:20px; background:#17a2b8;">Confirm Booking</button>
                    <a href="?view=trainers" style="display:block; text-align:center; margin-top:10px;">Cancel</a>
                </form>
            </div>
        <?php } ?>

    </div>

</body>
</html>