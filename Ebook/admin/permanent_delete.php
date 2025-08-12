<?php
// permanent_delete.php - Permanent delete functionality
if (basename($_SERVER['PHP_SELF']) === 'permanent_delete.php') {
?>
<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No book ID provided.";
    header('Location: ?page=manage-books&view=deleted');
    exit;
}

$book_id = intval($_GET['id']);

try {
    // Get book info before deleting
    $stmt = $pdo->prepare("SELECT title, cover_image FROM books WHERE id = ? AND is_deleted = 1");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $_SESSION['error'] = "Book not found in trash.";
        header('Location: ?page=manage-books&view=deleted');
        exit;
    }
    
    // Delete associated cover image if exists
    if (!empty($book['cover_image'])) {
        $coverPath = "../uploads/covers/" . $book['cover_image'];
        if (file_exists($coverPath)) {
            unlink($coverPath);
        }
    }
    
    // Permanently delete the book from database
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ? AND is_deleted = 1");
    $stmt->execute([$book_id]);

    $_SESSION['success'] = "Book '{$book['title']}' has been permanently deleted.";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error permanently deleting book: " . $e->getMessage();
}

header('Location: ?page=manage-books&view=deleted');
exit;
?>
<?php } ?>