<?php
session_start();
require_once 'db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$theaterId = isset($_GET['theater']) ? (int)$_GET['theater'] : 0;
$showtime = isset($_GET['showtime']) ? $_GET['showtime'] : '';
$movieId = isset($_GET['movie']) ? (int)$_GET['movie'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$theaterId || !$showtime || !$movieId || !$date) {
    die("Invalid booking parameters.");
}

// Get movie and theater details
$stmt = $pdo->prepare("SELECT title, poster, duration, genre FROM movies WHERE id = ?");
$stmt->execute([$movieId]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT name FROM theaters WHERE id = ?");
$stmt->execute([$theaterId]);
$theater = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie || !$theater) {
    die("Movie or theater not found.");
}

// Get booked seats for this specific showtime with confirmed status
$stmt = $pdo->prepare("SELECT seat_number FROM bookings WHERE theater_id = ? AND movie_id = ? AND showtime = ? AND date = ? AND status = 'confirmed'");
$stmt->execute([$theaterId, $movieId, $showtime, $date]);
$bookedSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle seat booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_seats'])) {
    if (!$isLoggedIn) {
        die("Please login to complete booking.");
    }
    
    $selectedSeats = json_decode($_POST['selected_seats'], true);
    
    // Check if any selected seats are already booked
    if (!empty($selectedSeats)) {
        $placeholders = str_repeat('?,', count($selectedSeats) - 1) . '?';
        $stmt = $pdo->prepare("SELECT seat_number FROM bookings WHERE theater_id = ? AND movie_id = ? AND showtime = ? AND date = ? AND status = 'confirmed' AND seat_number IN ($placeholders)");
        $params = array_merge([$theaterId, $movieId, $showtime, $date], $selectedSeats);
        $stmt->execute($params);
        $alreadyBooked = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($alreadyBooked)) {
            $error = "Sorry, seats " . implode(", ", $alreadyBooked) . " have already been booked. Please select different seats.";
            die($error);
        }
    }
      // Calculate total price based on seat sections
    $totalPrice = 0;
    foreach ($selectedSeats as $seat) {
        $row = $seat[0]; // First character is the row letter
        if (in_array($row, ['G', 'H'])) {
            $totalPrice += 400; // VIP
        } elseif (in_array($row, ['D', 'E', 'F'])) {
            $totalPrice += 200; // Gold
        } else {
            $totalPrice += 150; // Silver
        }
    }
    
    // Redirect to payment page with all necessary information
    header("Location: payment.php?seats=" . urlencode(implode(',', $selectedSeats)) 
        . "&total=" . $totalPrice 
        . "&movie_id=" . $movieId 
        . "&theater_id=" . $theaterId 
        . "&time=" . urlencode($showtime)
        . "&date=" . urlencode($date));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - Seat&Screen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
    <style>
        .seat-selection-container {
            max-width: 1000px;
            margin: 80px auto 0;
            padding: 20px;
            background-color: #0f1119;
            min-height: calc(100vh - 80px);
        }

        .booking-header {
            background-color: #161925;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .movie-poster-small {
            width: 80px;
            height: 120px;
            border-radius: 4px;
            object-fit: cover;
        }

        .booking-info h2 {
            color: #ff3366;
            margin: 0 0 10px 0;
            font-size: 1.5rem;
        }

        .booking-details {
            color: #a0a0a0;
            line-height: 1.6;
        }

        .screen-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .screen {
            background: linear-gradient(to bottom, #ff3366, #e62958);
            height: 8px;
            border-radius: 4px;
            margin: 0 auto 20px;
            width: 60%;
            box-shadow: 0 2px 10px rgba(255, 51, 102, 0.3);
        }

        .screen-label {
            color: #a0a0a0;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }        .seating-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 8px;
            max-width: 600px;
            margin: 0 auto 30px;
        }

        /* Section gaps */
        .seat[data-row="C"] {
            margin-bottom: 20px; /* Gap after silver section */
        }
        
        .seat[data-row="F"] {
            margin-bottom: 20px; /* Gap after gold section */
        }

        .section-label {
            grid-column: 1 / -1;
            text-align: center;
            color: #a0a0a0;
            font-size: 0.9rem;
            padding: 10px 0;
        }

        .seat {
            width: 40px;
            height: 40px;
            border: 2px solid #3a3f54;
            border-radius: 6px;
            background-color: #242838;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 0.8rem;
            color: #a0a0a0;
        }

        .seat:hover:not(.booked) {
            border-color: #ff3366;
            background-color: rgba(255, 51, 102, 0.1);
        }

        .seat.selected {
            background-color: #ff3366;
            border-color: #ff3366;
            color: white;
        }

        .seat.booked {
            background-color: #666;
            border-color: #666;
            cursor: not-allowed;
            color: #999;
        }        .legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #a0a0a0;
            font-size: 0.9rem;
        }

        .legend-seat {
            width: 20px;
            height: 20px;
            border-radius: 3px;
            border: 2px solid;
        }

        .seat.vip {
            background-color: #2c1810;
            border-color: #8b4513;
        }

        .seat.gold {
            background-color: #2c2810;
            border-color: #8b8113;
        }

        .seat.silver {
            background-color: #242838;
            border-color: #3a3f54;
        }

        .seat.selected {
            background-color: #ff3366 !important;
            border-color: #ff3366 !important;
        }

        .seat.booked {
            background-color: #666 !important;
            border-color: #666 !important;
        }

        .legend-vip { background-color: #2c1810; border-color: #8b4513; }
        .legend-gold { background-color: #2c2810; border-color: #8b8113; }
        .legend-silver { background-color: #242838; border-color: #3a3f54; }
        .legend-selected { background-color: #ff3366; border-color: #ff3366; }
        .legend-booked { background-color: #666; border-color: #666; }

        .booking-summary {
            background-color: #161925;
            padding: 20px;
            border-radius: 8px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.5);
        }

        .summary-content {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .selected-info {
            color: #ffffff;
        }

        .total-price {
            color: #ff3366;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .proceed-btn {
            background-color: #ff3366;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        .proceed-btn:hover:not(:disabled) {
            background-color: #e62958;
        }

        .proceed-btn:disabled {
            background-color: #666;
            cursor: not-allowed;
        }

        .error-message {
            background-color: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .booking-header {
                flex-direction: column;
                text-align: center;
            }

            .seating-grid {
                grid-template-columns: repeat(8, 1fr);
                gap: 6px;
            }

            .seat {
                width: 35px;
                height: 35px;
                font-size: 0.7rem;
            }

            .summary-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .legend {
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">
            <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img">
        </a>        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="movies.php" class="active">Movies</a></li>
            <li><a href="my_bookings.php">My Bookings</a></li>
        </ul>
        <div class="user-actions">
            <?php if ($isLoggedIn): ?>
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="seat-selection-container">
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="booking-header">
            <img src="images/<?php echo htmlspecialchars($movie['poster']); ?>" alt="Movie Poster" class="movie-poster-small">
            <div class="booking-info">
                <h2><?php echo htmlspecialchars($movie['title']); ?></h2>
                <div class="booking-details">
                    <div><strong>Theater:</strong> <?php echo htmlspecialchars($theater['name']); ?></div>
                    <div><strong>Date:</strong> <?php echo htmlspecialchars($date); ?></div>
                    <div><strong>Showtime:</strong> <?php echo htmlspecialchars($showtime); ?></div>
                    <div><strong>Duration:</strong> <?php echo htmlspecialchars($movie['duration']); ?> min • <?php echo htmlspecialchars($movie['genre']); ?></div>
                </div>
            </div>
        </div>

        <div class="screen-container">
            <div class="screen"></div>
            <div class="screen-label">SCREEN</div>
        </div>

        <div class="seating-grid" id="seating-grid">
            <!-- Seats will be generated by JavaScript -->
        </div>        <div class="legend">
            <div class="legend-item">
                <div class="legend-seat legend-vip"></div>
                <span>VIP (₹400)</span>
            </div>
            <div class="legend-item">
                <div class="legend-seat legend-gold"></div>
                <span>Gold (₹200)</span>
            </div>
            <div class="legend-item">
                <div class="legend-seat legend-silver"></div>
                <span>Silver (₹150)</span>
            </div>
            <div class="legend-item">
                <div class="legend-seat legend-selected"></div>
                <span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="legend-seat legend-booked"></div>
                <span>Booked</span>
            </div>
        </div>
    </div>

    <div class="booking-summary" id="booking-summary" style="display: none;">
        <div class="summary-content">
            <div class="selected-info">
                <span id="selected-count">0</span> seat(s) selected: <span id="selected-seats"></span>
            </div>
            <div class="total-price">
                Total: ₹<span id="total-price">0</span>
            </div>
            <form method="POST" id="booking-form">
                <input type="hidden" name="selected_seats" id="selected-seats-input">
                <button type="submit" class="proceed-btn" id="proceed-btn" disabled>
                    <?php echo $isLoggedIn ? 'Proceed to Payment' : 'Login to Book'; ?>
                </button>
            </form>
        </div>
    </div>

    <script>        const bookedSeats = <?php echo json_encode($bookedSeats); ?>;
        const selectedSeats = new Set();
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        
        // Define seat prices and sections
        const seatPrices = {
            silver: 150,
            gold: 200,
            vip: 400
        };        // Define row sections (silver in front, then gold, then VIP)
        const sections = {
            silver: ['A', 'B', 'C'],
            gold: ['D', 'E', 'F'],
            vip: ['G', 'H']
        };

        function getSeatPrice(row) {
            if (sections.vip.includes(row)) return seatPrices.vip;
            if (sections.gold.includes(row)) return seatPrices.gold;
            return seatPrices.silver;
        }

        function getSeatSection(row) {
            if (sections.vip.includes(row)) return 'vip';
            if (sections.gold.includes(row)) return 'gold';
            return 'silver';
        }        // Generate seats (8 rows, 10 seats per row)
        function generateSeats() {
            const grid = document.getElementById('seating-grid');
            const rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            let currentSection = '';
            
            rows.forEach((row, index) => {
                const section = getSeatSection(row);
                
                // Add section labels when section changes
                if (section !== currentSection) {
                    const label = document.createElement('div');
                    label.className = 'section-label';
                    label.textContent = section.toUpperCase() + ' - ₹' + seatPrices[section];
                    grid.appendChild(label);
                    currentSection = section;
                }
                
                for (let i = 1; i <= 10; i++) {
                    const seatNumber = row + i;
                    const seat = document.createElement('div');
                    seat.className = `seat ${section}`;
                    seat.textContent = seatNumber;
                    seat.dataset.seat = seatNumber;
                    seat.dataset.price = getSeatPrice(row);
                    seat.dataset.section = section;
                    seat.dataset.row = row;
                    
                    if (bookedSeats.includes(seatNumber)) {
                        seat.classList.add('booked');
                    } else {
                        seat.addEventListener('click', () => toggleSeat(seat, seatNumber));
                    }
                    
                    grid.appendChild(seat);
                }
            });
        }

        function toggleSeat(seatElement, seatNumber) {
            if (selectedSeats.has(seatNumber)) {
                selectedSeats.delete(seatNumber);
                seatElement.classList.remove('selected');
            } else {
                selectedSeats.add(seatNumber);
                seatElement.classList.add('selected');
            }
            updateSummary();
        }        function updateSummary() {
            const summary = document.getElementById('booking-summary');
            const selectedCount = selectedSeats.size;
            
            if (selectedCount > 0) {
                summary.style.display = 'block';
                document.getElementById('selected-count').textContent = selectedCount;
                document.getElementById('selected-seats').textContent = Array.from(selectedSeats).sort().join(', ');
                
                // Calculate total price based on selected seats
                const totalPrice = Array.from(document.querySelectorAll('.seat.selected')).reduce((total, seat) => {
                    return total + parseInt(seat.dataset.price);
                }, 0);
                
                document.getElementById('total-price').textContent = totalPrice;
                document.getElementById('selected-seats-input').value = JSON.stringify(Array.from(selectedSeats));
                document.getElementById('proceed-btn').disabled = !isLoggedIn;
            } else {
                summary.style.display = 'none';
            }
        }

        // Initialize
        generateSeats();

        // Handle form submission for non-logged-in users
        document.getElementById('booking-form').addEventListener('submit', function(e) {
            if (!isLoggedIn) {
                e.preventDefault();
                window.location.href = 'login.php';
            }
        });
    </script>
</body>
</html>