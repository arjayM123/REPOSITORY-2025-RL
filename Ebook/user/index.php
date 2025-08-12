<?php
require_once '../includes/db.php';

// Set page configuration
$pageTitle = 'Home - ISUR-ORA Digital Library';

// Get total counts for statistics
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$totalBooksResult = $conn->query($totalBooksQuery);
$totalBooks = $totalBooksResult->fetch_assoc()['total'] ?? 0;

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
                // Get all books with their information
                $allBooksQuery = "
                    SELECT id, title, author, cover_image, type_of_material, department, created_at 
                    FROM books 
                    ORDER BY created_at DESC
                ";
                $allBooksResult = $conn->query($allBooksQuery);
                $allBooks = [];
                
                if ($allBooksResult && $allBooksResult->num_rows > 0) {
                    while ($book = $allBooksResult->fetch_assoc()) {
                        $allBooks[] = $book;
                    }
                }

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
                                    ?>
                                    <div class="col-6 col-md-4 col-lg-2 book-card" data-department="<?php echo htmlspecialchars($book['department'] ?? ''); ?>">
                                        <div class="card h-100 shadow-sm border-0">
                                            <a href="view_pdf.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">
                                                <!-- Book Cover with Bootstrap ratio -->
                                                <div class="ratio ratio-4x3 bg-light">
                                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                                         class="card-img-top object-fit-cover rounded-top"
                                                         alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                         loading="lazy"
                                                         onerror="this.src='../assets/images/genericBookCover.jpg';">
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
                                                        <small class="text-primary fw-medium">
                                                            <?php echo htmlspecialchars($book['department']); ?>
                                                        </small>
                                                    <?php endif; ?>
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

<!-- Pure Bootstrap JS only -->
<script>
    // Simple counter animation using vanilla JS
    document.addEventListener('DOMContentLoaded', function() {
        // Define counter targets from PHP
        const counters = [
            { element: document.getElementById('totalBooksCounter'), target: <?php echo $totalBooks; ?> },
            { element: document.getElementById('totalVisitorsCounter'), target: <?php echo $totalVisitors; ?> },
            { element: document.getElementById('materialTypesCounter'), target: <?php echo $materialTypesCount; ?> }
        ];

        // Simple counter animation
        counters.forEach(counter => {
            let current = 0;
            const increment = Math.ceil(counter.target / 50);
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= counter.target) {
                    counter.element.textContent = counter.target.toLocaleString();
                    clearInterval(timer);
                } else {
                    counter.element.textContent = current.toLocaleString();
                }
            }, 50);
        });
    });

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
        
        // Update filter info using Bootstrap classes
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

<?php
// Close database connection
$conn->close();
?>