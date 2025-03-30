const stripe = Stripe('your_stripe_publishable_key'); // Replace with your Stripe publishable key
const elements = stripe.elements();
const cardElement = elements.create('card');
cardElement.mount('#card-element');

const form = document.getElementById('payment-form');
const clientSecret = '<?php echo $clientSecret; ?>';

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: {
            card: cardElement,
        },
    });

    if (error) {
        document.getElementById('card-errors').textContent = error.message;
    } else if (paymentIntent.status === 'succeeded') {
        // Update payment status in the database
        fetch('update_payment_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `payment_id=${paymentIntent.id}&status=succeeded`
        });

        alert('Payment successful!');
        window.location.href = 'dashboard.php';
    }
});