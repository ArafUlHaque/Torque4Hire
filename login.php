<?php
session_start();
include 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            
            
            
            
            $check_owner = $conn->query("SELECT * FROM owners WHERE owner_email = '$email'");
            if ($check_owner->num_rows > 0) {
                $_SESSION['role'] = 'OWNER';
                header("Location: owner_dashboard.php"); 
                exit();
            }

            
            $check_trainer = $conn->query("SELECT * FROM trainers WHERE trainer_email = '$email'");
            if ($check_trainer->num_rows > 0) {
                $_SESSION['role'] = 'TRAINER';
                header("Location: trainer_dashboard.php");
                exit();
            }

            
            $_SESSION['role'] = 'RENTER';
            header("Location: renter_dashboard.php"); 
            exit();

        } else {
            $message = "Incorrect Password!";
        }
    } else {
        $message = "User not found! Please register first.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Torque4Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .show-pass-wrapper {
            display: flex;
            align-items: center;
            font-size: 14px;
            margin-bottom: 15px;
            color: #666;
        }
        .show-pass-wrapper input {
            margin: 0 8px 0 0;
            width: auto;
        }
    </style>
</head>
<body>

    <form method="POST" action="">
        <h2 style="margin-top: 0;">Login</h2>
        
        <p style="color:red;text-align:center;font-weight:bold;"><?php echo $message; ?></p>
        
        <label>Email:</label>
        <input type="email" name="email" placeholder="Enter your email" required>

        <label>Password:</label>
        <input type="password" name="password" id="passwordInput" placeholder="Enter your password" required>

        <div class="show-pass-wrapper">
            <input type="checkbox" onclick="togglePassword()"> Show Password
        </div>

        <button type="submit">Login</button>
        
        <a href="register.php">Don't have an account? Register here</a>
    </form>

    <script>
        function togglePassword() {
            var x = document.getElementById("passwordInput");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>
</html>