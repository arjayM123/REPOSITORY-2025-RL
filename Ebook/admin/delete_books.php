<?php
// delete_books.php - Soft delete functionality
if (basename($_SERVER['PHP_SELF']) === 'delete_books.php') {
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
    header('Location: ?page=manage-books');
    exit;
}

$book_id = intval($_GET['id']);

try {
    // Get book title for confirmation message
    $stmt = $pdo->prepare("SELECT title FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $_SESSION['error'] = "Book not found.";
        header('Location: ?page=manage-books');
        exit;
    }
    
    // Implement soft delete by setting is_deleted flag
    $stmt = $pdo->prepare("UPDATE books SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$book_id]);

    $_SESSION['success'] = "Book '{$book['title']}' has been moved to trash. <a href='undo_delete.php?id=" . $book_id . "' class='text-decoration-none btn btn-link p-0'>Undo</a>";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting book: " . $e->getMessage();
}

header('Location: ?page=manage-books');
exit;
?>
<?php } ?>