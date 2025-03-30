<?php
require 'vendor/autoload.php'; // Include Stripe PHP library

\Stripe\Stripe::setApiKey('your_stripe_secret_key'); // Replace with your Stripe secret key

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "diu_transportation";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bus = $_POST['bus'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $price = $_POST['price'];
    $user_id = $_SESSION['user_id']; // Assuming `user_id` is stored in the session

    try {
        // Create a payment intent
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => $price * 100, // Amount in cents
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'metadata' => [
                'bus' => $bus,
                'name' => $name,
                'contact' => $contact,
            ],
        ]);

        $clientSecret = $paymentIntent->client_secret;

        // Save payment details to the database
        $stmt = $conn->prepare("INSERT INTO payments (user_id, bus_name, amount, payment_status, stripe_payment_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $bus, $price, $payment_status, $stripe_payment_id);

        $payment_status = 'pending'; // Initial status
        $stripe_payment_id = $paymentIntent->id;

        $stmt->execute();
        $stmt->close();
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo 'Error: ' . $e->getMessage();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Payment</title>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css"> <!-- Include the global theme -->
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-xl font-bold mb-4">Complete Your Payment</h2>
        <form id="payment-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Card Number</label>
                <input type="text" id="card-number" class="input input-bordered w-full" placeholder="1234 5678 9012 3456" required />
            </div>
            <div class="flex space-x-4">
                <div class="w-1/2">
                    <label class="block text-sm font-medium text-gray-700">Expiration Date</label>
                    <input type="text" id="card-expiry" class="input input-bordered w-full" placeholder="MM/YY" required />
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-medium text-gray-700">CVV</label>
                    <input type="text" id="card-cvv" class="input input-bordered w-full" placeholder="123" required />
                </div>
            </div>
            <button id="submit" class="btn btn-primary w-full mt-4">Pay $<?php echo htmlspecialchars($price); ?></button>
        </form>
    </div>
    <?php include 'footer.php'; ?> <!-- Include the footer -->

    <script>
        const stripe = Stripe('your_stripe_publishable_key'); // Replace with your Stripe publishable key
        const form = document.getElementById('payment-form');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            // Simulate payment processing
            alert('Payment processing...');
            setTimeout(() => {
                alert('Payment successful!');
                window.location.href = 'dashboard.php';
            }, 2000);
        });
    </script>
</body>
</html>
