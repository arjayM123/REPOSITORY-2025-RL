
<?php
// undo_delete.php - For restoring deleted books
if (basename($_SERVER['PHP_SELF']) === 'undo_delete.php') {
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

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        // Restore the book by setting is_deleted to 0 and clearing deleted_at
        $stmt = $pdo->prepare("UPDATE books SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['success'] = "Book has been restored successfully.";
        } else {
            $_SESSION['error'] = "Error restoring book.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

header("Location: ?page=manage-books");
exit;
?>
<?php } ?>