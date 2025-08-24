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
    // Get book details for file deletion
    $stmt = $pdo->prepare("SELECT title, file_path, cover_image FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $_SESSION['error'] = "Book not found.";
        header('Location: ?page=manage-books');
        exit;
    }
    
    // Debug: Log the raw file_path from database
    error_log("Raw file_path from database: " . ($book['file_path'] ?? 'NULL'));
    error_log("Raw cover_image from database: " . ($book['cover_image'] ?? 'NULL'));
    
    // Define upload directories (matching the structure in add_book.php)
    $uploadsDir = __DIR__ . "/../uploads";
    $booksDir = $uploadsDir . "/books";
    $coversDir = $uploadsDir . "/covers";
    
    // Log directory paths and verify they exist
    error_log("Current script directory: " . __DIR__);
    error_log("Uploads directory: " . $uploadsDir);
    error_log("Books directory: " . $booksDir);
    error_log("Covers directory: " . $coversDir);
    error_log("Uploads directory exists: " . (is_dir($uploadsDir) ? 'YES' : 'NO'));
    error_log("Books directory exists: " . (is_dir($booksDir) ? 'YES' : 'NO'));
    error_log("Covers directory exists: " . (is_dir($coversDir) ? 'YES' : 'NO'));
    
    // Check permissions
    if (is_dir($booksDir)) {
        error_log("Books directory is writable: " . (is_writable($booksDir) ? 'YES' : 'NO'));
    }
    if (is_dir($coversDir)) {
        error_log("Covers directory is writable: " . (is_writable($coversDir) ? 'YES' : 'NO'));
    }
    
    // Build file paths - based on how uploadFile() works in add_book.php
    // Files are stored as just filenames in database, actual files are in uploads/books/ and uploads/covers/
    $pdfPath = null;
    $coverPath = null;
    
    if (!empty($book['file_path'])) {
        // Since uploadFile() only stores the filename, construct the full path
        $pdfPath = $booksDir . "/" . $book['file_path'];
        error_log("Constructed PDF path: " . $pdfPath);
        error_log("PDF file exists: " . (file_exists($pdfPath) ? 'YES' : 'NO'));
        
        if (!file_exists($pdfPath)) {
            error_log("PDF file not found at expected location: " . $pdfPath);
            $pdfPath = null;
        }
    }
    
    if (!empty($book['cover_image']) && $book['cover_image'] !== 'genericBookCover.jpg') {
        // Since uploadFile() only stores the filename, construct the full path
        $coverPath = $coversDir . "/" . $book['cover_image'];
        error_log("Constructed cover path: " . $coverPath);
        error_log("Cover file exists: " . (file_exists($coverPath) ? 'YES' : 'NO'));
        
        if (!file_exists($coverPath)) {
            error_log("Cover file not found at expected location: " . $coverPath);
            $coverPath = null;
        }
    }
    
    // Log final paths
    error_log("Final PDF path for deletion: " . ($pdfPath ?: 'NULL'));
    error_log("Final cover path for deletion: " . ($coverPath ?: 'NULL'));
    
    // Implement soft delete by setting is_deleted flag
    $stmt = $pdo->prepare("UPDATE books SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$book_id]);

    $deletionMessages = [];

    // Delete physical PDF file if found
    if ($pdfPath) {
        if (is_writable(dirname($pdfPath))) {
            if (unlink($pdfPath)) {
                error_log("Successfully deleted PDF file: " . $pdfPath);
                $deletionMessages[] = "PDF file deleted";
            } else {
                error_log("Failed to delete PDF file: " . $pdfPath);
                $deletionMessages[] = "PDF file could not be deleted (check permissions)";
            }
        } else {
            error_log("Directory not writable: " . dirname($pdfPath));
            $deletionMessages[] = "PDF directory not writable";
        }
    } else {
        error_log("PDF file not found for book ID: " . $book_id);
        $deletionMessages[] = "PDF file not found";
    }

    // Delete physical cover image if found
    if ($coverPath) {
        if (is_writable(dirname($coverPath))) {
            if (unlink($coverPath)) {
                error_log("Successfully deleted cover image: " . $coverPath);
                $deletionMessages[] = "Cover image deleted";
            } else {
                error_log("Failed to delete cover image: " . $coverPath);
                $deletionMessages[] = "Cover image could not be deleted";
            }
        } else {
            error_log("Cover directory not writable: " . dirname($coverPath));
            $deletionMessages[] = "Cover directory not writable";
        }
    }

    $message = "Book '{$book['title']}' has been moved to trash.";
    if (!empty($deletionMessages)) {
        $message .= " (" . implode(", ", $deletionMessages) . ")";
    }
    $message .= " <a href='undo_delete.php?id=" . $book_id . "' class='text-decoration-none btn btn-link p-0'>Undo</a>";
    
    $_SESSION['success'] = $message;
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting book: " . $e->getMessage();
} catch (Exception $e) {
    error_log("File deletion error: " . $e->getMessage());
    $_SESSION['error'] = "Error deleting files: " . $e->getMessage();
}

header('Location: ?page=manage-books');
exit;
?>
<?php } ?>