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

// --- LOGIC HANDLERS ---

// 1. Delete Machine
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM machinery WHERE machine_id=$id AND owner_email='$email'");
    header("Location: owner_dashboard.php?msg=Machine Deleted");
    exit();
}

// 2. Add Machine
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

// 3. Update Profile (Including Company Name)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $company = $_POST['company_name'];
    $pass = $_POST['password'];

    // Update Users Table
    $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE email=?");
    $stmt->bind_param("ssss", $name, $phone, $address, $email);
    $stmt->execute();

    // Update Owners Table (Company Name)
    $stmt2 = $conn->prepare("UPDATE owners SET company_name=? WHERE owner_email=?");
    $stmt2->bind_param("ss", $company, $email);
    $stmt2->execute();

    if (!empty($pass)) {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password_hash='$hashed' WHERE email='$email'");
    }
    $message = "Profile Updated!";
}

// View Logic
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Owner Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: block; background-color: #f4f6f9; padding: 20px; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 3fr; gap: 30px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 30px; border-radius: 8px; margin-bottom: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>

    <div class="navbar">
        <h2>Owner Dashboard</h2>
        <div>
            <a href="?view=dashboard" style="display:inline; margin-right:20px;">📊 My Fleet</a>
            <a href="?view=profile" style="display:inline; margin-right:20px;">👤 Edit Profile</a>
            <a href="logout.php" style="color:red; display:inline;">Logout</a>
        </div>
    </div>

    <?php if($message) echo "<p style='text-align:center; color:green; background:white; padding:10px;'>$message</p>"; ?>

    <?php if($view == 'dashboard') { 
        $my_machines = $conn->query("SELECT m.*, c.category_name FROM machinery m JOIN machine_categories c ON m.category_id = c.category_id WHERE m.owner_email = '$email' ORDER BY m.machine_id DESC");
        $cats = $conn->query("SELECT * FROM machine_categories");
    ?>
        <div class="dashboard-container">
            <div class="card" style="height:fit-content;">
                <h3>+ Add Machine</h3>
                <form method="POST" action="?view=dashboard" style="padding:0; box-shadow:none; width:auto;">
                    <input type="text" name="model_name" placeholder="Model Name" required>
                    <select name="category_id" required>
                        <option value="" disabled selected>Select Category</option>
                        <?php while($c = $cats->fetch_assoc()) echo "<option value='{$c['category_id']}'>{$c['category_name']}</option>"; ?>
                    </select>
                    <input type="number" name="daily_rate" placeholder="Daily Rate ($)" required>
                    <button type="submit" name="add_machine">Add</button>
                </form>
            </div>

            <div class="card">
                <h3>My Inventory</h3>
                <table>
                    <thead><tr><th>Model</th><th>Category</th><th>Rate</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($row = $my_machines->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $row['model_name']; ?></td>
                            <td><?php echo $row['category_name']; ?></td>
                            <td>$<?php echo $row['daily_rate']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                            <td>
                                <?php if($row['status']=='AVAILABLE') { ?>
                                    <a href="?delete=<?php echo $row['machine_id']; ?>" style="color:red;" onclick="return confirm('Delete?')">Delete</a>
                                    <a href="maintenance.php?id=<?php echo $row['machine_id']; ?>" style="color:orange;">Report Issue</a>
                                <?php } elseif($row['status']=='MAINTENANCE') { ?>
                                    <a href="maintenance.php?action=finish&id=<?php echo $row['machine_id']; ?>" style="color:green;">Mark Fixed</a>
                                <?php } else { echo "In Use"; } ?>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php } elseif($view == 'profile') { 
        $user = $conn->query("SELECT u.*, o.company_name FROM users u JOIN owners o ON u.email = o.owner_email WHERE u.email='$email'")->fetch_assoc();
    ?>
        <div class="card" style="max-width:600px; margin:0 auto;">
            <h3>Edit Company Profile</h3>
            <form method="POST" action="?view=profile">
                <label>Owner Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                
                <label>Company Name:</label>
                <input type="text" name="company_name" value="<?php echo htmlspecialchars($user['company_name']); ?>" required>

                <label>Phone:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

                <label>Address:</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($user['address']); ?>">

                <label style="color:red; margin-top:20px;">New Password:</label>
                <input type="password" name="password" placeholder="Leave blank to keep current">

                <button type="submit" name="update_profile" style="background:#007bff; margin-top:20px;">Update Profile</button>
            </form>
        </div>
    <?php } ?>

</body>
</html>