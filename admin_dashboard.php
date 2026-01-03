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
        if($stmt->execute()) $message = "RENTAL #$rental_id APPROVED - AWAITING PAYMENT.";
    } 
    elseif ($action == 'reject') {
        
        $check = $conn->query("SELECT machine_id FROM rentals WHERE rental_id = $rental_id")->fetch_assoc();
        $mach_id = $check['machine_id'];
        
        $conn->begin_transaction();
        try {
            $conn->query("UPDATE rentals SET rental_status = 'REJECTED' WHERE rental_id = $rental_id");
            $conn->query("UPDATE machinery SET status = 'AVAILABLE' WHERE machine_id = $mach_id");
            $conn->commit();
            $message = "RENTAL #$rental_id REJECTED.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    }
}

// --- DATA FETCHING ---

// 1. STATS
$renters_count = $conn->query("SELECT COUNT(*) as total FROM renters")->fetch_assoc()['total'];
$owners_count = $conn->query("SELECT COUNT(*) as total FROM owners")->fetch_assoc()['total'];
$machines_count = $conn->query("SELECT COUNT(*) as total FROM machinery")->fetch_assoc()['total'];
$rentals_count = $conn->query("SELECT COUNT(*) as total FROM rentals")->fetch_assoc()['total'];

// 2. RENTALS (Existing)
$sql_rentals = "SELECT r.*, u.name as renter_name, m.model_name, o.company_name 
                FROM rentals r
                JOIN users u ON r.renter_email = u.email
                JOIN machinery m ON r.machine_id = m.machine_id
                JOIN owners o ON m.owner_email = o.owner_email
                ORDER BY r.rental_id DESC";
$all_rentals = $conn->query($sql_rentals);

// 3. OWNERS (Existing)
$sql_owners = "SELECT o.*, u.name, u.phone, u.address, u.created_at 
               FROM owners o
               JOIN users u ON o.owner_email = u.email
               ORDER BY u.created_at DESC";
$all_owners = $conn->query($sql_owners);

// 4. NEW: RENTERS LIST
$sql_renters = "SELECT r.*, u.name, u.phone, u.address, u.created_at
                FROM renters r
                JOIN users u ON r.renter_email = u.email
                ORDER BY u.created_at DESC";
$all_renters = $conn->query($sql_renters);

// 5. NEW: MACHINES LIST
$sql_machines = "SELECT m.*, c.category_name, o.company_name
                 FROM machinery m
                 JOIN machine_categories c ON m.category_id = c.category_id
                 JOIN owners o ON m.owner_email = o.owner_email
                 ORDER BY m.machine_id DESC";
$all_machines = $conn->query($sql_machines);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ADMIN SPECIFIC OVERRIDES */
        body { 
            display: block; 
            padding: 20px; 
            /* Background Inherited (Dark Asphalt) */
        }
        
        /* Yellow Navbar */
        .navbar {
            display: flex; justify-content: space-between; align-items: center;
            background: #FFD700; padding: 15px 30px; 
            border-bottom: 5px solid #b39700; margin-bottom: 30px;
        }

        .navbar h2 {
            margin: 0; color: #1A1A1A; font-size: 24px; border: none; padding: 0;
        }
       
        /* Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 40px;
        }
        .stat-card {
            background: white; padding: 20px; text-align: center;
            border-top: 5px solid #FFD700; /* Yellow Accent */
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
        }
        .stat-number { font-size: 32px; font-weight: 900; color: #1A1A1A; margin: 5px 0; }
        .stat-label { color: #666; font-size: 13px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }

        /* Tables & Sections */
        .section-title { 
            margin-top: 50px; margin-bottom: 15px; 
            color: #FFD700; /* Yellow Titles */
            border-bottom: 2px solid #333; 
            padding-bottom: 10px; 
            text-transform: uppercase;
            font-weight: 800;
        }
        
        .card {
            background: white;
            padding: 0; /* Let table fill the card */
            border-top: 5px solid #1A1A1A;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            overflow-x: auto;
        }

        table { width: 100%; border-collapse: collapse; margin: 0; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { 
            background-color: #1A1A1A; 
            color: #FFD700; 
            font-weight: 700; 
            font-size: 14px; 
            text-transform: uppercase; 
        }
        td { color: #1A1A1A; font-weight: 500; }
        tr:hover { background-color: #f9f9f9; }

        /* Badges */
        .badge { padding: 5px 10px; font-size: 11px; font-weight: 900; text-transform: uppercase; display: inline-block; }
        .badge-req { background: #1A1A1A; color: #FFD700; border: 1px solid #FFD700; }
        .badge-conf { background: #28a745; color: white; }
        .badge-rej { background: #d32f2f; color: white; }

        /* Action Buttons */
        .btn-action { border: none; padding: 8px 12px; cursor: pointer; color: #1A1A1A; font-weight: 900; text-transform: uppercase; font-size: 11px; margin-right: 5px; box-shadow: 2px 2px 0 rgba(0,0,0,0.2); }
        .btn-approve { background-color: #FFD700; }
        .btn-reject { background-color: #d32f2f; color: white; }
        .btn-action:hover { transform: translateY(-1px); }

    </style>
</head>
<body>

    <div class="navbar">
        <h2>⚡ Admin Command Center</h2>
        <div>
            <span style="color:#1A1A1A; font-weight:bold; margin-right:20px;">
                SUP: <?php echo htmlspecialchars($_SESSION['admin_email']); ?>
            </span>
            <a href="logout.php" style="color:#d32f2f; font-weight:900; text-decoration:none; text-transform:uppercase;">Logout</a>
        </div>
    </div>

    <?php if($message): ?>
        <div style="background:#28a745; color:white; padding:15px; margin-bottom:20px; text-align:center; font-weight:bold; text-transform:uppercase; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $renters_count; ?></div>
            <div class="stat-label">Active Renters</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $owners_count; ?></div>
            <div class="stat-label">Fleet Owners</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $machines_count; ?></div>
            <div class="stat-label">Total Machines</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $rentals_count; ?></div>
            <div class="stat-label">Total Transactions</div>
        </div>
    </div>

    <h3 class="section-title">📝 Master Rental Log</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Renter</th>
                    <th>Machine Info</th>
                    <th>Owner</th>
                    <th>Period</th>
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
                        <small><?php echo $row['start_date']; ?> ➝ <br><?php echo $row['end_date']; ?></small>
                    </td>
                    <td style="font-weight:900;">$<?php echo $row['total_cost']; ?></td>
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
    </div>

    <h3 class="section-title">🚜 Machinery Repository</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Machine Model</th>
                    <th>Category</th>
                    <th>Provider</th>
                    <th>Daily Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($mach = $all_machines->fetch_assoc()) { ?>
                <tr>
                    <td><b><?php echo htmlspecialchars($mach['model_name']); ?></b></td>
                    <td><?php echo htmlspecialchars($mach['category_name']); ?></td>
                    <td><?php echo htmlspecialchars($mach['company_name']); ?></td>
                    <td>$<?php echo $mach['daily_rate']; ?></td>
                    <td style="font-weight:bold; color:<?php echo $mach['status']=='AVAILABLE'?'green':'#d32f2f'; ?>">
                        <?php echo $mach['status']; ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <h3 class="section-title">👷 Registered Renters</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email / Contact</th>
                    <th>License No.</th>
                    <th>Address</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php while($renter = $all_renters->fetch_assoc()) { ?>
                <tr>
                    <td><b><?php echo htmlspecialchars($renter['name']); ?></b></td>
                    <td>
                        <?php echo $renter['renter_email']; ?><br>
                        <small><?php echo $renter['phone'] ? $renter['phone'] : 'No Phone'; ?></small>
                    </td>
                    <td>
                        <?php if($renter['license_no']): ?>
                            <span style="background:#1A1A1A; color:#FFD700; padding:2px 5px; font-size:11px; font-weight:bold;"><?php echo $renter['license_no']; ?></span>
                        <?php else: ?>
                            <span style="color:#aaa;">Unlicensed</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $renter['address'] ? htmlspecialchars($renter['address']) : '<i style="color:#aaa">N/A</i>'; ?></td>
                    <td><?php echo date('M d, Y', strtotime($renter['created_at'])); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <h3 class="section-title">🏗️ Fleet Owners</h3>
    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Owner Name</th>
                    <th>Contact Info</th>
                    <th>Address</th>
                    <th>Joined</th>
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
    </div>

    <br><br><br>
</body>
</html>