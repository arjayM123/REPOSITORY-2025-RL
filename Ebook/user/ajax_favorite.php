<?php
// Create a new file: ajax_favorite.php
session_start();
require_once '../includes/db.php';
require_once '../includes/tracking_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    if ($book_id > 0) {
        $result = toggleBookFavorite($pdo, $book_id);
        
        // Get updated favorite count
        $countQuery = "SELECT favorite_count FROM books WHERE id = ?";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([$book_id]);
        $book = $countStmt->fetch();
        
        $result['favorite_count'] = $book['favorite_count'] ?? 0;
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>