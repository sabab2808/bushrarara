<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diu_transportation";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Log logout activity
    $activity_type = "Logout";
    $activity_details = "User logged out.";
    $conn->query("INSERT INTO user_activities (username, activity_type, activity_details) VALUES ('$username', '$activity_type', '$activity_details')");
}

session_destroy(); // Destroy the session
header("Location: index.php"); // Redirect to login page
exit;
?>
