<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    header("Location: login.php");
    exit();
}

$renter_email = $_SESSION['user_email'];

$sql = "SELECT r.*, m.model_name, m.daily_rate, o.company_name 
        FROM rentals r
        JOIN machinery m ON r.machine_id = m.machine_id
        JOIN owners o ON m.owner_email = o.owner_email
        WHERE r.renter_email = ?
        ORDER BY r.rental_id DESC"; // Show newest first

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $renter_email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Rentals - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover { background-color: #f1f1f1; }

        /* Status Badges */
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active { background-color: #fff3cd; color: #856404; } /* Yellow */
        .status-completed { background-color: #d4edda; color: #155724; } /* Green */

        /* Return Button */
        .btn-return {
            background-color: #dc3545; /* Red */
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-return:hover { background-color: #c82333; }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>My Rentals</h2>
        <div>
            <a href="view_machinery.php" style="display:inline; margin-right:20px;">Browse Machines</a>
            <a href="logout.php" style="color: red;">Logout</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])) { ?>
        <p style="color: green; text-align: center; font-weight: bold;">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </p>
    <?php } ?>

    <?php if ($result->num_rows > 0) { ?>
        <table>
            <thead>
                <tr>
                    <th>Machine</th>
                    <th>Owner</th>
                    <th>Dates</th>
                    <th>Total Cost</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><b><?php echo htmlspecialchars($row['model_name']); ?></b></td>
                        <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                        <td>
                            <?php echo $row['start_date']; ?> <br>
                            <span style="font-size:12px; color:#666;">to</span> <br>
                            <?php echo $row['end_date']; ?>
                        </td>
                        <td>$<?php echo $row['total_cost']; ?></td>
                        
                        <td>
                            <?php if ($row['rental_status'] == 'COMPLETED') { ?>
                                <span class="status status-completed">COMPLETED</span>
                            <?php } else { ?>
                                <span class="status status-active">ACTIVE</span>
                            <?php } ?>
                        </td>

                        <td>
                            <?php if ($row['rental_status'] != 'COMPLETED') { ?>
                                <a href="return_machine.php?rental_id=<?php echo $row['rental_id']; ?>&machine_id=<?php echo $row['machine_id']; ?>" 
                                   class="btn-return"
                                   onclick="return confirm('Are you sure you want to return this machine?');">
                                   Return Now
                                </a>
                            <?php } else { ?>
                                <span style="color:#aaa;">-</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p style="text-align:center; margin-top:50px; color:#666;">You haven't rented any machines yet.</p>
    <?php } ?>

</body>
</html>