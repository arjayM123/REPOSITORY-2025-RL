<?php
session_start();
require_once '../includes/db.php';

// Secret token for admin access
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

// Set the page title
$pageTitle = 'More Books - E-Book Library';

// Start output buffering to capture the content
ob_start();


$error = '';
$success = '';

function addBook(
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
    $description,
    $cover_image,
    $file_path
) {
    global $conn;
    try {
        $sql = "INSERT INTO books (
            title, author, place_of_publication, publisher, 
            date_of_publication, edition, isbn_issn, type_of_material, department,
            classification_number, call_number, accession_number, copies,
            description, cover_image, file_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssssssssss",
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
            $description,
            $cover_image,
            $file_path
        );

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        return true;
    } catch (Exception $e) {
        error_log("Error in addBook: " . $e->getMessage());
        throw new Exception("Failed to add book: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $place_of_publication = $_POST['place_of_publication'] ?? '';
    $publisher = $_POST['publisher'] ?? '';
    $date_of_publication = $_POST['date_of_publication'] ?? '';
    $edition = $_POST['edition'] ?? '';
    $isbn_issn = $_POST['isbn_issn'] ?? '';
    $type_of_material = $_POST['type_of_material'] ?? '';
    $department = $_POST['department'] ?? '';
    $classification_number = $_POST['classification_number'] ?? '';
    $call_number = $_POST['call_number'] ?? '';
    $accession_number = $_POST['accession_number'] ?? '';
    $copies = $_POST['copies'] ?? '1';
    $description = $_POST['description'] ?? '';

    if (empty($title) || empty($author)) {
        $error = 'Please fill in all required fields';
    } else {
        // Handle file uploads
        $cover_image = '';
        $file_path = '';

        // Create upload directories if they don't exist
        $cover_dir = "../uploads/covers/";
        $books_dir = "../uploads/books/";

        if (!file_exists($cover_dir)) {
            mkdir($cover_dir, 0777, true);
        }
        if (!file_exists($books_dir)) {
            mkdir($books_dir, 0777, true);
        }

        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $cover_image = uploadFile($_FILES['cover_image'], 'covers');
        }

        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === 0) {
            $file_path = uploadFile($_FILES['book_file'], 'books');
        }

        try {
            addBook(
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
                $description,
                $cover_image,
                $file_path
            );
            $success = 'Book added successfully!';
            // Clear form after successful submission
            $_POST = array();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

function uploadFile($file, $destination)
{
    try {
        $target_dir = "../uploads/" . $destination . "/";
        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $file_name = uniqid() . "." . $file_extension;
        $target_file = $target_dir . $file_name;

        if (!move_uploaded_file($file["tmp_name"], $target_file)) {
            throw new Exception("Failed to move uploaded file");
        }

        return $file_name;
    } catch (Exception $e) {
        error_log("Error in uploadFile: " . $e->getMessage());
        throw new Exception("File upload failed: " . $e->getMessage());
    }
}
include '_layout.php';
?>

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
        color: #2c3e50;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }

    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }

    .preview-image {
        max-width: 200px;
        max-height: 200px;
        display: none;
        margin-top: 10px;
    }

    .btn-submit {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        grid-column: span 2;
    }

    .btn-submit:hover {
        background-color: #0056b3;
    }

    .error-message {
        color: #e74c3c;
        font-weight: bold;
    }

    .success-message {
        color: #2ecc71;
        font-weight: bold;
    }
</style>

<div class="admin-container">
    <h2 style="color: #2c3e50;">Add New Book</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form class="admin-form" method="POST" enctype="multipart/form-data" id="addBookForm">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="author">Author</label>
            <input type="text" id="author" name="author" required>
        </div>

        <div class="form-group">
            <label for="place_of_publication">Place of Publication</label>
            <input type="text" id="place_of_publication" name="place_of_publication">
        </div>

        <div class="form-group">
            <label for="publisher">Publisher</label>
            <input type="text" id="publisher" name="publisher">
        </div>

        <div class="form-group">
            <label for="date_of_publication">Date of Publication</label>
            <input type="number" 
       id="date_of_publication" 
       name="date_of_publication" 
       placeholder="Enter year" 
       min="1900" 
       max="<?php echo date('Y'); ?>" 
       step="1"
       pattern="\d{4}"
       title="Please enter a valid year (1900-<?php echo date('Y'); ?>)"
       required>
        </div>

        <div class="form-group">
            <label for="edition">Edition</label>
            <input type="text" id="edition" name="edition" placeholder="e.g., 1st, 2nd, 3rd">
        </div>

        <div class="form-group">
            <label for="isbn_issn">ISBN/ISSN</label>
            <input type="number" id="isbn_issn" name="isbn_issn">
        </div>

        <div class="form-group">
            <label for="type_of_material">Type of Material</label>
            <select id="type_of_material" name="type_of_material">
                <option value="">Select Type</option>
                <option value="Book">Book</option>
                <option value="Journal">Journal</option>
                <option value="Magazine">Magazine</option>
                <option value="Newspaper">Newspaper</option>
                <option value="Reference">Reference</option>
                <option value="Thesis">Thesis</option>
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
            <input type="text" id="classification_number" name="classification_number">
        </div>

        <div class="form-group">
            <label for="call_number">Call Number</label>
            <input type="text" id="call_number" name="call_number">
        </div>

        <div class="form-group">
            <label for="accession_number">Accession Number</label>
            <input type="text" id="accession_number" name="accession_number">
        </div>

        <div class="form-group">
            <label for="copies">Copies</label>
            <input type="number" id="copies" name="copies" value="1" min="1">
        </div>

        <div class="form-group full-width">
            <label for="description">Description</label>
            <textarea id="description" name="description"></textarea>
        </div>

        <div class="form-group">
            <label for="cover_image">Cover Image</label>
            <input type="file" id="cover_image" name="cover_image" accept="image/*" onchange="previewImage(this)">
            <img id="imagePreview" class="preview-image" alt="Cover preview">
        </div>

        <div class="form-group">
            <label for="book_file">Book File</label>
            <input type="file" id="book_file" name="book_file">
        </div>

        <button type="submit" class="btn-submit">Add Book</button>
    </form>
</div>

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