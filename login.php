<?php
session_start();
include 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1. Check if user exists
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            // 3. Set Session Variables
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            
            $check_owner = $conn->query("SELECT * FROM owners WHERE owner_email = '$email'");
            if ($check_owner->num_rows > 0) {
                $_SESSION['role'] = 'OWNER';
                header("Location: add_machinery.php");
            } else {
                $_SESSION['role'] = 'RENTER';
                header("Location: view_machinery.php");
            }
            exit();
        } else {
            $message = "Invalid Password!";
        }
    } else {
        $message = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Login</h2>
    <p style="color:red;"><?php echo $message; ?></p>
    <form method="POST" action="">
        Email: <input type="email" name="email" required><br><br>
        Password: <input type="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>