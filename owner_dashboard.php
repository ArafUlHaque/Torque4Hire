<?php
session_start();
include 'db_connect.php';

// 1. SECURITY: Only Owners Allowed
if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'OWNER') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$message = "";

// 2. HANDLE ACTIONS (Add or Delete Machine)
// A. Delete Machine
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Security: Ensure this machine actually belongs to this owner before deleting
    $del_stmt = $conn->prepare("DELETE FROM machinery WHERE machine_id = ? AND owner_email = ?");
    $del_stmt->bind_param("is", $id, $email);
    if ($del_stmt->execute()) {
        $message = "Machine deleted successfully!";
    } else {
        $message = "Error deleting machine.";
    }
}

// B. Add Machine (Form Submission)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_machine'])) {
    $model = $_POST['model_name'];
    $cat_id = $_POST['category_id'];
    $rate = $_POST['daily_rate'];
    
    if ($rate < 0) {
        $message = "Error: Price cannot be negative.";
    } else {
        $stmt = $conn->prepare("INSERT INTO machinery (owner_email, category_id, model_name, daily_rate, status) VALUES (?, ?, ?, ?, 'AVAILABLE')");
        $stmt->bind_param("sisd", $email, $cat_id, $model, $rate);
        if ($stmt->execute()) {
            $message = "New machine added to your fleet!";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// 3. FETCH DATA
// A. Get Categories for the dropdown
$categories = $conn->query("SELECT * FROM machine_categories");

// B. Get My Machines (Joined with Category Name)
$my_machines_sql = "SELECT m.*, c.category_name 
                    FROM machinery m 
                    JOIN machine_categories c ON m.category_id = c.category_id 
                    WHERE m.owner_email = '$email' 
                    ORDER BY m.machine_id DESC";
$my_machines = $conn->query($my_machines_sql);

// C. Calculate Total Earnings
$earnings_sql = "SELECT SUM(r.total_cost) as total 
                 FROM rentals r 
                 JOIN machinery m ON r.machine_id = m.machine_id 
                 WHERE m.owner_email = '$email' AND r.rental_status = 'CONFIRMED'"; // Only count confirmed rentals
$earnings = $conn->query($earnings_sql)->fetch_assoc();
$total_income = $earnings['total'] ? $earnings['total'] : 0.00;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Owner Dashboard - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* DASHBOARD LAYOUT */
        body { display: block; background-color: #f4f6f9; padding: 20px; }
        
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 3fr; /* Sidebar (Form) vs Main Content */
            gap: 30px;
        }

        /* LEFT COLUMN: Add Machine Card */
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            height: fit-content;
        }

        /* RIGHT COLUMN: Inventory Table */
        .inventory-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        /* STATS BAR */
        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: #343a40;
            color: white;
            padding: 20px;
            border-radius: 8px;
            flex: 1;
            text-align: center;
        }
        .stat-box h3 { margin: 0; font-size: 32px; color: #28a745; }
        .stat-box p { margin: 5px 0 0; opacity: 0.8; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;}

        /* TABLE STYLING */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #333; font-weight: 600; }
        .status-avail { color: #28a745; font-weight: bold; }
        .status-rented { color: #fd7e14; font-weight: bold; }

        /* HEADER */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h2 style="margin:0; color:#333;">Owner Dashboard</h2>
        <div>
            <span style="margin-right: 20px; color: #666;">
                Welcome, <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b>
            </span>
            <a href="logout.php" style="color: #dc3545; font-weight: bold; text-decoration: none;">Logout</a>
        </div>
    </div>

    <div class="dashboard-container" style="display:block; margin-bottom: 0;">
        <div class="stats-bar">
            <div class="stat-box">
                <h3>$<?php echo number_format($total_income, 2); ?></h3>
                <p>Total Earnings</p>
            </div>
            <div class="stat-box">
                <h3 style="color: #17a2b8;"><?php echo $my_machines->num_rows; ?></h3>
                <p>Machines Owned</p>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        
        <div class="action-card">
            <h3 style="margin-top:0; border-bottom:2px solid #eee; padding-bottom:10px;">+ Add New Machine</h3>
            
            <?php if($message): ?>
                <p style="color: <?php echo strpos($message, 'Error') !== false ? 'red' : 'green'; ?>; font-size: 14px;">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>

            <form method="POST" action="" style="box-shadow:none; padding:0; width:100%;">
                <label>Model Name:</label>
                <input type="text" name="model_name" placeholder="e.g. Volvo EC220" required>
                
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="" disabled selected>Select Type</option>
                    <?php 
                    $categories->data_seek(0);
                    while($row = $categories->fetch_assoc()) { 
                        echo "<option value='".$row['category_id']."'>".$row['category_name']."</option>";
                    } 
                    ?>
                </select>

                <label>Daily Rate ($):</label>
                <input type="number" step="0.01" name="daily_rate" placeholder="0.00" required>

                <button type="submit" name="add_machine" style="width:100%;">Add to Fleet</button>
            </form>
        </div>

        <div class="inventory-section">
            <h3 style="margin-top:0;">🚜 My Fleet Inventory</h3>
            
            <?php if ($my_machines->num_rows > 0) { ?>
                <table>
                    <thead>
                        <tr>
                            <th>Machine Details</th>
                            <th>Category</th>
                            <th>Rate</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $my_machines->fetch_assoc()) { ?>
                        <tr>
                            <td><b><?php echo htmlspecialchars($row['model_name']); ?></b></td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td>$<?php echo $row['daily_rate']; ?></td>
                            <td>
                                <?php if($row['status'] == 'AVAILABLE') { ?>
                                    <span class="status-avail">Available</span>
                                <?php } else { ?>
                                    <span class="status-rented">Rented</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if($row['status'] == 'AVAILABLE') { ?>
                                    <a href="owner_dashboard.php?delete=<?php echo $row['machine_id']; ?>" 
                                       onclick="return confirm('Permanently remove this machine?')"
                                       style="color: #dc3545; font-size: 13px; font-weight: bold;">
                                       Delete
                                    </a>
                                <?php } else { ?>
                                    <span style="color:#ccc; font-size:12px;">In Use</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p style="color:#666; text-align:center; padding: 20px;">You haven't added any machines yet.</p>
            <?php } ?>
        </div>

    </div>

</body>
</html>