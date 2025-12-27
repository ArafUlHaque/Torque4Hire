<?php
include 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    //check if pass match
    if ($password !== $confirm_password) {
        $message = "Error: Passwords do not match!";
    } else {
        $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $message = "Error: That email is already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $conn->begin_transaction();

            try {
                $stmt_user = $conn->prepare("INSERT INTO users (email, name, password_hash) VALUES (?, ?, ?)");
                $stmt_user->bind_param("sss", $email, $name, $hashed_password);
                $stmt_user->execute();

                if ($role == 'OWNER') {
                    $company = $_POST['company_name'];
                    $stmt_owner = $conn->prepare("INSERT INTO owners (owner_email, company_name) VALUES (?, ?)");
                    $stmt_owner->bind_param("ss", $email, $company);
                    $stmt_owner->execute();
                } elseif ($role == 'RENTER') {
                    $license = $_POST['license'];
                    $stmt_renter = $conn->prepare("INSERT INTO renters (renter_email, license_no) VALUES (?, ?)");
                    $stmt_renter->bind_param("ss", $email, $license);
                    $stmt_renter->execute();
                }

                $conn->commit();
                $message = "Registration Successful! <a href='login.php'>Login Here</a>";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "System Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Torque4Hire</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

    <form method="POST" action="">
        <h2>Register for Torque4Hire</h2>
        
        <p style="color:red;text-align:center;"><?php echo $message; ?></p>
        
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <div class="password-wrapper">
            <input type="password" name="password" id="pass1" required>
            <span class="toggle-icon" onclick="togglePassword('pass1', this)">🙈</span>
        </div>

        <label>Confirm Password:</label>
        <div class="password-wrapper">
            <input type="password" name="confirm_password" id="pass2" required placeholder="Retype password">
            <span class="toggle-icon" onclick="togglePassword('pass2', this)">🙈</span>
        </div>
        
        <label>I want to be a:</label>
        <select name="role" id="roleSelect" required onchange="toggleFields()">
            <option value="RENTER">Renter (I need machines)</option>
            <option value="OWNER">Owner (I have machines)</option>
        </select>

        <div id="ownerField" class="hidden">
            <label>Company Name:</label>
            <input type="text" name="company_name" placeholder="Construction Co. Ltd">
        </div>

        <div id="renterField">
            <label>Operating License No:</label>
            <input type="text" name="license" placeholder="License #12345">
        </div>

        <button type="submit">Register</button>
        <a href="login.php">Already have an account? Login</a>
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

        function toggleFields() {
            var role = document.getElementById("roleSelect").value;
            var ownerDiv = document.getElementById("ownerField");
            var renterDiv = document.getElementById("renterField");

            if (role === "OWNER") {
                ownerDiv.classList.remove("hidden"); 
                renterDiv.classList.add("hidden"); 
            } else {
                ownerDiv.classList.add("hidden");
                renterDiv.classList.remove("hidden"); 
            }
        }
    </script>
</body>
</html>