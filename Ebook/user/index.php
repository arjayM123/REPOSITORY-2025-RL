<?php
session_start();
require_once '../includes/db.php';

// Set the page title
$pageTitle = 'E-Book Library';

// Get total counts for header
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$totalBooksResult = $conn->query($totalBooksQuery);
$totalBooks = $totalBooksResult->fetch_assoc()['total'];

// Include header navigation
include "_layout.php";
?>
<body>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Content Header -->
        <div class="content-header">
            <div class="header-top">
                <div class="header-title">
                    <h2>Available Books</h2>
                </div>
            </div>
            <div class="header-controls">
                <div class="search-form">
                    <div class="input-group">
                        <input class="form-control" type="search" id="searchInput" placeholder="Search books..." aria-label="Search">
                        <button class="btn btn-outline-primary" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="filter-dropdown">
                    <button class="btn btn-outline-secondary" id="filterButton">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <div class="filter-menu" id="filterMenu">
                        <h6 class="mb-3">Filter By Material Type:</h6>
                        <div class="mb-2">
                            <select class="form-select form-select-sm filter-select" id="materialFilter">
                                <option value="">All Types</option>
                                <?php
                                // Get distinct material types for filter
                                $materialQuery = "SELECT DISTINCT type_of_material FROM books WHERE type_of_material IS NOT NULL AND type_of_material != '' ORDER BY type_of_material";
                                $materialResult = $conn->query($materialQuery);
                                if ($materialResult && $materialResult->num_rows > 0) {
                                    while ($material = $materialResult->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($material['type_of_material']) . '">' . 
                                             htmlspecialchars($material['type_of_material']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary" id="applyFilters">Apply Filter</button>
                            <button class="btn btn-sm btn-outline-secondary ms-1" id="resetFilters">Reset</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="books-container" id="booksContainer">
            <?php
            // Query to get books grouped by material type
            $materialTypesQuery = "SELECT DISTINCT type_of_material FROM books WHERE type_of_material IS NOT NULL AND type_of_material != '' ORDER BY type_of_material";
            $materialTypesResult = $conn->query($materialTypesQuery);

            if ($materialTypesResult && $materialTypesResult->num_rows > 0) {
                while ($materialType = $materialTypesResult->fetch_assoc()) {
                    $currentMaterial = $materialType['type_of_material'];
                    
                    // Get books for this material type
                    $booksQuery = "SELECT id, title, author, cover_image, type_of_material, created_at FROM books WHERE type_of_material = ? ORDER BY created_at DESC";
                    $stmt = $conn->prepare($booksQuery);
                    $stmt->bind_param("s", $currentMaterial);
                    $stmt->execute();
                    $booksResult = $stmt->get_result();
                    
                    if ($booksResult && $booksResult->num_rows > 0) {
                        ?>
                        <div class="material-section" data-material="<?php echo htmlspecialchars($currentMaterial); ?>">
<div class="section-header">
    <h3 class="section-title">
        <i class="bi bi-collection"></i>
        <?php echo htmlspecialchars($currentMaterial); ?>
    </h3>
    <p class="text-muted small ms-4"><?php echo $booksResult->num_rows; ?> book<?php echo $booksResult->num_rows > 1 ? 's' : ''; ?></p>
</div>
                            
                            <div class="book-grid">
                                <?php
                                while ($book = $booksResult->fetch_assoc()) {
                                    // Determine the image path or use a default image
                                    $imagePath = !empty($book['cover_image']) ? '../uploads/covers/' . $book['cover_image'] : 'https://via.placeholder.com/300x450/6c757d/ffffff?text=No+Cover';

                                    // Determine if it's new (less than 7 days old)
                                    $isNew = (strtotime($book['created_at']) > strtotime('-7 days'));
                                ?>
                                    <div class="book-card" 
                                         data-title="<?php echo htmlspecialchars($book['title']); ?>"
                                         data-author="<?php echo htmlspecialchars($book['author']); ?>"
                                         data-material="<?php echo htmlspecialchars($book['type_of_material'] ?? ''); ?>">
                                        <a href="view_books.php?id=<?php echo $book['id']; ?>" class="text-decoration-none">
                                            <div class="book-cover-container">
                                                <?php if ($isNew): ?>
                                                    <span class="badge bg-primary new-badge">NEW</span>
                                                <?php endif; ?>
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" class="book-cover"
                                                    alt="<?php echo htmlspecialchars($book['title']); ?>"
                                                    loading="lazy">
                                            </div>
                                        </a>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    $stmt->close();
                }
            } else {
                echo '<div class="text-center p-5"><p>No books available yet. Check back soon for new additions!</p></div>';
            }
            ?>
        </div>
    </div>

    <!-- CSS Styles -->
    <style>
        .main-content {
            padding: 20px;
            max-width: 1400px;
            margin: 200px auto;
        }

        .content-header {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-top {
            margin-bottom: 20px;
        }

        .header-title h2 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .header-title p {
            color: #6c757d;
            margin: 0;
        }

        .header-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-form {
            flex: 1;
            min-width: 250px;
        }

        .filter-dropdown {
            position: relative;
        }

        .filter-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            min-width: 250px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }

        .filter-menu.show {
            display: block;
        }

        .material-section {
            margin-bottom: 3rem;
        }
        
        .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .section-title {
            color: #495057;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 0.5rem;
            color: #6c757d;
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 0;
        }

        .book-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: fit-content;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .book-card a {
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .book-cover-container {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
        }

        .book-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .book-card:hover .book-cover {
            transform: scale(1.05);
        }

        .new-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            font-size: 0.75rem;
            padding: 4px 8px;
        }

        .book-info {
            padding: 15px;
        }

        .book-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
        }

        .book-author {
            color: #6c757d;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-material {
            margin: 0;
        }

        .book-material small {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .no-results-message {
            background: #f8f9fa;
            border-radius: 8px;
            margin: 2rem 0;
            padding: 3rem;
            text-align: center;
            color: #6c757d;
        }

        .no-results-message i,
        .text-center i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }

            .header-controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-form {
                min-width: auto;
            }

            .book-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }

            .book-cover-container {
                height: 220px;
            }

            .filter-menu {
                right: auto;
                left: 0;
                min-width: 100%;
            }
        }

        @media (max-width: 480px) {
            .book-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .section-header p {
    margin: 0;
    margin-top: 4px;
}
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('expanded');
                });
            }

            // Filter dropdown toggle
            const filterButton = document.getElementById('filterButton');
            const filterMenu = document.getElementById('filterMenu');

            filterButton.addEventListener('click', function() {
                filterMenu.classList.toggle('show');
            });

            // Close filter menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!filterButton.contains(event.target) && !filterMenu.contains(event.target)) {
                    filterMenu.classList.remove('show');
                }
            });

            // Get all book cards and sections
            const bookCards = document.querySelectorAll('.book-card');
            const materialSections = document.querySelectorAll('.material-section');
            const searchInput = document.getElementById('searchInput');
            const materialFilter = document.getElementById('materialFilter');
            const applyFilters = document.getElementById('applyFilters');
            const resetFilters = document.getElementById('resetFilters');

            // Real-time search functionality
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase().trim();
                let hasVisibleResults = false;

                materialSections.forEach(function(section) {
                    const sectionCards = section.querySelectorAll('.book-card');
                    let sectionHasVisible = false;

                    sectionCards.forEach(function(card) {
                        const title = card.dataset.title.toLowerCase();
                        const author = card.dataset.author.toLowerCase();
                        const material = card.dataset.material.toLowerCase();

                        if (title.includes(searchTerm) || author.includes(searchTerm) || material.includes(searchTerm)) {
                            card.style.display = '';
                            sectionHasVisible = true;
                            hasVisibleResults = true;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Hide/show section based on whether it has visible cards
                    section.style.display = sectionHasVisible ? '' : 'none';
                });

                checkNoResults(hasVisibleResults);
            });

            // Filter functionality
            applyFilters.addEventListener('click', function() {
                const selectedMaterial = materialFilter.value.toLowerCase();
                let hasVisibleResults = false;

                materialSections.forEach(function(section) {
                    const sectionMaterial = section.dataset.material.toLowerCase();

                    // Check if this section should be shown based on material filter
                    if (selectedMaterial && sectionMaterial !== selectedMaterial) {
                        section.style.display = 'none';
                    } else {
                        section.style.display = '';
                        hasVisibleResults = true;
                    }
                });

                filterMenu.classList.remove('show');
                checkNoResults(hasVisibleResults);
            });

            // Reset filters
            resetFilters.addEventListener('click', function() {
                materialFilter.selectedIndex = 0;
                searchInput.value = '';

                materialSections.forEach(function(section) {
                    section.style.display = '';
                    section.querySelectorAll('.book-card').forEach(function(card) {
                        card.style.display = '';
                    });
                });

                filterMenu.classList.remove('show');
                checkNoResults(true);
            });

            // Function to check if there are no visible results and display a message
            function checkNoResults(hasResults) {
                // Remove existing no results message if it exists
                const existingNoResults = document.querySelector('.no-results-message');
                if (existingNoResults) {
                    existingNoResults.remove();
                }

                // Add no results message if needed
                if (!hasResults) {
                    const noResults = document.createElement('div');
                    noResults.className = 'no-results-message';
                    noResults.innerHTML = '<i class="bi bi-search"></i><p>No books match your search or filter criteria.</p>';
                    document.getElementById('booksContainer').appendChild(noResults);
                }
            }
        });
    </script>
</body>
</html>