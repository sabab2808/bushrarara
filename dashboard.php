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

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bus'], $_POST['name'], $_POST['contact'])) {
    $username = $_SESSION['username'];
    $bus = $conn->real_escape_string($_POST['bus']);
    $name = $conn->real_escape_string($_POST['name']);
    $contact = $conn->real_escape_string($_POST['contact']);
    $price = 30; // Fixed ticket price

    $activity_type = "Bus Booking";
    $activity_details = "Booked $bus for $name (Contact: $contact)";

    $query = "INSERT INTO user_activities (username, activity_type, activity_details) VALUES ('$username', '$activity_type', '$activity_details')";
    if ($conn->query($query)) {
        $_SESSION['booking_success'] = "Your seat has been booked successfully!";

        // Log payment activity
        $payment_activity_type = "Payment";
        $payment_activity_details = "Paid $$price for booking $bus";
        $conn->query("INSERT INTO user_activities (username, activity_type, activity_details) VALUES ('$username', '$payment_activity_type', '$payment_activity_details')");
    } else {
        $_SESSION['booking_error'] = "Failed to book the seat. Please try again.";
    }

    // Redirect to the same page to prevent form resubmission
    header("Location: dashboard.php");
    exit;
}

// Handle cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bookingId = (int)$_POST['cancel_booking_id'];

    // Fetch the bus ID from the booking
    $query = "SELECT activity_details FROM user_activities WHERE id = $bookingId AND username = '{$_SESSION['username']}'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $activity = $result->fetch_assoc();
        preg_match('/Bus ID (\d+)/', $activity['activity_details'], $matches);
        if (isset($matches[1])) {
            $busId = (int)$matches[1];

            // Increment the available seats for the bus
            $conn->query("UPDATE buses SET available_seats = available_seats + 1 WHERE id = $busId");

            // Delete the booking activity
            $conn->query("DELETE FROM user_activities WHERE id = $bookingId AND username = '{$_SESSION['username']}'");
            echo json_encode(['success' => true, 'message' => 'Booking has been canceled successfully.', 'bookingId' => $bookingId]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'message' => 'Failed to cancel the booking. Please try again.']);
    exit;
}

// Handle clear activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_activity'])) {
    $conn->query("DELETE FROM user_activities WHERE username = '{$_SESSION['username']}'");
    $_SESSION['activity_success'] = "All activities have been cleared successfully.";

    // Redirect to prevent form resubmission
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.0.2/dist/full.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Include the global theme -->
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
        <div class="container mx-auto p-6 relative z-10">
            <h1 class="text-2xl md:text-3xl font-bold mb-4 text-center text-white">Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
            <!-- Display success or error message -->
            <?php if (isset($_SESSION['booking_success'])): ?>
                <div class="bg-green-500 text-white px-4 py-2 rounded shadow-md mb-4">
                    <?php echo htmlspecialchars($_SESSION['booking_success']); ?>
                </div>
                <?php unset($_SESSION['booking_success']); ?>
            <?php elseif (isset($_SESSION['booking_error'])): ?>
                <div class="bg-red-500 text-white px-4 py-2 rounded shadow-md mb-4">
                    <?php echo htmlspecialchars($_SESSION['booking_error']); ?>
                </div>
                <?php unset($_SESSION['booking_error']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['activity_success'])): ?>
                <div class="bg-green-500 text-white px-4 py-2 rounded shadow-md mb-4">
                    <?php echo htmlspecialchars($_SESSION['activity_success']); ?>
                </div>
                <?php unset($_SESSION['activity_success']); ?>
            <?php elseif (isset($_SESSION['activity_error'])): ?>
                <div class="bg-red-500 text-white px-4 py-2 rounded shadow-md mb-4">
                    <?php echo htmlspecialchars($_SESSION['activity_error']); ?>
                </div>
                <?php unset($_SESSION['activity_error']); ?>
            <?php endif; ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Bus Schedule -->
                <div class="card bg-gradient-to-r from-blue-500 to-blue-700 text-white shadow-lg rounded-lg p-6">
                    <h2 class="text-lg md:text-xl font-bold mb-2">Bus Schedule</h2>
                    <p class="mb-4">View the latest bus schedules for your routes.</p>
                    <button onclick="showModal('scheduleModal')" class="btn btn-primary w-full">View Schedule</button>
                </div>
                <!-- Bus Routes -->
                <div class="card bg-gradient-to-r from-green-500 to-green-700 text-white shadow-lg rounded-lg p-6">
                    <h2 class="text-lg md:text-xl font-bold mb-2">Bus Routes</h2>
                    <p class="mb-4">Explore the available bus routes.</p>
                    <button onclick="showModal('routesModal')" class="btn btn-primary w-full">View Routes</button>
                </div>
                <!-- Other Options -->
                <div class="card bg-gradient-to-r from-purple-500 to-purple-700 text-white shadow-lg rounded-lg p-6">
                    <h2 class="text-lg md:text-xl font-bold mb-2">Other Options</h2>
                    <p class="mb-4">Access additional features and settings.</p>
                    <button onclick="showModal('optionsModal')" class="btn btn-primary w-full">Explore More</button>
                </div>
            </div>
            <div class="mt-6">
                <button onclick="showModal('activityModal')" class="text-blue-500 hover:underline">View Activity</button>
                <a href="logout.php" class="text-red-500 hover:underline ml-4">Logout</a>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <!-- Schedule Modal -->
    <div id="scheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <div class="text-black">
                <p class="mb-4 font-bold">Here are the latest bus schedules:</p>
                <ul class="list-disc list-inside text-black mb-4">
                    <li>Bus 101: 8:00 AM - Mirpur</li>
                    <li>Bus 102: 9:30 AM - Uttara</li>
                    <li>Bus 103: 11:00 AM - Dhanmondi</li>
                </ul>
            </div>
            <h3 class="text-xl font-bold text-black mb-4">Bus Schedule</h3>
            <h4 class="text-lg font-bold text-black mb-2">Book a Seat</h4>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Select Bus</label>
                    <select name="bus" class="input input-bordered text-white w-full" required>
                        <option value="Bus 101">Surjomukhi</option>
                        <option value="Bus 102">Dolphin</option>
                        <option value="Bus 103">Oporajita</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Your Name</label>
                    <input type="text" name="name" class="input input-bordered text-white w-full" placeholder="Enter your name" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                    <div class="flex space-x-2">
                        <select name="country_code" class="input input-bordered text-white w-1/4" required>
                            <option value="+880">+880 (BD)</option>
                            <option value="+1">+1 (US)</option>
                            <option value="+44">+44 (UK)</option>
                            <option value="+91">+91 (IN)</option>
                        </select>
                        <input type="tel" name="contact" class="input input-bordered text-white w-3/4" placeholder="Enter your number" required />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Ticket Price</label>
                    <input type="text" name="price" value="30" class="input input-bordered text-white w-full" readonly />
                </div>
                <button type="button" onclick="showModal('paymentModal')" class="btn btn-primary w-full">Buy Ticket</button>
            </form>
            <button onclick="closeModal('scheduleModal')" class="btn btn-secondary w-full mt-4">Close</button>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Payment Details</h3>
            <form id="paymentForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Card Number</label>
                    <input type="text" class="input input-bordered w-full" placeholder="1234 5678 9012 3456" required />
                </div>
                <div class="flex space-x-4">
                    <div class="w-1/2">
                        <label class="block text-sm font-medium text-gray-700">Expiration Date</label>
                        <input type="text" class="input input-bordered w-full" placeholder="MM/YY" required />
                    </div>
                    <div class="w-1/2">
                        <label class="block text-sm font-medium text-gray-700">CVV</label>
                        <input type="text" class="input input-bordered w-full" placeholder="123" required />
                    </div>
                </div>
                <button type="button" onclick="processPayment()" class="btn btn-primary w-full">Pay Now</button>
            </form>
            <button onclick="closeModal('paymentModal')" class="btn btn-secondary w-full mt-4">Cancel</button>
        </div>
    </div>

    <!-- Routes Modal -->
    <div id="routesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white text-black p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Bus Routes</h3>
            <p>Here are the available bus routes:</p>
            <ul class="list-disc list-inside mt-4">
                <li>Route A: Stop 1 → Stop 2 → Stop 3</li>
                <li>Route B: Stop 4 → Stop 5 → Stop 6</li>
                <li>Route C: Stop 7 → Stop 8 → Stop 9</li>
            </ul>
            <button onclick="closeModal('routesModal')" class="btn btn-secondary w-full mt-4">Close</button>
        </div>
    </div>

    <!-- Other Options Modal -->
    <div id="optionsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-purple-100 p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4 text-purple-700">Other Options</h3>
            <ul class="space-y-2">
                <li><a href="profile_settings.php" class="text-blue-500 hover:underline">Profile Settings</a></li>
                <li><a href="notifications.php" class="text-blue-500 hover:underline">Notifications</a></li>
                <li><a href="help_support.php" class="text-blue-500 hover:underline">Help & Support</a></li>
            </ul>
            <button onclick="closeModal('optionsModal')" class="btn btn-secondary w-full mt-4">Close</button>
        </div>
    </div>

    <!-- Profile Settings Modal -->
    <div id="profileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-purple-100 p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4 text-purple-700">Profile Settings</h3>
            <p>Update your profile information here.</p>
            <button onclick="closeModal('profileModal')" class="btn btn-secondary w-full mt-4">Close</button>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div id="notificationsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-green-100 p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4 text-green-700">Notifications</h3>
            <p>View and manage your notifications here.</p>
            <button onclick="closeModal('notificationsModal')" class="btn btn-secondary w-full mt-4">Close</button>
        </div>
    </div>

    <!-- Help & Support Modal -->
    <div id="helpModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-blue-100 p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4 text-blue-700">Help & Support</h3>
            <p>Contact support or find answers to your questions here.</p>
            <button onclick="closeModal('helpModal')" class="btn btn-secondary w-full mt-4">Close</button>
        </div>
    </div>

    <!-- Activity Modal -->
    <div id="activityModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white text-black p-6 rounded-lg shadow-lg w-96">
            <h3 class="text-xl font-bold mb-4">Your Activities</h3>
            <ul id="activityList" class="list-disc list-inside mb-4">
                <!-- Activities will be dynamically loaded here -->
            </ul>
            <form method="POST">
                <button type="submit" name="clear_activity" class="btn btn-secondary w-full mt-4">Clear All Activities</button>
            </form>
            <button onclick="closeModal('activityModal')" class="btn btn-primary w-full mt-4">Close</button>
        </div>
    </div>

    <?php include 'footer.php'; ?> <!-- Include the footer -->
    <script>
        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            if (modalId === 'activityModal') {
                fetchActivities(); // Fetch activities when the activity modal is opened
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        function fetchActivities() {
            fetch('fetch_activities.php')
                .then(response => response.json())
                .then(data => {
                    const activityList = document.getElementById('activityList');
                    activityList.innerHTML = ''; // Clear existing activities

                    if (data.length === 0) {
                        activityList.innerHTML = '<p class="text-gray-500">No activities found.</p>';
                        return;
                    }

                    data.forEach(activity => {
                        const listItem = document.createElement('li');
                        listItem.id = `activity-${activity.id}`;
                        listItem.innerHTML = `
                            <strong>${activity.activity_type}:</strong> ${activity.activity_details} <br>
                            <small class="text-gray-500">On ${activity.created_at}</small>
                            ${activity.activity_type === 'Bus Booking' ? `<button onclick="cancelBooking(${activity.id})" class="text-red-500 hover:underline mt-2">Cancel Booking</button>` : ''}
                        `;
                        activityList.appendChild(listItem);
                    });
                })
                .catch(error => {
                    console.error('Error fetching activities:', error);
                });
        }

        function processPayment() {
            alert('Payment successful! Your ticket has been booked.');
            closeModal('paymentModal');
        }

        // Cancel a booking
        function cancelBooking(bookingId) {
            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cancel_booking_id=${bookingId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the canceled booking from the activity list
                    const activityElement = document.getElementById(`activity-${data.bookingId}`);
                    if (activityElement) {
                        activityElement.remove();
                    }
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error canceling booking:', error);
                alert('An error occurred while canceling the booking. Please try again.');
            });
        }
    </script>
</body>
</html>
