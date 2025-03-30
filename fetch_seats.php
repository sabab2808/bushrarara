<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diu_transportation";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

header('Content-Type: application/json');

$query = "SELECT bus_name, available_seats FROM buses";
$result = $conn->query($query);

$buses = [];
while ($row = $result->fetch_assoc()) {
    $buses[] = $row;
}

echo json_encode($buses);
$conn->close();
?>
