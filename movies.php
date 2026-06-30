<?php
session_start();

// Debug: Check if session variable is set
if (isset($_SESSION['username'])) {
    $isLoggedIn = true;
    $username = $_SESSION['username'];
} else {
    $isLoggedIn = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Movies - Seat&Screen Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
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
                <span class="welcome-message">Welcome, <?php echo htmlspecialchars($username); ?>!</span>
                <a href="logout.php" class="btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="search-bar">
        <input type="text" placeholder="Search for movies, genres, actors..." id="searchInput">
        <button id="searchBtn"><i class="fas fa-search"></i></button>
    </div>

    <div class="filters">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="now">Now Showing</button>
        <button class="filter-btn" data-filter="soon">Coming Soon</button>
        <button class="filter-btn" data-filter="Action">Action</button>
        <button class="filter-btn" data-filter="Comedy">Comedy</button>
        <button class="filter-btn" data-filter="Drama">Drama</button>
        <button class="filter-btn" data-filter="Sci-Fi">Sci-Fi</button>
        <button class="filter-btn" data-filter="Horror">Horror</button>
    </div>

    <div class="movie-grid" id="movieGrid">
        <!-- Movies will be loaded dynamically -->
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
                    <li><a href="theaters.html">Theaters</a></li>
                    <li><a href="offers.html">Offers & Promotions</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
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
                    <li><i class="fas fa-envelope"></i> <a href="/cdn-cgi/l/email-protection#395a4c4949564b4d794a5c584d58575d4a5a4b5c5c57175a5654">[email protected]</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>© 2025 Seat&Screen. All Rights Reserved.</p>
        </div>
    </footer>

    <script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script>
        // Theme Toggle Function
        function toggleTheme() {
            const body = document.body;
            if (body.classList.contains('light-mode')) {
                body.classList.remove('light-mode');
                localStorage.setItem('theme', 'dark');
            } else {
                body.classList.add('light-mode');
                localStorage.setItem('theme', 'light');
            }
        }

        // Load theme from localStorage on page load
        window.onload = function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-mode');
                document.getElementById('theme-switch').checked = true;
            }
        };

        // Dynamic Movie Loading
        function loadMovies(filter = 'all', search = '') {
            fetch(`get_movies.php?filter=${filter}&search=${search}`)
                .then(response => response.json())
                .then(movies => {
                    const movieGrid = document.getElementById('movieGrid');
                    movieGrid.innerHTML = '';
                    
                    movies.forEach(movie => {
                        const card = document.createElement('div');
                        card.className = 'movie-card';
                        card.innerHTML = `
                            <img src="images/${movie.poster}" alt="${movie.title}" class="movie-poster">
                            <div class="movie-info">
                                <h3 class="movie-title">${movie.title}</h3>
                                <div class="movie-details">
                                    <span>${movie.duration} min</span>
                                    <span>${movie.genre}</span>
                                </div>
                                <div class="movie-rating">
                                    <i class="fas fa-star icon"></i>
                                    <span>${movie.rating}/10</span>
                                </div>
                                <button class="btn" data-movie-id="${movie.id}">Book Tickets</button>
                            </div>
                        `;
                        movieGrid.appendChild(card);
                    });

                    // Add event listeners to book ticket buttons
                    document.querySelectorAll('.btn').forEach(button => {
                        button.addEventListener('click', (e) => {
                            const movieId = button.getAttribute('data-movie-id');
                            window.location.href = `movie_description.php?id=${movieId}`;
                        });
                    });
                })
                .catch(error => console.error('Error:', error));
        }

        // Initial load
        loadMovies();

        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                loadMovies(button.dataset.filter);
            });
        });

        // Search functionality
        document.getElementById('searchBtn').addEventListener('click', () => {
            const searchTerm = document.getElementById('searchInput').value;
            loadMovies('all', searchTerm);
        });

        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const searchTerm = document.getElementById('searchInput').value;
                loadMovies('all', searchTerm);
            }
        });
    </script>
</body>
</html>