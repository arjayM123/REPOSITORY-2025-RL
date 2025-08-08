<?php
session_start();
require_once '../includes/db.php';

$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No book ID provided.";
    header("Location: manage_books.php");
    exit;
}

$id = intval($_GET['id']);

// Process form submission if POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $place_of_publication = trim($_POST['place_of_publication'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $date_of_publication = trim($_POST['date_of_publication'] ?? '');
    $edition = trim($_POST['edition'] ?? '');
    $isbn_issn = trim($_POST['isbn_issn'] ?? '');
    $type_of_material = trim($_POST['type_of_material'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $classification_number = trim($_POST['classification_number'] ?? '');
    $call_number = trim($_POST['call_number'] ?? '');
    $accession_number = trim($_POST['accession_number'] ?? '');
    $copies = intval($_POST['copies'] ?? 1);
    $description = trim($_POST['description'] ?? '');

    // Validation
    $errors = [];
    if (empty($title)) $errors[] = "Title is required.";
    if (empty($author)) $errors[] = "Author is required.";

    // Handle file uploads
    $cover_image = null;
    $file_path = null;

    // Create upload directories if they don't exist
    $cover_dir = "../uploads/covers/";
    $books_dir = "../uploads/books/";
    if (!file_exists($cover_dir)) mkdir($cover_dir, 0777, true);
    if (!file_exists($books_dir)) mkdir($books_dir, 0777, true);

    // Handle cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        try {
            $cover_image = uploadFile($_FILES['cover_image'], 'covers');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    // Handle book file upload
    if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] == 0) {
        try {
            $file_path = uploadFile($_FILES['book_file'], 'books');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    // If no errors, update the book
    if (empty($errors)) {
        try {
            // Prepare update query
            $query = "UPDATE books SET 
                title = ?, author = ?, place_of_publication = ?,
                publisher = ?, date_of_publication = ?, edition = ?,
                isbn_issn = ?, type_of_material = ?, 
                department = ?,
                classification_number = ?, call_number = ?,
                accession_number = ?, copies = ?, description = ?";

            $params = [
                $title,
                $author,
                $place_of_publication,
                $publisher,
                $date_of_publication,
                $edition,
                $isbn_issn,
                $type_of_material,
                $department,
                $classification_number,
                $call_number,
                $accession_number,
                $copies,
                $description
            ];

            // Add cover image to update query if a new one was uploaded
            if ($cover_image !== null) {
                $query .= ", cover_image = ?";
                $params[] = $cover_image;
            }

            // Add book file to update query if a new one was uploaded
            if ($file_path !== null) {
                $query .= ", file_path = ?";
                $params[] = $file_path;
            }

            $query .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $conn->prepare($query);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            // Bind parameters dynamically
            $types = str_repeat("s", count($params) - 1) . "i";
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

            $_SESSION['success'] = "Book updated successfully.";
            header("Location: index.php");
            exit;
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

// Fetch book data for editing
try {
    $query = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Book not found.";
        header("Location: index.php");
        exit;
    }

    $book = $result->fetch_assoc();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// File upload function
function uploadFile($file, $destination)
{
    $target_dir = "../uploads/" . $destination . "/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $file_name = uniqid() . "." . $file_extension;
    $target_file = $target_dir . $file_name;

    if (!move_uploaded_file($file["tmp_name"], $target_file)) {
        throw new Exception("Failed to move uploaded file");
    }
    return $file_name;
}

include "_layout.php";
?>

<div class="admin-container">
    <h2>Edit Book</h2>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="error-message">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="admin-form" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="author">Author</label>
            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book['author']); ?>"
                required>
        </div>

        <div class="form-group">
            <label for="place_of_publication">Place of Publication</label>
            <input type="text" id="place_of_publication" name="place_of_publication"
                value="<?php echo htmlspecialchars($book['place_of_publication']); ?>">
        </div>

        <div class="form-group">
            <label for="publisher">Publisher</label>
            <input type="text" id="publisher" name="publisher"
                value="<?php echo htmlspecialchars($book['publisher']); ?>">
        </div>

        <div class="form-group">
            <label for="date_of_publication">Date of Publication</label>
            <input type="text" id="date_of_publication" name="date_of_publication"
                value="<?php echo htmlspecialchars($book['date_of_publication']); ?>" placeholder="year only">
        </div>

        <div class="form-group">
            <label for="edition">Edition</label>
            <input type="text" id="edition" name="edition" value="<?php echo htmlspecialchars($book['edition']); ?>"
                placeholder="e.g., 1st, 2nd, 3rd">
        </div>

        <div class="form-group">
            <label for="isbn_issn">ISBN/ISSN</label>
            <input type="text" id="isbn_issn" name="isbn_issn"
                value="<?php echo htmlspecialchars($book['isbn_issn']); ?>">
        </div>

        <div class="form-group">
            <label for="type_of_material">Type of Material</label>
            <select id="type_of_material" name="type_of_material">
                <option value="">Select Type</option>
                <option value="Book" <?php echo ($book['type_of_material'] == 'Book') ? 'selected' : ''; ?>>Book
                </option>
                <option value="Journal" <?php echo ($book['type_of_material'] == 'Journal') ? 'selected' : ''; ?>>
                    Journal</option>
                <option value="Magazine" <?php echo ($book['type_of_material'] == 'Magazine') ? 'selected' : ''; ?>>
                    Magazine</option>
                <option value="Newspaper" <?php echo ($book['type_of_material'] == 'Newspaper') ? 'selected' : ''; ?>>
                    Newspaper</option>
                <option value="Reference" <?php echo ($book['type_of_material'] == 'Reference') ? 'selected' : ''; ?>>
                    Reference</option>
                <option value="Thesis" <?php echo ($book['type_of_material'] == 'Thesis') ? 'selected' : ''; ?>>Thesis
                </option>
                <option value="Other" <?php echo ($book['type_of_material'] == 'Other') ? 'selected' : ''; ?>>Other
                </option>
            </select>
        </div>

        <div class="form-group">
            <label for="department">Department</label>
            <select class="type_of_material" name="department" required>
                <option value="">Select Department</option>
                <option value="BSIT">BSIT</option>
                <option value="BSED">BSED</option>
                <option value="BSAB">BSAB</option>
                <option value="BSCRIM">BSCRIM</option>
                <option value="BSA">BSA</option>
                <option value="FISHERS">FISHERS</option>
            </select>
        </div>

        <div class="form-group">
            <label for="classification_number">Classification Number</label>
            <input type="text" id="classification_number" name="classification_number"
                value="<?php echo htmlspecialchars($book['classification_number']); ?>">
        </div>

        <div class="form-group">
            <label for="call_number">Call Number</label>
            <input type="text" id="call_number" name="call_number"
                value="<?php echo htmlspecialchars($book['call_number']); ?>">
        </div>

        <div class="form-group">
            <label for="accession_number">Accession Number</label>
            <input type="number" id="accession_number" name="accession_number"
                value="<?php echo htmlspecialchars($book['accession_number']); ?>">
        </div>

        <div class="form-group">
            <label for="copies">Copies</label>
            <input type="number" id="copies" name="copies" value="<?php echo htmlspecialchars($book['copies']); ?>"
                min="1">
        </div>

        <div class="form-group full-width">
            <label for="description">Description</label>
            <textarea id="description"
                name="description"><?php echo htmlspecialchars($book['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="cover_image">Cover Image</label>
            <?php if (!empty($book['cover_image'])): ?>
                <div class="current-cover">
                    <img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Current Cover"
                        class="preview-image">
                </div>
            <?php endif; ?>
            <input type="file" id="cover_image" name="cover_image" accept="image/*" onchange="previewImage(this)">
            <img id="imagePreview" class="preview-image" style="display: none;">
            <small>Leave empty to keep the current image.</small>
        </div>

        <div class="form-group">
            <label for="book_file">Book File</label>
            <?php if (!empty($book['file_path'])): ?>
                <p>Current file: <?php echo htmlspecialchars($book['file_path']); ?></p>
            <?php endif; ?>
            <input type="file" id="book_file" name="book_file">
            <small>Leave empty to keep the current file.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Update Book</button>
            <a href="index.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
</div>

<style>
    .admin-container {
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .admin-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .full-width {
        grid-column: span 2;
    }

    .form-group label {
        font-weight: bold;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .form-group textarea {
        min-height: 150px;
    }

    .preview-image {
        max-width: 200px;
        max-height: 200px;
        margin-top: 10px;
    }

    .form-actions {
        grid-column: span 2;
        display: flex;
        justify-content: space-between;
    }

    .btn-submit,
    .btn-cancel {
        padding: 10px 20px;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn-submit {
        background-color: #28a745;
    }

    .btn-cancel {
        background-color: #6c757d;
    }

    .error-message {
        color: #e74c3c;
        margin-bottom: 20px;
    }
</style>

<script>
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>