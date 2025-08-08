<?php
session_start();
require_once '../includes/db.php';

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Check if book ID is provided
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
    } else {
        header("Location: book_details_template.php");
        exit();
    }
    $stmt->close();
} else {
    header("Location: books.php");
    exit();
}
include '_layout.php'
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($book['title']); ?> - E-Book Viewer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        .cover-placeholder {
            height: 400px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 100px;
            color: #adb5bd;
            border-radius: 5px;
        }

        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body >

<div class="container py-5">
    <div class="row bg-white shadow rounded overflow-hidden">
        <!-- Book Cover -->
        <div class="col-md-4 p-4">
            <?php if (!empty($book['cover_image'])): ?>
                <img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>"
                     alt="<?php echo htmlspecialchars($book['title']); ?> Cover"
                     class="img-fluid rounded">
            <?php else: ?>
                <div class="cover-placeholder">
                    <i class="fas fa-book"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Book Details -->
        <div class="col-md-8 p-4">
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <p class="text-muted">Authors: <?php echo htmlspecialchars($book['author']); ?></p>

            <ul class="list-group list-group-flush mb-4">
                <?php if (!empty($book['publisher'])): ?>
                    <li class="list-group-item"><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?></li>
                <?php endif; ?>
                <?php if (!empty($book['date_of_publication'])): ?>
                    <li class="list-group-item"><strong>Published:</strong> <?php echo htmlspecialchars($book['date_of_publication']); ?></li>
                <?php endif; ?>
                <?php if (!empty($book['edition'])): ?>
                    <li class="list-group-item"><strong>Edition:</strong> <?php echo htmlspecialchars($book['edition']); ?></li>
                <?php endif; ?>
                <?php if (!empty($book['isbn_issn'])): ?>
                    <li class="list-group-item"><strong>ISBN/ISSN:</strong> <?php echo htmlspecialchars($book['isbn_issn']); ?></li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($book['description'])): ?>
                <div class="mb-3">
                    <h5>Abstract:</h5>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Library</a>

                <!-- Read Book Button - now points to the separate PDF viewer page -->
                <?php if (!empty($book['file_path']) && file_exists("../uploads/" . $book['file_path'])): ?>
                    <a href="view_pdf.php?id=<?php echo $book_id; ?>" class="btn btn-success"><i class="fas fa-book-reader"></i> Read Book</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>