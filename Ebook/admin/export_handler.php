<?php
// export_handler.php - Separate file for handling exports to avoid header issues
session_start();

// Check if admin is logged in
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    die('Access denied');
}

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

require_once '../includes/db.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Shared\Converter;

// Get parameters
$report_type = $_GET['report_type'] ?? 'books';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-t');
$department = $_GET['department'] ?? '';
$export_format = $_GET['export'] ?? '';

// Validate export format
if (!in_array($export_format, ['excel', 'word'])) {
    http_response_code(400);
    die('Invalid export format');
}

// Get report data
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
        error_log("Database error in getReportData: " . $e->getMessage());
        return [];
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
    header('Pragma: public');
    
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
    header('Pragma: public');
    
    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save('php://output');
}

// Get the data
$data = getReportData($pdo, $report_type, $date_from, $date_to, $department);

// Export based on format
try {
    if ($export_format == 'excel') {
        exportToExcel($data, $report_type, $date_from, $date_to);
    } elseif ($export_format == 'word') {
        exportToWord($data, $report_type, $date_from, $date_to);
    }
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    http_response_code(500);
    die('Export failed: ' . $e->getMessage());
}
?>