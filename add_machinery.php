<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_email']) || $_SESSION['role'] != 'OWNER') {
    die("Access Denied. Only Owners can add machinery.");
}

$message = "";

$categories = $conn->query("SELECT * FROM machine_categories");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_email = $_SESSION['user_email']; // Get email from Session (Secure)
    $model_name = $_POST['model_name'];
    $category_id = $_POST['category_id'];
    $daily_rate = $_POST['daily_rate'];
    
    $sql = "INSERT INTO machinery (owner_email, category_id, model_name, daily_rate, status) 
            VALUES ('$owner_email', '$category_id', '$model_name', '$daily_rate', 'AVAILABLE')";

    if ($conn->query($sql) === TRUE) {
        $message = "Machine added successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Machinery</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Add New Machine</h2>
    <p>Welcome, Owner: <?php echo $_SESSION['user_name']; ?></p>
    <p style="color:green;"><?php echo $message; ?></p>

    <form method="POST" action="">
        Model Name: <input type="text" name="model_name" required><br><br>
        
        Category: 
        <select name="category_id">
            <?php while($row = $categories->fetch_assoc()) { ?>
                <option value="<?php echo $row['category_id']; ?>">
                    <?php echo $row['category_name']; ?>
                </option>
            <?php } ?>
        </select><br><br>

        Daily Rate ($): <input type="number" step="0.01" name="daily_rate" required><br><br>

        <button type="submit">Add Machine</button>
    </form>
    
    <br><a href="logout.php">Logout</a>
</body>
</html>