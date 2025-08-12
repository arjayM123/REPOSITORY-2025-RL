<?php
session_start();

// Hard-coded admin session for now (remove when login system is ready)
$_SESSION['is_admin'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['username'] = 'admin';

require_once '../includes/db.php';

// Handle POST actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $bookId = $_POST['book_id'] ?? '';
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'delete':
                // SOFT DELETE - move to trash
                $stmt = $pdo->prepare("UPDATE books SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$bookId]);
                $_SESSION['success'] = "Book moved to trash successfully. <button type='button' class='btn btn-link p-0 text-decoration-none' onclick='undoDelete({$bookId})'>Undo</button>";
                break;
            case 'restore':
                $stmt = $pdo->prepare("UPDATE books SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
                $stmt->execute([$bookId]);
                $_SESSION['success'] = "Book restored successfully.";
                break;
            case 'permanent_delete':
                // Get book info before deleting
                $stmt = $pdo->prepare("SELECT title, cover_image FROM books WHERE id = ? AND is_deleted = 1");
                $stmt->execute([$bookId]);
                $book = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($book) {
                    // Delete cover image if exists
                    if (!empty($book['cover_image'])) {
                        $coverPath = "../uploads/covers/" . $book['cover_image'];
                        if (file_exists($coverPath)) {
                            unlink($coverPath);
                        }
                    }
                    
                    // PERMANENT DELETE from database
                    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ? AND is_deleted = 1");
                    $stmt->execute([$bookId]);
                    $_SESSION['success'] = "Book '{$book['title']}' has been permanently deleted.";
                }
                break;
            case 'lock':
                $stmt = $pdo->prepare("UPDATE books SET status = 'locked' WHERE id = ?");
                $stmt->execute([$bookId]);
                $_SESSION['success'] = "Book locked successfully.";
                break;
            case 'unlock':
                $stmt = $pdo->prepare("UPDATE books SET status = 'active' WHERE id = ?");
                $stmt->execute([$bookId]);
                $_SESSION['success'] = "Book unlocked successfully.";
                break;
        }
        
        // Redirect to prevent form resubmission
        $redirectPage = isset($_GET['page']) ? $_GET['page'] : 'manage-books';
        $redirectView = isset($_GET['view']) ? "&view=" . $_GET['view'] : "";
        header("Location: ?page=" . $redirectPage . $redirectView);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Get the page parameter from URL, default to 'dashboard'
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Define allowed pages for security
$allowed_pages = ['dashboard', 'add_books', 'manage-books', 'reports', 'book-list', 'edit_book'];

// Validate the page parameter
if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin ORA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="position-fixed bg-dark min-vh-100" style="width: 280px; left: 0; top: 0;">
            <div class="d-flex flex-column h-100">
                    <!-- Logo and Title -->
                    <div class="text-center py-4 border-bottom border-secondary">
                        <img src="../assets/images/images-removebg-preview.png" alt="Logo" class="img-fluid mb-2" style="max-height: 60px;">
                        <h4 class="text-white fw-bold mb-0">Admin ORA</h4>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <nav class="nav flex-column py-3 flex-grow-1">
                        <!-- Dashboard -->
                        <a class="nav-link text-white py-3 px-4 d-flex align-items-center <?php echo ($page == 'dashboard') ? 'bg-primary' : ''; ?>" href="?page=dashboard" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                            <i class="bi bi-speedometer2 me-3"></i>
                            <span>Dashboard</span>
                        </a>
                        
                        <!-- Cataloging Dropdown -->
                        <div class="nav-item">
                            <a class="nav-link text-white py-3 px-4 d-flex align-items-center" data-bs-toggle="collapse" href="#catalogingMenu" role="button" aria-expanded="false" aria-controls="catalogingMenu">
                                <i class="bi bi-book me-3"></i>
                                <span class="flex-grow-1">Cataloging</span>
                                <i class="bi bi-chevron-down"></i>
                            </a>
                            <div class="collapse" id="catalogingMenu">
                                <div class="bg-secondary bg-opacity-25">
                                    <a class="nav-link text-white-50 py-2 px-5 d-flex align-items-center" href="?page=add_books">
                                        <i class="bi bi-plus-circle me-3"></i>
                                        <span>Create New Book</span>
                                    </a>
                                    <a class="nav-link text-white-50 py-2 px-5 d-flex align-items-center" href="?page=manage-books">
                                        <i class="bi bi-gear me-3"></i>
                                        <span>Manage Books</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reports -->
                        <a class="nav-link text-white py-3 px-4 d-flex align-items-center <?php echo ($page == 'reports') ? 'bg-primary' : ''; ?>" href="?page=reports" data-bs-toggle="tooltip" data-bs-placement="right" title="Reports">
                            <i class="bi bi-graph-up me-3"></i>
                            <span>Reports</span>
                        </a>
                        
                        <!-- Book List -->
                        <a class="nav-link text-white py-3 px-4 d-flex align-items-center <?php echo ($page == 'book-list') ? 'bg-primary' : ''; ?>" href="?page=book-list" data-bs-toggle="tooltip" data-bs-placement="right" title="Book List">
                            <i class="bi bi-list-ul me-3"></i>
                            <span>Book List</span>
                        </a>
                    </nav>
                    
                    <!-- Logout at Bottom -->
                    <div class="mt-auto border-top border-secondary">
                        <a class="nav-link text-danger py-3 px-4 d-flex align-items-center" href="#" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                            <i class="bi bi-box-arrow-right me-3"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="flex-grow-1" style="margin-left: 280px;">
                
                <!-- Content Area -->
                <main class="p-4">
                    <div class="bg-white rounded shadow-sm p-4">
                        <?php
                        // Include the appropriate page based on the parameter
                        switch($page) {
                            case 'dashboard':
                                if(file_exists('dashboard.php')) {
                                    include 'dashboard.php';
                                } else {
                                    echo '<div class="text-center text-muted py-5">';
                                    echo '<i class="bi bi-exclamation-triangle display-1"></i>';
                                    echo '<h3 class="mt-3">Dashboard file not found</h3>';
                                    echo '<p>Please make sure dashboard.php exists in the same directory.</p>';
                                    echo '</div>';
                                }
                                break;
                            case 'add_books':
                                if(file_exists('add_books.php')) {
                                    include 'add_books.php';
                                } else {
                                    echo '<div class="text-center text-muted py-5">';
                                    echo '<h3>Add New Book</h3>';
                                    echo '<p>Add books page not found.</p>';
                                    echo '</div>';
                                }
                                break;
                            case 'manage-books':
                                if(file_exists('manage-books.php')) {
                                    include 'manage-books.php';
                                } else {
                                    echo '<div class="text-center text-muted py-5">';
                                    echo '<h3>Manage Books</h3>';
                                    echo '<p>Manage books page coming soon...</p>';
                                    echo '</div>';
                                }
                                break;
                            case 'edit_book':
                                if(file_exists('edit_book.php')) {
                                    include 'edit_book.php';
                                } else {
                                    echo '<div class="text-center text-muted py-5">';
                                    echo '<h3>Edit Book</h3>';
                                    echo '<p>Edit book page not found.</p>';
                                    echo '</div>';
                                }
                                break;
                            case 'reports':
                                if(file_exists('reports.php')) {
                                    include 'reports.php';
                                } else {
                                    echo '<div class="text-center text-muted py-5">';
                                    echo '<h3>Reports</h3>';
                                    echo '<p>Reports page coming soon...</p>';
                                    echo '</div>';
                                }
                                break;
                            case 'book-list':
                                if(file_exists('book-list.php')) {
                                    include 'book-list.php';
                                } else {
                                    echo '<div class="text-center text-muted py-5">';
                                    echo '<h3>Book List</h3>';
                                    echo '<p>Book list page coming soon...</p>';
                                    echo '</div>';
                                }
                                break;
                            default:
                                echo '<div class="text-center text-muted py-5">';
                                echo '<i class="bi bi-file-earmark-text display-1"></i>';
                                echo '<h3 class="mt-3">Welcome to Admin ORA</h3>';
                                echo '<p class="lead">Select a menu item from the sidebar to get started.</p>';
                                echo '</div>';
                        }
                        ?>
                    </div>
                </main>
            </div>
        </div>
    </div>
    
    <!-- Mobile Sidebar Offcanvas -->
    <div class="offcanvas offcanvas-start bg-dark d-md-none" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header border-bottom border-secondary">
            <div class="text-center w-100">
                <img src="../assets/images/images-removebg-preview.png" alt="Logo" class="img-fluid mb-2" style="max-height: 40px;">
                <h5 class="text-white mb-0">Admin ORA</h5>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body px-0">
            <!-- Same navigation menu for mobile -->
            <nav class="nav flex-column">
                <a class="nav-link text-white py-3 px-4 d-flex align-items-center" href="#">
                    <i class="bi bi-speedometer2 me-3"></i>
                    <span>Dashboard</span>
                </a>
                
                <div class="nav-item">
                    <a class="nav-link text-white py-3 px-4 d-flex align-items-center" data-bs-toggle="collapse" href="#catalogingMenuMobile">
                        <i class="bi bi-book me-3"></i>
                        <span class="flex-grow-1">Cataloging</span>
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="collapse" id="catalogingMenuMobile">
                        <div class="bg-secondary bg-opacity-25">
                            <a class="nav-link text-white-50 py-2 px-5 d-flex align-items-center" href="#">
                                <i class="bi bi-plus-circle me-3"></i>
                                <span>Create New Book</span>
                            </a>
                            <a class="nav-link text-white-50 py-2 px-5 d-flex align-items-center" href="#">
                                <i class="bi bi-gear me-3"></i>
                                <span>Manage Books</span>
                            </a>
                        </div>
                    </div>
                </div>
                
                <a class="nav-link text-white py-3 px-4 d-flex align-items-center" href="#">
                    <i class="bi bi-graph-up me-3"></i>
                    <span>Reports</span>
                </a>
                
                <a class="nav-link text-white py-3 px-4 d-flex align-items-center" href="#">
                    <i class="bi bi-list-ul me-3"></i>
                    <span>Book List</span>
                </a>
                
                <a class="nav-link text-danger py-3 px-4 d-flex align-items-center mt-auto" href="#">
                    <i class="bi bi-box-arrow-right me-3"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>