<?php
session_start();
require_once 'db_connect.php';

$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['username']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$userId = $_SESSION['user_id']; // Store user_id from session
$paymentDone = false;
$error = '';
$movieId = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$theaterId = isset($_GET['theater_id']) ? (int)$_GET['theater_id'] : 0;
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$time = isset($_GET['time']) ? trim($_GET['time']) : '';
$seats = isset($_GET['seats']) ? array_filter(explode(',', $_GET['seats'])) : [];
$total = isset($_GET['total']) ? floatval($_GET['total']) : 0.0;

// Get movie and theater details
$stmt = $pdo->prepare("SELECT title FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);
$movieTitle = $movie ? $movie['title'] : 'Movie';

$stmt = $pdo->prepare("SELECT name FROM theaters WHERE id = ?");
$stmt->execute([$theaterId]);
$theater = $stmt->fetch(PDO::FETCH_ASSOC);
$theaterName = $theater ? $theater['name'] : 'Theater';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now']) && !$paymentDone) {
    try {
        $pdo->beginTransaction();
        
        // First check if seats are still available
        $stmt = $pdo->prepare("SELECT seat_number FROM bookings WHERE theater_id = ? AND movie_id = ? AND showtime = ? AND date = ? AND status = 'confirmed'");
        $stmt->execute([$theaterId, $movieId, $time, $date]);
        $bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Check if any selected seats are already booked
        $conflictingSeats = array_intersect($seats, $bookedSeats);
        if (!empty($conflictingSeats)) {
            throw new Exception("Some seats have already been booked: " . implode(", ", $conflictingSeats));
        }
        
        // No need to query for user ID since we have it in session
        $userId = $_SESSION['user_id'];
        
        // Insert booking records
        $lastBookingId = null;
        foreach ($seats as $seat) {
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, theater_id, movie_id, seat_number, showtime, date, price, status) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
            $stmt->execute([$userId, $theaterId, $movieId, $seat, $time, $date, $total/count($seats)]);
            if (!$lastBookingId) {
                $lastBookingId = $pdo->lastInsertId();
            }
        }
        
        $pdo->commit();
        $paymentDone = true;
          // Store booking info in session
        $_SESSION['booking_info'] = [
            'booking_id' => $lastBookingId,
            'seats' => implode(',', $seats),
            'total' => $total,
            'movie_id' => $movieId,
            'theater_id' => $theaterId,
            'date' => $date,
            'time' => $time
        ];
        
        // Delay redirect to show success message
        echo "<script>
            setTimeout(function() {
                window.location.href = 'ticket.php';
            }, 2000);
        </script>";
    } catch (Exception $e) {
        $pdo->rollback();
        $error = $e->getMessage();
    }
}

function formatDate($dateStr) {
    try {
        $date = new DateTime($dateStr);
        return $date->format('D, M j, Y');
    } catch (Exception $e) {
        return $dateStr;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Seat&Screen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
    <link href="login_register.css" rel="stylesheet">    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #0f1119;
            color: #ffffff;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #161925;
            padding: 15px 30px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        .navbar .logo img {
            height: 50px;
            width: 200px;
        }
        .nav-links {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 30px;
        }
        .nav-links li a {
            color: #a0a0a0;
            text-decoration: none;
            transition: color 0.3s;
        }
        .nav-links li a:hover {
            color: #ffffff;
        }
        .user-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .welcome-message {
            color: #a0a0a0;
        }
        .user-actions .btn {
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
            background-color: #242838;
            color: #ffffff;
        }
        .user-actions .btn:hover {
            background-color: #ff3366;
        }
        .user-actions .btn.primary {
            background-color: #ff3366;
        }
        .user-actions .btn.primary:hover {
            background-color: #e62958;
        }
        .container {
            max-width: 800px;
            margin: 100px auto 20px;
            padding: 20px;
        }
        .card {
            background: #161925;
            padding: 30px;
            width:1000px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }
        .card h2 {
            color: #ff3366;
            margin-bottom: 25px;
            font-size: 24px;
        }
        .booking-details {
            background: #1c2035;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .booking-details p {
            margin: 15px 0;
            color: #a0a0a0;
            font-size: 16px;
        }
        .booking-details strong {
            color: #ffffff;
        }
        .error {
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background-color: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            color: #66ff66;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
            margin: 10px;
        }
        .btn-pay {
            background-color: #ff3366;
            color: white;
        }
        .btn-pay:hover {
            background-color: #e62958;
        }
        .btn-back {
            background-color: #242838;
            color: #a0a0a0;
        }
        .btn-back:hover {
            background-color: #1c2035;
            color: #ffffff;
        }
        .total-amount {
            font-size: 24px;
            color: #ff3366;
            margin: 20px 0;
        }
        .actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img">
        </a>
        <div class="user-actions">            <?php if ($isLoggedIn): ?>
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</span>
                <a href="logout.php" class="btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Payment</h2>

            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php elseif ($paymentDone): ?>
                <p class="success">Payment Done successfully! Your booking is confirmed.</p>
            <?php else: ?>                <div class="booking-details">
                    <h3 style="color: #ff3366; margin-bottom: 20px;">Booking Summary</h3>
                    <p><strong>Movie:</strong> <?php echo htmlspecialchars($movieTitle); ?></p>
                    <p><strong>Theater:</strong> <?php echo htmlspecialchars($theaterName); ?></p>
                    <p><strong>Date & Time:</strong> <?php echo formatDate($date) . ', ' . htmlspecialchars($time); ?></p>
                    <p><strong>Selected Seats:</strong> <?php echo htmlspecialchars(implode(', ', $seats)); ?></p>
                    <div class="total-amount">
                        <strong>Total Amount:</strong> ₹<?php echo number_format($total, 2); ?>
                    </div>
                </div>
                <form method="POST" style="margin-top: 20px;">
                    <div style="background: #1c2035; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="color: #ff3366; margin-bottom: 15px;">Payment Method</h3>
                        <div style="text-align: left; margin-bottom: 15px;">
                            <input type="radio" id="upi" name="payment_method" value="upi" checked>
                            <label for="upi" style="color: #a0a0a0; margin-left: 10px;">UPI Payment</label>
                        </div>
                        <div style="text-align: left;">
                            <input type="radio" id="card" name="payment_method" value="card">
                            <label for="card" style="color: #a0a0a0; margin-left: 10px;">Credit/Debit Card</label>
                        </div>
                    </div>
                    <button type="submit" name="pay_now" class="btn btn-pay">
                        <i class="fas fa-lock" style="margin-right: 8px;"></i>Pay Securely Now
                    </button>
                </form>
            <?php endif; ?>

            <div class="actions">
                <button class="btn btn-back" onclick="window.history.back()">Back</button>
                <?php if ($error): ?>
                    <button class="btn btn-pay" onclick="window.location.href='seat_selection.php?theater=<?php echo $theaterId; ?>&showtime=<?php echo urlencode($time); ?>&movie=<?php echo $movieId; ?>&date=<?php echo $date; ?>'">Try Again</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>