<?php
session_start();

// Only process POST requests from Firebase authentication
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['firebase_uid']) && isset($_POST['email'])) {
    $firebase_uid = $_POST['firebase_uid'];
    $email = $_POST['email'];
      require_once 'db_connect.php';
    
    // Get user ID from database
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE firebase_uid = ?");
    $stmt->execute([$firebase_uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Create session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['firebase_uid'] = $firebase_uid;
        $_SESSION['email'] = $email;
        $_SESSION['username'] = $user['username'];
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Return success response
    echo json_encode(['success' => true]);
    exit();
}

// If there's already an active session, redirect to index
if (isset($_SESSION['firebase_uid'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Seat&Screen</title>
    <link href="login_register.css" rel="stylesheet">
    <!-- Add these script tags before your module script -->
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-auth-compat.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img">
            </div>
        </div>        <div class="card">
            <h2>Login</h2>
            <div id="error-message" class="error"></div>

            <form id="login-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your Email" style="color:black;" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" style="color:black;" required>
                </div>

                <button type="submit" class="btn" id="loginBtn">Sign In</button><br><br>
                <button type="button" class="btn google-btn" onclick="signInWithGoogle()">
                    <i class="fab fa-google"></i> Sign in with Google
                </button>

                <div class="links">
                    <a href="#" id="forgotPassword">Forgot Password?</a><br><br>
                    <a href="register.php">New to Seat&Screen? Sign Up</a><br><br>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="forgotPasswordModal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Reset Password</h3>
            <div class="form-group">
                <label for="resetEmail">Email</label>
                <input type="email" id="resetEmail" placeholder="Enter your email" style="color:black;" required>
            </div>
            <div id="resetMessage" class="message"></div>
            <button type="button" class="btn" onclick="resetPassword()">Send Reset Link</button>
        </div>
    </div>

    <script>
        // Firebase configuration
        const firebaseConfig = {
            apiKey: "AIzaSyAi6_HF1RxH4LaHuUQjwuD7cKDvVLMrNqY",
            authDomain: "seat-and-screen.firebaseapp.com",
            projectId: "seat-and-screen",
            storageBucket: "seat-and-screen.firebasestorage.app",
            messagingSenderId: "557948288320",
            appId: "1:557948288320:web:8b1aea34a41cb716f38ea5"
        };

        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        
        // Login form handler
        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            try {
                const userCredential = await firebase.auth().signInWithEmailAndPassword(
                    document.getElementById('email').value,
                    document.getElementById('password').value
                );
                
                // Send user data to PHP for session creation
                const formData = new FormData();
                formData.append('firebase_uid', userCredential.user.uid);
                formData.append('email', userCredential.user.email);

                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    window.location.href = 'index.php';
                }            } catch (error) {
                const errorDiv = document.getElementById('error-message');
                if (error.code === 'auth/wrong-password' || error.code === 'auth/user-not-found') {
                    errorDiv.textContent = 'Invalid email or password';
                } else if (error.code === 'auth/invalid-email') {
                    errorDiv.textContent = 'Please enter a valid email address';
                } else {
                    errorDiv.textContent = 'Wrong email or password.';
                }
                errorDiv.style.display = 'block';
            }
        });

        // Google Sign In
        function signInWithGoogle() {
            const provider = new firebase.auth.GoogleAuthProvider();
            firebase.auth().signInWithPopup(provider)
                .then(async (result) => {
                    const formData = new FormData();
                    formData.append('firebase_uid', result.user.uid);
                    formData.append('email', result.user.email);

                    const response = await fetch('login.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        window.location.href = 'index.php';
                    }
                })
                .catch(error => {
                    alert(error.message);
                });
        }

        // Modal handling
        const modal = document.getElementById('forgotPasswordModal');
        const closeBtn = document.getElementsByClassName('close')[0];
        const forgotPasswordLink = document.getElementById('forgotPassword');

        forgotPasswordLink.onclick = function(e) {
            e.preventDefault();
            modal.style.display = 'block';
        }

        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Password reset function
        async function resetPassword() {
            const resetEmail = document.getElementById('resetEmail').value;
            const messageDiv = document.getElementById('resetMessage');
            
            if (!resetEmail) {
                messageDiv.className = 'message error';
                messageDiv.textContent = 'Please enter your email address';
                return;
            }

            try {
                await firebase.auth().sendPasswordResetEmail(resetEmail);
                messageDiv.className = 'message success';
                messageDiv.textContent = 'Password reset email sent! Please check your inbox.';
                
                // Close the modal after 3 seconds
                setTimeout(() => {
                    modal.style.display = 'none';
                    messageDiv.textContent = '';
                }, 3000);
            } catch (error) {
                messageDiv.className = 'message error';
                messageDiv.textContent = error.message;
            }
        }
    </script>
</body>
</html>