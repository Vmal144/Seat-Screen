<?php
session_start();
require_once 'db_connect.php';

$isLoggedIn = isset($_SESSION['username']);
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// First, update any past showtimes to be 3 days from today
$updateStmt = $pdo->prepare("
    UPDATE showtimes 
    SET date = DATE_ADD(CURDATE(), INTERVAL 3 DAY) 
    WHERE movie_id = ? 
    AND date < CURDATE()
");
$updateStmt->execute([$movieId]);

$movie = null;
if ($movieId) {
    $stmt = $pdo->prepare("SELECT m.*, md.year, md.plot, md.backdrop, md.trailer FROM movies m LEFT JOIN movie_details md ON m.id = md.id WHERE m.id = ?");
    $stmt->execute([$movieId]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$movie) {
    die("Movie not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Details - Seat&Screen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
    <link href="movie_description.css" rel="stylesheet">
    <style>
        /* Styles for showtime section */
        .theater-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: #161925;
            border-radius: 8px;
        }
        .theater-name {
            flex: 1;
        }
        .theater-name h3 {
            margin: 0;
            color: #ffffff;
            font-size: 1.2rem;
        }
        .theater-amenities {
            margin-top: 0.5rem;
            color: #a0a0a0;
            font-size: 0.9rem;
        }
        .showtimes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .showtime-btn {
            background-color: #ff3366;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        .showtime-btn:hover {
            background-color: #e62958;
        }
        .date-selector {
            margin-bottom: 1.5rem;
        }
        .date-btn {
            background-color: #242838;
            color: #a0a0a0;
            border: 1px solid #3a3f54;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .date-btn.active {
            background-color: #ff3366;
            color: white;
            border-color: #ff3366;
        }

        /* Styles for trailer modal */
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
            position: relative;
            background-color: #161925;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
        }
        .close-modal {
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 24px;
            color: #ffffff;
            cursor: pointer;
            background-color: #ff3366;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 30px;
        }
        .video-container {
            position: relative;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .modal.active {
            display: flex;
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

    <div class="movie-container">
        <div class="movie-backdrop" style="background-image: url('images/<?php echo htmlspecialchars($movie['backdrop'] ?? 'default_backdrop.jpg'); ?>');">
            <div class="backdrop-overlay"></div>
        </div>

        <div class="movie-details-container">
            <div class="movie-poster-container">
                <img id="movie-poster" src="images/<?php echo htmlspecialchars($movie['poster'] ?? 'default_poster.jpg'); ?>" alt="Movie Poster" class="movie-detail-poster">
                <div class="movie-rating">
                    <i class="fas fa-star"></i>
                    <span id="movie-rating"><?php echo htmlspecialchars($movie['rating'] ?? 0); ?></span><span>/10</span>
                </div>
            </div>

            <div class="movie-info-container">
                <h1 id="movie-title"><?php echo htmlspecialchars($movie['title'] ?? 'Unknown Movie'); ?></h1>
                
                <div class="movie-meta">
                    <span id="movie-year"><?php echo htmlspecialchars($movie['year'] ?? '2025'); ?></span>
                    <span class="separator">•</span>
                    <span id="movie-duration"><?php echo htmlspecialchars($movie['duration'] ?? '120'); ?> min</span>
                    <span class="separator">•</span>
                    <span id="movie-genre"><?php echo htmlspecialchars($movie['genre'] ?? 'Unknown'); ?></span>
                </div>
                
                <div class="movie-buttons">
                    <button class="btn book-btn primary" id="book-tickets-btn">
                        <i class="fas fa-ticket-alt"></i> Book Tickets
                    </button>
                    <button class="btn trailer-btn" id="watch-trailer-btn">
                        <i class="fas fa-play"></i> Watch Trailer
                    </button>
                </div>

                <div class="movie-description">
                    <h3>Synopsis</h3>
                    <p id="movie-plot"><?php echo htmlspecialchars($movie['plot'] ?? 'No synopsis available.'); ?></p>
                </div>

                <div class="movie-cast">
                    <h3>Cast & Crew</h3>
                    <div class="cast-members" id="cast-members">
                        <?php
                        $stmt = $pdo->prepare("SELECT name, movie_char, image FROM movie_cast WHERE movie_id = ?");
                        $stmt->execute([$movieId]);
                        $cast = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($cast as $actor) {
                            echo '<div class="cast-member">
                                <div class="cast-name">' . htmlspecialchars($actor['name']) . '</div>
                                <div class="cast-character">' . htmlspecialchars($actor['movie_char']) . '</div>
                            </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="showtime-section" id="show-time">
            <h2>Available Showtimes</h2>              <div class="date-selector">
                <?php
                $currentDate = new DateTime();  // Current date
                $tomorrow = (new DateTime())->modify('+1 day');
                
                // First get dates that are either future dates or today
                $stmt = $pdo->prepare("SELECT DISTINCT s.date 
                    FROM showtimes s
                    WHERE s.movie_id = ? 
                    AND (s.date > CURDATE() 
                         OR (s.date = CURDATE() AND s.time > DATE_ADD(NOW(), INTERVAL 30 MINUTE)))
                    ORDER BY s.date LIMIT 7");
                $stmt->execute([$movieId]);
                $showtimeDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $dateLabels = [];
                $firstAvailableDate = null;
                
                foreach ($showtimeDates as $date) {
                    $dateObj = new DateTime($date);
                    
                    // Set label based on the date
                    if ($dateObj->format('Y-m-d') === $currentDate->format('Y-m-d')) {
                        $label = 'Today';
                        $dataDate = 'today';
                    } elseif ($dateObj->format('Y-m-d') === $tomorrow->format('Y-m-d')) {
                        $label = 'Tomorrow';
                        $dataDate = 'tomorrow';
                    } else {
                        $label = $dateObj->format('D, M j');  // e.g., "Mon, Jun 3"
                        $dataDate = 'day-' . $dateObj->format('Y-m-d');
                    }
                    $dateLabels[$dataDate] = $date;
                    
                    // Store first available date for default selection
                    if ($firstAvailableDate === null) {
                        $firstAvailableDate = $dataDate;
                    }
                    
                    echo '<button class="date-btn' . ($dataDate === $firstAvailableDate ? ' active' : '') . 
                         '" data-date="' . $dataDate . '">' . $label . '</button>';
                }
                ?>
            </div>

            <div class="theaters-container" id="theaters-container">
                <?php                $theaterItems = [];                foreach ($dateLabels as $dataDate => $date) {
                    // For today, only show times that are at least 30 minutes in the future
                    $timeCondition = "";
                    if ($date === date('Y-m-d')) {
                        $timeCondition = " AND s.time > DATE_ADD(NOW(), INTERVAL 30 MINUTE)";
                    }
                    
                    // Query theaters and showtimes with proper date/time filtering
                    $stmt = $pdo->prepare("SELECT t.id AS theater_id, t.name, t.amenities, s.time 
                        FROM theaters t 
                        JOIN showtimes s ON t.id = s.theater_id 
                        WHERE s.movie_id = ? AND s.date = ?" . $timeCondition . "
                        ORDER BY s.time ASC");
                    $stmt->execute([$movieId, $date]);
                    $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($showtimes as $showtime) {
                        $theaterId = $showtime['theater_id'];
                        if (!isset($theaterItems[$theaterId])) {
                            $theaterItems[$theaterId] = [
                                'name' => $showtime['name'],
                                'amenities' => explode(',', $showtime['amenities'] ?? ''),
                                'times' => []
                            ];
                        }
                        $theaterItems[$theaterId]['times'][] = [
                            'date' => $date,
                            'dataDate' => $dataDate,
                            'time' => $showtime['time']
                        ];
                    }
                }

                if (empty($theaterItems)) {
                    echo '<p>No showtimes available for this movie.</p>';
                } else {
                    foreach ($theaterItems as $theaterId => $theater) {
                        echo '<div class="theater-item" data-theater-id="' . $theaterId . '">
                            <div class="theater-name">
                                <h3>' . htmlspecialchars($theater['name']) . '</h3>
                                <div class="theater-amenities">';
                        foreach ($theater['amenities'] as $amenity) {
                            echo '<span class="amenity"><i class="fas fa-' . htmlspecialchars(trim($amenity)) . '"></i> ' . ucfirst(htmlspecialchars(trim($amenity))) . '</span>';
                        }
                        echo '</div></div>
                            <div class="showtimes">';
                        foreach ($theater['times'] as $timeData) {
                            // Format the time in 12-hour format with AM/PM
            $formattedTime = date('g:i A', strtotime($timeData['time']));
            echo '<a href="javascript:void(0)" class="showtime-btn" data-theater="' . $theaterId . '" data-showtime="' . htmlspecialchars($timeData['time']) . '" data-date="' . htmlspecialchars($timeData['date']) . '" data-date-key="' . htmlspecialchars($timeData['dataDate']) . '">' . htmlspecialchars($formattedTime) . '</a>';
                        }
                        echo '</div>
                        </div>';
                    }
                }
                ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll for Book Tickets button
            document.getElementById('book-tickets-btn').addEventListener('click', function(e) {
                e.preventDefault();
                const showtimeSection = document.getElementById('show-time');
                if (showtimeSection) {
                    showtimeSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });

            // Date button click handler (only for highlighting)
            document.querySelectorAll('.date-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.date-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Showtime button click handler
            document.querySelectorAll('.showtime-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const theaterId = this.getAttribute('data-theater');
                    const showtime = this.getAttribute('data-showtime');
                    const date = this.getAttribute('data-date');
                    window.location.href = `seat_selection.php?theater=${theaterId}&showtime=${encodeURIComponent(showtime)}&movie=<?php echo $movieId; ?>&date=${encodeURIComponent(date)}`;
                });
            });            // No need to set initial active state as it's handled in PHP now

            // Trailer modal handlers
            const trailerModal = document.getElementById('trailer-modal');
            const trailerBtn = document.getElementById('watch-trailer-btn');
            const closeModal = trailerModal.querySelector('.close-modal');
            const trailerIframe = trailerModal.querySelector('#trailer-iframe');

            trailerBtn.addEventListener('click', function() {
                // Ensure trailer URL is loaded
                trailerIframe.src = '<?php echo htmlspecialchars($movie['trailer'] ?? ''); ?>';
                trailerModal.classList.add('active');
            });

            closeModal.addEventListener('click', function() {
                trailerModal.classList.remove('active');
                // Reset iframe to stop playback
                trailerIframe.src = trailerIframe.src;
            });

            // Close modal when clicking outside
            trailerModal.addEventListener('click', function(e) {
                if (e.target === trailerModal) {
                    trailerModal.classList.remove('active');
                    trailerIframe.src = trailerIframe.src;
                }
            });

            // Close modal with Esc key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && trailerModal.classList.contains('active')) {
                    trailerModal.classList.remove('active');
                    trailerIframe.src = trailerIframe.src;
                }
            });
        });
        </script>

        <div class="reviews-section">
            <h2>User Reviews</h2>
            
            <div class="reviews-container" id="reviews-container">
                <?php
                $stmt = $pdo->prepare("SELECT user_name, rating, date, comment FROM reviews WHERE movie_id = ? LIMIT 2");
                $stmt->execute([$movieId]);
                $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($reviews as $review) {
                    echo '<div class="review-item">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <span class="reviewer-name">' . htmlspecialchars($review['user_name']) . '</span>
                                <div class="reviewer-rating">';
                    $rating = (int)($review['rating'] ?? 0);
                    for ($i = 0; $i < 5; $i++) {
                        echo '<i class="' . ($i < $rating ? 'fas' : 'far') . ' fa-star"></i>';
                    }
                    echo '</div></div>
                            <span class="review-date">' . htmlspecialchars($review['date']) . '</span>
                        </div>
                        <p class="review-text">' . htmlspecialchars($review['comment']) . '</p>
                    </div>';
                }
                ?>
            </div>
            
            <button class="btn load-more-btn">Load More Reviews</button>
        </div>
    </div>

    <div id="trailer-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="video-container">
                <iframe id="trailer-iframe" src="<?php echo htmlspecialchars($movie['trailer'] ?? ''); ?>" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>Seat&Screen</h3>
                <p>Your premier destination for the ultimate movie experience. Book tickets online and enjoy the best cinema experience with our state-of-the-art theaters.</p>
                <div class="social-media">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="movies.php">Movies</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Help & Support</h3>
                <ul>
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Refund Policy</a></li>
                    <li><a href="#">Feedback</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact Information</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Movie Street, Cinema City</li>
                    <li><i class="fas fa-phone"></i> +1 234 567 8900</li>
                    <li><i class="fas fa-envelope"></i> <a href="/cdn-cgi/l/email-protection#2c494740404f423a594f4b5e4b444e59494a5d5d56161f">[email protected]</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>© 2025 Seat&Screen. All Rights Reserved.</p>
        </div>
    </footer>

    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="movie_description.js"></script>
</body>
</html>