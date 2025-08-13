<?php
require_once '../includes/db.php';

// Get filter parameters
$materialFilter = $_GET['material'] ?? 'all';
$departmentFilter = $_GET['department'] ?? 'all';

// Set page title based on filter
$pageTitle = 'Books';
if ($materialFilter !== 'all') {
    $pageTitle = ucfirst($materialFilter) . ' - ISUR-ORA Digital Library';
}

// Build query based on filters
$whereClause = "WHERE 1=1";
$params = [];

if ($materialFilter !== 'all') {
    $whereClause .= " AND type_of_material = ?";
    $params[] = $materialFilter;
}

if ($departmentFilter !== 'all') {
    $whereClause .= " AND department = ?";
    $params[] = $departmentFilter;
}

// Get filtered books
$booksQuery = "
    SELECT id, title, author, cover_image, type_of_material, department, created_at 
    FROM books 
    $whereClause
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($booksQuery);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Include header
include "_layout.php";
?>

<div style="padding-top: 80px;">
    <section class="py-5 bg-light">
        <div class="container">
            <!-- Page Header -->
            <div class="mb-4">
                <h2 class="h3 fw-bold text-dark">
                    <?php if ($materialFilter !== 'all'): ?>
                        <?php echo htmlspecialchars(ucfirst($materialFilter)); ?> Materials
                    <?php else: ?>
                        All Books
                    <?php endif; ?>
                </h2>
            </div>

            <!-- Back Button -->
            <div class="mb-4">
                <a href="index.php" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Home
                </a>
            </div>

            <!-- Books Grid -->
            <div class="row g-3">
                <?php foreach ($books as $book): ?>
                    <?php
                    // Determine image path
                    if (empty($book['cover_image']) || $book['cover_image'] === 'genericBookCover.jpg') {
                        $imagePath = '../assets/images/genericBookCover.jpg';
                    } else {
                        $imagePath = '../uploads/covers/' . $book['cover_image'];
                    }
                    ?>
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="card h-100 shadow-sm border-0">
                            <a href="view_pdf.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">
                                <div class=" bg-light">
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>"
                                         class="img-fluid "
                                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                         loading="lazy"
                                         onerror="this.src='../assets/images/genericBookCover.jpg';">
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="card-title text-dark fw-semibold mb-2 lh-sm">
                                        <?php echo htmlspecialchars($book['title']); ?>
                                    </h6>
                                    <p class="card-text text-muted mb-2 small">
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
                <?php endforeach; ?>
            </div>

            <?php if (empty($books)): ?>
                <div class="text-center py-5">
                    <div class="card border-0 shadow-sm mx-auto" style="max-width: 500px;">
                        <div class="card-body p-5">
                            <i class="bi bi-search display-1 text-muted mb-3"></i>
                            <h4 class="card-title text-dark">No Books Found</h4>
                            <p class="card-text text-muted">No books match your current criteria.</p>
                            <a href="index.php" class="btn btn-primary">Back to Home</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php
// OPTION 3: JavaScript function to expand categories in the same page
// Add this JavaScript function to your existing script section:
?>

<script>
// Add this function to your existing JavaScript
function showAllInCategory(materialType) {
    const category = document.querySelector(`[data-material="${materialType}"]`);
    const booksGrid = category.querySelector('.books-grid');
    const viewAllBtn = category.querySelector('.view-all-btn');
    
    // Get all books for this material type from PHP (you'll need to make this data available)
    // For now, we'll show a simple approach - redirect to books.php
    window.location.href = `books.php?material=${encodeURIComponent(materialType)}`;
}

// Alternative: Show more books in the same page (if you have the data)
function expandCategory(materialType) {
    const category = document.querySelector(`[data-material="${materialType}"]`);
    const booksGrid = category.querySelector('.books-grid');
    const viewAllBtn = category.querySelector('.view-all-btn');
    const btnText = viewAllBtn.querySelector('.btn-text');
    
    // Toggle between showing 6 and all books
    const allBooks = booksGrid.querySelectorAll('.book-card');
    const hiddenBooks = Array.from(allBooks).slice(6);
    
    if (btnText.textContent === 'View All') {
        // Show all books
        hiddenBooks.forEach(book => book.style.display = 'block');
        btnText.textContent = 'Show Less';
        viewAllBtn.querySelector('i').className = 'bi bi-arrow-up ms-1';
    } else {
        // Show only first 6 books
        hiddenBooks.forEach(book => book.style.display = 'none');
        btnText.textContent = 'View All';
        viewAllBtn.querySelector('i').className = 'bi bi-arrow-right ms-1';
    }
}
</script>