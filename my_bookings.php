<?php
session_start();
require_once 'db_connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user's bookings
$stmt = $pdo->prepare("
    SELECT b.id, b.user_id, b.theater_id, b.movie_id, b.seat_number, b.showtime, b.date, b.price, b.booking_time, b.status,
           m.title AS movie_title
    FROM bookings b
    JOIN movies m ON b.movie_id = m.id
    JOIN theaters t ON b.theater_id = t.id
    WHERE b.user_id = ? AND b.status = 'confirmed'
    ORDER BY b.booking_time DESC
");
$stmt->execute([$userId]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

function canCancel($showDate, $showTime) {
    try {
        $showtime = new DateTime($showDate . ' ' . $showTime);
        $now = new DateTime();
        $interval = $now->diff($showtime);        
        $minutesUntilShow = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        return !$interval->invert && $minutesUntilShow >= 60;
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Seat&Screen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
    <style>        .bookings-container {
            max-width: 800px;
            margin: 100px auto 20px;
            padding: 20px;
        }
        .booking-card {
            border: 1px solid #2a2e3a;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #161925;
            color: #fff;
        }
        .booking-card h3 {
            margin: 0 0 15px;
            font-size: 1.2em;
            color: #ff3366;
        }
        .booking-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .booking-detail {
            font-size: 0.9em;
            color: #a0a0a0;
        }
        .booking-detail strong {
            color: #fff;
            display: inline-block;
            margin-right: 5px;
        }
        .booking-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn-cancel {
            background: #ff3366;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-cancel:hover {
            background: #e62958;
        }.btn-cancel:disabled {
            background: #2a2e3a;
            cursor: not-allowed;
        }
          .btn-download {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-download:hover {
            background: #218838;
            color: #fff;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #161925;
            padding: 30px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            position: relative;
            text-align: center;
        }
        .modal h2 {
            color: #ff3366;
            margin-bottom: 20px;
        }
        .modal p {
            color: #a0a0a0;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .modal-btn {
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            border: none;
            font-weight: 500;
        }
        .modal-btn.confirm {
            background-color: #ff3366;
            color: white;
        }
        .modal-btn.confirm:hover {
            background-color: #e62958;
        }
        .modal-btn.cancel {
            background-color: transparent;
            border: 1px solid #2a2e3a;
            color: #a0a0a0;
        }
        .modal-btn.cancel:hover {
            background-color: #2a2e3a;
        }
    </style>
</head>
<body>
    <!-- Add modal HTML after the navbar -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <h2>Cancel Booking</h2>
            <p>Are you sure you want to cancel this booking? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel" onclick="closeModal()">No, Keep it</button>
                <button class="modal-btn confirm" onclick="confirmCancellation()">Yes, Cancel it</button>
            </div>
        </div>
    </div>
    
    <nav class="navbar">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img">
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="movies.php">Movies</a></li>
            <li><a href="my_bookings.php" class="active">My Bookings</a></li>
        </ul>
        <div class="user-actions">
            <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </nav>

    <div class="bookings-container">
        <h1>My Bookings</h1>
        <?php if (empty($bookings)): ?>
            <p>You don't have any bookings yet.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card">
                    <h3><?php echo htmlspecialchars($booking['movie_title']); ?></h3>                    <div class="booking-details">
                        <div class="booking-detail">
                            <strong>Date:</strong> <?php echo htmlspecialchars($booking['date']); ?>
                        </div>
                        <div class="booking-detail">
                            <strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($booking['showtime']))); ?>
                        </div>
                        <div class="booking-detail">
                            <strong>Seat:</strong> <?php echo htmlspecialchars($booking['seat_number']); ?>
                        </div>
                        <div class="booking-detail">
                            <strong>Amount:</strong> ₹<?php echo htmlspecialchars(number_format($booking['price'], 2)); ?>
                        </div>
                    </div>
                    <?php if (canCancel($booking['date'], $booking['showtime'])): ?>
                        <button class="btn-cancel" onclick="showCancelModal(<?php echo $booking['id']; ?>)">
                            Cancel Booking
                        </button>
                    <?php else: ?>
                        <button class="btn-cancel" disabled title="Cannot cancel within 1 hour of showtime">
                            Cannot Cancel
                        </button>                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        let currentBookingId = null;

        function showCancelModal(bookingId) {
            currentBookingId = bookingId;
            document.getElementById('cancelModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('cancelModal').style.display = 'none';
            currentBookingId = null;
        }

        function confirmCancellation() {
            if (!currentBookingId) return;
            
            const formData = new FormData();
            formData.append('booking_id', currentBookingId);

            fetch('cancel_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to show updated bookings
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while canceling the booking');
            })
            .finally(() => {
                closeModal();
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('cancelModal');
            if (event.target === modal) {
                closeModal();
            }
        }    </script>
</body>
</html>