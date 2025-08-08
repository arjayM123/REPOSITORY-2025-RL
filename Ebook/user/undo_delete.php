<?php
require_once '../includes/functions.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    require 'db_connect.php';

    $stmt = $conn->prepare("UPDATE books SET is_deleted = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: books.php?message=Book restored successfully");
    } else {
        echo "Error restoring book.";
    }
}