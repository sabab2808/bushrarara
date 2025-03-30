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

header('Content-Type: application/json');

$username = $_SESSION['username'];
$query = "SELECT id, activity_type, activity_details, created_at FROM user_activities WHERE username = '$username' ORDER BY created_at DESC";
$result = $conn->query($query);

$activities = [];
while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

echo json_encode($activities);
$conn->close();
?>
