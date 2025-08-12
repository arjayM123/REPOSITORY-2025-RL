<?php
// bulk_actions.php - For bulk operations on books
if (basename($_SERVER['PHP_SELF']) === 'bulk_actions.php') {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['bulk_action'] ?? '';
    $selected_books = $_POST['selected_books'] ?? [];
    
    if (empty($selected_books) || !is_array($selected_books)) {
        $_SESSION['error'] = "No books selected.";
        header('Location: ?page=manage-books');
        exit;
    }
    
    $book_ids = array_map('intval', $selected_books);
    $placeholders = str_repeat('?,', count($book_ids) - 1) . '?';
    
    try {
        switch ($action) {
            case 'bulk_delete':
                $stmt = $pdo->prepare("UPDATE books SET is_deleted = 1, deleted_at = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($book_ids);
                $_SESSION['success'] = count($book_ids) . " book(s) moved to trash successfully.";
                break;
                
            case 'bulk_restore':
                $stmt = $pdo->prepare("UPDATE books SET is_deleted = 0, deleted_at = NULL WHERE id IN ($placeholders)");
                $stmt->execute($book_ids);
                $_SESSION['success'] = count($book_ids) . " book(s) restored successfully.";
                break;
                
            case 'bulk_permanent_delete':
                // Get cover images to delete
                $stmt = $pdo->prepare("SELECT cover_image FROM books WHERE id IN ($placeholders) AND is_deleted = 1");
                $stmt->execute($book_ids);
                $covers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete cover images
                foreach ($covers as $cover) {
                    if (!empty($cover)) {
                        $coverPath = "../uploads/covers/" . $cover;
                        if (file_exists($coverPath)) {
                            unlink($coverPath);
                        }
                    }
                }
                
                // Permanently delete books
                $stmt = $pdo->prepare("DELETE FROM books WHERE id IN ($placeholders) AND is_deleted = 1");
                $stmt->execute($book_ids);
                $_SESSION['success'] = count($book_ids) . " book(s) permanently deleted.";
                break;
                
            case 'bulk_lock':
                $stmt = $pdo->prepare("UPDATE books SET status = 'locked' WHERE id IN ($placeholders) AND is_deleted = 0");
                $stmt->execute($book_ids);
                $_SESSION['success'] = count($book_ids) . " book(s) locked successfully.";
                break;
                
            case 'bulk_unlock':
                $stmt = $pdo->prepare("UPDATE books SET status = 'active' WHERE id IN ($placeholders) AND is_deleted = 0");
                $stmt->execute($book_ids);
                $_SESSION['success'] = count($book_ids) . " book(s) unlocked successfully.";
                break;
                
            default:
                $_SESSION['error'] = "Invalid bulk action.";
                break;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error performing bulk action: " . $e->getMessage();
    }
}

$view = $_POST['current_view'] ?? 'active';
header("Location: ?page=manage-books&view=$view");
exit;
?>
<?php } ?>