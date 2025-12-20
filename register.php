<?php
include 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        $sql_user = "INSERT INTO users (email, name, password_hash) VALUES ('$email', '$name', '$hashed_password')";
        $conn->query($sql_user);

        if ($role == 'OWNER') {
            $company = $_POST['company_name'];
            $sql_owner = "INSERT INTO owners (owner_email, company_name) VALUES ('$email', '$company')";
            $conn->query($sql_owner);
        } elseif ($role == 'RENTER') {
            $license = $_POST['license'];
            $sql_renter = "INSERT INTO renters (renter_email, license_no) VALUES ('$email', '$license')";
            $conn->query($sql_renter);
        }

        $conn->commit();
        $message = "Registration Successful! <a href='login.php'>Login Here</a>";
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $message = "Error: " . $exception->getMessage();
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
    <h2>Torque4Hire</h2>
    
    <p style="color:red;"><?php echo $message; ?></p>
    
    <form method="POST" action="">
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>
        
        <label>Role:</label>
        <select name="role" required>
            <option value="RENTER">Renter</option>
            <option value="OWNER">Machine Owner</option>
        </select>

        <input type="text" name="company_name" placeholder="Company Name (Owners only)">
        <input type="text" name="license" placeholder="License No (Renters only)">

        <button type="submit">Register</button>
    </form>
</body>
</html>