<?php
session_start();
require_once '../includes/db.php';

// Check if book ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: books.php');
    exit;
}

$book_id = intval($_GET['id']);

// Get book details
$sql = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: books.php');
    exit;
}

$book = $result->fetch_assoc();

// Set the page title
$pageTitle = 'Reading: ' . htmlspecialchars($book['title']) . ' - E-Book Library';

// Start output buffering to capture the content
ob_start();


?>

<div class="container reader-container">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><?php echo htmlspecialchars($book['title']); ?></h2>
            <p class="text-muted mb-4">By <?php echo htmlspecialchars($book['author']); ?></p>

            <div class="reader-content">
                <?php if (!empty($book['file_path'])): ?>
                    <?php
                    $file_extension = pathinfo($book['file_path'], PATHINFO_EXTENSION);
                    $file_path = '../uploads/books/' . $book['file_path'];

                    if ($file_extension === 'pdf') {
                        // PDF viewer
                        echo '<div class="pdf-container">';
                        echo '<iframe src="' . htmlspecialchars($file_path) . '" width="100%" height="800px" style="border: none;"></iframe>';
                        echo '</div>';
                    } else if (in_array($file_extension, ['epub', 'mobi'])) {
                        // For EPUB/MOBI files, you might need a specialized reader
                        echo '<div class="alert alert-info">This file format requires a specialized reader. <a href="' . htmlspecialchars($file_path) . '" class="btn btn-primary btn-sm ms-3">Download E-book</a></div>';
                    } else {
                        // For other formats, offer download
                        echo '<div class="alert alert-info">This file format cannot be viewed directly in the browser. <a href="' . htmlspecialchars($file_path) . '" class="btn btn-primary btn-sm ms-3">Download E-book</a></div>';
                    }
                    ?>
                <?php else: ?>
                    <div class="alert alert-warning">No file available for this book.</div>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <a href="view_books.php?id=<?php echo $book_id; ?>" class="btn btn-secondary">Back to Details</a>
                <a href="books.php" class="btn btn-outline-primary ms-2">Browse More Books</a>
            </div>
        </div>
    </div>
</div>

<?php
// Get the content from the output buffer
$content = ob_get_clean();

// Include the layout
include '_layout.php';
?>