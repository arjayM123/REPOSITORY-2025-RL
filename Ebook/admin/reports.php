<?php


require_once '../includes/db.php';

// Load Composer autoloader for PhpOffice libraries
require_once '../../vendor/autoload.php';

// Include PhpOffice libraries at the top
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Shared\Converter;

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'books';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month
$department = $_GET['department'] ?? '';
$export_format = $_GET['export'] ?? '';

// Get unique departments for filter
try {
    $dept_stmt = $pdo->prepare("SELECT DISTINCT department FROM books WHERE department IS NOT NULL AND department != '' ORDER BY department");
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $departments = [];
}


// Get report data based on type
$report_data = getReportData($pdo, $report_type, $date_from, $date_to, $department);

function getReportData($pdo, $report_type, $date_from, $date_to, $department)
{
    try {
        switch ($report_type) {
            case 'books':
                $sql = "SELECT 
                    id, title, author, publisher, date_of_publication, 
                    department, type_of_material, classification_number, 
                    call_number, accession_number, copies, 
                    CASE 
                        WHEN is_deleted = 1 THEN 'Deleted'
                        WHEN status = 'locked' THEN 'Locked' 
                        ELSE 'Active'
                    END as status,
                    DATE(created_at) as date_added
                FROM books 
                WHERE DATE(created_at) BETWEEN ? AND ?";
                $params = [$date_from, $date_to];

                if (!empty($department)) {
                    $sql .= " AND department = ?";
                    $params[] = $department;
                }
                $sql .= " ORDER BY created_at DESC";
                break;

            case 'summary':
                $sql = "SELECT 
                    COUNT(*) as total_books,
                    COUNT(CASE WHEN is_deleted = 0 THEN 1 END) as active_books,
                    COUNT(CASE WHEN is_deleted = 1 THEN 1 END) as deleted_books,
                    COUNT(CASE WHEN status = 'locked' THEN 1 END) as locked_books,
                    SUM(copies) as total_copies,
                    COUNT(DISTINCT department) as total_departments,
                    COUNT(DISTINCT author) as total_authors
                FROM books 
                WHERE DATE(created_at) BETWEEN ? AND ?";
                $params = [$date_from, $date_to];

                if (!empty($department)) {
                    $sql .= " AND department = ?";
                    $params[] = $department;
                }
                break;

            case 'by_department':
                $sql = "SELECT 
                    COALESCE(department, 'Unassigned') as department,
                    COUNT(*) as total_books,
                    COUNT(CASE WHEN is_deleted = 0 THEN 1 END) as active_books,
                    SUM(copies) as total_copies
                FROM books 
                WHERE DATE(created_at) BETWEEN ? AND ?";
                $params = [$date_from, $date_to];

                if (!empty($department)) {
                    $sql .= " AND department = ?";
                    $params[] = $department;
                }
                $sql .= " GROUP BY department ORDER BY total_books DESC";
                break;

            case 'popular_books':
                $params = [$date_from, $date_to];
                $sql = "SELECT 
        b.id,
        b.title,
        b.author,
        b.department,
        COUNT(DISTINCT bv.ip_address) as view_count,
        COUNT(DISTINCT bf.ip_address) as favorite_count,
        b.copies,
        DATE(b.created_at) as date_added
    FROM books b
    LEFT JOIN book_views bv ON b.id = bv.book_id
    LEFT JOIN book_favorites bf ON b.id = bf.book_id
    WHERE DATE(b.created_at) BETWEEN ? AND ?
    AND b.is_deleted = 0";

                if (!empty($department)) {
                    $sql .= " AND b.department = ?";
                    $params[] = $department;
                }

                $sql .= " GROUP BY b.id
        ORDER BY view_count DESC, favorite_count DESC
        LIMIT 50";
                break;

            case 'book_requests':
                $params = [$date_from, $date_to];
                $sql = "SELECT 
        id,
        student_name,
        id_number,
        book_title,
        book_author,
        book_copy as copies_requested,
        contact,
        address,
        DATE(requested_at) as request_date
    FROM book_requests
    WHERE DATE(requested_at) BETWEEN ? AND ?
    ORDER BY requested_at DESC";
                break;

            case 'by_author':
                $sql = "SELECT 
                    author,
                    COUNT(*) as total_books,
                    SUM(copies) as total_copies,
                    GROUP_CONCAT(DISTINCT department) as departments
                FROM books 
                WHERE DATE(created_at) BETWEEN ? AND ? AND is_deleted = 0";
                $params = [$date_from, $date_to];

                if (!empty($department)) {
                    $sql .= " AND department = ?";
                    $params[] = $department;
                }
                $sql .= " GROUP BY author ORDER BY total_books DESC";
                break;

            case 'visitors':
                $sql = "SELECT 
                    DATE(visit_date) as visit_date,
                    COUNT(*) as total_visits,
                    COUNT(DISTINCT ip_address) as unique_visitors
                FROM visitors 
                WHERE DATE(visit_date) BETWEEN ? AND ?
                GROUP BY DATE(visit_date) 
                ORDER BY visit_date DESC";
                $params = [$date_from, $date_to];
                break;

            default:
                return [];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}


?>

<!-- Reports Page Content -->
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h3 mb-1"><i class="bi bi-graph-up me-2"></i>Reports & Analytics</h2>
                    <p class="text-muted mb-0">Generate comprehensive library reports and export data</p>
                </div>

            </div>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Report Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <input type="hidden" name="page" value="reports">
                        <div class="row g-3">
                            <!-- Report Type -->
                            <div class="col-md-3">
                                <label for="report_type" class="form-label fw-semibold">Report Type</label>
                                <select name="report_type" id="report_type" class="form-select">
                                    <option value="books" <?php echo $report_type == 'books' ? 'selected' : ''; ?>>Books
                                        Catalog</option>
                                    <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>
                                        Summary Statistics</option>
                                    <option value="by_department" <?php echo $report_type == 'by_department' ? 'selected' : ''; ?>>By Department</option>
                                    <option value="by_author" <?php echo $report_type == 'by_author' ? 'selected' : ''; ?>>By Author</option>
                                    <option value="popular_books" <?php echo $report_type == 'popular_books' ? 'selected' : ''; ?>>Popular Books</option>
                                    <option value="book_requests" <?php echo $report_type == 'book_requests' ? 'selected' : ''; ?>>Book Requests</option>
                                    <option value="visitors" <?php echo $report_type == 'visitors' ? 'selected' : ''; ?>>
                                        Visitor Analytics</option>
                                </select>
                            </div>

                            <!-- Date From -->
                            <div class="col-md-2">
                                <label for="date_from" class="form-label fw-semibold">Date From</label>
                                <input type="date" name="date_from" id="date_from" class="form-control"
                                    value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>

                            <!-- Date To -->
                            <div class="col-md-2">
                                <label for="date_to" class="form-label fw-semibold">Date To</label>
                                <input type="date" name="date_to" id="date_to" class="form-control"
                                    value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>

                            <!-- Department -->
                            <div class="col-md-3">
                                <label for="department" class="form-label fw-semibold">Department</label>
                                <select name="department" id="department" class="form-select">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department == $dept ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Generate Button -->
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search me-2"></i>Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Results -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-table me-2"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $report_type)); ?> Report
                        <span class="badge bg-primary ms-2"><?php echo count($report_data); ?> records</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($report_data)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-1 text-muted"></i>
                            <h4 class="mt-3 text-muted">No Data Found</h4>
                            <p class="text-muted">No records found for the selected filters. Try adjusting your search
                                criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="reportTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="fw-semibold">No.</th>
                                        <?php
                                        // Skip the 'id' column if it exists
                                        foreach (array_keys($report_data[0]) as $header):
                                            if (strtolower($header) !== 'id'):
                                                ?>
                                                <th class="fw-semibold"><?php echo ucfirst(str_replace('_', ' ', $header)); ?></th>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rowNumber = 1;
                                    foreach ($report_data as $row):
                                        ?>
                                        <tr>
                                            <td class="fw-semibold text-center"><?php echo $rowNumber++; ?></td>
                                            <?php
                                            foreach ($row as $key => $value):
                                                if (strtolower($key) !== 'id'):
                                                    ?>
                                                    <td>
                                                        <?php if ($key === 'status'): ?>
                                                            <?php if ($value === 'Active'): ?>
                                                                <span class="badge bg-success">Active</span>
                                                            <?php elseif ($value === 'Locked'): ?>
                                                                <span class="badge bg-warning">Locked</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Deleted</span>
                                                            <?php endif; ?>
                                                        <?php elseif (is_numeric($value) && $value > 0): ?>
                                                            <span class="fw-semibold"><?php echo number_format($value); ?></span>
                                                        <?php else: ?>
                                                            <?php echo htmlspecialchars($value ?? 'N/A'); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
<tfoot class="table-light">
    <tr>
        <td colspan="<?php echo count(array_keys($report_data[0])); ?>" class="text-end pe-4">
            <strong>Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?> | Total Records: <?php echo count($report_data); ?></strong>
        </td>
    </tr>
</tfoot>
                            </table>
                        </div>

                        <!-- Pagination would go here if needed -->
                        <div class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    
    /* Enhanced Print Styles - Better visuals when printing the table */
    @media print {

        /* Hide everything except the table content */
        body * {
            visibility: hidden;
        }

        /* Show only the table and its contents */
        .table-responsive,
        .table-responsive *,
        .card:has(.table-responsive),
        .card:has(.table-responsive) * {
            visibility: visible;
        }

        /* Page setup */
        @page {
            margin: 0.5in;
            size: landscape;
        }

        /* Center and position the table container */
        .card:has(.table-responsive) {
            position: absolute;
            left: 50%;
            top: 0;
            transform: translateX(-50%);
            width: 95% !important;
            max-width: none !important;
            box-shadow: none !important;
            border: none !important;
        }

        .card-body {
            padding: 0 !important;
        }

        /* Enhanced table styling */
        .table {
            font-size: 9px !important;
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 0 auto !important;
            background-color: white !important;
        }

        /* Beautiful table header */
        .table thead {
            display: table-header-group !important;
        }

        .table thead th {
            background-color: #1a365d !important;
            color: white !important;
            font-weight: bold !important;
            text-align: center !important;
            padding: 10px 6px !important;
            border: 1px solid #2d3748 !important;
            font-size: 8px !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            vertical-align: middle !important;
        }

        /* Enhanced table body */
    .table tfoot td {
        padding: 10px 6px !important;
        border-top: 2px solid #2d3748 !important;
        font-size: 9px !important;
        font-weight: bold !important;
        color: #1a365d !important;
        background-color: #f7fafc !important;
    }
        .table thead th,
    .table tbody td {
        text-align: center !important;
        vertical-align: middle !important;
    }

        /* Alternating row colors for better readability */
        .table tbody tr:nth-child(even) td {
            background-color: #f7fafc !important;
        }

        .table tbody tr:nth-child(odd) td {
            background-color: white !important;
        }

        /* Enhanced badge styling for print */
        .badge {
            font-size: 7px !important;
            padding: 2px 6px !important;
            border-radius: 8px !important;
            font-weight: bold !important;
            text-transform: uppercase !important;
            letter-spacing: 0.3px !important;
        }

        /* Status badge colors - properly visible in print */
        .badge.bg-success {
            background-color: #22c55e !important;
            color: white !important;
            border: 1px solid #16a34a !important;
        }

        .badge.bg-warning {
            background-color: #f59e0b !important;
            color: #000 !important;
            border: 1px solid #d97706 !important;
        }

        .badge.bg-danger {
            background-color: #ef4444 !important;
            color: white !important;
            border: 1px solid #dc2626 !important;
        }

        /* Numeric values styling */
        .fw-semibold {
            font-weight: 600 !important;
            color: #1a365d !important;
        }

        /* Add school header before the table */
        .table-responsive::before {
            content: "";
            display: block;
            height: 80px;
            background-image: url('../assets/images/images-removebg-preview.png');
            background-repeat: no-repeat;
            background-position: left center;
            background-size: 60px;
            margin-bottom: 20px;
            position: relative;
            top: -20px;
        }

        /* Add school header before the table */
        .table-responsive::before {
            content: "";
            display: block;
            height: 80px;
            background-image: url('../assets/images/images-removebg-preview.png');
            background-repeat: no-repeat;
            background-position: left center;
            background-size: 60px;
            margin-bottom: 20px;
            position: relative;
        }

        /* University name - large and bold */
        .card:has(.table-responsive)::after {
            content: "ISABELA STATE UNIVERSITY";
            display: block;
            position: absolute;
            top: 5px;
            left: 80px;
            font-size: 18px !important;
            font-weight: bold !important;
            color: #1a365d !important;
            line-height: 1.2;
        }

        /* Add campus name with smaller text using a different approach */
        .table-responsive::after {
            content: "Roxas Campus" "\A" " LIBRARY REPORT - <?php echo strtoupper(str_replace('_', ' ', $report_type)); ?>";
            white-space: pre-line;
            display: block;
            position: absolute;
            top: 25px;
            left: 80px;
            font-size: 12px !important;
            color: #4a5568 !important;
            line-height: 1.4;
            border-bottom: 2px solid #1a365d !important;
            padding-bottom: 10px;
            margin-bottom: 20px;
            width: calc(100% - 90px);
        }

        /* Style the library report line specifically */
        .table-responsive::after {
            background: linear-gradient(to bottom,
                    #4a5568 0%,
                    /* Campus name color - smaller */
                    #4a5568 15px,
                    /* Campus name */
                    transparent 15px,
                    transparent 20px,
                    #1a365d 20px,
                    /* Report title color - larger */
                    #1a365d 100%
                    /* Report title */
                );
            background-clip: text;
            -webkit-background-clip: text;
        }

        /* Ensure proper page breaks */
        .table tr {
            page-break-inside: avoid !important;
        }

        .table thead tr {
            page-break-after: avoid !important;
        }

        /* Hide card headers and footers */
        .card-header,
        .card-footer {
            display: none !important;
        }

        /* Better spacing for the entire print layout */
        .table-responsive {
            margin: 20px 0 !important;
            border-radius: 0 !important;
            overflow: visible !important;
        }

        /* Make sure table borders are crisp */
        .table,
        .table th,
        .table td {
            border-color: #2d3748 !important;
            border-style: solid !important;
        }
    }

    /* Add JavaScript to set dynamic content for print */
    @media print {
        .table-responsive {
            --print-date: "<?php echo date('M d, Y H:i'); ?>";
            --record-count: "<?php echo count($report_data); ?>";
        }
    }
</style>

<script>
    // Simple function to trigger the enhanced print
    function printTable() {
        // Set dynamic data for print pseudo-elements
        const tableResponsive = document.querySelector('.table-responsive');
        if (tableResponsive) {
            tableResponsive.setAttribute('data-print-date', new Date().toLocaleDateString());
            tableResponsive.setAttribute('data-record-count', document.querySelectorAll('.table tbody tr').length);
        }

        // Use the browser's print with our enhanced CSS
        window.print();
    }
</script>