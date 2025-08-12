<?php
// reports.php - Professional Library Reports System

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

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

// Handle export requests BEFORE any HTML output
if (!empty($export_format) && in_array($export_format, ['excel', 'word'])) {
    // Clear any output that might have been sent
    if (ob_get_level()) {
        ob_end_clean();
    }
    handleExport($pdo, $report_type, $date_from, $date_to, $department, $export_format);
    exit();
}

// Get report data based on type
$report_data = getReportData($pdo, $report_type, $date_from, $date_to, $department);

function getReportData($pdo, $report_type, $date_from, $date_to, $department) {
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

function handleExport($pdo, $report_type, $date_from, $date_to, $department, $export_format) {
    $data = getReportData($pdo, $report_type, $date_from, $date_to, $department);
    
    if ($export_format == 'excel') {
        exportToExcel($data, $report_type, $date_from, $date_to);
    } elseif ($export_format == 'word') {
        exportToWord($data, $report_type, $date_from, $date_to);
    }
}

function exportToExcel($data, $report_type, $date_from, $date_to) {
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set document properties
    $spreadsheet->getProperties()
        ->setCreator("ORA Library System")
        ->setTitle(ucfirst(str_replace('_', ' ', $report_type)) . " Report")
        ->setDescription("Library report generated on " . date('Y-m-d H:i:s'));
    
    // Header styling
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0d6efd']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
    ];
    
    // Title
    $sheet->setCellValue('A1', 'ORA Library System - ' . ucfirst(str_replace('_', ' ', $report_type)) . ' Report');
    $sheet->setCellValue('A2', 'Period: ' . $date_from . ' to ' . $date_to);
    $sheet->setCellValue('A3', 'Generated on: ' . date('Y-m-d H:i:s'));
    
    $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 16]]);
    $sheet->getStyle('A2:A3')->applyFromArray(['font' => ['italic' => true]]);
    
    if (empty($data)) {
        $sheet->setCellValue('A5', 'No data found for the selected period.');
        $filename = 'library_report_' . $report_type . '_' . date('Y-m-d') . '.xlsx';
    } else {
        $row = 5;
        $headers = array_keys($data[0]);
        
        // Set headers
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, ucfirst(str_replace('_', ' ', $header)));
            $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
            $col++;
        }
        
        // Add data
        $row++;
        foreach ($data as $record) {
            $col = 'A';
            foreach ($record as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = 'library_report_' . $report_type . '_' . date('Y-m-d') . '.xlsx';
    }
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

function exportToWord($data, $report_type, $date_from, $date_to) {
    
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    
    // Title
    $section->addText('ORA Library System', ['bold' => true, 'size' => 18], ['alignment' => 'center']);
    $section->addText(ucfirst(str_replace('_', ' ', $report_type)) . ' Report', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
    $section->addText('Period: ' . $date_from . ' to ' . $date_to, ['size' => 12], ['alignment' => 'center']);
    $section->addText('Generated on: ' . date('Y-m-d H:i:s'), ['italic' => true, 'size' => 10], ['alignment' => 'center']);
    $section->addTextBreak(2);
    
    if (empty($data)) {
        $section->addText('No data found for the selected period.', ['size' => 12]);
    } else {
        // Table styling
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '0d6efd',
            'cellMargin' => Converter::inchToTwip(0.1),
            'width' => 100 * 50
        ];
        
        $headerStyle = ['bold' => true, 'color' => 'ffffff'];
        $headerCellStyle = ['bgColor' => '0d6efd'];
        
        $table = $section->addTable($tableStyle);
        
        // Headers
        $headers = array_keys($data[0]);
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell()->addText(ucfirst(str_replace('_', ' ', $header)), $headerStyle, $headerCellStyle);
        }
        
        // Data rows
        foreach ($data as $record) {
            $table->addRow();
            foreach ($record as $value) {
                $table->addCell()->addText((string)$value);
            }
        }
    }
    
    $filename = 'library_report_' . $report_type . '_' . date('Y-m-d') . '.docx';
    
    // Output file
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save('php://output');
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
                <div class="d-flex gap-2">
                    <?php if (!empty($report_data)): ?>
                    <a href="export_handler.php?<?php echo http_build_query($_GET); ?>&export=excel" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                    </a>
                    <a href="export_handler.php?<?php echo http_build_query($_GET); ?>&export=word" class="btn btn-primary">
                        <i class="bi bi-file-earmark-word me-2"></i>Export Word
                    </a>
                    <?php endif; ?>
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
                                    <option value="books" <?php echo $report_type == 'books' ? 'selected' : ''; ?>>Books Catalog</option>
                                    <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Statistics</option>
                                    <option value="by_department" <?php echo $report_type == 'by_department' ? 'selected' : ''; ?>>By Department</option>
                                    <option value="by_author" <?php echo $report_type == 'by_author' ? 'selected' : ''; ?>>By Author</option>
                                    <option value="visitors" <?php echo $report_type == 'visitors' ? 'selected' : ''; ?>>Visitor Analytics</option>
                                </select>
                            </div>
                            
                            <!-- Date From -->
                            <div class="col-md-2">
                                <label for="date_from" class="form-label fw-semibold">Date From</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            
                            <!-- Date To -->
                            <div class="col-md-2">
                                <label for="date_to" class="form-label fw-semibold">Date To</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
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
                    <small class="text-muted">
                        Period: <?php echo $date_from; ?> to <?php echo $date_to; ?>
                    </small>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($report_data)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">No Data Found</h4>
                        <p class="text-muted">No records found for the selected filters. Try adjusting your search criteria.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <?php foreach (array_keys($report_data[0]) as $header): ?>
                                    <th class="fw-semibold"><?php echo ucfirst(str_replace('_', ' ', $header)); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $key => $value): ?>
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
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination would go here if needed -->
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Showing <?php echo count($report_data); ?> record(s) 
                                <?php if (!empty($department)): ?>
                                    for department: <strong><?php echo htmlspecialchars($department); ?></strong>
                                <?php endif; ?>
                            </small>
                            <div class="d-flex gap-2">
                                <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                                <?php if (!empty($report_data)): ?>
                                <a href="export_handler.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'excel'])); ?>" class="btn btn-success btn-sm">
                                    <i class="bi bi-download me-1"></i>Excel
                                </a>
                                <a href="export_handler.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'word'])); ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-download me-1"></i>Word
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print">
    .btn, .card-footer, .card-header .d-flex > div:last-child {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    body {
        font-size: 12px;
    }
    .table {
        font-size: 11px;
    }
</style>

<script>
// Auto-submit form when report type changes
document.getElementById('report_type').addEventListener('change', function() {
    // You can auto-submit here or let user click Generate Report
    // this.form.submit();
});

// Set default dates if empty
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    if (!dateFrom.value) {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        dateFrom.value = firstDay.toISOString().split('T')[0];
    }
    
    if (!dateTo.value) {
        const now = new Date();
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        dateTo.value = lastDay.toISOString().split('T')[0];
    }
});
</script>