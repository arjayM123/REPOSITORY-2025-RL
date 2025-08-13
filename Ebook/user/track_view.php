<?php
// Create a new file: track_view.php
require_once '../includes/db.php';
require_once '../includes/tracking_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    if ($book_id > 0) {
        $counted = trackBookView($pdo, $book_id);
        
        // Get updated view count
        $countQuery = "SELECT view_count FROM books WHERE id = ?";
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute([$book_id]);
        $book = $countStmt->fetch();
        
        echo json_encode([
            'success' => true,
            'counted' => $counted,
            'view_count' => $book['view_count'] ?? 0
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid book ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>