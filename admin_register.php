<?php
include 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    
    if ($password !== $confirm_password) {
        $message = "Error: Passwords do not match!";
    } else {
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        
        $stmt = $conn->prepare("INSERT INTO admins (email, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashed_password);

        try {
            if ($stmt->execute()) {
                $message = "Admin Created Successfully! <a href='admin_login.php'>Login Here</a>";
            } else {
                $message = "Error: " . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            
            if ($e->getCode() == 1062) {
                $message = "Error: This email is already an Admin.";
            } else {
                $message = "System Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <form method="POST" action="">
        <h2 style="color: #dc3545;">Admin Registration</h2>
        <p style="text-align:center; color: #666;">(Authorized Personnel Only)</p>
        
        <p style="color:red; text-align:center;"><?php echo $message; ?></p>

        <label>Admin Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="pass1" required>
            <span class="toggle-icon" onclick="togglePassword('pass1', this)">🙈</span>
        </div>

        <label>Confirm Password:</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" id="pass2" required>
            <span class="toggle-icon" onclick="togglePassword('pass2', this)">🙈</span>
        </div>

        <button type="submit" style="background-color: #dc3545;">Create Admin</button>
        
        <a href="admin_login.php">Go to Admin Login</a>
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