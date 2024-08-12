<?php
$servername = "localhost";
$username = "lada_Demo";
$password = "P55w0rd";
$dbname = "lada_demo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
