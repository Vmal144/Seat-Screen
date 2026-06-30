<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Get the raw POST data
    $postData = file_get_contents('php://input');
    if (!$postData) {
        $postData = $_POST;
    }
    
    // If it's a JSON string, decode it
    if (is_string($postData)) {
        $postData = json_decode($postData, true) ?? $_POST;
    }
    
    $response = array();
    
    if (isset($postData['firebase_uid']) && isset($postData['email'])) {
        require_once 'db_connect.php';
        
        try {
            // First check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE firebase_uid = ? OR email = ?");
            $stmt->execute([$postData['firebase_uid'], $postData['email']]);
            $existingUser = $stmt->fetch();            if (!$existingUser) {                // Insert new user into MySQL database
                // If username is not provided, use the part before @ in email
                if (!isset($postData['username']) || empty($postData['username'])) {
                    $emailParts = explode('@', $postData['email']);
                    $postData['username'] = $emailParts[0];
                }
                
                $stmt = $pdo->prepare("INSERT INTO users (firebase_uid, email, username) VALUES (?, ?, ?)");
                $stmt->execute([
                    $postData['firebase_uid'],
                    $postData['email'],
                    $postData['username']
                ]);
                
                // Get the newly created user's ID
                $userId = $pdo->lastInsertId();
                
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $userId;
                $_SESSION['firebase_uid'] = $postData['firebase_uid'];
                $_SESSION['email'] = $postData['email'];
                if (isset($postData['username'])) {
                    $_SESSION['username'] = $postData['username'];
                }
                
                $response['success'] = true;
            } else {
                $response['success'] = false;
                $response['error'] = 'User already exists';
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['error'] = 'Missing required data';
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Seat&Screen</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="login_register.css" rel="stylesheet">    <style>
        /* Loading state */
        .btn.loading {
            position: relative;
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/11.7.3/firebase-auth-compat.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img">
            </div>
        </div>

        <div class="card">
            <h2>Sign Up</h2>
            <div id="error-message" class="error"></div>

            <form id="register-form">
                <div class="form-group">
                    <label for="username">Full Name</label>
                    <input type="text" id="username" name="username" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                    
                    <div class="password-requirements">
                        <div class="requirement" id="minLength">
                            <i class="fas fa-check"></i> At least 8 characters
                        </div>
                        <div class="requirement" id="maxLength">
                            <i class="fas fa-check"></i> Maximum 64 characters
                        </div>
                        <div class="requirement" id="uppercase">
                            <i class="fas fa-check"></i> At least 1 uppercase letter (A-Z)
                        </div>
                        <div class="requirement" id="lowercase">
                            <i class="fas fa-check"></i> At least 1 lowercase letter (a-z)
                        </div>
                        <div class="requirement" id="number">
                            <i class="fas fa-check"></i> At least 1 number (0-9)
                        </div>
                        <div class="requirement" id="special">
                            <i class="fas fa-check"></i> At least 1 special character (!@#$%^&*)
                        </div>
                    </div>

                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>

                <button type="submit" class="btn" id="registerBtn">Sign Up</button><br><br>
                <button type="button" class="btn google-btn" onclick="signUpWithGoogle()">
                    <i class="fab fa-google"></i> Sign up with Google
                </button>

                <div class="links">
                    <p align="center" style="color:#ff3366;">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </form>
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

        // Password validation elements
        const passwordInput = document.getElementById('password');
        const minLengthIndicator = document.getElementById('minLength');
        const maxLengthIndicator = document.getElementById('maxLength');
        const uppercaseIndicator = document.getElementById('uppercase');
        const lowercaseIndicator = document.getElementById('lowercase');
        const numberIndicator = document.getElementById('number');
        const specialIndicator = document.getElementById('special');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        const passwordToggle = document.getElementById('passwordToggle');

        // Password toggle functionality
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Password validation with real-time feedback
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let score = 0;
            let validRequirements = 0;

            // Minimum length validation
            if (password.length >= 8) {
                minLengthIndicator.classList.add('valid');
                minLengthIndicator.classList.remove('invalid');
                score += 15;
                validRequirements++;
            } else {
                minLengthIndicator.classList.remove('valid');
                minLengthIndicator.classList.add('invalid');
            }

            // Maximum length validation
            if (password.length <= 64) {
                maxLengthIndicator.classList.add('valid');
                maxLengthIndicator.classList.remove('invalid');
                score += 10;
                validRequirements++;
            } else {
                maxLengthIndicator.classList.remove('valid');
                maxLengthIndicator.classList.add('invalid');
            }

            // Uppercase letter validation
            if (/[A-Z]/.test(password)) {
                uppercaseIndicator.classList.add('valid');
                uppercaseIndicator.classList.remove('invalid');
                score += 15;
                validRequirements++;
            } else {
                uppercaseIndicator.classList.remove('valid');
                uppercaseIndicator.classList.add('invalid');
            }

            // Lowercase letter validation
            if (/[a-z]/.test(password)) {
                lowercaseIndicator.classList.add('valid');
                lowercaseIndicator.classList.remove('invalid');
                score += 15;
                validRequirements++;
            } else {
                lowercaseIndicator.classList.remove('valid');
                lowercaseIndicator.classList.add('invalid');
            }

            // Number validation
            if (/[0-9]/.test(password)) {
                numberIndicator.classList.add('valid');
                numberIndicator.classList.remove('invalid');
                score += 15;
                validRequirements++;
            } else {
                numberIndicator.classList.remove('valid');
                numberIndicator.classList.add('invalid');
            }

            // Special character validation
            if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                specialIndicator.classList.add('valid');
                specialIndicator.classList.remove('invalid');
                score += 15;
                validRequirements++;
            } else {
                specialIndicator.classList.remove('valid');
                specialIndicator.classList.add('invalid');
            }

            // Additional scoring for length
            if (password.length >= 12) score += 10;
            if (password.length >= 16) score += 5;

            // Update strength bar and text
            updatePasswordStrength(score, validRequirements);
        });

        function updatePasswordStrength(score, validRequirements) {
            strengthBar.className = 'strength-bar';
            
            if (score < 30) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#ff4757';
            } else if (score < 60) {
                strengthBar.classList.add('strength-fair');
                strengthText.textContent = 'Fair';
                strengthText.style.color = '#ffa502';
            } else if (score < 85) {
                strengthBar.classList.add('strength-good');
                strengthText.textContent = 'Good';
                strengthText.style.color = '#2ed573';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#1e90ff';
            }
        }

        function isPasswordValid() {
            const password = passwordInput.value;
            return password.length >= 8 && 
                   password.length <= 64 && 
                   /[A-Z]/.test(password) && 
                   /[a-z]/.test(password) && 
                   /[0-9]/.test(password) && 
                   /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
        }

        // Registration form handler
        document.getElementById('register-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('error-message');
            const submitBtn = document.getElementById('registerBtn');

            // Validate password before submission
            if (!isPasswordValid()) {
                errorDiv.textContent = 'Please ensure your password meets all requirements.';
                errorDiv.style.display = 'block';
                return;
            }

            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            try {
                // Create user with email and password
                const userCredential = await firebase.auth().createUserWithEmailAndPassword(email, password);
                
                // Update user profile with display name
                await userCredential.user.updateProfile({
                    displayName: username
                });

                // Send email verification
                await userCredential.user.sendEmailVerification();

                // Send user data to PHP for session creation
                const formData = new FormData();
                formData.append('firebase_uid', userCredential.user.uid);
                formData.append('email', email);

                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    alert('Registration successful! Please check your email to verify your account.');
                    window.location.href = 'login.php';
                } else {
                    throw new Error(data.error || 'Registration failed');
                }
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            } finally {
                // Remove loading state
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }
        });

        // Google Sign Up
        async function signUpWithGoogle() {
            const provider = new firebase.auth.GoogleAuthProvider();
            try {
                const result = await firebase.auth().signInWithPopup(provider);
                
                // Create form data to send to backend
                const formData = new FormData();
                formData.append('firebase_uid', result.user.uid);
                formData.append('email', result.user.email);
                formData.append('username', result.user.displayName);

                // Send the data to register.php
                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                if (data.success) {
                    alert('Registration successful!');
                    window.location.href = 'index.php';
                } else {
                    throw new Error(data.error || 'Registration failed');
                }
            } catch (error) {
                const errorDiv = document.getElementById('error-message');
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            }
        }
    </script>
</body>
</html>