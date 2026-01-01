<?php
session_start();

$redirect_url = "login.php"; 

if (isset($_SESSION['role']) && $_SESSION['role'] == 'ADMIN') {
    $redirect_url = "admin_login.php"; 
}

session_unset();
session_destroy();

header("Location: " . $redirect_url);
exit();
?>