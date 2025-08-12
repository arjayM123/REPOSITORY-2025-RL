<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'ISUR-ORA Digital Library'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Inter', sans-serif;">
    <!-- Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container-fluid px-3 px-lg-4">
            <!-- Logo Section -->
            <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
                <img src="../assets/images/images-removebg-preview.png" alt="ISUR-ORA Logo" height="45" class="me-2">
                <div>
                    <div class="fw-bold fs-4 text-dark mb-0 lh-1">ISUR-ORA</div>
                    <small class="text-muted">Digital Library</small>
                </div>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Home -->
                    <li class="nav-item">
                        <a class="nav-link fw-medium px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active text-primary' : ''; ?>" href="index.php">
                            <i class="bi bi-house me-1"></i>Home
                        </a>
                    </li>
                    
                    <!-- Materials Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-medium px-3" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-collection me-1"></i>Materials
                        </a>
                        <ul class="dropdown-menu shadow border-0 mt-2">
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'all')">
                                <i class="bi bi-grid me-2 text-muted"></i>All Materials
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Book', 'all')">
                                <i class="bi bi-book me-2 text-muted"></i>Books
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Electronic Resource', 'all')">
                                <i class="bi bi-laptop me-2 text-muted"></i>Electronic Resources
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Map', 'all')">
                                <i class="bi bi-map me-2 text-muted"></i>Maps
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Music', 'all')">
                                <i class="bi bi-music-note me-2 text-muted"></i>Music
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Continuing Resource', 'all')">
                                <i class="bi bi-arrow-repeat me-2 text-muted"></i>Continuing Resources
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Visual Material', 'all')">
                                <i class="bi bi-image me-2 text-muted"></i>Visual Materials
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Mixed Material', 'all')">
                                <i class="bi bi-collection-fill me-2 text-muted"></i>Mixed Materials
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Thesis', 'all')">
                                <i class="bi bi-mortarboard me-2 text-muted"></i>Thesis
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Article', 'all')">
                                <i class="bi bi-file-text me-2 text-muted"></i>Articles
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('Analytics', 'all')">
                                <i class="bi bi-graph-up me-2 text-muted"></i>Analytics
                            </a></li>
                        </ul>
                    </li>

                    <!-- Courses Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-medium px-3" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-mortarboard me-1"></i>Courses
                        </a>
                        <ul class="dropdown-menu shadow border-0 mt-2">
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'all')">
                                <i class="bi bi-grid me-2 text-muted"></i>All Courses
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'BSIT')">
                                <i class="bi bi-code-slash me-2 text-muted"></i>BSIT
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'BSED')">
                                <i class="bi bi-book me-2 text-muted"></i>BSED
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'BSAB')">
                                <i class="bi bi-briefcase me-2 text-muted"></i>BSAB
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'BSCRIM')">
                                <i class="bi bi-shield me-2 text-muted"></i>BSCRIM
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'BSA')">
                                <i class="bi bi-calculator me-2 text-muted"></i>BSA
                            </a></li>
                            <li><a class="dropdown-item py-2" href="#" onclick="applyFilter('all', 'FISHERS')">
                                <i class="bi bi-water me-2 text-muted"></i>FISHERS
                            </a></li>
                        </ul>
                    </li>

                    <!-- Favorites -->
                    <li class="nav-item">
                        <a class="nav-link fw-medium px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'favorites.php' ? 'active text-primary' : ''; ?>" href="favorites.php">
                            <i class="bi bi-heart me-1"></i>Favorites
                        </a>
                    </li>

                    <!-- Requested Books -->
                    <li class="nav-item">
                        <a class="nav-link fw-medium px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'requested_books.php' ? 'active text-primary' : ''; ?>" href="requested_books.php">
                            <i class="bi bi-bookmark me-1"></i>Requested Books
                        </a>
                    </li>

                    <!-- About Us -->
                    <li class="nav-item">
                        <a class="nav-link fw-medium px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active text-primary' : ''; ?>" href="about.php">
                            <i class="bi bi-info-circle me-1"></i>About Us
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Bootstrap JS (Required for dropdowns and mobile menu) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Simple filter function using vanilla JS -->
    <script>
        // Simple filter function that works with any page
        function applyFilter(material, course) {
            // Check if we're on the index page and if filterBooks function exists
            if (typeof filterBooks === 'function') {
                filterBooks(material, course);
            } else {
                // For other pages, redirect to index with parameters
                let url = 'index.php?';
                if (material !== 'all') url += 'material=' + encodeURIComponent(material) + '&';
                if (course !== 'all') url += 'course=' + encodeURIComponent(course) + '&';
                window.location.href = url.replace(/&$/, '');
            }
            
            // Close dropdown after selection (Bootstrap handles this automatically but we can ensure it)
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                const bsDropdown = new bootstrap.Dropdown(dropdown.previousElementSibling);
                bsDropdown.hide();
            });
        }
    </script>
</body>
</html>