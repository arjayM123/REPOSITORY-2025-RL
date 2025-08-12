<?php
// lock_book.php - Lock book functionality
if (basename($_SERVER['PHP_SELF']) === 'lock_book.php') {
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
    $stmt = $pdo->prepare("SELECT title FROM books WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $_SESSION['error'] = "Book not found.";
        header('Location: ?page=manage-books');
        exit;
    }
    
    // Lock the book
    $stmt = $pdo->prepare("UPDATE books SET status = 'locked' WHERE id = ?");
    $stmt->execute([$book_id]);

    $_SESSION['success'] = "Book '{$book['title']}' has been locked successfully.";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error locking book: " . $e->getMessage();
}

header('Location: ?page=manage-books');
exit;
?>
<?php } ?>