<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    $_SESSION['error'] = "Unauthorized access";
    header('Location: index.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No book ID provided.";
    header('Location: index.php');
    exit;
}

$book_id = intval($_GET['id']);

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if the book exists and get file paths
    $check_stmt = $conn->prepare("SELECT id, title, cover_image, file_path FROM books WHERE id = ?");
    $check_stmt->bind_param("i", $book_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Book not found.");
    }

    $book = $result->fetch_assoc();
    $book_title = $book['title'];
    $cover_image = $book['cover_image'];
    $file_path = $book['file_path'];

    // Delete the book from database
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);

    if (!$stmt->execute()) {
        throw new Exception("Error deleting book from database");
    }

    // Delete files only if database deletion was successful
    $fileErrors = [];

    // Delete cover image if exists
    if (!empty($cover_image)) {
        $cover_path = "../uploads/covers/" . $cover_image;
        if (file_exists($cover_path) && !unlink($cover_path)) {
            $fileErrors[] = "Could not delete cover image";
        }
    }

    // Delete PDF file if exists
    if (!empty($file_path)) {
        $pdf_path = "../uploads/books/" . $file_path;
        if (file_exists($pdf_path) && !unlink($pdf_path)) {
            $fileErrors[] = "Could not delete PDF file";
        }
    }

    // Commit transaction if everything is successful
    $conn->commit();

    if (empty($fileErrors)) {
        $_SESSION['success'] = "Book \"$book_title\" has been deleted successfully.";
    } else {
        $_SESSION['warning'] = "Book deleted but with issues: " . implode(", ", $fileErrors);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Error: " . $e->getMessage();
} finally {
    // Close statements
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
}

// Redirect back to books page
header('Location: index.php');
exit;