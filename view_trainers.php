<?php
session_start();
include 'db_connect.php';

$u_email = $_SESSION['user_email'];
$check_q = $conn->query("SELECT license_no FROM renters WHERE renter_email = '$u_email'");
$r_info = $check_q->fetch_assoc();
if (!empty($r_info['license_no'])) {
    header("Location: view_machinery.php?msg=Already Qualified");
    exit();
}

// 1. SECURITY: Renters Only
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    header("Location: login.php");
    exit();
}

// 2. FETCH TRAINERS
// We join 'trainers' with 'users' to get the real name and phone number
$sql = "SELECT t.*, u.name, u.phone 
        FROM trainers t
        JOIN users u ON t.trainer_email = u.email";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Find a Trainer - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>

        body { 
            display: block; 
            height: auto; 
            overflow-y: auto; 
            padding: 40px 20px; 
        }
        .trainer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .trainer-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 5px solid #007bff;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .available { background: #d4edda; color: #155724; }
        .booked { background: #f8d7da; color: #721c24; }
        .offline { background: #e2e3e5; color: #383d41; }

        .btn-book {
            display: block;
            width: 100%;
            padding: 10px;
            text-align: center;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .btn-book:hover { background: #0056b3; }
        .btn-disabled { background: #ccc; pointer-events: none; }
    </style>
</head>
<body>

    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>Find a Trainer</h2>
        <a href="view_machinery.php">Back to Machines</a>
    </div>

    <div class="trainer-grid">
        <?php while($row = $result->fetch_assoc()) { 
            $status = $row['availability'];
            $badge_class = strtolower($status); // 'available' -> class 'available'
        ?>
            <div class="trainer-card">
                <span class="status-badge <?php echo $badge_class; ?>">
                    <?php echo $status; ?>
                </span>
                
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <p style="color:#666; margin:5px 0;">Expertise: <b><?php echo htmlspecialchars($row['expertise']); ?></b></p>
                <p style="font-size:14px;">📧 <?php echo $row['trainer_email']; ?></p>
                
                <?php if ($status == 'AVAILABLE') { ?>
                    <a href="book_trainers.php?trainer=<?php echo $row['trainer_email']; ?>" class="btn-book">Book Session</a>
                <?php } else { ?>
                    <a href="#" class="btn-book btn-disabled">Currently Unavailable</a>
                <?php } ?>
            </div>
        <?php } ?>
    </div>

</body>
</html>