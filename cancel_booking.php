<?php
session_start();
require_once 'db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

try {
    if (!isset($_POST['booking_id'])) {
        throw new Exception('Booking ID is required');
    }
    
    $bookingId = (int)$_POST['booking_id'];      // Check if booking exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT b.*, m.title as movie_title, u.email, t.name as theater_name
        FROM bookings b
        JOIN movies m ON b.movie_id = m.id
        JOIN users u ON b.user_id = u.id
        JOIN theaters t ON b.theater_id = t.id
        WHERE b.id = ? AND b.user_id = ? AND b.status = 'confirmed'");    
    $stmt->execute([$bookingId, $_SESSION['user_id']]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        throw new Exception('Invalid booking');
    }
    
    // Check if cancellation is allowed (1 hour before show time)
    try {
        $showtime = new DateTime($booking['date'] . ' ' . $booking['showtime']);
        $now = new DateTime();
        $interval = $now->diff($showtime);
        $minutesUntilShow = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
        
        if ($interval->invert || $minutesUntilShow < 60) {
            throw new Exception('Cancellation is only allowed up to 1 hour before show time');
        }
    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }    // Delete the booking
    $stmt = $pdo->prepare("
        DELETE FROM bookings 
        WHERE id = ?
    ");
    $stmt->execute([$bookingId]);

    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>