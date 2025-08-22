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

// Get unique departments for filter dropdown
$departmentsQuery = "SELECT DISTINCT department FROM books WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departmentsResult = $pdo->query($departmentsQuery);
$departments = $departmentsResult->fetchAll(PDO::FETCH_COLUMN);

// Get unique material types for filter dropdown
$materialTypesQuery = "SELECT DISTINCT type_of_material FROM books WHERE type_of_material IS NOT NULL AND type_of_material != '' ORDER BY type_of_material";
$materialTypesResult = $pdo->query($materialTypesQuery);
$availableMaterialTypes = $materialTypesResult->fetchAll(PDO::FETCH_COLUMN);

// Include the header
include "_layout.php";
?>
<style>
.book-restricted {
    pointer-events: none;
    user-select: none;
}

.restriction-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 20;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.blurred-content {
    filter: blur(5px);
}

/* Add hover effect for non-restricted books only */
.card:not(.book-restricted):hover {
    transform: translateY(-5px);
    transition: transform 0.2s ease-in-out;
}

/* Add to your existing style section */
.position-relative[onclick] {
    cursor: pointer;
}

.position-relative[onclick]:hover {
    opacity: 0.9;
}

#restrictedBookModal .modal-content {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

#restrictedBookModal .bi-lock-fill {
    filter: drop-shadow(0 0 0.5rem rgba(220, 53, 69, 0.3));
}

/* Horizontal Scrolling Styles */
.horizontal-books-container {
    display: flex;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 15px 0;
    gap: 15px;
    scroll-behavior: smooth;
    direction: ltr; /* Start from left */
}

.horizontal-books-container::-webkit-scrollbar {
    height: 8px;
}

.horizontal-books-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.horizontal-books-container::-webkit-scrollbar-thumb {
    background: #007bff;
    border-radius: 4px;
}

.horizontal-books-container::-webkit-scrollbar-thumb:hover {
    background: #0056b3;
}

.horizontal-book-item {
    flex: 0 0 auto;
    width: 180px;
    direction: ltr; /* Keep text direction normal */
}

/* Mobile responsive adjustments */
@media screen and (max-width: 767.98px) {
    .horizontal-book-item {
        width: 140px;
    }
    
    .horizontal-books-container {
        gap: 10px;
        padding: 10px 0;
    }
}


.favorite-link {
    text-decoration: none;
    transition: transform 0.2s ease;
    padding: 5px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
}

.favorite-link:hover {
    transform: scale(1.1);
    background-color: rgba(0, 0, 0, 0.05);
}

.favorite-link:active {
    transform: scale(0.95);
}

.favorite-link[disabled] {
    opacity: 0.6;
    pointer-events: none;
}

.favorite-link .bi-heart-fill {
    color: #dc3545;
}

.favorite-link .bi-heart {
    color: #6c757d;
}

/* Fix horizontal scrolling */
html, body {
    overflow-x: hidden;
    width: 100%;
    margin: 0;
    padding: 0;
}

/* Container adjustments */
.container-fluid {
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
}

/* Row adjustments */
.row {
    margin-left: 0 !important;
    margin-right: 0 !important;
}

/* Books grid adjustments */
.books-grid {
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
}

/* Adjust container padding */
.container {
    padding-left: 15px !important;
    padding-right: 15px !important;
    max-width: 100% !important;
}

/* Mobile specific fixes */
@media screen and (max-width: 767.98px) {
    /* Column adjustments */
    .col-12, .col-md-10, .col-md-2 {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    /* Book card adjustments */
    .book-card {
        padding-left: 5px !important;
        padding-right: 5px !important;
    }

    /* Section padding adjustments */
    section {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    /* Material category adjustments */
    .material-category {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
}

/* Navigation arrows for horizontal scroll */
.scroll-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #ddd;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
}

.scroll-nav:hover {
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.scroll-nav-left {
    left: 10px;
}

.scroll-nav-right {
    right: 10px;
}

.category-wrapper {
    position: relative;
}
</style>
<!-- Main Content with Bootstrap spacing for fixed navbar -->
<div style="padding-top: 80px;">

    <!-- Search Section -->
<section class="py-2 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <!-- Search Input -->
            <div class="col-md-6">
                <!-- Mobile Label (visible only on small screens) -->
                <label for="searchInput-mobile" class="form-label fw-semibold d-md-none">
                    <i class="bi bi-search me-1"></i>Search Books
                </label>
                
                <!-- Desktop: Label and Input in same row -->
                <div class="d-none d-md-flex align-items-center gap-3">
                    <label for="searchInput-desktop" class="form-label fw-semibold mb-0 text-nowrap">
                        <i class="bi bi-search me-1"></i>Search Books
                    </label>
                    <div class="input-group flex-grow-1">
                        <input type="text" class="form-control" id="searchInput-desktop" 
                               placeholder="Search by title, author, or keyword..." 
                               autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch-desktop">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile: Label above input (visible only on small screens) -->
                <div class="d-md-none">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput-mobile" 
                               placeholder="Search by title, author, or keyword..." 
                               autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch-mobile">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search Info -->
        <div class="row mt-3">
            <div class="col-12">
                <div id="searchInfo" class="alert alert-info d-none mb-0" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <span id="searchText">Showing all materials</span>
                    <span class="ms-2">•</span>
                    <span id="resultCount" class="ms-2">0 books found</span>
                    <button type="button" class="btn btn-sm btn-outline-primary ms-3" onclick="clearAllFilters()">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Books Showcase Section -->
    <section class="py-5 bg-light">
        <div class="container-fluid">
            <div class="row">
                <!-- Main Books Content (Full Width) -->
                <div class="col-12">
                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Searching books...</p>
                    </div>
                    
                    <!-- Books Container -->
                    <div id="booksContainer">
                        <?php
                        // Get all books with their information including view and favorite counts
                        $allBooksQuery = "
                            SELECT id, title, author, cover_image, type_of_material, department, created_at,
                                   view_count, favorite_count, status 
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
                                    <!-- Category Header -->
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-3 border-bottom border-3 border-primary px-3">
                                        <h3 class="h4 fw-semibold text-dark mb-2 mb-md-0">
                                            <i class="bi bi-collection text-primary me-2"></i>
                                            <?php echo htmlspecialchars($materialType); ?>
                                        </h3>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge bg-primary rounded-pill px-3 py-2 category-count">
                                                <?php echo $bookCount; ?> book<?php echo $bookCount > 1 ? 's' : ''; ?>
                                            </span>
                                            <a href="books.php?material=<?php echo urlencode($materialType); ?>" 
                                               class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold">
                                                View All <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Horizontal Books Container with Navigation -->
                                    <div class="category-wrapper px-3">
                                        <!-- Left Navigation Arrow -->
                                        <div class="scroll-nav scroll-nav-left" onclick="scrollBooks('<?php echo htmlspecialchars($materialType); ?>', 'left')">
                                            <i class="bi bi-chevron-left"></i>
                                        </div>
                                        
                                        <!-- Right Navigation Arrow -->
                                        <div class="scroll-nav scroll-nav-right" onclick="scrollBooks('<?php echo htmlspecialchars($materialType); ?>', 'right')">
                                            <i class="bi bi-chevron-right"></i>
                                        </div>
                                        
                                        <!-- Horizontal Scrolling Books -->
                                        <div class="horizontal-books-container" id="books-<?php echo htmlspecialchars($materialType); ?>">
                                           <?php
foreach ($books as $book) {
    // Determine image path
    if (empty($book['cover_image']) || $book['cover_image'] === 'genericBookCover.jpg') {
        $imagePath = '../assets/images/genericBookCover.jpg';
    } else {
        $imagePath = '../uploads/covers/' . $book['cover_image'];
    }
    
    // Check if this book is favorited by current user
    $isFavorited = checkIfFavorited($pdo, $book['id']);
    ?>
    <div class="horizontal-book-item book-card" 
         data-department="<?php echo htmlspecialchars($book['department'] ?? ''); ?>"
         data-title="<?php echo htmlspecialchars(strtolower($book['title'])); ?>"
         data-author="<?php echo htmlspecialchars(strtolower($book['author'])); ?>"
         data-material="<?php echo htmlspecialchars($book['type_of_material']); ?>">
        <div class="card h-100 shadow-sm border-0">
            <!-- Book Cover with Fixed Size -->
            <div class="position-relative" style="height: 200px; overflow: hidden;">
                <?php if ($book['status'] === 'locked'): ?>
                    <!-- Lock Icon Overlay -->
                    <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center bg-white bg-opacity-75" 
                         style="z-index: 10; cursor: pointer;"
                         onclick="showRestrictedModal()">
                        <div class="text-center">
                            <i class="bi bi-lock-fill text-danger fs-1"></i>
                            <div class="text-danger fw-bold small">Restricted</div>
                        </div>
                    </div>
                    <!-- Blurred Image -->
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                         class="card-img-top object-fit-cover blur img-fluid" 
                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                         style="height: 100%; filter: blur(3px);"
                         loading="lazy"
                         onerror="this.src='../assets/images/genericBookCover.jpg';">
                <?php else: ?>
                    <!-- Clickable Link for Unlocked Books -->
                    <a href="view_pdf.php?id=<?php echo $book['id']; ?>" 
                       class="text-decoration-none book-link d-block h-100" 
                       data-book-id="<?php echo $book['id']; ?>">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                             class="card-img-top object-fit-cover img-fluid " 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             style=" height: 100%;"
                             loading="lazy"
                             onerror="this.src='../assets/images/genericBookCover.jpg';">
                    </a>
                <?php endif; ?>
            </div>

            <!-- Book Info -->
            <div class="card-body p-2 d-flex flex-column">
                <?php if ($book['status'] === 'active'): ?>
                <a href="view_pdf.php?id=<?php echo $book['id']; ?>" 
                   class="text-decoration-none flex-grow-1">
                <?php endif; ?>
                    <h6 class="card-title text-dark fw-semibold mb-1 lh-sm small" 
                        style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"
                        title="<?php echo htmlspecialchars($book['title']); ?>">
                        <?php echo htmlspecialchars($book['title']); ?>
                    </h6>
                    <p class="card-text text-muted mb-2" 
                       style="font-size: 0.75rem; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;"
                       title="<?php echo htmlspecialchars($book['author']); ?>">
                        <?php echo htmlspecialchars($book['author']); ?>
                    </p>
                <?php if ($book['status'] === 'active'): ?>
                </a>
                <?php endif; ?>
                
                <!-- Footer with Stats and Favorite Button -->
                <div class="d-flex justify-content-between align-items-center mt-auto">
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <i class="bi bi-eye me-1"></i>
                        <span class="view-count"><?php echo $book['view_count'] ?? 0; ?></span>
                    </small>
                    <a href="javascript:void(0);" 
                       class="favorite-link text-decoration-none d-flex align-items-center" 
                       onclick="toggleFavorite(this)"
                       data-book-id="<?php echo $book['id']; ?>"
                       <?php echo $book['status'] === 'locked' ? 'style="pointer-events: none; opacity: 0.5;"' : ''; ?>>
                        <i class="bi <?php echo $isFavorited ? 'bi-heart-fill text-danger' : 'bi-heart text-muted'; ?>"></i>
                        <span class="favorite-count ms-1 text-muted" style="font-size: 0.7rem;"><?php echo $book['favorite_count'] ?? 0; ?></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <!-- No books message -->
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
                    
                    <!-- No Results Message (Hidden by default) -->
                    <div id="noResultsMessage" class="text-center py-5 d-none">
                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 500px;">
                            <div class="card-body p-5">
                                <i class="bi bi-search display-1 text-muted mb-3"></i>
                                <h4 class="card-title text-dark">No Materials Found</h4>
                                <p class="card-text text-muted">No materials match your current search criteria. Try different search terms.</p>
                                <button type="button" class="btn btn-primary" onclick="clearAllFilters()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Reset Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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

<!-- Restricted Book Modal -->
<div class="modal fade" id="restrictedBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <i class="bi bi-lock-fill text-danger display-1 mb-4"></i>
                <h4 class="modal-title mb-3">Restricted Access</h4>
                <p class="text-muted mb-4">
                    This material is only accessible within the library premises. 
                    Please visit our library to access this content.
                </p>
            </div>
        </div>
    </div>
</div>


<?php include 'footer.php'; ?>
<script>
    // Global variables
    let searchTimeout;
    let currentMaterialFilter = 'all';
    let currentCourseFilter = 'all';
    const allBooks = document.querySelectorAll('.book-card');
    const allCategories = document.querySelectorAll('.material-category');

    // Expose filter function globally for the layout to use
    window.filterBooksByCategory = function(material, course) {
        currentMaterialFilter = material;
        currentCourseFilter = course;
        applyAllFilters();
    };

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page loaded - Total books: <?php echo $totalBooks; ?>');
        
        // Initialize favorite buttons
        initializeFavoriteButtons();
        
        // Initialize book view tracking
        initializeBookViewTracking();
        
        // Initialize search functionality
        initializeSearch();
        
        // Update initial count
        updateResultCount();
        
        // Check for URL parameters and apply filters
        checkUrlParameters();

        // Initialize sticky search behavior
        initializeStickySearch();
        
        // Initialize horizontal scroll to start from right
        initializeHorizontalScroll();
    });

    function initializeHorizontalScroll() {
        // Set all horizontal containers to start from the left (scroll position 0)
        document.querySelectorAll('.horizontal-books-container').forEach(container => {
            container.scrollLeft = 0;
        });
    }

    function scrollBooks(materialType, direction) {
        const container = document.getElementById('books-' + materialType);
        if (!container) return;
        
        const scrollAmount = 300; // Adjust scroll distance
        
        if (direction === 'left') {
            container.scrollLeft -= scrollAmount;
        } else {
            container.scrollLeft += scrollAmount;
        }
    }

    function checkUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        const material = urlParams.get('material');
        const course = urlParams.get('course');
        
        if (material || course) {
            currentMaterialFilter = material || 'all';
            currentCourseFilter = course || 'all';
            applyAllFilters();
        }
    }

    function initializeFavoriteButtons() {
        const favoriteButtons = document.querySelectorAll('.favorite-btn');
        favoriteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleFavorite(this);
            });
        });
    }

    function initializeBookViewTracking() {
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
    }

    function initializeSearch() {
        // Get both mobile and desktop elements
        const searchInputDesktop = document.getElementById('searchInput-desktop');
        const searchInputMobile = document.getElementById('searchInput-mobile');
        const clearButtonDesktop = document.getElementById('clearSearch-desktop');
        const clearButtonMobile = document.getElementById('clearSearch-mobile');

        // Function to handle search input
        function handleSearchInput(input) {
            clearTimeout(searchTimeout);
            showLoadingIndicator(true);
            showNoResultsMessage(false);
            
            // Sync the other input value
            if (input.id === 'searchInput-desktop') {
                searchInputMobile.value = input.value;
            } else {
                searchInputDesktop.value = input.value;
            }
            
            searchTimeout = setTimeout(() => {
                applyAllFilters();
            }, 300);
        }

        // Function to handle clear button
        function handleClearButton() {
            clearSearch();
        }

        // Add event listeners for desktop
        if (searchInputDesktop) {
            searchInputDesktop.addEventListener('input', function() {
                handleSearchInput(this);
            });
        }

        // Add event listeners for mobile
        if (searchInputMobile) {
            searchInputMobile.addEventListener('input', function() {
                handleSearchInput(this);
            });
        }

        // Add clear button handlers
        if (clearButtonDesktop) {
            clearButtonDesktop.addEventListener('click', handleClearButton);
        }
        if (clearButtonMobile) {
            clearButtonMobile.addEventListener('click', handleClearButton);
        }
    }

    function applyAllFilters() {
        const searchTermDesktop = document.getElementById('searchInput-desktop')?.value.toLowerCase().trim() || '';
        const searchTermMobile = document.getElementById('searchInput-mobile')?.value.toLowerCase().trim() || '';
        const searchTerm = searchTermDesktop || searchTermMobile;
        const searchInfo = document.getElementById('searchInfo');
        let visibleCount = 0;

        // Get all book cards
        const books = document.querySelectorAll('.book-card');
        
        books.forEach(book => {
            const title = book.dataset.title || '';
            const author = book.dataset.author || '';
            const material = book.dataset.material || '';
            const department = book.dataset.department || '';
            
            let shouldShow = true;

            // Apply search filter
            if (searchTerm) {
                shouldShow = title.includes(searchTerm) || author.includes(searchTerm);
            }

            // Apply material filter
            if (shouldShow && currentMaterialFilter !== 'all') {
                shouldShow = material === currentMaterialFilter;
            }

            // Apply department/course filter
            if (shouldShow && currentCourseFilter !== 'all') {
                shouldShow = department === currentCourseFilter;
            }

            // Show/hide the book
            if (shouldShow) {
                book.style.display = '';
                visibleCount++;
            } else {
                book.style.display = 'none';
            }
        });

        // Show/hide categories based on visible books
        document.querySelectorAll('.material-category').forEach(category => {
            const visibleBooksInCategory = category.querySelectorAll('.book-card[style=""]').length;
            category.style.display = visibleBooksInCategory > 0 ? '' : 'none';
            
            // Update category count
            const categoryCountElement = category.querySelector('.category-count');
            if (categoryCountElement) {
                const categoryName = category.dataset.material;
                const totalInCategory = category.querySelectorAll('.book-card').length;
                categoryCountElement.textContent = `${visibleBooksInCategory} of ${totalInCategory} book${totalInCategory !== 1 ? 's' : ''}`;
            }
        });

        // Update search info visibility
        if (searchTerm || currentMaterialFilter !== 'all' || currentCourseFilter !== 'all') {
            searchInfo.classList.remove('d-none');
            let searchText = '';
            if (searchTerm) {
                searchText = `Search results for: "${searchTerm}"`;
            } else {
                searchText = 'Filtered results';
            }
            document.getElementById('searchText').textContent = searchText;
        } else {
            searchInfo.classList.add('d-none');
        }

        // Update result count
        document.getElementById('resultCount').textContent = `${visibleCount} book${visibleCount !== 1 ? 's' : ''} found`;

        // Show/hide no results message
        showNoResultsMessage(visibleCount === 0);
        showLoadingIndicator(false);
    }

    function updateResultCount(count = null) {
        const resultCountElement = document.getElementById('resultCount');
        if (!resultCountElement) return;
        
        if (count === null) {
            count = document.querySelectorAll('.book-card:not([style*="display: none"])').length;
        }
        
        resultCountElement.textContent = `${count} book${count !== 1 ? 's' : ''} found`;
    }

    function showNoResultsMessage(show) {
        const noResultsMessage = document.getElementById('noResultsMessage');
        const booksContainer = document.getElementById('booksContainer');
        
        if (show) {
            noResultsMessage.classList.remove('d-none');
            booksContainer.classList.add('d-none');
        } else {
            noResultsMessage.classList.add('d-none');
            booksContainer.classList.remove('d-none');
        }
    }

    function showLoadingIndicator(show) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const booksContainer = document.getElementById('booksContainer');
        
        if (show) {
            loadingIndicator.classList.remove('d-none');
            booksContainer.classList.add('d-none');
        } else {
            loadingIndicator.classList.add('d-none');
            booksContainer.classList.remove('d-none');
        }
    }

    function clearSearch() {
        const desktopInput = document.getElementById('searchInput-desktop');
        const mobileInput = document.getElementById('searchInput-mobile');
        
        if (desktopInput) desktopInput.value = '';
        if (mobileInput) mobileInput.value = '';
        
        applyAllFilters();
    }

    function clearAllFilters() {
        const desktopInput = document.getElementById('searchInput-desktop');
        const mobileInput = document.getElementById('searchInput-mobile');
        
        if (desktopInput) desktopInput.value = '';
        if (mobileInput) mobileInput.value = '';
        
        currentMaterialFilter = 'all';
        currentCourseFilter = 'all';
        applyAllFilters();
        
        // Clear URL parameters
        const url = new URL(window.location);
        url.searchParams.delete('material');
        url.searchParams.delete('course');
        window.history.replaceState(null, '', url);
    }

    // Toggle favorite function
    function toggleFavorite(link) {
        const bookId = link.getAttribute('data-book-id');
        const heartIcon = link.querySelector('i');
        
        // Disable link temporarily
        link.style.pointerEvents = 'none';
        
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
                    link.title = 'Remove from favorites';
                } else {
                    heartIcon.className = 'bi bi-heart text-muted';
                    link.title = 'Add to favorites';
                }
                
                // Update favorite count
                const favoriteCountElements = document.querySelectorAll(`[data-book-id="${bookId}"] .favorite-count`);
                favoriteCountElements.forEach(element => {
                    if (element) {
                        element.textContent = data.favorite_count;
                    }
                });
                
                showToast(data.message, 'success');
            } else {
                showToast(data.message, 'warning');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error occurred while updating favorites', 'error');
        })
        .finally(() => {
            // Re-enable link
            link.style.pointerEvents = 'auto';
        });
    }

    // Show toast notification
    function showToast(message, type = 'info') {
        const toastElement = document.getElementById('favoriteToast');
        const toastMessage = document.getElementById('toastMessage');
        
        if (!toastElement || !toastMessage) return;
        
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

    // Show restricted modal
    function showRestrictedModal() {
        const modal = new bootstrap.Modal(document.getElementById('restrictedBookModal'));
        modal.show();
    }

    // Initialize sticky search behavior
    function initializeStickySearch() {
        if (window.innerWidth < 768) { // Mobile only
            let lastScrollTop = 0;
            const searchWrapper = document.querySelector('.sticky-search-wrapper');
            
            if (searchWrapper) {
                window.addEventListener('scroll', function() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    
                    if (scrollTop > lastScrollTop) {
                        // Scrolling down
                        searchWrapper.style.transform = 'translateY(-100%)';
                        searchWrapper.style.transition = 'transform 0.3s ease-in-out';
                    } else {
                        // Scrolling up
                        searchWrapper.style.transform = 'translateY(0)';
                        searchWrapper.style.transition = 'transform 0.3s ease-in-out';
                    }
                    
                    lastScrollTop = scrollTop;
                });
            }
        }
    }

    // Add smooth scrolling behavior for horizontal containers
    document.addEventListener('DOMContentLoaded', function() {
        // Add wheel event listener for horizontal scrolling
        document.querySelectorAll('.horizontal-books-container').forEach(container => {
            container.addEventListener('wheel', function(e) {
                if (e.deltaY !== 0) {
                    e.preventDefault();
                    container.scrollLeft += e.deltaY;
                }
            });
        });
    });
</script>