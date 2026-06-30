<?php
// get_movies.php
require_once 'db_connect.php';

header('Content-Type: application/json');

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    $query = "SELECT id, title, duration, genre, rating, poster FROM movies WHERE 1=1";
    $params = array();

    if ($filter !== 'all') {
        if (in_array($filter, ['now', 'soon'])) {
            $query .= " AND status = :filter";
        } else {
            $query .= " AND genre LIKE :filter";
        }
        $params[':filter'] = $filter === 'now' || $filter === 'soon' ? $filter : "%$filter%";
    }

    if (!empty($search)) {
        $query .= " AND title LIKE :search";
        $params[':search'] = "%$search%";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($movies);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>