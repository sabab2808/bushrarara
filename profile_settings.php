<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php"); // Redirect to login if not authenticated
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diu_transportation";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$username = $_SESSION['username'];
$query = "SELECT * FROM users WHERE username = '$username'";
$result = $conn->query($query);
$user = $result->fetch_assoc();

// Handle password update
$passwordMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_password'], $_POST['new_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];

    // Verify old password
    if (password_verify($oldPassword, $user['password'])) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

        // Update the password in the database
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashedPassword, $username);

        if ($stmt->execute()) {
            $passwordMessage = "Password updated successfully.";

            // Log password update activity
            $activity_type = "Password Update";
            $activity_details = "User updated their password.";
            $conn->query("INSERT INTO user_activities (username, activity_type, activity_details) VALUES ('$username', '$activity_type', '$activity_details')");
        } else {
            $passwordMessage = "Failed to update password. Please try again.";
        }

        $stmt->close();
    } else {
        $passwordMessage = "Old password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.0.2/dist/full.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Include the global theme -->
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="bg-banner2">
        <img src="banner2.png" alt="Banner" class="w-full h-full object-cover absolute top-0 left-0 opacity-85" /> <!-- Background image -->
    </div> <!-- Add the blurred background -->
    <div class="container mx-auto p-6 relative z-10">
        <h1 class="text-2xl font-bold mb-6 text-black">Profile Settings</h1>
        <?php if (!empty($passwordMessage)): ?>
            <div class="mb-4 p-4 rounded <?php echo strpos($passwordMessage, 'successfully') !== false ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                <?php echo htmlspecialchars($passwordMessage); ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="space-y-4 bg-white p-6 rounded-lg shadow-md">
            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" class="input input-bordered w-full" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="input input-bordered w-full" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" class="input input-bordered w-full" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Institution</label>
                <input type="text" name="institution" placeholder="Enter your institution" class="input input-bordered w-full" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Semester</label>
                <input type="text" name="semester" placeholder="Enter your semester" class="input input-bordered w-full" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Batch</label>
                <input type="text" name="batch" placeholder="Enter your batch" class="input input-bordered w-full" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Profile Photo</label>
                <input type="file" name="profile_photo" class="file-input file-input-bordered w-full" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Old Password</label>
                <input type="password" name="old_password" placeholder="Enter old password" class="input input-bordered w-full" required />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="new_password" placeholder="Enter new password" class="input input-bordered w-full" required />
            </div>
            <button type="submit" class="btn btn-primary w-full">Save Changes</button>
        </form>
        <a href="dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
    </div>
    <?php include 'footer.php'; ?> <!-- Include the footer -->
</body>
</html>
