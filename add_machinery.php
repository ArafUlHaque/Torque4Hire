<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'OWNER') {
    die("Access Denied. Only Owners can add machinery.");
}

$message = "";

$categories = $conn->query("SELECT * FROM machine_categories");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_email = $_SESSION['user_email'];
    $model_name = $_POST['model_name'];
    $category_id = $_POST['category_id'];
    $daily_rate = $_POST['daily_rate'];
    
    $stmt = $conn->prepare("INSERT INTO machinery (owner_email, category_id, model_name, daily_rate, status) VALUES (?, ?, ?, ?, 'AVAILABLE')");
    $stmt->bind_param("sisd", $owner_email, $category_id, $model_name, $daily_rate);

    if ($stmt->execute()) {
        $message = "Machine added successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Machinery - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background-color: white;
        }
        
        .welcome-text {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <form method="POST" action="">
        <h2 style="margin-top: 0;">Add New Machine</h2>
        
        <div class="welcome-text">
            Welcome, <b><?php echo htmlspecialchars($_SESSION['user_name']); ?></b>
        </div>
        
        <?php if($message): ?>
            <p style="color: <?php echo strpos($message, 'Error') !== false ? 'red' : 'green'; ?>; text-align: center; font-weight: bold;">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <label>Model Name:</label>
        <input type="text" name="model_name" placeholder="e.g. CAT 320 Excavator" required>
        
        <label>Category:</label>
        <select name="category_id" required>
            <option value="" disabled selected>Select a Category</option>
            <?php 
            $categories->data_seek(0); 
            while($row = $categories->fetch_assoc()) { ?>
                <option value="<?php echo $row['category_id']; ?>">
                    <?php echo $row['category_name']; ?>
                </option>
            <?php } ?>
        </select>

        <label>Daily Rate (BDT/USD):</label>
        <input type="number" step="0.01" name="daily_rate" placeholder="0.00" required>

        <button type="submit">Add Machine</button>
        
        <a href="logout.php" style="color: #dc3545;">Logout</a>
    </form>
    
</body>
</html>