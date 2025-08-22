<?php

session_start();
require_once '../includes/db.php';

// Verify that this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header("Location: _layout.php?page=manage-books&view=book_request");
    exit;
}

// Verify action is set and is delete_request
if (!isset($_POST['action']) || $_POST['action'] !== 'delete_request') {
    $_SESSION['error'] = "Invalid action";
    header("Location: _layout.php?page=manage-books&view=book_request");
    exit;
}

try {
    // Add input validation and error logging
    if (!isset($_POST['request_id']) || empty($_POST['request_id'])) {
        throw new Exception('Request ID is required');
    }
    
    $requestId = filter_var($_POST['request_id'], FILTER_VALIDATE_INT);
    if ($requestId === false) {
        throw new Exception('Invalid request ID format');
    }

    // Verify the request exists before deleting
    $checkStmt = $pdo->prepare("SELECT id FROM book_requests WHERE id = ?");
    $checkStmt->execute([$requestId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Request not found');
    }

    // Perform the deletion
    $stmt = $pdo->prepare("DELETE FROM book_requests WHERE id = ?");
    if ($stmt->execute([$requestId])) {
        $_SESSION['success'] = "Book request deleted successfully.";
    } else {
        throw new Exception('Failed to delete book request');
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

// Redirect back to the layout with manage-books page
header("Location: _layout.php?page=manage-books&view=book_request");
exit;