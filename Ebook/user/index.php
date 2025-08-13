<?php
require_once '../includes/db.php';
require_once '../includes/tracking_functions.php';

// Set page configuration
$pageTitle = 'Home - ISUR-ORA Digital Library';

// Get total counts for statistics
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$totalBooksResult = $pdo->query($totalBooksQuery);
$totalBooks = $totalBooksResult->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get total visitors (you may need to implement visitor tracking)
$totalVisitors = 1234; // Placeholder - implement visitor tracking

// Include the header
include "_layout.php";
?>

<!-- Main Content with Bootstrap spacing for fixed navbar -->
<div style="padding-top: 80px;">
    <!-- Books Showcase Section with Bootstrap background -->
    <section class="py-5 bg-light">
        <div class="container">
            <!-- Books Container -->
            <div id="booksContainer">
                <?php
                // Get all books with their information including view and favorite counts
                $allBooksQuery = "
                    SELECT id, title, author, cover_image, type_of_material, department, created_at,
                           view_count, favorite_count
                    FROM books 
                    ORDER BY created_at DESC
                ";
                $allBooksResult = $pdo->query($allBooksQuery);
                $allBooks = $allBooksResult->fetchAll();

                // Group books by material type
                $materialTypes = [];
                foreach ($allBooks as $book) {
                    if (!empty($book['type_of_material'])) {
                        if (!isset($materialTypes[$book['type_of_material']])) {
                            $materialTypes[$book['type_of_material']] = [];
                        }
                        $materialTypes[$book['type_of_material']][] = $book;
                    }
                }

                // Sort material types by book count
                uksort($materialTypes, function($a, $b) use ($materialTypes) {
                    return count($materialTypes[$b]) - count($materialTypes[$a]);
                });

                if (!empty($materialTypes)) {
                    foreach ($materialTypes as $materialType => $books) {
                        $bookCount = count($books);
                        ?>
                        <div class="mb-5 material-category" data-material="<?php echo htmlspecialchars($materialType); ?>">
                            <!-- Category Header using Bootstrap flex utilities -->
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom border-3 border-primary">
                                <h3 class="h4 fw-semibold text-dark mb-2 mb-md-0">
                                    <i class="bi bi-collection text-primary me-2"></i>
                                    <?php echo htmlspecialchars($materialType); ?>
                                </h3>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-primary rounded-pill px-3 py-2">
                                        <?php echo $bookCount; ?> book<?php echo $bookCount > 1 ? 's' : ''; ?>
                                    </span>
                                    <a href="books.php?material=<?php echo urlencode($materialType); ?>" 
                                       class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold">
                                        View All <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Books Grid using Bootstrap grid system -->
                            <div class="row g-3 books-grid">
                                <?php
                                // Show first 6 books
                                $displayBooks = array_slice($books, 0, 6);
                                foreach ($displayBooks as $book) {
                                    // Determine image path
                                    if (empty($book['cover_image']) || $book['cover_image'] === 'genericBookCover.jpg') {
                                        $imagePath = '../assets/images/genericBookCover.jpg';
                                    } else {
                                        $imagePath = '../uploads/covers/' . $book['cover_image'];
                                    }
                                    
                                    // Check if this book is favorited by current user
                                    $isFavorited = checkIfFavorited($pdo, $book['id']);
                                    ?>
                                    <div class="col-6 col-md-4 col-lg-2 book-card" 
                                         data-department="<?php echo htmlspecialchars($book['department'] ?? ''); ?>">
                                        <div class="card h-100 shadow-sm border-0" style="position: relative;">
                                            <!-- Favorite Heart Button - Top Left -->
                                            <button class="btn btn-sm favorite-btn" 
                                                    data-book-id="<?php echo $book['id']; ?>"
                                                    style="position: absolute; top: 8px; left: 8px; z-index: 10; 
                                                           background: rgba(255, 255, 255, 0.9); border: 1px solid #dee2e6; 
                                                           border-radius: 50%; width: 35px; height: 35px; padding: 0; 
                                                           display: flex; align-items: center; justify-content: center;
                                                           box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                                                    title="Add to favorites">
                                                <i class="bi <?php echo $isFavorited ? 'bi-heart-fill text-danger' : 'bi-heart text-muted'; ?>" 
                                                   style="font-size: 16px;"></i>
                                            </button>
                                            
                                            <a href="view_pdf.php?id=<?php echo $book['id']; ?>" 
                                               class="text-decoration-none book-link" 
                                               data-book-id="<?php echo $book['id']; ?>">
                                                <!-- Book Cover with Bootstrap ratio -->
                                                <div class="bg-light">
                                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                                         class="img-fluid rounded"
                                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                         loading="lazy"
                                                         onerror="this.src='../assets/images/genericBookCover.jpg';"
                                                         style="width: 100%; height: 200px; object-fit: cover;">
                                                </div>
                                                
                                                <!-- Book Info using Bootstrap card body -->
                                                <div class="card-body p-3">
                                                    <h6 class="card-title text-dark fw-semibold mb-2 lh-sm text-truncate">
                                                        <?php echo htmlspecialchars($book['title']); ?>
                                                    </h6>
                                                    <p class="card-text text-muted mb-2 small text-truncate">
                                                        <?php echo htmlspecialchars($book['author']); ?>
                                                    </p>
                                                    <?php if (!empty($book['department'])): ?>
                                                        <small class="text-primary fw-medium d-block mb-2">
                                                            <?php echo htmlspecialchars($book['department']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                    
                                                    <!-- View and Favorite Stats -->
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">
                                                            <i class="bi bi-eye me-1"></i><?php echo $book['view_count'] ?? 0; ?>
                                                        </small>
                                                        <small class="text-muted">
                                                            <i class="bi bi-heart me-1"></i><?php echo $book['favorite_count'] ?? 0; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <!-- No books message using Bootstrap components -->
                    <div class="text-center py-5">
                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 500px;">
                            <div class="card-body p-5">
                                <i class="bi bi-book display-1 text-muted mb-3"></i>
                                <h4 class="card-title text-dark">No Books Available Yet</h4>
                                <p class="card-text text-muted">Our digital library is being updated. Please check back soon for new materials!</p>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>
</div>

<!-- Toast Notification for Favorites -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
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

<?php $materialTypesCount = count($materialTypes); ?>
<!-- Pure Bootstrap JS only -->
<script>
    // Simple counter animation using vanilla JS
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded - Total books: <?php echo $totalBooks; ?>');
        
        // Initialize favorite buttons
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleFavorite(this);
            });
        });
        
        // Track book views when clicking on book links
        const bookLinks = document.querySelectorAll('.book-link');
        bookLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const bookId = this.getAttribute('data-book-id');
                // Track the view (fire and forget)
                fetch('track_view.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'book_id=' + bookId
                }).catch(err => console.log('View tracking failed:', err));
            });
        });
    });

    // Toggle favorite function
    function toggleFavorite(button) {
        const bookId = button.getAttribute('data-book-id');
        const heartIcon = button.querySelector('i');
        
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
                // Update heart icon
                if (data.action === 'added') {
                    heartIcon.className = 'bi bi-heart-fill text-danger';
                    button.title = 'Remove from favorites';
                }
                
                // Update favorite count in stats
                const card = button.closest('.book-card');
                const statsHeart = card.querySelector('.card-body small:last-child i.bi-heart');
                if (statsHeart) {
                    statsHeart.parentNode.innerHTML = '<i class="bi bi-heart me-1"></i>' + data.favorite_count;
                }
                
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
        const toastHeader = toastElement.querySelector('.toast-header');
        
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

    // Simple filter function using vanilla JS and Bootstrap classes
    function filterBooks(materialFilter, courseFilter) {
        const categories = document.querySelectorAll('.material-category');
        const filterInfo = document.getElementById('filterInfo');
        const filterText = document.getElementById('filterText');
        let visibleCount = 0;
        
        categories.forEach(category => {
            const categoryMaterial = category.getAttribute('data-material');
            const books = category.querySelectorAll('.book-card');
            let categoryVisible = false;
            
            // Check if category should be visible based on material filter
            if (materialFilter === 'all' || materialFilter === categoryMaterial) {
                if (courseFilter === 'all') {
                    // Show all books in this category
                    books.forEach(book => {
                        book.classList.remove('d-none');
                        categoryVisible = true;
                        visibleCount++;
                    });
                } else {
                    // Filter books by course/department
                    books.forEach(book => {
                        const bookDepartment = book.getAttribute('data-department');
                        if (bookDepartment === courseFilter) {
                            book.classList.remove('d-none');
                            categoryVisible = true;
                            visibleCount++;
                        } else {
                            book.classList.add('d-none');
                        }
                    });
                }
            } else {
                // Hide entire category
                books.forEach(book => book.classList.add('d-none'));
            }
            
            // Show/hide category based on visible books
            if (categoryVisible) {
                category.classList.remove('d-none');
            } else {
                category.classList.add('d-none');
            }
        });
        
        // Update filter info using Bootstrap classes (only if elements exist)
        if (filterInfo && filterText) {
            let filterMessage = 'Showing ';
            if (materialFilter !== 'all' && courseFilter !== 'all') {
                filterMessage += materialFilter + ' materials for ' + courseFilter;
            } else if (materialFilter !== 'all') {
                filterMessage += materialFilter + ' materials';
            } else if (courseFilter !== 'all') {
                filterMessage += 'materials for ' + courseFilter;
            } else {
                filterMessage += 'all materials';
            }
            
            filterText.textContent = filterMessage;
            
            // Show/hide filter info using Bootstrap classes
            if (materialFilter !== 'all' || courseFilter !== 'all') {
                filterInfo.classList.remove('d-none');
            } else {
                filterInfo.classList.add('d-none');
            }
        }
        
        // Show no results message if needed using Bootstrap components
        if (visibleCount === 0) {
            let noResultsMessage = document.querySelector('.no-results-message');
            if (!noResultsMessage) {
                noResultsMessage = document.createElement('div');
                noResultsMessage.className = 'text-center py-5 no-results-message';
                noResultsMessage.innerHTML = `
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 500px;">
                        <div class="card-body p-5">
                            <i class="bi bi-search display-1 text-muted mb-3"></i>
                            <h4 class="card-title text-dark">No Materials Found</h4>
                            <p class="card-text text-muted">No materials match your current filter criteria. Try adjusting your selection.</p>
                        </div>
                    </div>
                `;
                document.getElementById('booksContainer').appendChild(noResultsMessage);
            }
            noResultsMessage.classList.remove('d-none');
        } else {
            const noResultsMessage = document.querySelector('.no-results-message');
            if (noResultsMessage) {
                noResultsMessage.classList.add('d-none');
            }
        }
    }
</script>