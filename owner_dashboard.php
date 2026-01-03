<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'OWNER') {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['user_email'];
$message = "";
if(isset($_GET['msg'])) $message = $_GET['msg'];




if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM machinery WHERE machine_id=$id AND owner_email='$email'");
    header("Location: owner_dashboard.php?msg=Machine Deleted");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_machine'])) {
    $model = $_POST['model_name'];
    $cat = $_POST['category_id'];
    $rate = $_POST['daily_rate'];
    if($rate < 0) $message = "Error: Negative Rate";
    else {
        $stmt = $conn->prepare("INSERT INTO machinery (owner_email, category_id, model_name, daily_rate) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisd", $email, $cat, $model, $rate);
        $stmt->execute();
        $message = "Machine Added!";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $company = $_POST['company_name'];
    $pass = $_POST['password'];

    
    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE email=?");
    $stmt->bind_param("ssss", $name, $phone, $address, $email);
    $stmt->execute();

    
    $stmt2 = $conn->prepare("UPDATE owners SET company_name=? WHERE owner_email=?");
    $stmt2->bind_param("ss", $company, $email);
    $stmt2->execute();

    if (!empty($pass)) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password_hash='$hashed' WHERE email='$email'");
    }
    $message = "Profile Updated!";
}


$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Owner Dashboard - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
       
        body { 
            display: block; 
            padding: 20px; 
           
        }

        .dashboard-container { 
            max-width: 1200px; 
            margin: 0 auto; 
            display: grid; 
            grid-template-columns: 1fr 3fr; 
            gap: 30px; 
        }

       
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: #FFD700;
            padding: 15px 30px; 
            border-bottom: 5px solid #b39700;
            margin-bottom: 30px; 
        }

        .navbar h2 {
            margin: 0;
            border: none;
            padding: 0;
            color: #1A1A1A;
            font-size: 24px;
        }

        .navbar a {
            display: inline-block;
            margin: 0 10px;
            color: #1A1A1A;
            font-weight: 800;
            text-transform: uppercase;
            text-decoration: none;
        }
        
        .navbar a:hover {
            text-decoration: underline;
        }

       
        .card { 
            background: white; 
            padding: 25px; 
            border-top: 5px solid #1A1A1A;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5); 
        }

        .card h3 {
            margin-top: 0;
            color: #1A1A1A;
            text-transform: uppercase;
            font-weight: 800;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

       
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        
        th, td { 
            padding: 15px; 
            text-align: left; 
            border: 1px solid #ddd; 
        }

        th { 
            background: #1A1A1A; 
            color: #FFD700;
            text-transform: uppercase;
            font-size: 14px;
        }

        td {
            color: #333;
            font-weight: 500;
        }

       
        td a {
            display: inline;
            margin: 0 5px;
            font-size: 13px;
            text-transform: uppercase;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>🏗️ Owner Panel</h2>
        <div>
            <a href="?view=dashboard">📊 Fleet</a>
            <a href="?view=profile">👤 Profile</a>
            <a href="logout.php" style="color:#d32f2f;">Logout</a>
        </div>
    </div>

    <?php if($message) echo "<div style='background:#28a745; color:white; padding:15px; text-align:center; margin-bottom:20px; font-weight:bold; text-transform:uppercase;'>$message</div>"; ?>

    <?php if($view == 'dashboard') { 
        $my_machines = $conn->query("SELECT m.*, c.category_name FROM machinery m JOIN machine_categories c ON m.category_id = c.category_id WHERE m.owner_email = '$email' ORDER BY m.machine_id DESC");
        $cats = $conn->query("SELECT * FROM machine_categories");
    ?>
        <div class="dashboard-container">
            <div class="card" style="height:fit-content;">
                <h3>+ Add Machine</h3>
                <form method="POST" action="?view=dashboard" style="padding:0; box-shadow:none; width:auto; border:none; border-top:none;">
                    <label>Model Name</label>
                    <input type="text" name="model_name" placeholder="e.g. CAT D5 Dozer" required>
                    
                    <label>Category</label>
                    <select name="category_id" required>
                        <option value="" disabled selected>Select Category</option>
                        <?php while($c = $cats->fetch_assoc()) echo "<option value='{$c['category_id']}'>{$c['category_name']}</option>"; ?>
                    </select>
                    
                    <label>Daily Rate ($)</label>
                    <input type="number" name="daily_rate" placeholder="0.00" required>
                    
                    <button type="submit" name="add_machine">Deploy Asset</button>
                </form>
            </div>

            <div class="card">
                <h3>My Inventory</h3>
                <?php if($my_machines->num_rows > 0) { ?>
                <table>
                    <thead><tr><th>Model</th><th>Category</th><th>Rate</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $my_machines->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['model_name']; ?></td>
                            <td><?php echo $row['category_name']; ?></td>
                            <td>$<?php echo $row['daily_rate']; ?></td>
                            <td style="font-weight:bold; color:<?php echo $row['status']=='AVAILABLE'?'green':($row['status']=='MAINTENANCE'?'orange':'red'); ?>">
                                <?php echo $row['status']; ?>
                            </td>
                            <td>
                                <?php if($row['status']=='AVAILABLE') { ?>
                                    <a href="?delete=<?php echo $row['machine_id']; ?>" style="color:#d32f2f;" onclick="return confirm('Delete?')">DEL</a>
                                    <a href="maintenance.php?id=<?php echo $row['machine_id']; ?>" style="color:#f57c00;">MAINT</a>
                                <?php } elseif($row['status']=='MAINTENANCE') { ?>
                                    <a href="maintenance.php?action=finish&id=<?php echo $row['machine_id']; ?>" style="color:green;">FIXED</a>
                                <?php } else { echo "IN USE"; } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php } else { echo "<p style='color:#666; margin-top:20px;'>No machines listed.</p>"; } ?>
            </div>
        </div>

    <?php } elseif($view == 'profile') { 
        $user = $conn->query("SELECT u.*, o.company_name FROM users u JOIN owners o ON u.email = o.owner_email WHERE u.email='$email'")->fetch_assoc();
    ?>
        <div class="card" style="max-width:600px; margin:0 auto;">
            <h3>Edit Company Profile</h3>
            <form method="POST" action="?view=profile" style="padding:0; box-shadow:none; width:auto; border:none; border-top:none;">
                <label>Owner Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                
                <label>Company Name:</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($user['company_name']); ?>" required>

                <label>Phone:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">

                <label style="color:#d32f2f; margin-top:20px;">New Password:</label>
                <input type="password" name="password" placeholder="Leave blank to keep current">

                <button type="submit" name="update_profile">Update Profile</button>
            </form>
        </div>
    <?php } ?>

</body>
</html>