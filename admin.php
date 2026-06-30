<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Prevent browser caching of admin dashboard
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Seat&Screen</title>
    <!-- Fonts and Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Styles -->
    <link href="admin_styles.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="images/logo.png" alt="Seat&Screen Admin" onerror="this.src='https://placehold.co/200x50/161925/ff3366?text=Seat%26Screen';">
            </div>
            <ul class="sidebar-menu">
                <li class="active" data-tab="dashboard"><a onclick="switchTab('dashboard')"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li data-tab="movies"><a onclick="switchTab('movies')"><i class="fas fa-film"></i> Movies</a></li>
                <li data-tab="theaters"><a onclick="switchTab('theaters')"><i class="fas fa-building"></i> Theaters</a></li>
                <li data-tab="showtimes"><a onclick="switchTab('showtimes')"><i class="fas fa-calendar-alt"></i> Showtimes</a></li>
                <li data-tab="users"><a onclick="switchTab('users')"><i class="fas fa-users"></i> Users</a></li>
                <li data-tab="bookings"><a onclick="switchTab('bookings')"><i class="fas fa-ticket-alt"></i> Bookings</a></li>
            </ul>
            <div class="sidebar-footer">
                <button onclick="logoutAdmin()" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Dashboard Tab -->
            <div id="tab-dashboard" class="tab-content active">
                <div class="main-header">
                    <h1>Dashboard Overview</h1>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Movies</h3>
                            <p id="stat-movies-count">-</p>
                        </div>
                        <i class="fas fa-film stat-icon"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Theaters</h3>
                            <p id="stat-theaters-count">-</p>
                        </div>
                        <i class="fas fa-building stat-icon"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Users</h3>
                            <p id="stat-users-count">-</p>
                        </div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Bookings</h3>
                            <p id="stat-bookings-count">-</p>
                        </div>
                        <i class="fas fa-ticket-alt stat-icon"></i>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Revenue</h3>
                            <p id="stat-revenue-sum">₹0.00</p>
                        </div>
                        <i class="fas fa-wallet stat-icon"></i>
                    </div>
                </div>

                <!-- Recent Activities Panel -->
                <div class="card">
                    <div class="card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button onclick="switchTab('movies'); openMovieAdd();" class="btn"><i class="fas fa-plus"></i> Add New Movie</button>
                        <button onclick="switchTab('theaters'); openTheaterAdd();" class="btn"><i class="fas fa-plus"></i> Add New Theater</button>
                        <button onclick="switchTab('showtimes'); openShowtimeAdd();" class="btn"><i class="fas fa-plus"></i> Schedule Showtime</button>
                        <button onclick="window.open('index.php', '_blank');" class="btn btn-secondary"><i class="fas fa-external-link-alt"></i> Open Live Site</button>
                    </div>
                </div>
            </div>

            <!-- Movies Tab -->
            <div id="tab-movies" class="tab-content">
                <div class="main-header">
                    <h1>Manage Movies</h1>
                    <button onclick="openMovieAdd()" class="btn"><i class="fas fa-plus"></i> Add Movie</button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Poster</th>
                                    <th>Title</th>
                                    <th>Genre</th>
                                    <th>Duration</th>
                                    <th>Rating</th>
                                    <th>Status</th>
                                    <th>Year</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="movies-table-body">
                                <tr><td colspan="8" align="center">Loading movies...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Theaters Tab -->
            <div id="tab-theaters" class="tab-content">
                <div class="main-header">
                    <h1>Manage Theaters</h1>
                    <button onclick="openTheaterAdd()" class="btn"><i class="fas fa-plus"></i> Add Theater</button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Theater Name</th>
                                    <th>Amenities</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="theaters-table-body">
                                <tr><td colspan="4" align="center">Loading theaters...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Showtimes Tab -->
            <div id="tab-showtimes" class="tab-content">
                <div class="main-header">
                    <h1>Schedule Showtimes</h1>
                    <button onclick="openShowtimeAdd()" class="btn"><i class="fas fa-plus"></i> Add Showtime</button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Movie</th>
                                    <th>Theater</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="showtimes-table-body">
                                <tr><td colspan="6" align="center">Loading showtimes...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div id="tab-users" class="tab-content">
                <div class="main-header">
                    <h1>Registered Users</h1>
                    <button onclick="openUserAdd()" class="btn"><i class="fas fa-plus"></i> Add User</button>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Firebase UID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <tr><td colspan="5" align="center">Loading users...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bookings Tab -->
            <div id="tab-bookings" class="tab-content">
                <div class="main-header">
                    <h1>Customer Bookings</h1>
                </div>
                <div class="card">
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>User</th>
                                    <th>Movie</th>
                                    <th>Theater</th>
                                    <th>Seats</th>
                                    <th>Showtime</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Booking Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="bookings-table-body">
                                <tr><td colspan="11" align="center">Loading bookings...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Movie Modal -->
    <div id="movie-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('movie-modal')">&times;</span>
            <h2 id="movie-modal-title" class="modal-title">Add Movie</h2>
            <form id="movie-form" onsubmit="saveMovie(event)">
                <input type="hidden" id="movie-id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="movie-title-input">Title *</label>
                        <input type="text" id="movie-title-input" name="title" class="form-control" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="movie-genre-input">Genre *</label>
                        <input type="text" id="movie-genre-input" name="genre" class="form-control" placeholder="Action, Adventure" required autocomplete="off">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="movie-duration-input">Duration (minutes) *</label>
                        <input type="number" id="movie-duration-input" name="duration" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="movie-rating-input">Rating (0.0 to 10.0) *</label>
                        <input type="number" id="movie-rating-input" name="rating" min="0" max="10" step="0.1" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="movie-status-input">Status *</label>
                        <select id="movie-status-input" name="status" class="form-control" required>
                            <option value="now">Now Showing</option>
                            <option value="soon">Coming Soon</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="movie-year-input">Release Year *</label>
                        <input type="text" id="movie-year-input" name="year" class="form-control" maxlength="4" placeholder="2026" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="movie-trailer-input">Trailer Embed Link</label>
                        <input type="text" id="movie-trailer-input" name="trailer" class="form-control" placeholder="https://www.youtube.com/embed/..." autocomplete="off">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="movie-poster-input">Poster Image File</label>
                        <input type="file" id="movie-poster-input" name="poster" class="form-control" accept="image/*">
                        <div style="margin-top: 8px; display: flex; align-items: center; gap: 10px;">
                            <img id="movie-poster-preview" src="" class="image-preview" style="display:none;">
                            <span id="movie-poster-name" class="badge badge-info" style="display:none;"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="movie-backdrop-input">Backdrop Image File</label>
                        <input type="file" id="movie-backdrop-input" name="backdrop" class="form-control" accept="image/*">
                        <div style="margin-top: 8px; display: flex; align-items: center; gap: 10px;">
                            <img id="movie-backdrop-preview" src="" class="backdrop-preview" style="display:none;">
                            <span id="movie-backdrop-name" class="badge badge-info" style="display:none;"></span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="movie-plot-input">Synopsis (Plot) *</label>
                    <textarea id="movie-plot-input" name="plot" class="form-control" required></textarea>
                </div>

                <!-- Cast list section -->
                <div class="form-group">
                    <label>Cast Members</label>
                    <div class="cast-manager">
                        <div id="cast-rows-container" class="cast-list-container">
                            <!-- Rows added dynamically -->
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addCastRow()"><i class="fas fa-plus"></i> Add Cast Member</button>
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('movie-modal')">Cancel</button>
                    <button type="submit" class="btn">Save Movie</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Theater Modal -->
    <div id="theater-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('theater-modal')">&times;</span>
            <h2 id="theater-modal-title" class="modal-title">Add Theater</h2>
            <form id="theater-form" onsubmit="saveTheater(event)">
                <input type="hidden" id="theater-id" name="id">
                
                <div class="form-group">
                    <label for="theater-name-input">Theater Name *</label>
                    <input type="text" id="theater-name-input" name="name" class="form-control" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="theater-amenities-input">Amenities (comma-separated list)</label>
                    <input type="text" id="theater-amenities-input" name="amenities" class="form-control" placeholder="Dolby Atmos, 4K Projection, Recliner Seats" autocomplete="off">
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('theater-modal')">Cancel</button>
                    <button type="submit" class="btn">Save Theater</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Showtime Modal -->
    <div id="showtime-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('showtime-modal')">&times;</span>
            <h2 id="showtime-modal-title" class="modal-title">Schedule Showtime</h2>
            <form id="showtime-form" onsubmit="saveShowtime(event)">
                <input type="hidden" id="showtime-id" name="id">
                
                <div class="form-group">
                    <label for="showtime-movie-input">Select Movie *</label>
                    <select id="showtime-movie-input" name="movie_id" class="form-control" required>
                        <option value="">Choose a Movie...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="showtime-theater-input">Select Theater *</label>
                    <select id="showtime-theater-input" name="theater_id" class="form-control" required>
                        <option value="">Choose a Theater...</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="showtime-date-input">Date *</label>
                        <input type="date" id="showtime-date-input" name="date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="showtime-time-input">Time *</label>
                        <input type="time" id="showtime-time-input" name="time" class="form-control" required>
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('showtime-modal')">Cancel</button>
                    <button type="submit" class="btn">Save Showtime</button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Modal -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal('user-modal')">&times;</span>
            <h2 id="user-modal-title" class="modal-title">Add User</h2>
            <form id="user-form" onsubmit="saveUser(event)">
                <input type="hidden" id="user-id" name="id">
                
                <div class="form-group">
                    <label for="user-uid-input">Firebase UID (Leave blank to auto-generate mock ID)</label>
                    <input type="text" id="user-uid-input" name="firebase_uid" class="form-control" autocomplete="off" placeholder="e.g. wj89H2gSdfY2s4nLksj3n">
                </div>

                <div class="form-group">
                    <label for="user-name-input">Username *</label>
                    <input type="text" id="user-name-input" name="username" class="form-control" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="user-email-input">Email Address *</label>
                    <input type="email" id="user-email-input" name="email" class="form-control" required autocomplete="off">
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('user-modal')">Cancel</button>
                    <button type="submit" class="btn">Save User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i id="toast-icon" class="fas fa-check-circle"></i>
        <span id="toast-message">Success! Action completed.</span>
    </div>

    <!-- JS Logic -->
    <script>
        // Tab switching logic
        function switchTab(tabId) {
            // Remove active classes
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Add active class to corresponding menu item & content pane
            const menuItem = document.querySelector(`.sidebar-menu li[data-tab="${tabId}"]`);
            if (menuItem) menuItem.classList.add('active');
            
            const contentPane = document.getElementById(`tab-${tabId}`);
            if (contentPane) contentPane.classList.add('active');

            // Load data for the active tab
            if (tabId === 'dashboard') {
                loadDashboardStats();
            } else if (tabId === 'movies') {
                loadMovies();
            } else if (tabId === 'theaters') {
                loadTheaters();
            } else if (tabId === 'showtimes') {
                loadShowtimes();
            } else if (tabId === 'users') {
                loadUsers();
            } else if (tabId === 'bookings') {
                loadBookings();
            }
        }

        // Logout
        function logoutAdmin() {
            if (confirm('Are you sure you want to log out of Admin Portal?')) {
                window.location.href = 'admin_logout.php';
            }
        }

        // Modal Helpers
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Toast Helper
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toast-icon');
            const msgSpan = document.getElementById('toast-message');
            
            toast.className = 'toast'; // reset classes
            
            if (type === 'success') {
                toast.classList.add('toast-success');
                icon.className = 'fas fa-check-circle';
                icon.style.color = 'var(--success-color)';
            } else if (type === 'danger') {
                toast.classList.add('toast-danger');
                icon.className = 'fas fa-exclamation-circle';
                icon.style.color = 'var(--danger-color)';
            }
            
            msgSpan.textContent = message;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        // LOAD: Dashboard Stats
        function loadDashboardStats() {
            fetch('admin_api.php?action=get_stats')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('stat-movies-count').textContent = data.stats.movies;
                        document.getElementById('stat-theaters-count').textContent = data.stats.theaters;
                        document.getElementById('stat-users-count').textContent = data.stats.users;
                        document.getElementById('stat-bookings-count').textContent = data.stats.bookings;
                        document.getElementById('stat-revenue-sum').textContent = '₹' + data.stats.revenue;
                    } else {
                        showToast(data.error || 'Failed to fetch stats', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Failed to load dashboard statistics', 'danger');
                });
        }

        // LOAD: Movies Table
        function loadMovies() {
            const tableBody = document.getElementById('movies-table-body');
            tableBody.innerHTML = '<tr><td colspan="8" align="center">Loading movies...</td></tr>';
            
            fetch('admin_api.php?action=get_movies')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = '';
                        if (data.movies.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="8" align="center">No movies found. Click Add Movie to add one!</td></tr>';
                            return;
                        }
                        
                        data.movies.forEach(movie => {
                            const badge = movie.status === 'now' 
                                ? '<span class="badge badge-success">Now Showing</span>' 
                                : '<span class="badge badge-warning">Coming Soon</span>';
                            
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td><img src="images/${movie.poster}" alt="Poster" style="width: 40px; height: 55px; object-fit: cover; border-radius: 4px;"></td>
                                <td><strong>${escapeHtml(movie.title)}</strong></td>
                                <td>${escapeHtml(movie.genre)}</td>
                                <td>${movie.duration} mins</td>
                                <td><i class="fas fa-star" style="color: #ffa502;"></i> ${movie.rating}/10</td>
                                <td>${badge}</td>
                                <td>${escapeHtml(movie.year || '-')}</td>
                                <td>
                                    <div class="table-actions">
                                        <button onclick="openMovieEdit(${movie.id})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</button>
                                        <button onclick="deleteMovie(${movie.id})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });
                    } else {
                        showToast(data.error || 'Failed to fetch movies', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error fetching movies list', 'danger');
                });
        }

        // Add Cast Row helper
        function addCastRow(name = '', character = '', image = 'default_cast.jpg') {
            const container = document.getElementById('cast-rows-container');
            const row = document.createElement('div');
            row.className = 'cast-item-row';
            row.innerHTML = `
                <input type="text" class="form-control cast-name" placeholder="Actor Name" value="${escapeHtml(name)}" required style="flex: 2;">
                <input type="text" class="form-control cast-char" placeholder="Character Name" value="${escapeHtml(character)}" required style="flex: 2;">
                <input type="text" class="form-control cast-img" placeholder="Image filename (e.g. actor.jpg)" value="${escapeHtml(image)}" required style="flex: 1.5;">
                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()" style="padding: 0.6rem;"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }

        // Movie Forms Actions
        function openMovieAdd() {
            document.getElementById('movie-form').reset();
            document.getElementById('movie-id').value = '';
            document.getElementById('movie-modal-title').textContent = 'Add Movie';
            
            // Clear image previews
            document.getElementById('movie-poster-preview').style.display = 'none';
            document.getElementById('movie-poster-name').style.display = 'none';
            document.getElementById('movie-backdrop-preview').style.display = 'none';
            document.getElementById('movie-backdrop-name').style.display = 'none';
            
            // Clear cast
            document.getElementById('cast-rows-container').innerHTML = '';
            // Add one default empty cast row
            addCastRow();
            
            openModal('movie-modal');
        }

        function openMovieEdit(id) {
            document.getElementById('movie-form').reset();
            document.getElementById('movie-id').value = id;
            document.getElementById('movie-modal-title').textContent = 'Edit Movie';
            
            fetch(`admin_api.php?action=get_movie&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const m = data.movie;
                        document.getElementById('movie-title-input').value = m.title;
                        document.getElementById('movie-genre-input').value = m.genre;
                        document.getElementById('movie-duration-input').value = m.duration;
                        document.getElementById('movie-rating-input').value = m.rating;
                        document.getElementById('movie-status-input').value = m.status;
                        document.getElementById('movie-year-input').value = m.year || '';
                        document.getElementById('movie-trailer-input').value = m.trailer || '';
                        document.getElementById('movie-plot-input').value = m.plot || '';
                        
                        // Poster image preview
                        if (m.poster) {
                            const pImg = document.getElementById('movie-poster-preview');
                            pImg.src = `images/${m.poster}`;
                            pImg.style.display = 'block';
                            const pName = document.getElementById('movie-poster-name');
                            pName.textContent = m.poster;
                            pName.style.display = 'inline-block';
                        }
                        
                        // Backdrop image preview
                        if (m.backdrop) {
                            const bImg = document.getElementById('movie-backdrop-preview');
                            bImg.src = `images/${m.backdrop}`;
                            bImg.style.display = 'block';
                            const bName = document.getElementById('movie-backdrop-name');
                            bName.textContent = m.backdrop;
                            bName.style.display = 'inline-block';
                        }
                        
                        // Clear and populate cast members
                        const castContainer = document.getElementById('cast-rows-container');
                        castContainer.innerHTML = '';
                        if (m.cast && m.cast.length > 0) {
                            m.cast.forEach(actor => {
                                addCastRow(actor.name, actor.movie_char, actor.image);
                            });
                        } else {
                            addCastRow();
                        }
                        
                        openModal('movie-modal');
                    } else {
                        showToast(data.error || 'Failed to load movie details', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error fetching movie details', 'danger');
                });
        }

        function saveMovie(event) {
            event.preventDefault();
            const form = document.getElementById('movie-form');
            const formData = new FormData(form);
            
            // Gather cast members from dynamic rows
            const cast = [];
            const rows = document.querySelectorAll('#cast-rows-container .cast-item-row');
            rows.forEach(row => {
                const name = row.querySelector('.cast-name').value.trim();
                const movie_char = row.querySelector('.cast-char').value.trim();
                const image = row.querySelector('.cast-img').value.trim();
                if (name) {
                    cast.push({ name, movie_char, image });
                }
            });
            formData.append('cast', JSON.stringify(cast));
            
            fetch('admin_api.php?action=save_movie', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('movie-modal');
                    showToast(data.message || 'Movie saved successfully');
                    loadMovies();
                } else {
                    showToast(data.error || 'Failed to save movie', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error saving movie details', 'danger');
            });
        }

        function deleteMovie(id) {
            if (confirm('WARNING: Deleting this movie will cancel and delete all associated showtimes, seats, and bookings! Are you absolutely sure?')) {
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('admin_api.php?action=delete_movie', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Movie deleted');
                        loadMovies();
                    } else {
                        showToast(data.error || 'Failed to delete movie', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error deleting movie', 'danger');
                });
            }
        }

        // LOAD: Theaters Table
        function loadTheaters() {
            const tableBody = document.getElementById('theaters-table-body');
            tableBody.innerHTML = '<tr><td colspan="4" align="center">Loading theaters...</td></tr>';
            
            fetch('admin_api.php?action=get_theaters')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = '';
                        if (data.theaters.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="4" align="center">No theaters configured. Click Add Theater to add one!</td></tr>';
                            return;
                        }
                        
                        data.theaters.forEach(theater => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${theater.id}</td>
                                <td><strong>${escapeHtml(theater.name)}</strong></td>
                                <td>${escapeHtml(theater.amenities || 'None')}</td>
                                <td>
                                    <div class="table-actions">
                                        <button onclick="openTheaterEdit(${theater.id})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</button>
                                        <button onclick="deleteTheater(${theater.id})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });
                    } else {
                        showToast(data.error || 'Failed to fetch theaters', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error fetching theaters list', 'danger');
                });
        }

        function openTheaterAdd() {
            document.getElementById('theater-form').reset();
            document.getElementById('theater-id').value = '';
            document.getElementById('theater-modal-title').textContent = 'Add Theater';
            openModal('theater-modal');
        }

        function openTheaterEdit(id) {
            document.getElementById('theater-form').reset();
            document.getElementById('theater-id').value = id;
            document.getElementById('theater-modal-title').textContent = 'Edit Theater';
            
            fetch('admin_api.php?action=get_theaters')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const theater = data.theaters.find(t => t.id == id);
                        if (theater) {
                            document.getElementById('theater-name-input').value = theater.name;
                            document.getElementById('theater-amenities-input').value = theater.amenities || '';
                            openModal('theater-modal');
                        } else {
                            showToast('Theater details not found local storage', 'danger');
                        }
                    }
                });
        }

        function saveTheater(event) {
            event.preventDefault();
            const form = document.getElementById('theater-form');
            const formData = new FormData(form);
            
            fetch('admin_api.php?action=save_theater', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('theater-modal');
                    showToast(data.message || 'Theater saved successfully');
                    loadTheaters();
                } else {
                    showToast(data.error || 'Failed to save theater', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error saving theater details', 'danger');
            });
        }

        function deleteTheater(id) {
            if (confirm('WARNING: Deleting this theater will cancel and delete all associated showtimes, seats, and bookings! Are you absolutely sure?')) {
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('admin_api.php?action=delete_theater', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Theater deleted');
                        loadTheaters();
                    } else {
                        showToast(data.error || 'Failed to delete theater', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error deleting theater', 'danger');
                });
            }
        }

        // LOAD: Showtimes Table
        function loadShowtimes() {
            const tableBody = document.getElementById('showtimes-table-body');
            tableBody.innerHTML = '<tr><td colspan="6" align="center">Loading showtimes...</td></tr>';
            
            fetch('admin_api.php?action=get_showtimes')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = '';
                        if (data.showtimes.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="6" align="center">No showtimes scheduled. Click Add Showtime to add one!</td></tr>';
                            return;
                        }
                        
                        data.showtimes.forEach(st => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${st.id}</td>
                                <td><strong>${escapeHtml(st.movie_title)}</strong></td>
                                <td>${escapeHtml(st.theater_name)}</td>
                                <td>${formatDate(st.date)}</td>
                                <td>${formatTime(st.time)}</td>
                                <td>
                                    <div class="table-actions">
                                        <button onclick="openShowtimeEdit(${st.id})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</button>
                                        <button onclick="deleteShowtime(${st.id})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });
                    } else {
                        showToast(data.error || 'Failed to fetch showtimes', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error fetching showtimes list', 'danger');
                });
        }

        // Populates movie & theater dropdown options inside showtime modal
        function populateDropdowns(selectedMovieId = '', selectedTheaterId = '') {
            const movieSelect = document.getElementById('showtime-movie-input');
            const theaterSelect = document.getElementById('showtime-theater-input');
            
            // Clear but keep first placeholder
            movieSelect.innerHTML = '<option value="">Choose a Movie...</option>';
            theaterSelect.innerHTML = '<option value="">Choose a Theater...</option>';

            // Load movies
            fetch('admin_api.php?action=get_movies')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        data.movies.forEach(movie => {
                            const opt = document.createElement('option');
                            opt.value = movie.id;
                            opt.textContent = movie.title;
                            if (movie.id == selectedMovieId) opt.selected = true;
                            movieSelect.appendChild(opt);
                        });
                    }
                });

            // Load theaters
            fetch('admin_api.php?action=get_theaters')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        data.theaters.forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.id;
                            opt.textContent = t.name;
                            if (t.id == selectedTheaterId) opt.selected = true;
                            theaterSelect.appendChild(opt);
                        });
                    }
                });
        }

        function openShowtimeAdd() {
            document.getElementById('showtime-form').reset();
            document.getElementById('showtime-id').value = '';
            document.getElementById('showtime-modal-title').textContent = 'Schedule Showtime';
            
            populateDropdowns();
            openModal('showtime-modal');
        }

        function openShowtimeEdit(id) {
            document.getElementById('showtime-form').reset();
            document.getElementById('showtime-id').value = id;
            document.getElementById('showtime-modal-title').textContent = 'Edit Showtime';
            
            fetch('admin_api.php?action=get_showtimes')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const st = data.showtimes.find(item => item.id == id);
                        if (st) {
                            populateDropdowns(st.movie_id, st.theater_id);
                            document.getElementById('showtime-date-input').value = st.date;
                            // Time might have format HH:MM:SS, HTML time input takes HH:MM
                            document.getElementById('showtime-time-input').value = st.time.substring(0, 5);
                            openModal('showtime-modal');
                        }
                    }
                });
        }

        function saveShowtime(event) {
            event.preventDefault();
            const form = document.getElementById('showtime-form');
            const formData = new FormData(form);
            
            fetch('admin_api.php?action=save_showtime', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('showtime-modal');
                    showToast(data.message || 'Showtime scheduled successfully');
                    loadShowtimes();
                } else {
                    showToast(data.error || 'Failed to schedule showtime', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error saving showtime scheduling', 'danger');
            });
        }

        function deleteShowtime(id) {
            if (confirm('WARNING: Deleting this showtime will cancel and delete all user bookings booked for this showtime slot! Are you absolutely sure?')) {
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('admin_api.php?action=delete_showtime', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Showtime deleted');
                        loadShowtimes();
                    } else {
                        showToast(data.error || 'Failed to delete showtime', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error deleting showtime', 'danger');
                });
            }
        }

        // LOAD: Users Table
        function loadUsers() {
            const tableBody = document.getElementById('users-table-body');
            tableBody.innerHTML = '<tr><td colspan="5" align="center">Loading users...</td></tr>';
            
            fetch('admin_api.php?action=get_users')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = '';
                        if (data.users.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="5" align="center">No users registered in database.</td></tr>';
                            return;
                        }
                        
                        data.users.forEach(user => {
                            tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>${user.id}</td>
                                <td><small class="badge badge-info">${escapeHtml(user.firebase_uid || 'Mock user')}</small></td>
                                <td><strong>${escapeHtml(user.username)}</strong></td>
                                <td>${escapeHtml(user.email)}</td>
                                <td>
                                    <div class="table-actions">
                                        <button onclick="openUserEdit(${user.id})" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</button>
                                        <button onclick="deleteUser(${user.id})" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });
                    } else {
                        showToast(data.error || 'Failed to fetch users', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error fetching users list', 'danger');
                });
        }

        function openUserAdd() {
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = '';
            document.getElementById('user-modal-title').textContent = 'Add User';
            openModal('user-modal');
        }

        function openUserEdit(id) {
            document.getElementById('user-form').reset();
            document.getElementById('user-id').value = id;
            document.getElementById('user-modal-title').textContent = 'Edit User';
            
            fetch('admin_api.php?action=get_users')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const user = data.users.find(u => u.id == id);
                        if (user) {
                            document.getElementById('user-uid-input').value = user.firebase_uid || '';
                            document.getElementById('user-name-input').value = user.username;
                            document.getElementById('user-email-input').value = user.email;
                            openModal('user-modal');
                        }
                    }
                });
        }

        function saveUser(event) {
            event.preventDefault();
            const form = document.getElementById('user-form');
            const formData = new FormData(form);
            
            fetch('admin_api.php?action=save_user', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('user-modal');
                    showToast(data.message || 'User profile saved');
                    loadUsers();
                } else {
                    showToast(data.error || 'Failed to save user', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error saving user details', 'danger');
            });
        }

        function deleteUser(id) {
            if (confirm('WARNING: Deleting this user will cancel all bookings and reviews made by them! Are you absolutely sure?')) {
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('admin_api.php?action=delete_user', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'User deleted');
                        loadUsers();
                    } else {
                        showToast(data.error || 'Failed to delete user', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error deleting user profile', 'danger');
                });
            }
        }

        // LOAD: Bookings Table
        function loadBookings() {
            const tableBody = document.getElementById('bookings-table-body');
            tableBody.innerHTML = '<tr><td colspan="11" align="center">Loading bookings...</td></tr>';
            
            fetch('admin_api.php?action=get_bookings')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = '';
                        if (data.bookings.length === 0) {
                            tableBody.innerHTML = '<tr><td colspan="11" align="center">No tickets booked yet by users.</td></tr>';
                            return;
                        }
                        
                        data.bookings.forEach(b => {
                            const badge = b.status === 'confirmed' 
                                ? '<span class="badge badge-success">Confirmed</span>' 
                                : '<span class="badge badge-danger">Cancelled</span>';
                            
                            const actionBtn = b.status === 'confirmed'
                                ? `<button onclick="cancelBooking(${b.id})" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i> Cancel</button>`
                                : `<span style="color: var(--text-muted); font-size: 0.85rem;">None</span>`;

                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td>#${b.id}</td>
                                <td>${escapeHtml(b.username || 'User ('+b.user_id+')')}</td>
                                <td><strong>${escapeHtml(b.movie_title)}</strong></td>
                                <td>${escapeHtml(b.theater_name)}</td>
                                <td><span class="badge badge-info">${escapeHtml(b.seat_number)}</span></td>
                                <td>${formatTime(b.showtime)}</td>
                                <td>${formatDate(b.date)}</td>
                                <td>₹${parseFloat(b.price).toFixed(2)}</td>
                                <td><small>${formatDateTime(b.booking_time)}</small></td>
                                <td>${badge}</td>
                                <td>${actionBtn}</td>
                            `;
                            tableBody.appendChild(tr);
                        });
                    } else {
                        showToast(data.error || 'Failed to fetch bookings', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Connection error fetching bookings list', 'danger');
                });
        }

        function cancelBooking(id) {
            if (confirm('Are you sure you want to cancel booking #' + id + '? This will free up the seat.')) {
                const formData = new FormData();
                formData.append('id', id);
                
                fetch('admin_api.php?action=cancel_booking', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Booking cancelled');
                        loadBookings();
                    } else {
                        showToast(data.error || 'Failed to cancel booking', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Error cancelling ticket booking', 'danger');
                });
            }
        }

        // Helper String Formatting Functions
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            try {
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                const date = new Date(dateStr);
                return date.toLocaleDateString('en-IN', options);
            } catch (e) {
                return dateStr;
            }
        }

        function formatTime(timeStr) {
            if (!timeStr) return '';
            // e.g. "18:30:00" -> "06:30 PM"
            try {
                const parts = timeStr.split(':');
                let hours = parseInt(parts[0]);
                const minutes = parts[1];
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                return (hours < 10 ? '0' + hours : hours) + ':' + minutes + ' ' + ampm;
            } catch (e) {
                return timeStr;
            }
        }

        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '';
            try {
                const dateObj = new Date(dateTimeStr.replace(' ', 'T'));
                return dateObj.toLocaleDateString('en-IN', {month: 'short', day: 'numeric'}) + ' ' + 
                       dateObj.toLocaleTimeString('en-IN', {hour: '2-digit', minute:'2-digit'});
            } catch(e) {
                return dateTimeStr;
            }
        }

        // Page Load Initializer
        window.onload = function() {
            // Load dashboard counters
            loadDashboardStats();
            
            // Check for file selection to show preview instantly in form
            document.getElementById('movie-poster-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const preview = document.getElementById('movie-poster-preview');
                        preview.src = evt.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('movie-backdrop-input').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(evt) {
                        const preview = document.getElementById('movie-backdrop-preview');
                        preview.src = evt.target.result;
                        preview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    </script>
</body>
</html>
