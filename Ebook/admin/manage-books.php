<?php
require_once '../includes/db.php';


// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$type_material = isset($_GET['type_material']) ? $_GET['type_material'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$view = isset($_GET['view']) ? $_GET['view'] : 'active'; // active, deleted, all

// Build query based on view
$whereConditions = [];
$params = [];

if ($view === 'active') {
    $whereConditions[] = "is_deleted = 0";
} elseif ($view === 'deleted') {
    $whereConditions[] = "is_deleted = 1";
}
// For 'all' view, no condition needed

// Add search conditions
if (!empty($search)) {
    $whereConditions[] = "(title LIKE ? OR author LIKE ? OR isbn_issn LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if (!empty($department)) {
    $whereConditions[] = "department = ?";
    $params[] = $department;
}

if (!empty($type_material)) {
    $whereConditions[] = "type_of_material = ?";
    $params[] = $type_material;
}

if (!empty($status)) {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

try {
    // Main query for books
    $query = "SELECT * FROM books";
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistics queries
    $stats = [];
    
    // Total books (active only)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE is_deleted = 0");
    $stmt->execute();
    $stats['total_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Books by department with totals
    $stmt = $pdo->prepare("SELECT department, COUNT(*) as count FROM books WHERE is_deleted = 0 AND department IS NOT NULL AND department != '' GROUP BY department ORDER BY count DESC");
    $stmt->execute();
    $stats['by_department'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total books across all departments (for the card)
    $total_department_books = 0;
    foreach ($stats['by_department'] as $dept) {
        $total_department_books += $dept['count'];
    }
    $stats['total_department_books'] = $total_department_books;
    
    // Books by material type
    $stmt = $pdo->prepare("SELECT type_of_material, COUNT(*) as count FROM books WHERE is_deleted = 0 AND type_of_material IS NOT NULL AND type_of_material != '' GROUP BY type_of_material ORDER BY count DESC");
    $stmt->execute();
    $stats['by_material'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Status distribution
    $stmt = $pdo->prepare("SELECT 
        SUM(CASE WHEN status = 'active' OR status IS NULL THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'locked' THEN 1 ELSE 0 END) as locked
        FROM books WHERE is_deleted = 0");
    $stmt->execute();
    $stats['by_status'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Deleted books count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE is_deleted = 1");
    $stmt->execute();
    $stats['deleted_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 text-primary fw-bold">
        <i class="bi bi-collection me-2"></i>Library Management
    </h2>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#statisticsModal">
            <i class="bi bi-graph-up me-1"></i>Statistics
        </button>
        <a href="?page=add_books" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Add Book
        </a>
    </div>
</div>

<!-- Statistics Cards Row -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white text-center h-100">
            <div class="card-body">
                <i class="bi bi-book fs-1 mb-2"></i>
                <h3 class="fw-bold"><?php echo number_format($stats['total_books']); ?></h3>
                <p class="mb-0">Total Books</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white text-center h-100">
            <div class="card-body">
                <i class="bi bi-building fs-1 mb-2"></i>
                <h3 class="fw-bold"><?php echo number_format($stats['total_department_books']); ?></h3>
                <p class="mb-0">Books by Department</p>
                <small class="opacity-75"><?php echo count($stats['by_department']); ?> departments</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white text-center h-100">
            <div class="card-body">
                <i class="bi bi-collection fs-1 mb-2"></i>
                <h3 class="fw-bold"><?php echo count($stats['by_material']); ?></h3>
                <p class="mb-0">Material Types</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark text-center h-100">
            <div class="card-body">
                <i class="bi bi-trash fs-1 mb-2"></i>
                <h3 class="fw-bold"><?php echo number_format($stats['deleted_books']); ?></h3>
                <p class="mb-0">Deleted Books</p>
            </div>
        </div>
    </div>
</div>

<!-- View Tabs -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $view === 'active' ? 'active' : ''; ?>" href="#" onclick="changeView('active')">
            <i class="bi bi-check-circle me-1"></i>Active Books (<?php echo $stats['total_books']; ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $view === 'deleted' ? 'active' : ''; ?>" href="#" onclick="changeView('deleted')">
            <i class="bi bi-trash me-1"></i>Deleted Books (<?php echo $stats['deleted_books']; ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $view === 'all' ? 'active' : ''; ?>" href="#" onclick="changeView('all')">
            <i class="bi bi-list me-1"></i>All Books
        </a>
    </li>
</ul>

<!-- Filters Section -->
<div class="card bg-light mb-4">
    <div class="card-body">
        <h5 class="card-title"><i class="bi bi-funnel me-2"></i>Filters</h5>
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="manage-books">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search books..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            
            <div class="col-md-2">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    <option value="BSIT" <?php echo $department === 'BSIT' ? 'selected' : ''; ?>>BSIT</option>
                    <option value="BSED" <?php echo $department === 'BSED' ? 'selected' : ''; ?>>BSED</option>
                    <option value="BSAB" <?php echo $department === 'BSAB' ? 'selected' : ''; ?>>BSAB</option>
                    <option value="BSCRIM" <?php echo $department === 'BSCRIM' ? 'selected' : ''; ?>>BSCRIM</option>
                    <option value="BSA" <?php echo $department === 'BSA' ? 'selected' : ''; ?>>BSA</option>
                    <option value="FISHERS" <?php echo $department === 'FISHERS' ? 'selected' : ''; ?>>FISHERS</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="type_material" class="form-select">
                    <option value="">All Types</option>
                    <option value="Book" <?php echo $type_material === 'Book' ? 'selected' : ''; ?>>Book</option>
                    <option value="Journal" <?php echo $type_material === 'Journal' ? 'selected' : ''; ?>>Journal</option>
                    <option value="Magazine" <?php echo $type_material === 'Magazine' ? 'selected' : ''; ?>>Magazine</option>
                    <option value="Newspaper" <?php echo $type_material === 'Newspaper' ? 'selected' : ''; ?>>Newspaper</option>
                    <option value="Reference" <?php echo $type_material === 'Reference' ? 'selected' : ''; ?>>Reference</option>
                    <option value="Thesis" <?php echo $type_material === 'Thesis' ? 'selected' : ''; ?>>Thesis</option>
                    <option value="Other" <?php echo $type_material === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="locked" <?php echo $status === 'locked' ? 'selected' : ''; ?>>Locked</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="?page=manage-books&view=<?php echo htmlspecialchars($view); ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Help Text for Double Click -->
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Tip:</strong> Double-click on any book card to view detailed information. Use the dropdown menu for quick actions.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

<!-- Books Grid -->
<?php if (!empty($books)): ?>
<div class="row">
    <?php foreach ($books as $book): ?>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 shadow-sm position-relative book-card" 
             data-book='<?php echo htmlspecialchars(json_encode($book)); ?>'>
            
            <!-- Action Dropdown - Fixed positioning -->
            <div class="position-absolute top-0 end-0 m-2" style="z-index: 1000;">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle shadow-sm" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            data-bs-auto-close="outside"
                            onclick="event.stopPropagation()">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <?php if ($view !== 'deleted'): ?>
                            <li>
                                <a href="?page=edit_book&id=<?php echo $book['id']; ?>" 
                                   class="dropdown-item"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-pencil me-2"></i>Edit
                                </a>
                            </li>
                            
                            <?php if (!isset($book['status']) || $book['status'] !== 'locked'): ?>
                            <li>
                                <form method="POST" class="d-inline" onclick="event.stopPropagation()">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <input type="hidden" name="action" value="lock">
                                    <button type="submit" class="dropdown-item text-warning">
                                        <i class="bi bi-lock me-2"></i>Lock
                                    </button>
                                </form>
                            </li>
                            <?php else: ?>
                            <li>
                                <form method="POST" class="d-inline" onclick="event.stopPropagation()">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <input type="hidden" name="action" value="unlock">
                                    <button type="submit" class="dropdown-item text-success">
                                        <i class="bi bi-unlock me-2"></i>Unlock
                                    </button>
                                </form>
                            </li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
<li>
    <button type="button" class="dropdown-item text-danger" 
            onclick="event.stopPropagation(); showDeleteModal(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">
        <i class="bi bi-trash me-2"></i>Move to Trash
    </button>
</li>
                        <?php else: ?>
                            <li>
                                <form method="POST" class="d-inline" onclick="event.stopPropagation()">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <button type="submit" class="dropdown-item text-success">
                                        <i class="bi bi-arrow-clockwise me-2"></i>Restore
                                    </button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
<li>
    <button type="button" class="dropdown-item text-danger" 
            onclick="event.stopPropagation(); showPermanentDeleteModal(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>')">
        <i class="bi bi-x-circle me-2"></i>Delete Permanently
    </button>
</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Book Cover -->
            <div class="text-center pt-3">
                <?php 
                $coverImage = "../assets/images/genericBookCover.jpg"; // Default image
                if (!empty($book['cover_image'])) {
                    $uploadPath = "../uploads/covers/" . $book['cover_image'];
                    if (file_exists($uploadPath)) {
                        $coverImage = $uploadPath;
                    }
                }
                ?>
                <img src="<?php echo htmlspecialchars($coverImage); ?>" 
                     class="img-fluid rounded" 
                     alt="<?php echo htmlspecialchars($book['title']); ?>"
                     style="height: 200px; width: 150px; object-fit: cover;"
                     onerror="this.src='../assets/images/genericBookCover.jpg'">
            </div>
            
            <div class="card-body">
                <h5 class="card-title fw-bold text-primary mb-2" style="height: 48px; overflow: hidden;">
                    <?php echo htmlspecialchars($book['title']); ?>
                </h5>
                <p class="text-muted mb-2">
                    <i class="bi bi-person me-1"></i>
                    <?php echo htmlspecialchars($book['author'] ?: 'N/A'); ?>
                </p>
                
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">
                        <i class="bi bi-building me-1"></i>
                        <?php echo htmlspecialchars($book['department'] ?: 'N/A'); ?>
                    </small>
                    <span class="badge bg-info">
                        <?php echo htmlspecialchars($book['type_of_material'] ?: 'N/A'); ?>
                    </span>
                </div>
                
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        <i class="bi bi-stack me-1"></i>
                        <?php echo $book['copies']; ?> copies
                    </small>
                    
                    <!-- Status Badges -->
                    <div>
                        <?php if ($book['is_deleted']): ?>
                            <span class="badge bg-danger">Deleted</span>
                        <?php elseif (isset($book['status']) && $book['status'] === 'locked'): ?>
                            <span class="badge bg-warning">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success">Active</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-transparent">
                <small class="text-muted">
                    <i class="bi bi-calendar me-1"></i>
                    Added: <?php echo date('M d, Y', strtotime($book['created_at'])); ?>
                </small>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<!-- Empty State -->
<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-journal-x display-1 text-muted"></i>
    </div>
    <h4 class="text-muted mb-3">No Books Found</h4>
    <p class="text-muted mb-4">
        <?php if (!empty($search) || !empty($department) || !empty($type_material) || !empty($status)): ?>
            No books match your current filter criteria.
        <?php elseif ($view === 'deleted'): ?>
            No deleted books to show.
        <?php else: ?>
            Your library is empty. Start by adding some books.
        <?php endif; ?>
    </p>
    <div class="d-flex justify-content-center gap-2">
        <?php if (!empty($search) || !empty($department) || !empty($type_material) || !empty($status)): ?>
            <a href="?page=manage-books&view=<?php echo htmlspecialchars($view); ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-clockwise me-2"></i>Clear Filters
            </a>
        <?php endif; ?>
        <?php if ($view !== 'deleted'): ?>
            <a href="?page=add_books" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>Add Your First Book
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Book Details Modal -->
<div class="modal fade" id="bookDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Book Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <img id="modal-cover" src="../assets/images/genericBookCover.jpg" 
                             class="img-fluid rounded mb-3" 
                             style="max-height: 300px;"
                             onerror="this.src='../assets/images/genericBookCover.jpg'">
                    </div>
                    <div class="col-md-8">
                        <table class="table table-borderless">
                            <tr><th>Title:</th><td id="modal-title"></td></tr>
                            <tr><th>Author:</th><td id="modal-author"></td></tr>
                            <tr><th>Publisher:</th><td id="modal-publisher"></td></tr>
                            <tr><th>Publication Date:</th><td id="modal-date"></td></tr>
                            <tr><th>Edition:</th><td id="modal-edition"></td></tr>
                            <tr><th>ISBN/ISSN:</th><td id="modal-isbn"></td></tr>
                            <tr><th>Department:</th><td id="modal-department"></td></tr>
                            <tr><th>Material Type:</th><td id="modal-type"></td></tr>
                            <tr><th>Copies:</th><td id="modal-copies"></td></tr>
                            <tr><th>Classification:</th><td id="modal-classification"></td></tr>
                            <tr><th>Call Number:</th><td id="modal-call-number"></td></tr>
                            <tr><th>Accession Number:</th><td id="modal-accession"></td></tr>
                        </table>
                        <div class="mt-3">
                            <h6>Description:</h6>
                            <p id="modal-description" class="text-muted"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="modal-edit-btn" href="#" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit Book
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div class="modal fade" id="statisticsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-graph-up me-2"></i>Library Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Books by Department</h6>
                        <div class="list-group list-group-flush">
                            <?php if (!empty($stats['by_department'])): ?>
                                <?php foreach ($stats['by_department'] as $dept): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo number_format($dept['count']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="list-group-item text-muted text-center">No department data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Books by Material Type</h6>
                        <div class="list-group list-group-flush">
                            <?php if (!empty($stats['by_material'])): ?>
                                <?php foreach ($stats['by_material'] as $material): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($material['type_of_material']); ?>
                                    <span class="badge bg-info rounded-pill"><?php echo number_format($material['count']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="list-group-item text-muted text-center">No material type data available</div>
                            <?php endif; ?>
                        </div>
                        
                        <h6 class="fw-bold mt-4">Status Distribution</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Active Books
                                <span class="badge bg-success rounded-pill"><?php echo number_format($stats['by_status']['active']); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Locked Books
                                <span class="badge bg-warning rounded-pill"><?php echo number_format($stats['by_status']['locked']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirm Move to Trash
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to move this book to trash?</p>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Book:</strong> <span id="delete-book-title"></span><br>
                    <small class="text-muted">You can restore this book later from the Deleted Books section.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <form method="POST" class="d-inline" id="deleteForm">
                    <input type="hidden" name="book_id" id="delete-book-id">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Move to Trash
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Permanent Delete Confirmation Modal -->
<div class="modal fade" id="permanentDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Permanent Delete Warning
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Are you sure you want to <strong>permanently delete</strong> this book?</p>
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Book:</strong> <span id="permanent-delete-book-title"></span><br>
                    <small><strong>Warning:</strong> This action cannot be undone! The book will be completely removed from the system.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <form method="POST" class="d-inline" id="permanentDeleteForm">
                    <input type="hidden" name="book_id" id="permanent-delete-book-id">
                    <input type="hidden" name="action" value="permanent_delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
// Function to change view
function changeView(view) {
    const url = new URL(window.location);
    url.searchParams.set('view', view);
    url.searchParams.delete('search');
    url.searchParams.delete('department');
    url.searchParams.delete('type_material');
    url.searchParams.delete('status');
    window.location.href = url.toString();
}

// Function to show book details in modal
function showBookDetails(book) {
    // Set cover image with fallback
    let coverSrc = '../assets/images/genericBookCover.jpg'; // Default fallback
    
    if (book.cover_image && book.cover_image !== '') {
        coverSrc = `../uploads/covers/${book.cover_image}`;
    }
    
    const modalCover = document.getElementById('modal-cover');
    modalCover.src = coverSrc;
    
    // Helper function to display value or N/A
    function displayValue(value) {
        return value && value !== '' ? value : 'N/A';
    }
    
    // Fill modal with book data
    document.getElementById('modal-title').textContent = displayValue(book.title);
    document.getElementById('modal-author').textContent = displayValue(book.author);
    document.getElementById('modal-publisher').textContent = displayValue(book.publisher);
    document.getElementById('modal-date').textContent = displayValue(book.date_of_publication);
    document.getElementById('modal-edition').textContent = displayValue(book.edition);
    document.getElementById('modal-isbn').textContent = displayValue(book.isbn_issn);
    document.getElementById('modal-department').textContent = displayValue(book.department);
    document.getElementById('modal-type').textContent = displayValue(book.type_of_material);
    document.getElementById('modal-copies').textContent = book.copies || '0';
    document.getElementById('modal-classification').textContent = displayValue(book.classification_number);
    document.getElementById('modal-call-number').textContent = displayValue(book.call_number);
    document.getElementById('modal-accession').textContent = displayValue(book.accession_number);
    document.getElementById('modal-description').textContent = displayValue(book.description);
    
    // Set edit button link
    document.getElementById('modal-edit-btn').href = `?page=edit_book&id=${book.id}`;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('bookDetailsModal'));
    modal.show();
}

// Function to handle undo delete
function undoDelete(bookId) {
    if (confirm('Are you sure you want to restore this book?')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="book_id" value="${bookId}">
            <input type="hidden" name="action" value="restore">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Add double-click event listeners and hover effects to cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.book-card');
    
    cards.forEach(card => {
        // Add double-click event for modal
        card.addEventListener('dblclick', function(e) {
            e.preventDefault();
            const bookData = JSON.parse(this.getAttribute('data-book'));
            showBookDetails(bookData);
        });
        
        // Add hover effects using Bootstrap classes
        card.addEventListener('mouseenter', function() {
            this.classList.add('shadow');
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'all 0.2s ease-in-out';
        });
        
        card.addEventListener('mouseleave', function() {
            this.classList.remove('shadow');
            this.classList.add('shadow-sm');
            this.style.transform = 'translateY(0)';
        });
        
        // Prevent single click from doing anything except for interactive elements
        card.addEventListener('click', function(e) {
            // Only prevent default if it's not a dropdown, form element, or link
            if (!e.target.closest('.dropdown') && 
                !e.target.closest('form') && 
                !e.target.closest('a') && 
                !e.target.closest('button')) {
                e.preventDefault();
            }
        });
    });
    
    // Handle dropdown click events to prevent card interaction
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
    
    // Handle image load errors
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            if (this.src !== '../assets/images/genericBookCover.jpg') {
                this.src = '../assets/images/genericBookCover.jpg';
            }
        });
    });
});

// Initialize tooltips if Bootstrap tooltips are available
if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Handle form submissions with confirmation
document.querySelectorAll('form[onsubmit]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.stopPropagation();
    });
});

// Prevent dropdown from closing when clicking inside
document.querySelectorAll('.dropdown-menu').forEach(menu => {
    menu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
});

// Function to show delete confirmation modal
function showDeleteModal(bookId, bookTitle) {
    document.getElementById('delete-book-id').value = bookId;
    document.getElementById('delete-book-title').textContent = bookTitle;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    modal.show();
}

// Function to show permanent delete confirmation modal
function showPermanentDeleteModal(bookId, bookTitle) {
    document.getElementById('permanent-delete-book-id').value = bookId;
    document.getElementById('permanent-delete-book-title').textContent = bookTitle;
    
    const modal = new bootstrap.Modal(document.getElementById('permanentDeleteModal'));
    modal.show();
}
</script>