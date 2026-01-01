<?php
session_start();
include 'db_connect.php';
$u_email = $_SESSION['user_email'];
$check_q = $conn->query("SELECT license_no FROM renters WHERE renter_email = '$u_email'");
$r_info = $check_q->fetch_assoc();
$is_qualified = !empty($r_info['license_no']);

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'RENTER') {
    header("Location: login.php"); 
    exit(); 
}

$sql = "SELECT m.machine_id, m.model_name, m.daily_rate, c.category_name, o.company_name
        FROM machinery m 
        JOIN machine_categories c ON m.category_id = c.category_id
        JOIN owners o ON m.owner_email = o.owner_email
        WHERE m.status = 'AVAILABLE'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Machinery - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
       
       
        body {
            display: block; 
            height: auto;
            padding: 20px;
        }

       
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

       
        .machine-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

       
        .machine-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .machine-card:hover {
            transform: translateY(-5px);
        }

        .category-tag {
            background-color: #e2e6ea;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #555;
            font-weight: bold;
        }

        .price {
            font-size: 18px;
            color: #28a745;
            font-weight: bold;
            margin: 10px 0;
        }

        .rent-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .rent-btn:hover { background-color: #0056b3; }
    </style>
</head>
<body>

    <div class="navbar">
    <h2>Torque4Hire</h2>
    <div>
        <span>Welcome, <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b></span>
        
        <a href="view_trainers.php" style="margin-left: 15px; text-decoration: underline; color: #0056b3;">Find a Trainer</a>

        <a href="my_rentals.php" style="margin-left: 15px; text-decoration: underline;">My Rentals</a>
        
        <a href="logout.php" style="margin-left: 15px; color: red;">Logout</a>
    </div>
</div>
</div>

    <h3>Available Machines</h3>

    <div class="machine-grid">
        
        <?php 
        
        if ($result->num_rows > 0) {
            
            
            while($row = $result->fetch_assoc()) { 
        ?>
            <div class="machine-card">
                <span class="category-tag"><?php echo htmlspecialchars($row['category_name']); ?></span>
                
                <h4><?php echo htmlspecialchars($row['model_name']); ?></h4>
                <p style="font-size: 14px; color: #666;">
                    Owner: <?php echo htmlspecialchars($row['company_name']); ?>
                </p>
                
                <div class="price">$<?php echo $row['daily_rate']; ?> / day</div>
                
                <?php if ($is_qualified): ?>
                    <a href="rent_machine.php?id=<?php echo $row['machine_id']; ?>" class="rent-btn">Rent Now</a>
                <?php else: ?>
                    <a href="view_trainers.php" class="rent-btn" style="background-color: #ffc107; color: black; font-weight: bold;">Training Required</a>
                <?php endif; ?>
            </div>

        <?php 
            }
        } else {
            echo "<p>No machines currently available.</p>";
        }
        ?>

    </div>

</body>
</html> 