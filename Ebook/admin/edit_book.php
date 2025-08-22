<?php
// No need for session_start() and db.php as they're included in _layout.php

$error = '';
$success = '';
$book = null;

// Get book ID from URL parameter
$book_id = $_GET['id'] ?? '';

if (empty($book_id)) {
    header("Location: ?page=manage-books");
    exit();
}

// Fetch book data
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$book) {
        $_SESSION['error'] = "Book not found.";
        header("Location: ?page=manage-books");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching book data: " . $e->getMessage();
    header("Location: ?page=manage-books");
    exit();
}

function updateBook(
    $id,
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
    $cover_image = null,
    $file_path = null
) {
    global $pdo;
    try {
        // Build dynamic query based on whether files are being updated
        $sql = "UPDATE books SET 
            title = ?, author = ?, place_of_publication = ?, publisher = ?, 
            date_of_publication = ?, edition = ?, isbn_issn = ?, type_of_material = ?, department = ?,
            classification_number = ?, call_number = ?, accession_number = ?, copies = ?,
            description = ?, updated_at = NOW()";
        
        $params = [
            $title, $author, $place_of_publication, $publisher,
            $date_of_publication, $edition, $isbn_issn, $type_of_material, $department,
            $classification_number, $call_number, $accession_number, $copies, $description
        ];
        
        // Add cover image update if provided
        if ($cover_image !== null) {
            $sql .= ", cover_image = ?";
            $params[] = $cover_image;
        }
        
        // Add file path update if provided
        if ($file_path !== null) {
            $sql .= ", file_path = ?";
            $params[] = $file_path;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if (!$result) {
            throw new Exception("Failed to execute query");
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error in updateBook: " . $e->getMessage());
        throw new Exception("Database error: " . $e->getMessage());
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
        $cover_image = null; // null means don't update
        $file_path = null;   // null means don't update

        // Create upload directories if they don't exist
        $cover_dir = "../uploads/covers/";
        $books_dir = "../uploads/books/";

        if (!file_exists($cover_dir)) {
            mkdir($cover_dir, 0777, true);
        }
        if (!file_exists($books_dir)) {
            mkdir($books_dir, 0777, true);
        }

        // Handle cover image upload
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $uploaded_cover = uploadFile($_FILES['cover_image'], 'covers');
            if ($uploaded_cover) {
                // Delete old cover image if it's not the default
                if (!empty($book['cover_image']) && $book['cover_image'] !== 'genericBookCover.jpg') {
                    $old_cover_path = "../uploads/covers/" . $book['cover_image'];
                    if (file_exists($old_cover_path)) {
                        unlink($old_cover_path);
                    }
                }
                $cover_image = $uploaded_cover;
            }
        }

        // Handle book file upload
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === 0) {
            $uploaded_file = uploadFile($_FILES['book_file'], 'books');
            if ($uploaded_file) {
                // Delete old book file
                if (!empty($book['file_path'])) {
                    $old_file_path = "../uploads/books/" . $book['file_path'];
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
                $file_path = $uploaded_file;
            }
        }

        try {
            updateBook(
                $book_id,
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
            
            $success = 'Book updated successfully!';
            
            // Refresh book data to show updated information
            $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$book_id]);
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
?>

<style>
    .admin-container {
        max-width: 1200px;
        margin: 0 auto;
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

    .form-column {
        display: flex;
        flex-direction: column;
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
        color: #15722cff;
        margin-bottom: 5px;
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
        min-height: 120px;
        resize: vertical;
    }

    /* File input container styling */
    .file-input-container {
        position: relative;
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background-color: #f9f9f9;
        transition: all 0.3s ease;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .file-input-container:hover {
        border-color: #2f8f17ff;
        background-color: #f0f8ff;
    }

    .file-input-container.has-file {
        border-color: #28a745;
        background-color: #f0fff0;
    }

    .file-input {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .file-input-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        pointer-events: none;
        color: #666;
    }

    .file-icon {
        font-size: 30px;
        color: #155515ff;
    }

    .file-text {
        font-size: 14px;
        font-weight: 500;
    }

    .file-preview {
        max-width: 100%;
        max-height: 80px;
        border-radius: 4px;
        margin-top: 10px;
    }

    .file-name {
        font-size: 12px;
        color: #28a745;
        font-weight: 600;
        margin-top: 5px;
        word-break: break-all;
    }

    .remove-file {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        cursor: pointer;
        font-size: 12px;
        display: none;
    }

    .file-input-container.has-file .remove-file {
        display: block;
    }


    .btn-group {
        display: flex;
        gap: 15px;
        grid-column: span 2;
        margin-top: 20px;
    }

    .error-message {
        color: #e74c3c;
        font-weight: bold;
        padding: 10px;
        background-color: #ffeaa7;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .success-message {
        color: #2ecc71;
        font-weight: bold;
        padding: 10px;
        background-color: #d5f4e6;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .current-file-info {
        background-color: #e9ecef;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
        font-size: 12px;
        color: #495057;
    }

    .current-cover-preview {
        max-width: 80px;
        max-height: 80px;
        border-radius: 4px;
        margin-top: 5px;
    }

    @media screen and (max-width: 768px) {
        .admin-form {
            grid-template-columns: 1fr;
        }
        
        .full-width {
            grid-column: span 1;
        }
        
        .btn-group {
            grid-column: span 1;
            flex-direction: column;
        }
    }
</style>

<div class="admin-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: #2c3e50; margin: 0;">Edit Book</h2>
    </div>

    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form class="admin-form" method="POST" enctype="multipart/form-data" id="editBookForm">
        <div class="form-group">
            <label for="cover_image">Cover Image</label>
            <div class="file-input-container" id="cover-container">
                <input type="file" id="cover_image" name="cover_image" accept="image/*" class="file-input" onchange="handleFileSelect(this, 'cover')">
                <button type="button" class="remove-file" onclick="removeFile('cover')">&times;</button>
                <div class="file-input-label" id="cover-label">
                    <div class="file-icon">📷</div>
                    <div class="file-text">Click or drag to upload new cover image</div>
                    <div style="font-size: 12px; color: #999;">PNG, JPG, GIF up to 10MB</div>
                </div>
            </div>
            <?php if (!empty($book['cover_image'])): ?>
                <div class="current-file-info">
                    <strong>Current Cover:</strong> <?php echo htmlspecialchars($book['cover_image']); ?>
                    <?php if ($book['cover_image'] !== 'genericBookCover.jpg'): ?>
                        <br><img src="../uploads/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Current cover" class="current-cover-preview">
                    <?php else: ?>
                        <br><small>Using default cover</small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="book_file">Book File</label>
            <div class="file-input-container" id="book-container">
                <input type="file" id="book_file" name="book_file" class="file-input" onchange="handleFileSelect(this, 'book')">
                <button type="button" class="remove-file" onclick="removeFile('book')">&times;</button>
                <div class="file-input-label" id="book-label">
                    <div class="file-icon">📄</div>
                    <div class="file-text">Click or drag to upload new book file</div>
                    <div style="font-size: 12px; color: #999;">PDF, EPUB, DOC up to 50MB</div>
                </div>
            </div>
            <?php if (!empty($book['file_path'])): ?>
                <div class="current-file-info">
                    <strong>Current File:</strong> <?php echo htmlspecialchars($book['file_path']); ?>
                </div>
            <?php else: ?>
                <div class="current-file-info">
                    <strong>Current File:</strong> No file uploaded
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Left Column (First 5 fields) -->
        <div class="form-column">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" required value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="place_of_publication">Place of Publication</label>
                <input type="text" id="place_of_publication" name="place_of_publication" value="<?php echo htmlspecialchars($book['place_of_publication'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($book['publisher'] ?? ''); ?>">
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
                       value="<?php echo htmlspecialchars($book['date_of_publication'] ?? ''); ?>">
            </div>
        </div>

        <!-- Right Column (Next 5 fields) -->
        <div class="form-column">
            <div class="form-group">
                <label for="edition">Edition</label>
                <input type="text" id="edition" name="edition" placeholder="e.g., 1st, 2nd, 3rd" value="<?php echo htmlspecialchars($book['edition'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="isbn_issn">ISBN/ISSN</label>
                <input type="text" id="isbn_issn" name="isbn_issn" value="<?php echo htmlspecialchars($book['isbn_issn'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="type_of_material">Type of Material</label>
                <select id="type_of_material" name="type_of_material">
                    <option value="">Select Type</option>
                    <option value="Book" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Book') ? 'selected' : ''; ?>>Book</option>
                    <option value="Electronic Resource" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Electronic Resource') ? 'selected' : ''; ?>>Electronic Resource</option>
                    <option value="Map" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Map') ? 'selected' : ''; ?>>Map</option>
                    <option value="Music" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Music') ? 'selected' : ''; ?>>Music</option>
                    <option value="Continuing Resource" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Continuing Resource') ? 'selected' : ''; ?>>Continuing Resource</option>
                    <option value="Visual Material" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Visual Material') ? 'selected' : ''; ?>>Visual Material</option>
                    <option value="Mixed Material" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Mixed Material') ? 'selected' : ''; ?>>Mixed Material</option>
                    <option value="Thesis" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Thesis') ? 'selected' : ''; ?>>Thesis</option>
                    <option value="Article" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Article') ? 'selected' : ''; ?>>Article</option>
                    <option value="Analytics" <?php echo (isset($book['type_of_material']) && $book['type_of_material'] === 'Analytics') ? 'selected' : ''; ?>>Analytics</option>
                </select>
            </div>

            <div class="form-group">
                <label for="department">Department *</label>
                <select name="department" required>
                    <option value="">Select Department</option>
                    <option value="BSIT" <?php echo (isset($book['department']) && $book['department'] === 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                    <option value="BSED" <?php echo (isset($book['department']) && $book['department'] === 'BSED') ? 'selected' : ''; ?>>BSED</option>
                    <option value="BSAB" <?php echo (isset($book['department']) && $book['department'] === 'BSAB') ? 'selected' : ''; ?>>BSAB</option>
                    <option value="BSCRIM" <?php echo (isset($book['department']) && $book['department'] === 'BSCRIM') ? 'selected' : ''; ?>>BSCRIM</option>
                    <option value="BSA" <?php echo (isset($book['department']) && $book['department'] === 'BSA') ? 'selected' : ''; ?>>BSA</option>
                    <option value="FISHERS" <?php echo (isset($book['department']) && $book['department'] === 'FISHERS') ? 'selected' : ''; ?>>FISHERS</option>
                </select>
            </div>

            <div class="form-group">
                <label for="classification_number">Classification Number</label>
                <input type="text" id="classification_number" name="classification_number" value="<?php echo htmlspecialchars($book['classification_number'] ?? ''); ?>">
            </div>
        </div>

        <!-- Additional fields in full width -->
        <div class="full-width" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="call_number">Call Number</label>
                <input type="text" id="call_number" name="call_number" value="<?php echo htmlspecialchars($book['call_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="accession_number">Accession Number</label>
                <input type="text" id="accession_number" name="accession_number" value="<?php echo htmlspecialchars($book['accession_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="copies">Copies</label>
                <input type="number" id="copies" name="copies" value="<?php echo htmlspecialchars($book['copies'] ?? '1'); ?>" min="1">
            </div>
        </div>

        <div class="form-group full-width">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($book['description'] ?? ''); ?></textarea>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-save"></i> Update Book</button>
            <a href="?page=manage-books" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancel</a>
        </div>
    </form>
</div>

<script>
    function handleFileSelect(input, type) {
        const container = document.getElementById(type + '-container');
        const label = document.getElementById(type + '-label');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            container.classList.add('has-file');
            
            if (type === 'cover' && file.type.startsWith('image/')) {
                // Show image preview for cover
                const reader = new FileReader();
                reader.onload = function(e) {
                    label.innerHTML = `
                        <img src="${e.target.result}" class="file-preview" alt="Cover preview">
                        <div class="file-name">${file.name}</div>
                        <div style="font-size: 11px; color: #666;">${formatFileSize(file.size)}</div>
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                // Show file name and info for other files
                const icon = getFileIcon(file.name);
                label.innerHTML = `
                    <div class="file-icon">${icon}</div>
                    <div class="file-name">${file.name}</div>
                    <div style="font-size: 11px; color: #666;">${formatFileSize(file.size)}</div>
                `;
            }
        }
    }
    
    function removeFile(type) {
        const input = document.getElementById(type === 'cover' ? 'cover_image' : 'book_file');
        const container = document.getElementById(type + '-container');
        const label = document.getElementById(type + '-label');
        
        input.value = '';
        container.classList.remove('has-file');
        
        if (type === 'cover') {
            label.innerHTML = `
                <div class="file-icon">📷</div>
                <div class="file-text">Click or drag to upload new cover image</div>
                <div style="font-size: 12px; color: #999;">PNG, JPG, GIF up to 10MB</div>
            `;
        } else {
            label.innerHTML = `
                <div class="file-icon">📄</div>
                <div class="file-text">Click or drag to upload new book file</div>
                <div style="font-size: 12px; color: #999;">PDF, EPUB, DOC up to 50MB</div>
            `;
        }
    }
    
    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        switch (ext) {
            case 'pdf': return '📕';
            case 'doc':
            case 'docx': return '📘';
            case 'epub': return '📗';
            case 'txt': return '📄';
            default: return '📄';
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Add drag and drop functionality
    document.querySelectorAll('.file-input-container').forEach(container => {
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.borderColor = '#007bff';
            this.style.backgroundColor = '#f0f8ff';
        });
        
        container.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.borderColor = '#ddd';
            this.style.backgroundColor = '#f9f9f9';
        });
        
        container.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.style.borderColor = '#ddd';
            this.style.backgroundColor = '#f9f9f9';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = this.querySelector('.file-input');
                input.files = files;
                
                const type = input.id.includes('cover') ? 'cover' : 'book';
                handleFileSelect(input, type);
            }
        });
    });
</script>