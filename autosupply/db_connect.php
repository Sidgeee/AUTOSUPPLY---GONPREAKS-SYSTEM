

<?php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "auto_supply_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection - FIXED syntax here
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for special characters
$conn->set_charset("utf8mb4");
?>