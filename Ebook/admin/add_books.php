<?php
// No need for session_start() and db.php as they're included in _layout.php

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
    global $pdo;
    try {
        $sql = "INSERT INTO books (
            title, author, place_of_publication, publisher, 
            date_of_publication, edition, isbn_issn, type_of_material, department,
            classification_number, call_number, accession_number, copies,
            description, cover_image, file_path
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
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
        ]);

        if (!$result) {
            throw new Exception("Failed to execute query");
        }
        return true;
    } catch (PDOException $e) {
        error_log("Error in addBook: " . $e->getMessage());
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
        $cover_image = 'genericBookCover.jpg'; // Default cover image
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

        // Handle cover image upload - use default if no image uploaded
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $uploaded_cover = uploadFile($_FILES['cover_image'], 'covers');
            if ($uploaded_cover) {
                $cover_image = $uploaded_cover;
            }
        }
        // If no cover uploaded, $cover_image remains as 'genericBookCover.jpg'

        // Handle book file upload
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
            if ($cover_image === 'genericBookCover.jpg') {
                $success .= ' (Default cover image applied)';
            }
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
?>

<style>
    .admin-container {
        max-width: 1200px;
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

    .btn-submit {
        padding: 15px 30px;
        background-color: #177c2dff;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 18px;
        cursor: pointer;
        grid-column: span 2;
        margin-top: 20px;
        transition: background-color 0.3s ease;
    }

    .btn-submit:hover {
        background-color: #34d457ff;
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

    .default-cover-info {
        background-color: #c8e9c5ff;
        border: 1px solid #1a8d16ff;
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
        font-size: 12px;
        color: #105025ff;
        text-align: center;
    }

    @media (max-width: 768px) {
        .admin-form {
            grid-template-columns: 1fr;
        }
        
        .full-width {
            grid-column: span 1;
        }
        
        .btn-submit {
            grid-column: span 1;
        }
    }
</style>

<div class="admin-container">
    <h2 style="color: #2c3e50; text-align: center; margin-bottom: 30px;">Add New Book</h2>

    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form class="admin-form" method="POST" enctype="multipart/form-data" id="addBookForm">
        <div class="form-group">
            <label for="cover_image">Cover Image (Optional)</label>
            <div class="file-input-container" id="cover-container">
                <input type="file" id="cover_image" name="cover_image" accept="image/*" class="file-input" onchange="handleFileSelect(this, 'cover')">
                <button type="button" class="remove-file" onclick="removeFile('cover')">&times;</button>
                <div class="file-input-label" id="cover-label">
                    <div class="file-icon">📷</div>
                    <div class="file-text">Click or drag to upload cover image</div>
                    <div style="font-size: 12px; color: #999;">PNG, JPG, GIF up to 10MB</div>
                </div>
            </div>
            <div class="default-cover-info">
                <strong>📚 Default Cover:</strong> If no image is uploaded, a generic book cover will be used automatically.
            </div>
        </div>

        <div class="form-group">
            <label for="book_file">Book File (Optional)</label>
            <div class="file-input-container" id="book-container">
                <input type="file" id="book_file" name="book_file" class="file-input" onchange="handleFileSelect(this, 'book')">
                <button type="button" class="remove-file" onclick="removeFile('book')">&times;</button>
                <div class="file-input-label" id="book-label">
                    <div class="file-icon">📄</div>
                    <div class="file-text">Click or drag to upload book file</div>
                    <div style="font-size: 12px; color: #999;">PDF, EPUB, DOC up to 50MB</div>
                </div>
            </div>
        </div>
        
        <!-- Left Column (First 5 fields) -->
        <div class="form-column">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" required value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="place_of_publication">Place of Publication</label>
                <input type="text" id="place_of_publication" name="place_of_publication" value="<?php echo htmlspecialchars($_POST['place_of_publication'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="publisher">Publisher</label>
                <input type="text" id="publisher" name="publisher" value="<?php echo htmlspecialchars($_POST['publisher'] ?? ''); ?>">
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
                       value="<?php echo htmlspecialchars($_POST['date_of_publication'] ?? ''); ?>">
            </div>
        </div>

        <!-- Right Column (Next 5 fields) -->
        <div class="form-column">
            <div class="form-group">
                <label for="edition">Edition</label>
                <input type="text" id="edition" name="edition" placeholder="e.g., 1st, 2nd, 3rd" value="<?php echo htmlspecialchars($_POST['edition'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="isbn_issn">ISBN/ISSN</label>
                <input type="text" id="isbn_issn" name="isbn_issn" value="<?php echo htmlspecialchars($_POST['isbn_issn'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="type_of_material">Type of Material</label>
                <select id="type_of_material" name="type_of_material">
                    <option value="">Select Type</option>
                    <option value="Book" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Book') ? 'selected' : ''; ?>>Book</option>
                    <option value="Electronic Resource" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Electronic Resource') ? 'selected' : ''; ?>>Electronic Resource</option>
                    <option value="Map" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Map') ? 'selected' : ''; ?>>Map</option>
                    <option value="Music" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Music') ? 'selected' : ''; ?>>Music</option>
                    <option value="Continuing Resource" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Continuing Resource') ? 'selected' : ''; ?>>Continuing Resource</option>
                    <option value="Visual Material" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Visual Material') ? 'selected' : ''; ?>>Visual Material</option>
                    <option value="Mixed Material" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Mixed Material') ? 'selected' : ''; ?>>Mixed Material</option>
                    <option value="Thesis" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Thesis') ? 'selected' : ''; ?>>Thesis</option>
                    <option value="Article" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Article') ? 'selected' : ''; ?>>Article</option>
                    <option value="Analytics" <?php echo (isset($_POST['type_of_material']) && $_POST['type_of_material'] === 'Analytics') ? 'selected' : ''; ?>>Analytics</option>
                </select>
            </div>

            <div class="form-group">
                <label for="department">Department *</label>
                <select name="department" required>
                    <option value="">Select Department</option>
                    <option value="BSIT" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                    <option value="BSED" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BSED') ? 'selected' : ''; ?>>BSED</option>
                    <option value="BSAB" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BSAB') ? 'selected' : ''; ?>>BSAB</option>
                    <option value="BSCRIM" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BSCRIM') ? 'selected' : ''; ?>>BSCRIM</option>
                    <option value="BSA" <?php echo (isset($_POST['department']) && $_POST['department'] === 'BSA') ? 'selected' : ''; ?>>BSA</option>
                    <option value="FISHERS" <?php echo (isset($_POST['department']) && $_POST['department'] === 'FISHERS') ? 'selected' : ''; ?>>FISHERS</option>
                </select>
            </div>

            <div class="form-group">
                <label for="classification_number">Classification Number</label>
                <input type="text" id="classification_number" name="classification_number" value="<?php echo htmlspecialchars($_POST['classification_number'] ?? ''); ?>">
            </div>
        </div>

        <!-- Additional fields in full width -->
        <div class="full-width" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="call_number">Call Number</label>
                <input type="text" id="call_number" name="call_number" value="<?php echo htmlspecialchars($_POST['call_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="accession_number">Accession Number</label>
                <input type="text" id="accession_number" name="accession_number" value="<?php echo htmlspecialchars($_POST['accession_number'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="copies">Copies</label>
                <input type="number" id="copies" name="copies" value="<?php echo htmlspecialchars($_POST['copies'] ?? '1'); ?>" min="1">
            </div>
        </div>

        <div class="form-group full-width">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn-submit">Save Book</button>
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
                <div class="file-text">Click or drag to upload cover image</div>
                <div style="font-size: 12px; color: #999;">PNG, JPG, GIF up to 10MB</div>
            `;
        } else {
            label.innerHTML = `
                <div class="file-icon">📄</div>
                <div class="file-text">Click or drag to upload book file</div>
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