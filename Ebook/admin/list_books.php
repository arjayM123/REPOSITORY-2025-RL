<?php
include '../includes/db.php';

// Enhanced filtering functionality
try {
    $params = [];
    $sql = "SELECT 
        classification_number,
        call_number,
        date_of_publication,
        accession_number,
        author,
        title,
        edition,
        place_of_publication,
        publisher,
        copies,
        department,
        type_of_material
    FROM books 
    WHERE is_deleted = 0";
    
    // Department filter
    if (isset($_GET['department']) && $_GET['department'] !== '') {
        $sql .= " AND department = ?";
        $params[] = $_GET['department'];
    }
    
    // DDC Category filter
    if (isset($_GET['ddc_category']) && $_GET['ddc_category'] !== '') {
        $category_range = $_GET['ddc_category'];
        $range_parts = explode('-', $category_range);
        if (count($range_parts) == 2) {
            $start = intval($range_parts[0]);
            $end = intval($range_parts[1]);
            $sql .= " AND (CAST(SUBSTRING(classification_number, 1, 3) AS UNSIGNED) BETWEEN ? AND ?)";
            $params[] = $start;
            $params[] = $end;
        }
    }
    
    // Material type filter
    if (isset($_GET['material_type']) && $_GET['material_type'] !== '') {
        $sql .= " AND type_of_material = ?";
        $params[] = $_GET['material_type'];
    }
    
    // Search filter
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $search_term = '%' . $_GET['search'] . '%';
        $sql .= " AND (title LIKE ? OR author LIKE ? OR classification_number LIKE ? OR call_number LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql .= " ORDER BY classification_number ASC, call_number ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

try {
    // Get unique departments
    $dept_stmt = $pdo->prepare("SELECT DISTINCT department FROM books WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get unique material types
    $material_stmt = $pdo->prepare("SELECT DISTINCT type_of_material FROM books WHERE type_of_material IS NOT NULL AND type_of_material != '' ORDER BY type_of_material");
    $material_stmt->execute();
    $material_types = $material_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Define DDC categories
$ddc_categories = array(
    '000-099' => 'Computers, Information, & General Reference',
    '100-199' => 'Philosophy and Psychology',
    '200-299' => 'Religion',
    '300-399' => 'Social Sciences',
    '400-499' => 'Language',
    '500-599' => 'Science',
    '600-699' => 'Applied Science Technology',
    '700-799' => 'Arts and Recreation',
    '800-899' => 'Literature',
    '900-999' => 'History and Geography'
);
?>

<style>
    @media print {
    /* Hide filter summary and tags */
    .filter-summary,
    .filter-tag,
    .filter-info {
        display: none !important;
        visibility: hidden !important;
    }

    /* Hide any other filter-related elements */
    .filter-section,
    .filter-form,
    .filter-group,
    .filter-buttons {
        display: none !important;
        visibility: hidden !important;
    }

    /* Ensure proper spacing after removing filters */
    .header-section {
        margin-top: 0;
        padding-top: 0;
    }

    /* Hide active filters text completely */
    div:has(> .filter-tag) {
        display: none !important;
    }
}
    .filter-section {
        background-color: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #495057;
    }

    .form-control, .form-select {
        padding: 0.5rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #80bdff;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .filter-buttons {
        display: flex;
        gap: 0.5rem;
        grid-column: 1 / -1;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #545b62;
        transform: translateY(-1px);
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background-color: #1e7e34;
        transform: translateY(-1px);
    }

    .btn-info {
        background-color: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background-color: #117a8b;
        transform: translateY(-1px);
    }

    .filter-summary {
        background-color: #e9ecef;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .filter-tag {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin: 0.25rem;
        font-size: 0.8rem;
    }

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px;
}

.category-title {
    font-weight: bold;
    font-size: 18px;
}

.category-count {
    font-weight: bold;
    font-size: 16px;
    color: #3d4146ff;
}


    .books-table {
        margin-bottom: 30px;
    }

    .content-wrapper {
        margin-left: 0 !important;
        /* Override any existing margin */
        width: 100% !important;
        background: white;
    }


    .header-section {
        text-align: center;
        margin-bottom: 30px;
        padding-top: 20px;
    }

    .header-section img {
        width: 100px;
        margin-bottom: 10px;
    }

    .university-header {
        margin: 0;
        font-size: 18px;
        font-weight: bold;
        line-height: 1.5;
    }

    .address {
        margin: 5px 0;
        line-height: 1.2;
    }

    .department {
        font-size: 16px;
        font-weight: bold;
        margin: 15px 0;
        line-height: 1.5;
    }

    .books-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        table-layout: fixed;
    }

    .books-table th,
    .books-table td {
        border: 1px solid black;
        padding: 8px;
        text-align: center;
    }

    .books-table th {
        background-color: #f5f5f5;
        text-align: center;
        font-weight: bold;
    }

    /* Column widths */
    .books-table th:nth-child(1) {
        width: 15%;
    }

    /* CALL NO. */
    .books-table th:nth-child(2) {
        width: 12%;
    }

    /* ACCESSION NO. */
    .books-table th:nth-child(3) {
        width: 55%;
    }

    /* AUTHOR/TITLE */
    .books-table th:nth-child(4) {
        width: 9%;
    }

    /* TITLE */
    .books-table th:nth-child(5) {
        width: 9%;
    }

    /* VOLUME */

    .print-button {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .call-number {
        white-space: pre-line;
    }

    /* Print-specific styles */
    @media print {


        .content-wrapper {
            margin-left: 0 !important;
            width: 100% !important;
            background: white;
        }

        /* Header styles */
        .header-section {
            text-align: center;
            margin-bottom: 30px;
            padding-top: 20px;
        }

        .header-section img {
            width: 100px;
            height: auto;
            margin-bottom: 10px;
        }

        .university-header {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            line-height: 1.5;
        }

        .address {
            margin: 5px 0;
            line-height: 1.2;
        }

        .department {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            line-height: 1.5;
        }

        /* Table styles */
        .books-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: auto;
        }

        .books-table th,
        .books-table td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .books-table th {
            background-color: #f5f5f5;
            text-align: center;
            font-weight: bold;
        }

        .books-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        /* Column widths */
        .books-table th:nth-child(1),
        .books-table td:nth-child(1) {
            width: 15%;
        }

        .books-table th:nth-child(2),
        .books-table td:nth-child(2) {
            width: 12%;
        }

        .books-table th:nth-child(3),
        .books-table td:nth-child(3) {
            width: 55%;
        }

        .books-table th:nth-child(4),
        .books-table td:nth-child(4) {
            width: 9%;
        }

        .books-table th:nth-child(5),
        .books-table td:nth-child(5) {
            width: 9%;
        }

        .call-number {
            white-space: pre-line;
        }

        /* Print button */
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Print-specific styles */
        @media print {

            /* Reset page margins and size */
            @page {
                size: A4;
                margin: 1cm;
            }

            /* Basic print reset */
            html,
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background: white !important;
            }

            /* Table print optimization */
            .books-table {
                font-size: 9pt;
                width: 100%;
                table-layout: fixed;
            }

            .books-table th,
            .books-table td {
                padding: 4px;
            }

            /* Ensure proper text wrapping */
            .books-table td {
                overflow-wrap: break-word;
                word-wrap: break-word;
                -ms-word-break: break-all;
                word-break: break-word;
            }

            /* Hide unnecessary elements */
            .print-button,
            .sidebar,
            nav,
            footer,
            .main-sidebar,
            .navbar,
            .layout-fixed,
            .layout-navbar-fixed,
            .layout-footer-fixed {
                display: none !important;
            }

            /* Container adjustments */
            .container {
                width: 100% !important;
                max-width: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Header adjustments for print */
            .header-section {
                margin-bottom: 20px;
            }

            .header-section img {
                width: 80px;
            }

            /* Force background colors and images */
            * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    background-color: transparent !important;
}
            /* Remove any potential watermarks */
            body::before,
            body::after,
            .content-wrapper::before,
            .content-wrapper::after {
                display: none !important;
                content: none !important;
            }
        }
    }

    .filter-section {
        margin-bottom: 20px;
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
    }

    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
    }

    .filter-group label {
        margin-bottom: 5px;
        font-weight: bold;
    }

    .filter-group select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .filter-button,
    .print-button {
        padding: 8px 15px;
        background-color: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .print-button {
        background-color: #2196F3;
    }

    .filter-info {
        margin: 5px 0;
        font-weight: bold;
        font-style: italic;
    }

    .total-count {
        font-weight: bold;
        font-size: 16px;
        text-align: right;
    }

    .no-books {
        padding: 20px;
        text-align: center;
        font-size: 18px;
        color: #666;
        background-color: #f9f9f9;
        border-radius: 5px;
        margin-top: 20px;
    }

    .category-header {
        font-weight: bold;
        margin-top: 15px;
    }

    @media print {
        .filter-section {
            display: none;
        }
        .toggle-sidebar {
            display: none;
        }
    }

    @media print {
        .excel-button, .word-button {
            display: none;
        }
        
    }
    @media print {
    /* Hide the back to dashboard button */
    .btn-back-to-dashboard,
    .btn-group,
    .card-footer {
        display: none !important;
        visibility: hidden !important;
    }

    /* Hide any navigation buttons */
    .nav-buttons,
    .action-buttons,
    button[onclick="window.history.back()"],
    a[href*="dashboard"] {
        display: none !important;
        visibility: hidden !important;
    }

    /* Ensure all buttons are hidden in print */
    .btn,
    .button,
    button {
        display: none !important;
        visibility: hidden !important;
    }

    /* Hide the filter section */
    .row.mb-4 .card:first-of-type {
        display: none !important;
        visibility: hidden !important;
    }
}
</style>


<style>
    .back-button {
    padding: 10px 0;
}

.back-button .btn {
    transition: all 0.3s ease;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 6px;
}

.back-button .btn:hover {
    transform: translateX(-5px);
    background-color: #b1b1b1ff;
    color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

@media print {
    .back-button {
        display: none !important;
    }
}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<div class="main-content">
<div class="back-button mb-4">
    <a href="_layout.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
    </a>
</div>
<!-- Enhanced Filter Section -->
    <div class="filter-section mb-4">
        <h5 class="mb-3"><i class="fas fa-filter"></i> Advanced Filters</h5>
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="department"><i class="fas fa-building"></i> Department:</label>
                <select name="department" id="department" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo htmlspecialchars($dept); ?>" 
                                <?php echo (isset($_GET['department']) && $_GET['department'] === $dept) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="ddc_category"><i class="fas fa-list-alt"></i> DDC Category:</label>
                <select name="ddc_category" id="ddc_category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($ddc_categories as $range => $name): ?>
                        <option value="<?php echo $range; ?>" 
                                <?php echo (isset($_GET['ddc_category']) && $_GET['ddc_category'] === $range) ? 'selected' : ''; ?>>
                            <?php echo $range . ' - ' . $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="material_type"><i class="fas fa-bookmark"></i> Material Type:</label>
                <select name="material_type" id="material_type" class="form-select">
                    <option value="">All Materials</option>
                    <?php foreach ($material_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" 
                                <?php echo (isset($_GET['material_type']) && $_GET['material_type'] === $type) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>


            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
                <button type="button" onclick="window.print()" class="btn print-button">
                    <i class="fas fa-print"></i> Print List
                </button>
            </div>
        </form>
    </div>

        <?php 
    $active_filters = [];
    if (isset($_GET['department']) && $_GET['department'] !== '') {
        $active_filters[] = 'Department: ' . $_GET['department'];
    }
    if (isset($_GET['ddc_category']) && $_GET['ddc_category'] !== '') {
        $active_filters[] = 'Category: ' . $_GET['ddc_category'] . ' - ' . $ddc_categories[$_GET['ddc_category']];
    }
    if (isset($_GET['material_type']) && $_GET['material_type'] !== '') {
        $active_filters[] = 'Material: ' . $_GET['material_type'];
    }
    if (!empty($active_filters)): ?>
        <div class="filter-summary">
            <strong><i class="fas fa-info-circle"></i> Active Filters:</strong>
            <?php foreach ($active_filters as $filter): ?>
                <span class="filter-tag"><?php echo htmlspecialchars($filter); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

   <div class="form-container">

       <div class="container">
           <div class="header-section">
               <img src="../assets/images/images-removebg-preview.png" alt="ISU Logo">
               <p class="university-header">Republic of the Philippines</p>
               <p class="university-header">ISABELA STATE UNIVERSITY</p>
               <h2>LIST OF GENERAL COLLECTION</h2>
           </div>


           <div class="export-buttons">


            <div class="export-buttons"></div>
               <button type="button" onclick="window.print()" class="print-button">
                   <i class="fas fa-print"></i> Print List
               </button>
           </div>

           <?php
           if (isset($error)):
               echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
           else:
               // Define categories with DDC ranges
               $categories = array(
                   '000-099' => 'Computers, Information, & General Reference',
                   '100-199' => 'Philosophy and Psychology',
                   '200-299' => 'Religion',
                   '300-399' => 'Social Sciences',
                   '400-499' => 'Language',
                   '500-599' => 'Science',
                   '600-699' => 'Applied Science Technology',
                   '700-799' => 'Arts and Recreation',
                   '800-899' => 'Literature',
                   '900-999' => 'History and Geography'
               );

               // Function to determine category based on classification number
               function getDDCCategory($callNo) {
                   if (preg_match('/^(\d+)/', $callNo, $matches)) {
                       $classNum = intval($matches[1]);
                       $baseRange = floor($classNum / 100) * 100;
                       $categoryKey = sprintf('%03d-%03d', $baseRange, $baseRange + 99);
                       return $categoryKey;
                   }
                   return '000-099';
               }

               // Create an array to store books by category
               $categorized_books = array();
               $total_books = 0;

               if (!empty($books)) {
                   foreach ($books as $book) {
                       $category = getDDCCategory($book['classification_number'] ?? '000');
                       if (!isset($categorized_books[$category])) {
                           $categorized_books[$category] = array();
                       }
                       
                       // Format the author/title field
                       $author_title = $book['author'] . ' ' . $book['title'];
                       if (!empty($book['edition'])) {
                           $author_title .= ', ' . $book['edition'];
                       }
                       if (!empty($book['place_of_publication'])) {
                           $author_title .= ', ' . $book['place_of_publication'];
                       }
                       if (!empty($book['publisher'])) {
                           $author_title .= ': ' . $book['publisher'];
                       }
                       if (!empty($book['date_of_publication'])) {
                           $author_title .= ', c' . $book['date_of_publication'];
                       }
                       
                       // Create formatted book array
                       $formatted_book = array(
                           'classification_number' => $book['classification_number'],
                           'call_number' => $book['call_number'],
                           'date_of_publication' => $book['date_of_publication'],
                           'accession_number' => $book['accession_number'],
                           'author_title' => $author_title,
                           'title_count' => '1',
                           'volume' => $book['copies']
                       );
                       
                       $categorized_books[$category][] = $formatted_book;
                       $total_books++;
                   }
               }



               // Display books by category
               foreach ($categories as $range => $categoryName):
                   if (isset($categorized_books[$range]) && !empty($categorized_books[$range])):
               ?>
<div class="category-header">
    <div class="category-title">
        <?php echo $categoryName; ?> (<?php echo $range; ?>)
    </div>
    <div class="category-count">
        <?php echo "Total Books: " . count($categorized_books[$range]); ?>
    </div>
</div>

                       <table class="books-table">
                           <thead>
                               <tr>
                                   <th>CALL NO.</th>
                                   <th>ACCESSION NO.</th>
                                   <th>AUTHOR/TITLE OF BOOK</th>
                                   <th>TITLE</th>
                                   <th>VOLUME</th>
                               </tr>
                           </thead>
                           <tbody>
                               <?php foreach ($categorized_books[$range] as $row): ?>
                                   <tr>
                                       <td class="call-number"><?php echo htmlspecialchars($row['classification_number']) . "\n" . 
                                           htmlspecialchars($row['call_number']) . "\n" . 
                                           htmlspecialchars($row['date_of_publication']); ?></td>
                                       <td><?php echo htmlspecialchars($row['accession_number']); ?></td>
                                       <td><?php echo htmlspecialchars($row['author_title']); ?></td>
                                       <td><?php echo htmlspecialchars($row['title_count']); ?></td>
                                       <td><?php echo htmlspecialchars($row['volume']); ?></td>
                                   </tr>
                               <?php endforeach; ?>
                           </tbody>
                       </table>
                   <?php
                   endif;
               endforeach;

                if ($total_books == 0):
                ?>
                    <div class="no-books">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 20px; color: #ced4da;"></i>
                        <h4>No Books Found</h4>
                        <p>No books match your current filter criteria. Try adjusting your filters or clearing them to see more results.</p>
                        <a href="?page=list-books" class="btn btn-primary">
                            <i class="fas fa-refresh"></i> Clear All Filters
                        </a>
                    </div>
                <?php 
                endif;
            endif;
           ?>
       </div>
   </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script>
   function exportToExcel() {
       let params = new URLSearchParams(window.location.search);
       let exportUrl = 'export_excel.php';
       
       if(params.toString()) {
           exportUrl += '?' + params.toString();
       }
       
       window.location.href = exportUrl;
   }

   function exportToWord() {
       let params = new URLSearchParams(window.location.search);
       let exportUrl = 'export_word.php';
       
       if(params.toString()) {
           exportUrl += '?' + params.toString();
       }
       
       window.location.href = exportUrl;
   }
</script>