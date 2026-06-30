<?php
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$username = $isLoggedIn ? $_SESSION['username'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat&Screen Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="styles.css" rel="stylesheet">
    <style>
        /* Styles for the arrow button */
        .arrow-button {
            display: flex;
            color: #ffffff;
            background-color: #1c2035;
            padding: 10px 16px;
            border-radius: 20px;
            transition: all .3s ease;
            font-weight: bold;
            cursor: pointer;
            align-items: center;
            font-size: 14px;
        }

        .arrow-button > .arrow {
            width: 6px;
            height: 6px;
            border-right: 2px solid #C2FFE9;
            border-bottom: 2px solid #C2FFE9;
            position: relative;
            transform: rotate(-45deg);
            margin: 0 6px;
            transition: all .3s ease;
        }

        .arrow-button > .arrow::before {
            display: block;
            background-color: currentColor;
            width: 3px;
            transform-origin: bottom right;
            height: 2px;
            position: absolute;
            opacity: 0;
            bottom: calc(-2px / 2);
            transform: rotate(45deg);
            transition: all .3s ease;
            content: "";
            right: 0;
        }

        .arrow-button:hover > .arrow {
            transform: rotate(-45deg) translate(4px, 4px);
            border-color: #fff;
        }

        .arrow-button:hover > .arrow::before {
            opacity: 1;
            width: 8px;
        }

        .arrow-button:hover {
            background-color: #ff3366;
            color: #fff;
        }

        /* Center the button wrapper */
        .more-movies-wrapper {
            text-align: center;
            margin: 20px 0;
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
            <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
                <li><a href="admin.php" style="color: #ff3366;"><i class="fas fa-user-shield"></i> Admin</a></li>
            <?php endif; ?>
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

    <div class="hero">
        <h1>Discover Amazing Movies</h1>
        <p>Find the latest blockbusters, indie gems, and classics. Book your tickets and enjoy the best cinema experience.</p>
    </div>

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
                    <li><a href="admin.php"><i class="fas fa-lock"></i> Admin Portal</a></li>
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
                    <li><i class="fas fa-envelope"></i> <a href="/cdn-cgi/l/email-protection#395a4c4949564b4d794a5c584d58575d4a5a4b5c5c57175a5654">[email protected]</a></li>
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
                    
                    // Limit to 8 movies
                    movies.slice(0, 8).forEach((movie, index) => {
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

                    // Add arrow button after 8th movie
                    if (movies.length > 8) {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'more-movies-wrapper';
                        wrapper.innerHTML = '<button class="arrow-button">See more<span class="arrow"></span></button>';
                        wrapper.querySelector('.arrow-button').addEventListener('click', () => {
                            window.location.href = 'movies.php';
                        });
                        movieGrid.appendChild(wrapper);
                    }

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