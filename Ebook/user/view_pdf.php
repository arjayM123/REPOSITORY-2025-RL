<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/tracking_functions.php';

// Check if book ID is provided
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];
    
    // Track the view when PDF is opened
    trackBookView($pdo, $book_id);
    
    // NEW CODE:
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();

    if ($book) {
        // Check if file exists
        if (empty($book['file_path']) || !file_exists("../uploads/books/" . $book['file_path'])) {
            header("Location: view_books.php?id=" . $book_id);
            exit();
        }
    } else {
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($book['title']); ?> - Reading View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
   <style>
    html {
        scrollbar-width: none;
        scroll-behavior: smooth;
        -ms-overflow-style: none;
    }
    
    ::-webkit-scrollbar {
        display: none;
    }
    
    /* CSS styles */
    body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }
    
    .top-bar {
        background-color: #343a40;
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: fixed;
        width: 100%;
        top: 0;
        z-index: 1000;
    }
    
    .pdf-container {
        width: 100%;
        height: calc(100vh);
        position: relative;
        overflow: hidden;
    }
    
    .pdf-viewer {
        width: 100%;
        height: 100%;
        border: none;
        position: relative;
        z-index: 1;
    }
    
    .watermark-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 10;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .watermark {
        opacity: 0.10;
        transform: rotate(-30deg);
        text-align: center;
    }
    
    .watermark img {
        max-width: 150px; /* Smaller for mobile */
        height: auto;
    }
    
    .watermark-text {
        font-size: 18px; /* Smaller for mobile */
        font-weight: bold;
        color: #006400;
        margin-top: 10px;
    }
    
    /* Mobile specific watermark adjustments */
    @media screen and(max-width: 768px) {
        .watermark img {
            max-width: 120px;
        }
        
        .watermark-text {
            font-size: 14px;
        }
    }
    
    .book-title {
        margin: 0;
        font-size: 1.2rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 60%; /* Reduced for mobile button space */
    }
    
    /* Favorite button in top bar */
    .favorite-btn-top {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        border-radius: 6px;
        padding: 8px 12px;
        transition: all 0.3s ease;
    }
    
    .favorite-btn-top:hover {
        background: rgba(255, 255, 255, 0.3);
        color: white;
    }
    
    .favorite-btn-top.favorited {
        background: rgba(220, 53, 69, 0.8);
        border-color: #dc3545;
        color: white;
    }
    
    @media (max-width: 768px) {
        .book-title {
            font-size: 1rem;
            max-width: 40%;
        }
        
        .top-bar {
            padding: 8px 15px;
        }
        
        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .favorite-btn-top {
            padding: 6px 8px;
            font-size: 0.8rem;
        }
    }
</style>
</head>
<body>
<div class="top-bar">
    <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
    <div class="d-flex gap-2 align-items-center">
        <!-- View and Favorite Stats -->
        <small class="text-light me-3 d-none d-md-block">
            <i class="bi bi-eye me-1"></i><span id="viewCount"><?php echo $book['view_count'] ?? 0; ?></span>
            <i class="bi bi-heart ms-2 me-1"></i><span id="favoriteCount"><?php echo $book['favorite_count'] ?? 0; ?></span>
        </small>
        
        <!-- Favorite Button -->
        <?php $isFavorited = checkIfFavorited($pdo, $book['id']); ?>
        <button class="btn btn-sm favorite-btn-top <?php echo $isFavorited ? 'favorited' : ''; ?>" 
                data-book-id="<?php echo $book['id']; ?>"
                id="topFavoriteBtn">
            <i class="bi <?php echo $isFavorited ? 'bi-heart-fill' : 'bi-heart'; ?> me-1"></i>
            <span class="d-none d-md-inline"><?php echo $isFavorited ? 'Favorited' : 'Favorite'; ?></span>
        </button>
        
        <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#bookInfoModal">
            <i class="fas fa-info-circle"></i> <span class="d-none d-md-inline">Book Info</span>
        </button>
        <a href="index.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-times"></i> <span class="d-none d-md-inline">Close Reader</span>
        </a>
    </div>
</div>

<!-- Toast Notification for Favorites -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055; margin-top: 60px;">
    <div id="favoriteToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="bi bi-heart-fill text-danger me-2"></i>
            <strong class="me-auto">Favorites</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            <!-- Message will be inserted here -->
        </div>
    </div>
</div>

<div class="pdf-container">
    <!-- PDF Viewer for Desktop -->
    <object class="pdf-viewer d-none d-md-block" 
            data="../uploads/books/<?php echo htmlspecialchars($book['file_path']); ?>#toolbar=0&navpanes=0&scrollbar=1"
            type="application/pdf">
        <p>It appears your browser doesn't support embedded PDFs. 
           <a href="view_books.php?id=<?php echo $book_id; ?>">Return to book details</a>.</p>
    </object>
    
    <!-- Mobile PDF Viewer with iframe -->
    <div class="d-block d-md-none h-100 position-relative">
        <iframe class="pdf-viewer" 
                src="../uploads/books/<?php echo htmlspecialchars($book['file_path']); ?>#toolbar=0&navpanes=0&scrollbar=1&view=FitH"
                type="application/pdf">
        </iframe>
        
        <!-- Mobile Fallback (if iframe fails) -->
        <div class="position-absolute top-50 start-50 translate-middle text-center" 
             style="display: none; z-index: 100;" id="mobileFallback">
            <div class="card border-0 shadow-lg" style="max-width: 350px;">
                <div class="card-body p-4">
                    <i class="fas fa-file-pdf display-1 text-danger mb-3"></i>
                    <h6 class="card-title text-dark mb-3"><?php echo htmlspecialchars($book['title']); ?></h6>
                    <p class="card-text text-muted mb-4 small">
                        Unable to display PDF. Please use the button below to view it.
                    </p>
                    <a href="../uploads/books/<?php echo htmlspecialchars($book['file_path']); ?>" 
                       target="_blank" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-2"></i>Open PDF
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Watermark Overlay -->
        <div class="watermark-container d-block d-md-none">
            <div class="watermark">
                <img src="../assets/images/images-removebg-preview.png" alt="ISU Logo">
                <div class="watermark-text">PROPERTY OF ISU-ROXAS LIBRARY</div>
            </div>
        </div>
    </div>
    
    <!-- Desktop Watermark Overlay -->
    <div class="watermark-container d-none d-md-flex">
        <div class="watermark">
            <img src="../assets/images/images-removebg-preview.png" alt="ISU Logo">
            <div class="watermark-text">PROPERTY OF ISU-ROXAS LIBRARY</div>
        </div>
    </div>
</div>

<!-- Book Info Modal -->
<div class="modal fade" id="bookInfoModal" tabindex="-1" aria-labelledby="bookInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="bookInfoModalLabel">
                    <i class="fas fa-book me-2"></i>Complete Book Information
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Book Cover -->
                    <div class="col-md-3 text-center mb-3">
                        <?php
                        // Determine cover image path
                        if (empty($book['cover_image']) || $book['cover_image'] === 'genericBookCover.jpg') {
                            $coverPath = '../assets/images/genericBookCover.jpg';
                        } else {
                            $coverPath = '../uploads/covers/' . $book['cover_image'];
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($coverPath); ?>" 
                             class="img-fluid rounded shadow" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             style="max-height: 350px;"
                             onerror="this.src='../assets/images/genericBookCover.jpg';">
                        
                        <!-- Stats in Modal -->
                        <div class="mt-3">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="bg-light p-2 rounded">
                                        <i class="bi bi-eye text-primary"></i>
                                        <div class="fw-bold" id="modalViewCount"><?php echo $book['view_count'] ?? 0; ?></div>
                                        <small class="text-muted">Views</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light p-2 rounded">
                                        <i class="bi bi-heart text-danger"></i>
                                        <div class="fw-bold" id="modalFavoriteCount"><?php echo $book['favorite_count'] ?? 0; ?></div>
                                        <small class="text-muted">Favorites</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Book Details -->
                    <div class="col-md-9">
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Basic Information
                                </h6>
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <td class="fw-bold text-secondary" style="width: 40%;">Title:</td>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-secondary">Author:</td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    </tr>
                                    <?php if (!empty($book['place_of_publication'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Place of Publication:</td>
                                        <td><?php echo htmlspecialchars($book['place_of_publication']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['publisher'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Publisher:</td>
                                        <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['date_of_publication'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Date of Publication:</td>
                                        <td><?php echo htmlspecialchars($book['date_of_publication']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['edition'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Edition:</td>
                                        <td><?php echo htmlspecialchars($book['edition']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['isbn_issn'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">ISBN/ISSN:</td>
                                        <td><?php echo htmlspecialchars($book['isbn_issn']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            
                            <!-- Library Classification -->
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-bookmark me-2"></i>Library Classification
                                </h6>
                                <table class="table table-sm table-borderless">
                                    <?php if (!empty($book['type_of_material'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary" style="width: 40%;">Material Type:</td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($book['type_of_material']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['classification_number'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Classification Number:</td>
                                        <td><?php echo htmlspecialchars($book['classification_number']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['call_number'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Call Number:</td>
                                        <td>
                                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($book['call_number']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($book['accession_number'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Accession Number:</td>
                                        <td>
                                            <span class="badge bg-success"><?php echo htmlspecialchars($book['accession_number']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Copies Available:</td>
                                        <td>
                                            <span class="badge bg-info text-dark"><?php echo $book['copies']; ?></span>
                                        </td>
                                    </tr>
                                    <?php if (!empty($book['department'])): ?>
                                    <tr>
                                        <td class="fw-bold text-secondary">Department:</td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($book['department']); ?></span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Description Section -->
                        <?php if (!empty($book['description'])): ?>
                        <div class="mt-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-align-left me-2"></i>Description
                            </h6>
                            <div class="bg-light p-3 rounded">
                                <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Prevent keyboard shortcuts for printing and saving
    document.addEventListener('keydown', function(e) {
        // Prevent Ctrl+P (Print)
        if (e.ctrlKey && (e.key === 'p' || e.keyCode === 80)) {
            e.preventDefault();
            return false;
        }
        
        // Prevent Ctrl+S (Save)
        if (e.ctrlKey && (e.key === 's' || e.keyCode === 83)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Additional protection against right-clicks
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });

    // Initialize favorite button functionality
    document.addEventListener('DOMContentLoaded', function() {
        const favoriteBtn = document.getElementById('topFavoriteBtn');
        
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                toggleFavorite(this);
            });
        }
    });

    // Toggle favorite function
    function toggleFavorite(button) {
        const bookId = button.getAttribute('data-book-id');
        const heartIcon = button.querySelector('i');
        const buttonText = button.querySelector('span');
        
        // Disable button temporarily
        button.disabled = true;
        
        fetch('ajax_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'book_id=' + bookId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update heart icon and button state
                if (data.action === 'added') {
                    heartIcon.className = 'bi bi-heart-fill me-1';
                    button.classList.add('favorited');
                    if (buttonText) buttonText.textContent = 'Favorited';
                }
                
                // Update favorite counts in UI
                const favoriteCount = document.getElementById('favoriteCount');
                const modalFavoriteCount = document.getElementById('modalFavoriteCount');
                
                if (favoriteCount) favoriteCount.textContent = data.favorite_count;
                if (modalFavoriteCount) modalFavoriteCount.textContent = data.favorite_count;
                
                // Show toast notification
                showToast(data.message, data.success ? 'success' : 'info');
            } else {
                showToast(data.message, 'warning');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error occurred while updating favorites', 'error');
        })
        .finally(() => {
            // Re-enable button
            button.disabled = false;
        });
    }

    // Show toast notification
    function showToast(message, type = 'info') {
        const toastElement = document.getElementById('favoriteToast');
        const toastMessage = document.getElementById('toastMessage');
        
        toastMessage.textContent = message;
        
        // Update toast style based on type
        toastElement.className = 'toast';
        if (type === 'success') {
            toastElement.classList.add('border-success');
        } else if (type === 'warning') {
            toastElement.classList.add('border-warning');
        } else if (type === 'error') {
            toastElement.classList.add('border-danger');
        }
        
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    }
    
    // Create multiple watermarks throughout the document
    window.onload = function() {
        const container = document.querySelector('.pdf-container');
        const watermark = document.querySelector('.watermark');
        
        if (container && watermark) {
            const watermarkClone = watermark.cloneNode(true);
            
            // Create additional watermarks
            for (let i = 0; i < 2; i++) {
                const newWatermark = document.createElement('div');
                newWatermark.className = 'watermark-container';
                newWatermark.style.top = `${100 + (i * 100)}%`;
                
                const clone = watermarkClone.cloneNode(true);
                newWatermark.appendChild(clone);
                container.appendChild(newWatermark);
            }
        }

        // Handle mobile PDF fallback
        const iframe = document.querySelector('.pdf-viewer');
        const fallback = document.getElementById('mobileFallback');
        
        if (iframe && fallback) {
            iframe.onload = function() {
                try {
                    if (iframe.contentDocument === null) {
                        iframe.style.display = 'none';
                        fallback.style.display = 'block';
                    }
                } catch (e) {
                    // Cross-origin restriction, assume it's working
                }
            };
            
            iframe.onerror = function() {
                iframe.style.display = 'none';
                fallback.style.display = 'block';
            };
        }
    };
</script>
</body>
</html>