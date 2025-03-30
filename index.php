<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = ""; // Default password for XAMPP
$dbname = "diu_transportation";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start(); // Start the session

$message = ""; // Variable to store dynamic messages

function notifyWebSocketServer($message) {
    $socket = @stream_socket_client("tcp://localhost:8080", $errno, $errstr, 30); // Suppress warnings
    if (!$socket) {
        error_log("WebSocket connection failed: $errstr ($errno)");
        return;
    }
    fwrite($socket, $message);
    fclose($socket);
}

// Check if cookies exist and pre-fill the login form
if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) {
    $savedUsername = $_COOKIE['username'];
    $savedPassword = $_COOKIE['password'];
} else {
    $savedUsername = '';
    $savedPassword = '';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $rememberMe = isset($_POST['remember_me']); // Check if "Remember Me" is selected

    $query = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username']; // Store username in session
            $_SESSION['first_name'] = $user['first_name']; // Store first name in session

            // Log login activity
            $activity_type = "Login";
            $activity_details = "User logged in.";
            $conn->query("INSERT INTO user_activities (username, activity_type, activity_details) VALUES ('$username', '$activity_type', '$activity_details')");

            // Set cookies if "Remember Me" is selected
            if ($rememberMe) {
                setcookie('username', $username, time() + 7200, '/'); // Save for 2 hours
                setcookie('password', $password, time() + 7200, '/'); // Save for 2 hours
            } else {
                // Clear cookies if "Remember Me" is not selected
                setcookie('username', '', time() - 3600, '/');
                setcookie('password', '', time() - 3600, '/');
            }

            header("Location: dashboard.php"); // Redirect to dashboard
            exit;
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }
}

// Handle sign-up form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['username'], $_POST['password'])) {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password

    // Check for duplicate email or username
    $checkQuery = "SELECT * FROM users WHERE email='$email' OR username='$username'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        $message = "Error: Email or Username already exists.";
    } else {
        $query = "INSERT INTO users (first_name, last_name, email, phone, username, password) VALUES ('$first_name', '$last_name', '$email', '$phone', '$username', '$password')";
        if ($conn->query($query) === TRUE) {
            $_SESSION['username'] = $username; // Store username in session
            $_SESSION['first_name'] = $first_name; // Store first name in session
            header("Location: dashboard.php"); // Redirect to dashboard
            exit;
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

// Handle AJAX requests for real-time updates
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_updates') {
    $query = "SELECT first_name, last_name, username, email FROM users ORDER BY created_at DESC LIMIT 10";
    $result = $conn->query($query);

    $updates = [];
    while ($row = $result->fetch_assoc()) {
        $updates[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($updates);
    exit;
}

// Handle cookie cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_cookies'])) {
    setcookie('username', '', time() - 3600, '/'); // Clear username cookie
    setcookie('password', '', time() - 3600, '/'); // Clear password cookie
    $savedUsername = '';
    $savedPassword = '';
    $message = "Saved login credentials have been cleared.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DIU Student Transportation System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.0.2/dist/full.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .bg-banner {
            background-image: url('banner.png'); /* Path to banner.png */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen bg-gray-100">
    <div class="flex-grow relative bg-banner overflow-hidden">
        <div class="bg-black bg-opacity-50 absolute inset-0"></div> <!-- Add dark overlay -->
        <div class="flex items-center justify-center relative z-10 min-h-screen">
            <!-- Display dynamic message -->
            <?php if (!empty($message)): ?>
                <div class="fixed top-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-4 py-2 rounded shadow-md text-center">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="absolute top-10 text-center text-white px-4">
                <h1 class="text-4xl md:text-6xl lg:text-9xl font-bold">DIU</h1>
                <h2 class="text-xs md:text-sm lg:text-lg bg-green-600 px-4 py-1 rounded-md mt-2">STUDENT TRANSPORTATION SYSTEM</h2>
            </div>
            <div id="loginSection" class="bg-white bg-opacity-20 backdrop-blur-md p-6 md:p-8 rounded-lg shadow-lg w-72 md:w-80 lg:w-96 text-white">
                <form method="POST" action="">
                    <label class="block mb-2 font-semibold">USERNAME:</label>
                    <input type="text" name="username" placeholder="username" value="<?php echo htmlspecialchars($savedUsername); ?>" class="input input-bordered w-full text-white" required />
                    <label class="block mt-4 mb-2 font-semibold">PASSWORD:</label>
                    <input type="password" name="password" placeholder="password" value="<?php echo htmlspecialchars($savedPassword); ?>" class="input input-bordered w-full text-white" required />
                    <div class="flex items-center mt-4">
                        <input type="checkbox" name="remember_me" id="rememberMe" class="mr-2" <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?> />
                        <label for="rememberMe" class="text-sm">Remember Me</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full mt-6">LOGIN</button>
                </form>
                <form method="POST" action="" class="mt-4">
                    <button type="submit" name="cancel_cookies" class="btn btn-secondary w-full">Cancel Saved Login</button>
                </form>
                <div class="flex justify-between mt-4 text-sm">
                    <a href="#" class="text-blue-400 hover:underline" onclick="forgotPassword()">Forgot Password?</a>
                    <a href="#" class="text-blue-400 hover:underline" onclick="showSignUp()">Sign Up</a>
                </div>
            </div>
            <div id="signUpSection" class="hidden bg-white bg-opacity-20 backdrop-blur-md p-6 md:p-8 rounded-lg shadow-lg w-80 md:w-96 text-white">
                <h2 class="text-2xl font-bold mb-4 text-center">Sign Up</h2>
                <form method="POST" action="">
                    <label class="block mb-2 font-semibold">First Name:</label>
                    <input type="text" name="first_name" placeholder="First Name" class="input input-bordered w-full text-black" required />
                    <label class="block mt-4 mb-2 font-semibold">Last Name:</label>
                    <input type="text" name="last_name" placeholder="Last Name" class="input input-bordered w-full text-black" required />
                    <label class="block mt-4 mb-2 font-semibold">Email:</label>
                    <input type="email" name="email" placeholder="Email" class="input input-bordered w-full text-black" required />
                    <label class="block mt-4 mb-2 font-semibold">Phone Number:</label>
                    <input type="tel" name="phone" placeholder="Phone Number" class="input input-bordered w-full text-black" required />
                    <label class="block mt-4 mb-2 font-semibold">Username:</label>
                    <input type="text" name="username" placeholder="Username" class="input input-bordered w-full text-black" required />
                    <label class="block mt-4 mb-2 font-semibold">Password:</label>
                    <input type="password" name="password" placeholder="Password" class="input input-bordered w-full text-black" required />
                    <button type="submit" class="btn btn-primary w-full mt-6">Sign Up</button>
                </form>
                <div class="mt-4 text-sm text-center">
                    <a href="#" class="text-blue-400 hover:underline" onclick="showLogin()">Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-80">
            <h3 class="text-xl font-bold mb-4 text-center">Forgot Password</h3>
            <p class="text-sm text-gray-600 mb-4 text-center">Enter your email address to receive a password reset link.</p>
            <form id="forgotPasswordForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" id="forgotPasswordEmail" class="input input-bordered w-full" placeholder="Enter your email" required />
                </div>
                <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
            </form>
            <button onclick="closeForgotPasswordModal()" class="btn btn-secondary w-full mt-4">Cancel</button>
        </div>
    </div>

    <?php include 'footer.php'; ?> <!-- Include the footer -->
    <script src="script.js"></script>
    <script>
        // Show the Forgot Password modal
        function forgotPassword() {
            document.getElementById('forgotPasswordModal').classList.remove('hidden');
        }

        // Close the Forgot Password modal
        function closeForgotPasswordModal() {
            document.getElementById('forgotPasswordModal').classList.add('hidden');
        }

        // Handle Forgot Password form submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('forgotPasswordEmail').value;

            // Simulate sending a password reset link
            alert(`A password reset link has been sent to ${email}.`);
            closeForgotPasswordModal();
        });
    </script>
</body>
</html>
