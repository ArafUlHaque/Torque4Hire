<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "heavy_hire_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>