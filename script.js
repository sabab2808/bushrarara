// Show the sign-up section and hide the login section
function showSignUp() {
    document.getElementById('loginSection').classList.add('hidden');
    document.getElementById('signUpSection').classList.remove('hidden');
}

// Show the login section and hide the sign-up section
function showLogin() {
    document.getElementById('signUpSection').classList.add('hidden');
    document.getElementById('loginSection').classList.remove('hidden');
}

// Handle login functionality
function handleLogin() {
    const username = document.querySelector('#loginSection input[type="text"]').value;
    const password = document.querySelector('#loginSection input[type="password"]').value;

    if (username && password) {
        document.getElementById('successModal').classList.remove('hidden');
    } else {
        document.getElementById('infoModal').classList.remove('hidden');
    }
}

// Close the information modal
function closeModal() {
    document.getElementById('infoModal').classList.add('hidden');
}

// Close the success modal
function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
}

// Show the sign-up overview modal
function showSignUpOverview() {
    const firstName = document.querySelector('#signUpSection input[placeholder="First Name"]').value;
    const lastName = document.querySelector('#signUpSection input[placeholder="Last Name"]').value;
    const email = document.querySelector('#signUpSection input[placeholder="Email"]').value;
    const phone = document.querySelector('#signUpSection input[placeholder="Phone Number"]').value;
    const username = document.querySelector('#signUpSection input[placeholder="Username"]').value;
    const password = document.querySelector('#signUpSection input[placeholder="Password"]').value;

    const overviewContent = `
        <p><strong>First Name:</strong> ${firstName}</p>
        <p><strong>Last Name:</strong> ${lastName}</p>
        <p><strong>Email:</strong> ${email}</p>
        <p><strong>Phone:</strong> ${phone}</p>
        <p><strong>Username:</strong> ${username}</p>
    `;

    document.getElementById('signUpOverview').innerHTML = overviewContent;
    document.getElementById('signUpOverviewModal').classList.remove('hidden');
}

// Close the sign-up overview modal
function closeSignUpOverview() {
    document.getElementById('signUpOverviewModal').classList.add('hidden');
}

// Confirm the sign-up and show the success modal
function confirmSignUp() {
    document.getElementById('signUpOverviewModal').classList.add('hidden');
    document.getElementById('signUpSuccessModal').classList.remove('hidden');
}

// Close the sign-up success modal
function closeSignUpSuccessModal() {
    document.getElementById('signUpSuccessModal').classList.add('hidden');
}

// Handle forgot password functionality (placeholder for future implementation)
function forgotPassword() {
    alert('Forgot Password functionality is not yet implemented.');
}

const socket = new WebSocket('ws://localhost:8080/realtime');

socket.onopen = () => {
    console.log('Connected to WebSocket server');
};

socket.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'signup') {
        alert(`New user signed up: ${data.user}`);
    } else if (data.type === 'login') {
        alert(`User logged in: ${data.user}`);
    }
};

socket.onerror = (error) => {
    console.error('WebSocket error:', error);
};

socket.onclose = () => {
    console.log('WebSocket connection closed');
};

// Function to fetch real-time updates
function fetchUpdates() {
    fetch('index.php?action=fetch_updates')
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch updates');
            }
            return response.json();
        })
        .then(data => {
            const updatesContainer = document.getElementById('updatesContainer');
            updatesContainer.innerHTML = ''; // Clear previous updates

            if (data.length === 0) {
                updatesContainer.innerHTML = '<p class="text-gray-500">No recent updates available.</p>';
                return;
            }

            data.forEach(user => {
                const userElement = document.createElement('div');
                userElement.classList.add('p-2', 'border-b', 'border-gray-300');
                userElement.innerHTML = `
                    <p><strong>${user.first_name} ${user.last_name}</strong> (${user.username})</p>
                    <p>${user.email}</p>
                `;
                updatesContainer.appendChild(userElement);
            });
        })
        .catch(error => {
            console.error('Error fetching updates:', error);
        });
}

// Poll the server every 5 seconds
setInterval(fetchUpdates, 5000);

// Fetch updates on page load
fetchUpdates();

// Log viewing the bus schedule
function logViewSchedule() {
    fetch('dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=view_schedule'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('View schedule action logged.');
        }
    })
    .catch(error => {
        console.error('Error logging view schedule action:', error);
    });
}

// Log accessing profile settings
function logAccessProfileSettings() {
    fetch('dashboard.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=access_profile_settings'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Access profile settings action logged.');
        }
    })
    .catch(error => {
        console.error('Error logging access profile settings action:', error);
    });
}

// Attach event listeners
document.querySelector('.btn-view-schedule').addEventListener('click', logViewSchedule);
document.querySelector('.btn-profile-settings').addEventListener('click', logAccessProfileSettings);
