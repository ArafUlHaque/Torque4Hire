<?php
session_start();
include 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        
        if (password_verify($password, $admin['password_hash'])) {
            
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['role'] = 'ADMIN'; 
            
            
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $message = "Incorrect Password";
        }
    } else {
        $message = "Admin account not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <form method="POST" action="">
        <h2 style="color: #dc3545;">Admin Login</h2>
        
        <p style="color:red; text-align:center; font-weight:bold;"><?php echo $message; ?></p>

        <label>Admin Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="pass1" required>
            <span class="toggle-icon" onclick="togglePassword('pass1', this)">🙈</span>
        </div>

        <button type="submit" style="background-color: #dc3545;">Login</button>
        
        <a href="admin_register.php" style="font-size: 12px; color: #999;">Register new Admin (Dev Only)</a>
    </form>

    <script>
        function togglePassword(inputId, icon) {
            var input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "👁️"; 
            } else {
                input.type = "password";
                icon.textContent = "🙈"; 
            }
        }
    </script>
</body>
</html>