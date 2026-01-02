<?php
session_start();
include 'db_connect.php';


if (!isset($_SESSION['role']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: admin_login.php");
    exit();
}

$message = "";


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $rental_id = $_POST['rental_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE rentals SET rental_status = 'PAYMENT_PENDING' WHERE rental_id = ?");
        $stmt->bind_param("i", $rental_id);
        if($stmt->execute()) $message = "Rental #$rental_id Approved!";
    } 
    elseif ($action == 'reject') {
        
        
        $check = $conn->query("SELECT machine_id FROM rentals WHERE rental_id = $rental_id")->fetch_assoc();
        $mach_id = $check['machine_id'];
        
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE rentals SET rental_status = 'REJECTED' WHERE rental_id = $rental_id");
            $conn->query("UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = $mach_id");
            $conn->commit();
            $message = "Rental #$rental_id Rejected.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}


$renters_count = $conn->query("SELECT COUNT(*) as total FROM renters")->fetch_assoc()['total'];
$owners_count = $conn->query("SELECT COUNT(*) as total FROM owners")->fetch_assoc()['total'];
$machines_count = $conn->query("SELECT COUNT(*) as total FROM machinery")->fetch_assoc()['total'];
$rentals_count = $conn->query("SELECT COUNT(*) as total FROM rentals")->fetch_assoc()['total'];



$sql_rentals = "SELECT r.*, u.name as renter_name, m.model_name, o.company_name 
                FROM rentals r
                JOIN users u ON r.renter_email = u.email
                JOIN machinery m ON r.machine_id = m.machine_id
                JOIN owners o ON m.owner_email = o.owner_email
                ORDER BY r.rental_id DESC";
$all_rentals = $conn->query($sql_rentals);


$sql_owners = "SELECT o.*, u.name, u.phone, u.address, u.created_at 
               FROM owners o
               JOIN users u ON o.owner_email = u.email
               ORDER BY u.created_at DESC";
$all_owners = $conn->query($sql_owners);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; padding: 20px; background: #f4f6f9; }
        
       
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            background: white; padding: 15px 25px; border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px;
            border-left: 5px solid #dc3545;
        }

       
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 40px;
        }
        .stat-card {
            background: white; padding: 20px; border-radius: 8px; text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-number { font-size: 32px; font-weight: bold; color: #333; margin: 5px 0; }
        .stat-label { color: #888; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }

       
        .section-title { margin-top: 40px; margin-bottom: 15px; color: #444; border-bottom: 2px solid #ddd; padding-bottom: 10px; }
        
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #343a40; color: white; font-weight: 500; font-size: 14px; }
        tr:hover { background-color: #f8f9fa; }

       
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-req { background: #fff3cd; color: #856404; }
        .badge-conf { background: #d4edda; color: #155724; }
        .badge-rej { background: #f8d7da; color: #721c24; }

       
        .btn-action { border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; color: white; font-size: 12px; margin-right: 5px; }
        .btn-approve { background-color: #28a745; }
        .btn-reject { background-color: #dc3545; }
        .btn-approve:hover { background-color: #218838; }
        .btn-reject:hover { background-color: #c82333; }

    </style>
</head>
<body>

    <div class="navbar">
        <h2 style="margin:0; color:#dc3545;">Admin Dashboard</h2>
        <div>
            <span style="color:#555;">Supervisor: <b><?php echo htmlspecialchars($_SESSION['admin_email']); ?></b></span>
            <a href="logout.php" style="margin-left:20px; color:#dc3545; font-weight:bold; text-decoration:none;">Logout ➜</a>
        </div>
    </div>

    <?php if($message): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px; text-align:center;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $renters_count; ?></div>
            <div class="stat-label">Renters</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $owners_count; ?></div>
            <div class="stat-label">Owners</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $machines_count; ?></div>
            <div class="stat-label">Machines</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $rentals_count; ?></div>
            <div class="stat-label">Rentals</div>
        </div>
    </div>

    <h3 class="section-title">📝 Master Rental Log</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Renter</th>
                <th>Machine Info</th>
                <th>Owner Company</th>
                <th>Dates</th>
                <th>Total</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $all_rentals->fetch_assoc()) { ?>
            <tr>
                <td>#<?php echo $row['rental_id']; ?></td>
                <td>
                    <b><?php echo htmlspecialchars($row['renter_name']); ?></b><br>
                    <small style="color:#888;"><?php echo $row['renter_email']; ?></small>
                </td>
                <td><?php echo htmlspecialchars($row['model_name']); ?></td>
                <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                <td>
                    <small><?php echo $row['start_date']; ?> ➝ <?php echo $row['end_date']; ?></small>
                </td>
                <td style="font-weight:bold; color:#28a745;">$<?php echo $row['total_cost']; ?></td>
                <td>
                    <?php 
                        $status = $row['rental_status'];
                        $badge = $status == 'REQUESTED' ? 'badge-req' : ($status == 'CONFIRMED' ? 'badge-conf' : 'badge-rej');
                    ?>
                    <span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span>
                </td>
                <td>
                    <?php if($status == 'REQUESTED') { ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="rental_id" value="<?php echo $row['rental_id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn-action btn-approve">Approve</button>
                            <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Reject this rental?');">Reject</button>
                        </form>
                    <?php } else { echo "-"; } ?>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <h3 class="section-title">👷 Registered Owners</h3>
    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Owner Name</th>
                <th>Contact Info</th>
                <th>Address</th>
                <th>Joined Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while($owner = $all_owners->fetch_assoc()) { ?>
            <tr>
                <td><b><?php echo htmlspecialchars($owner['company_name']); ?></b></td>
                <td><?php echo htmlspecialchars($owner['name']); ?></td>
                <td>
                    <?php echo $owner['owner_email']; ?><br>
                    <small><?php echo $owner['phone'] ? $owner['phone'] : 'No Phone'; ?></small>
                </td>
                <td><?php echo $owner['address'] ? htmlspecialchars($owner['address']) : '<i style="color:#aaa">Not Provided</i>'; ?></td>
                <td><?php echo date('M d, Y', strtotime($owner['created_at'])); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <br><br><br> </body>
</html>