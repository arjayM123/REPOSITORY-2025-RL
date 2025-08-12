<?php
// book_details.php - Get book details via AJAX
if (basename($_SERVER['PHP_SELF']) === 'book_details.php') {
?>
<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No book ID provided']);
    exit;
}

$book_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
        exit;
    }
    
    // Set proper cover image path
    if (!empty($book['cover_image'])) {
        $coverPath = "../uploads/covers/" . $book['cover_image'];
        if (file_exists($coverPath)) {
            $book['cover_image_url'] = $coverPath;
        } else {
            $book['cover_image_url'] = '../assets/images/genericBookCover.jpg';
        }
    } else {
        $book['cover_image_url'] = '../assets/images/genericBookCover.jpg';
    }
    
    header('Content-Type: application/json');
    echo json_encode($book);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
<?php } ?>