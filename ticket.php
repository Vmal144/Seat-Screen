<?php
session_start();
require_once 'db_connect.php';

// Check if booking info exists in session
if (!isset($_SESSION['booking_info'])) {
    header("Location: index.php");
    exit;
}

// Get booking details from session
$bookingInfo = $_SESSION['booking_info'];

// Get movie and theater details
$stmt = $pdo->prepare("SELECT title, poster, duration, genre FROM movies WHERE id = ?");
$stmt->execute([$bookingInfo['movie_id']]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT name FROM theaters WHERE id = ?");
$stmt->execute([$bookingInfo['theater_id']]);
$theater = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate QR code data (you can enhance this with more booking details)
$qrData = "Booking ID: " . $bookingInfo['booking_id'] . "\n" .
          "Movie: " . $movie['title'] . "\n" .
          "Date: " . $bookingInfo['date'] . "\n" .
          "Time: " . $bookingInfo['time'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Ticket - Seat&Screen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #0f1119;
            color: #ffffff;
        }

        .ticket-container {
            max-width: 800px;
            margin: 100px auto 20px;
            padding: 20px;
        }        
        .ticket-card {
            background: #161925;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
            border-left: 2px dashed #ff3366;
            border-right: 2px dashed #ff3366;
        }

        /* Ticket holes */
        .ticket-card::before,
        .ticket-card::after {
            content: "";
            position: absolute;
            width: 20px;
            height: 20px;
            background: #0f1119;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .ticket-card::before {
            left: -10px;
        }
        
        .ticket-card::after {
            right: -10px;
        }

        @media print {
            body * {
                visibility: hidden;
            }
            .ticket-card, .ticket-card * {
                visibility: visible;
            }
            .ticket-card {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .download-btn {
                display: none;
            }
        }

        .success-banner {
            background: #28a745;
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .ticket-header {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .movie-poster {
            width: 150px;
            height: 225px;
            border-radius: 8px;
            object-fit: cover;
        }

        .movie-info {
            flex: 1;
        }

        .movie-title {
            color: #ff3366;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .theater-name {
            color: #ffffff;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .booking-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            background: #1c2035;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: #a0a0a0;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
        }

        .qr-section {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background: #1c2035;
            border-radius: 8px;
        }

        .qr-code {
            width: 100px;
            height: 100px;
            margin: 0 auto;
            background: #ffffff;
            padding: 10px;
            border-radius: 8px;
        }

        .booking-id {
            color: #ff3366;
            font-size: 16px;
            margin-top: 10px;
            font-weight: bold;
        }

        .download-btn {
            background: #ff3366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .download-btn:hover {
            background: #e62958;
        }

        @media (max-width: 768px) {
            .ticket-header {
                flex-direction: column;
            }

            .booking-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img">
        </a>
        <div class="user-actions">            <?php if (isset($_SESSION['username'])): ?>
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="ticket-container">
        <div class="success-banner">
            <i class="fas fa-check-circle"></i> Booking Confirmed! Your tickets are ready.
        </div>
        
        <div class="ticket-card">
            <div class="ticket-header">
                <img src="images/<?php echo htmlspecialchars($movie['poster']); ?>" alt="Movie Poster" class="movie-poster">
                <div class="movie-info">
                    <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <div class="theater-name"><?php echo htmlspecialchars($theater['name']); ?></div>
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value"><?php echo htmlspecialchars($movie['duration']); ?> min</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Genre</span>
                        <span class="detail-value"><?php echo htmlspecialchars($movie['genre']); ?></span>
                    </div>
                </div>
            </div>

            <div class="booking-details">
                <div class="detail-item">
                    <span class="detail-label">Date</span>
                    <span class="detail-value"><?php echo htmlspecialchars($bookingInfo['date']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Time</span>
                    <span class="detail-value"><?php echo htmlspecialchars($bookingInfo['time']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Seats</span>
                    <span class="detail-value"><?php echo htmlspecialchars($bookingInfo['seats']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Total Amount</span>
                    <span class="detail-value">₹<?php echo number_format($bookingInfo['total'], 2); ?></span>
                </div>
            </div>

            <div class="qr-section">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($qrData); ?>&size=100x100" 
                     alt="Booking QR Code" class="qr-code">
                <div class="booking-id">Booking ID: <?php echo htmlspecialchars($bookingInfo['booking_id']); ?></div>
            </div>            <button class="download-btn" onclick="printTicket()">
                <i class="fas fa-download"></i> Download Ticket
            </button>
        </div>
    </div>

    <script>
        function printTicket() {
            // Add print styles dynamically
            const style = document.createElement('style');
            style.innerHTML = `
                @page {
                    size: auto;
                    margin: 0mm;
                }
                @media print {
                    body * {
                        visibility: hidden;
                    }
                    .ticket-card, .ticket-card * {
                        visibility: visible;
                    }
                    .ticket-card {
                        position: absolute;
                        left: 50%;
                        transform: translateX(-50%);
                        top: 0;
                        width: 90%;
                        max-width: 600px;
                        border: none;
                    }
                    .ticket-card::before,
                    .ticket-card::after {
                        display: none;
                    }
                    .download-btn {
                        display: none;
                    }
                }
            `;
            document.head.appendChild(style);

            window.print();
        }
    </script>
</body>
</html>