<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diu_transportation";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE payments SET payment_status = ? WHERE stripe_payment_id = ?");
    $stmt->bind_param("ss", $status, $payment_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Payment status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update payment status.']);
    }

    $stmt->close();
    $conn->close();
}
?>
