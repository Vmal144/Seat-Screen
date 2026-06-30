<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Check admin session authorization
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper function to upload images
function upload_image($file_field) {
    if (isset($_FILES[$file_field]) && $_FILES[$file_field]['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES[$file_field]['tmp_name'];
        $file_name = basename($_FILES[$file_field]['name']);
        // Sanitize filename
        $file_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $file_name);
        
        // Ensure images directory exists
        if (!is_dir('images')) {
            mkdir('images', 0777, true);
        }
        
        $target = "images/" . $file_name;
        if (move_uploaded_file($file_tmp, $target)) {
            return $file_name;
        }
    }
    return null;
}

try {
    switch ($action) {
        case 'get_stats':
            // Total movies
            $movies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
            // Total theaters
            $theaters = $pdo->query("SELECT COUNT(*) FROM theaters")->fetchColumn();
            // Total users
            $users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            // Total active bookings
            $bookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
            // Total revenue
            $revenue = $pdo->query("SELECT SUM(price) FROM bookings WHERE status = 'confirmed'")->fetchColumn() ?: 0;
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'movies' => (int)$movies,
                    'theaters' => (int)$theaters,
                    'users' => (int)$users,
                    'bookings' => (int)$bookings,
                    'revenue' => number_format((float)$revenue, 2)
                ]
            ]);
            break;

        case 'get_movies':
            $stmt = $pdo->query("SELECT m.*, md.year, md.plot, md.backdrop, md.trailer FROM movies m LEFT JOIN movie_details md ON m.id = md.id ORDER BY m.id DESC");
            $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'movies' => $movies]);
            break;

        case 'get_movie':
            $id = (int)$_GET['id'];
            $stmt = $pdo->prepare("SELECT m.*, md.year, md.plot, md.backdrop, md.trailer FROM movies m LEFT JOIN movie_details md ON m.id = md.id WHERE m.id = ?");
            $stmt->execute([$id]);
            $movie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($movie) {
                // Get cast members
                $castStmt = $pdo->prepare("SELECT id, name, movie_char, image FROM movie_cast WHERE movie_id = ?");
                $castStmt->execute([$id]);
                $movie['cast'] = $castStmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'movie' => $movie]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Movie not found']);
            }
            break;

        case 'save_movie':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $title = trim($_POST['title']);
            $genre = trim($_POST['genre']);
            $duration = (int)$_POST['duration'];
            $rating = (float)$_POST['rating'];
            $status = trim($_POST['status']);
            $year = trim($_POST['year']);
            $plot = trim($_POST['plot']);
            $trailer = trim($_POST['trailer']);
            
            // Check for file uploads
            $poster = upload_image('poster');
            $backdrop = upload_image('backdrop');
            
            // Cast list decoded
            $cast = isset($_POST['cast']) ? json_decode($_POST['cast'], true) : [];

            $pdo->beginTransaction();

            if ($id > 0) {
                // Edit movie
                // If new images were not uploaded, retain current ones
                if ($poster === null) {
                    $stmt = $pdo->prepare("SELECT poster FROM movies WHERE id = ?");
                    $stmt->execute([$id]);
                    $poster = $stmt->fetchColumn();
                }
                if ($backdrop === null) {
                    $stmt = $pdo->prepare("SELECT backdrop FROM movie_details WHERE id = ?");
                    $stmt->execute([$id]);
                    $backdrop = $stmt->fetchColumn();
                }

                // Update movies table
                $stmt = $pdo->prepare("UPDATE movies SET title = ?, genre = ?, duration = ?, rating = ?, poster = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $genre, $duration, $rating, $poster, $status, $id]);

                // Update movie_details table (or insert if it doesn't exist)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM movie_details WHERE id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    $stmt = $pdo->prepare("UPDATE movie_details SET year = ?, plot = ?, backdrop = ?, trailer = ? WHERE id = ?");
                    $stmt->execute([$year, $plot, $backdrop, $trailer, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO movie_details (id, year, plot, backdrop, trailer) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $year, $plot, $backdrop, $trailer]);
                }
                
                $movieId = $id;
            } else {
                // Add new movie
                $poster = $poster ?? 'default_poster.jpg';
                $backdrop = $backdrop ?? 'default_backdrop.jpg';

                // Insert into movies table
                $stmt = $pdo->prepare("INSERT INTO movies (title, genre, duration, rating, poster, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $genre, $duration, $rating, $poster, $status]);
                $movieId = $pdo->lastInsertId();

                // Insert into movie_details table
                $stmt = $pdo->prepare("INSERT INTO movie_details (id, year, plot, backdrop, trailer) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$movieId, $year, $plot, $backdrop, $trailer]);
            }

            // Sync Cast Members: Clear existing and re-add
            $stmt = $pdo->prepare("DELETE FROM movie_cast WHERE movie_id = ?");
            $stmt->execute([$movieId]);

            foreach ($cast as $actor) {
                $actorName = trim($actor['name'] ?? '');
                $actorChar = trim($actor['movie_char'] ?? '');
                $actorImage = trim($actor['image'] ?? 'default_cast.jpg');
                
                if (!empty($actorName)) {
                    $stmt = $pdo->prepare("INSERT INTO movie_cast (movie_id, name, movie_char, image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$movieId, $actorName, $actorChar, $actorImage]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Movie saved successfully']);
            break;

        case 'delete_movie':
            $id = (int)$_POST['id'];
            
            $pdo->beginTransaction();
            
            // Delete bookings associated with showtimes of this movie
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE movie_id = ?");
            $stmt->execute([$id]);
            
            // Delete showtimes
            $stmt = $pdo->prepare("DELETE FROM showtimes WHERE movie_id = ?");
            $stmt->execute([$id]);
            
            // Delete reviews
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE movie_id = ?");
            $stmt->execute([$id]);
            
            // Delete movie cast
            $stmt = $pdo->prepare("DELETE FROM movie_cast WHERE movie_id = ?");
            $stmt->execute([$id]);
            
            // Delete movie details
            $stmt = $pdo->prepare("DELETE FROM movie_details WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete movie itself
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Movie deleted successfully']);
            break;

        case 'get_theaters':
            $stmt = $pdo->query("SELECT * FROM theaters ORDER BY id DESC");
            $theaters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'theaters' => $theaters]);
            break;

        case 'save_theater':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $name = trim($_POST['name']);
            $amenities = trim($_POST['amenities']);
            
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE theaters SET name = ?, amenities = ? WHERE id = ?");
                $stmt->execute([$name, $amenities, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO theaters (name, amenities) VALUES (?, ?)");
                $stmt->execute([$name, $amenities]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Theater saved successfully']);
            break;

        case 'delete_theater':
            $id = (int)$_POST['id'];
            
            $pdo->beginTransaction();
            // Delete bookings associated
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE theater_id = ?");
            $stmt->execute([$id]);
            // Delete showtimes
            $stmt = $pdo->prepare("DELETE FROM showtimes WHERE theater_id = ?");
            $stmt->execute([$id]);
            // Delete theater
            $stmt = $pdo->prepare("DELETE FROM theaters WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Theater deleted successfully']);
            break;

        case 'get_showtimes':
            $stmt = $pdo->query("
                SELECT s.*, m.title AS movie_title, t.name AS theater_name 
                FROM showtimes s 
                JOIN movies m ON s.movie_id = m.id 
                JOIN theaters t ON s.theater_id = t.id 
                ORDER BY s.date DESC, s.time DESC
            ");
            $showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'showtimes' => $showtimes]);
            break;

        case 'save_showtime':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $movie_id = (int)$_POST['movie_id'];
            $theater_id = (int)$_POST['theater_id'];
            $date = trim($_POST['date']);
            $time = trim($_POST['time']);
            
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE showtimes SET movie_id = ?, theater_id = ?, date = ?, time = ? WHERE id = ?");
                $stmt->execute([$movie_id, $theater_id, $date, $time, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO showtimes (movie_id, theater_id, date, time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$movie_id, $theater_id, $date, $time]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Showtime saved successfully']);
            break;

        case 'delete_showtime':
            $id = (int)$_POST['id'];
            // Warning: Deleting showtimes does not cascade automatically if not structured, so we clear bookings or handle it.
            $pdo->beginTransaction();
            // Get showtime details to delete associated bookings
            $stmt = $pdo->prepare("SELECT movie_id, theater_id, date, time FROM showtimes WHERE id = ?");
            $stmt->execute([$id]);
            $st = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($st) {
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE movie_id = ? AND theater_id = ? AND date = ? AND showtime = ?");
                $stmt->execute([$st['movie_id'], $st['theater_id'], $st['date'], $st['time']]);
            }
            
            $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Showtime deleted successfully']);
            break;

        case 'get_users':
            $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'save_user':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $firebase_uid = trim($_POST['firebase_uid']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            
            if (empty($firebase_uid)) {
                // If mock user created by admin, auto-generate a unique UID
                $firebase_uid = 'admin_mock_' . uniqid();
            }

            if ($id > 0) {
                // Check unique constraints except self
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $id]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'error' => 'Username or Email already exists']);
                    exit();
                }
                
                $stmt = $pdo->prepare("UPDATE users SET firebase_uid = ?, username = ?, email = ? WHERE id = ?");
                $stmt->execute([$firebase_uid, $username, $email, $id]);
            } else {
                // Check unique constraints
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'error' => 'Username or Email already exists']);
                    exit();
                }
                
                $stmt = $pdo->prepare("INSERT INTO users (firebase_uid, username, email) VALUES (?, ?, ?)");
                $stmt->execute([$firebase_uid, $username, $email]);
            }
            
            echo json_encode(['success' => true, 'message' => 'User saved successfully']);
            break;

        case 'delete_user':
            $id = (int)$_POST['id'];
            
            $pdo->beginTransaction();
            // Delete bookings associated
            $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
            $stmt->execute([$id]);
            // Delete reviews associated
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE user_name = (SELECT username FROM users WHERE id = ?)");
            $stmt->execute([$id]);
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        case 'get_bookings':
            $stmt = $pdo->query("
                SELECT b.*, u.username, m.title AS movie_title, t.name AS theater_name 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                JOIN movies m ON b.movie_id = m.id 
                JOIN theaters t ON b.theater_id = t.id 
                ORDER BY b.booking_time DESC
            ");
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'bookings' => $bookings]);
            break;

        case 'cancel_booking':
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
