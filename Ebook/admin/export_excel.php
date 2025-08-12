<?php
// Include database connection
include '../includes/db.php';

// Set headers for Excel download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="library_collection_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Require PhpSpreadsheet library
require '../../vendor/autoload.php'; // Make sure you have PhpSpreadsheet installed via Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new PhpSpreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Library Collection');

// Set document properties
$spreadsheet->getProperties()->setCreator('ISU Library')
    ->setLastModifiedBy('ISU Library')
    ->setTitle('Library Collection Export')
    ->setSubject('Library Collection')
    ->setDescription('Export of library collection data');

// Define categories with DDC ranges (same as in your main file)
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

// Function to determine category based on classification number (same as in your main file)
function getDDCCategory($callNo)
{
    // Extract the first number group from call number
    if (preg_match('/^(\d+)/', $callNo, $matches)) {
        $classNum = intval($matches[1]);

        // Determine category range
        $baseRange = floor($classNum / 100) * 100;
        $categoryKey = sprintf('%03d-%03d', $baseRange, $baseRange + 99);

        return $categoryKey;
    }
    return '000-099'; // Default category if no match
}

// Build WHERE clause based on filters
$whereClause = "";

if (isset($_GET['department']) && $_GET['department'] !== '') {
    $dept_id = intval($_GET['department']);
    $whereClause .= "department_id = $dept_id";
}

if (isset($_GET['location']) && $_GET['location'] !== '') {
    $location = $conn->real_escape_string($_GET['location']);
    if ($whereClause !== "") {
        $whereClause .= " AND ";
    }
    $whereClause .= "location = '$location'";
}

// Get filter information for header
$headerInfo = [];
$headerInfo[] = ['Republic of the Philippines'];
$headerInfo[] = ['ISABELA STATE UNIVERSITY'];
$headerInfo[] = ['LIST OF GENERAL COLLECTION'];

if (isset($_GET['department']) && $_GET['department'] !== '') {
    $dept_name_query = "SELECT department_name FROM departments WHERE id = " . intval($_GET['department']);
    $dept_name_result = $conn->query($dept_name_query);
    if ($dept_name_result && $dept_name_result->num_rows > 0) {
        $dept_name = $dept_name_result->fetch_assoc()['department_name'];
        $headerInfo[] = ["Department: {$dept_name}"];
    }
}

if (isset($_GET['location']) && $_GET['location'] !== '') {
    $headerInfo[] = ["( {$_GET['location']} )"];
}

// Add header info to Excel
$rowNum = 1;
foreach ($headerInfo as $headerRow) {
    $sheet->fromArray($headerRow, NULL, 'A' . $rowNum);
    // Merge cells for header
    $sheet->mergeCells('A' . $rowNum . ':E' . $rowNum);
    // Center align
    $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $rowNum++;
}

$rowNum += 1; // Add some space

// Add each category
$sheetFormat = $spreadsheet->getActiveSheet()->getStyle('A1:E1');
$sheetFormat->getFont()->setBold(true);

// Query for books
$query = "SELECT 
    classification_number,
    call_number,
    date_of_publication,
    accession_number,
    CONCAT(author, title, '.' ,' ', edition, place_of_publication, publisher,'.', ' c', date_of_publication) as author_title,
    '1' as title_count,
    copies as volume
FROM books ";

if ($whereClause !== "") {
    $query .= "WHERE $whereClause ";
}

$query .= "ORDER BY classification_number ASC, call_number ASC";

$result = $conn->query($query);

// Create an array to store books by category
$categorized_books = array();
$total_books = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $category = getDDCCategory($row['classification_number']);
        if (!isset($categorized_books[$category])) {
            $categorized_books[$category] = array();
        }
        $categorized_books[$category][] = $row;
        $total_books++;
    }
}

// Add total count
$sheet->setCellValue('A' . $rowNum, "Total: " . $total_books);
$sheet->mergeCells('A' . $rowNum . ':E' . $rowNum);
$sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
$rowNum++;

// Process each category
foreach ($categories as $range => $categoryName) {
    if (isset($categorized_books[$range]) && !empty($categorized_books[$range])) {
        // Add category header
        $rowNum++;
        $sheet->setCellValue('A' . $rowNum, $categoryName . ' (' . $range . ')');
        $sheet->mergeCells('A' . $rowNum . ':E' . $rowNum);
        $sheet->getStyle('A' . $rowNum)->getFont()->setBold(true);
        $rowNum++;
        
        // Add table headers
        $headers = ['CALL NO.', 'ACCESSION NO.', 'AUTHOR/TITLE OF BOOK', 'TITLE', 'VOLUME'];
        $sheet->fromArray($headers, NULL, 'A' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $rowNum++;
        
        // Add data rows
        foreach ($categorized_books[$range] as $row) {
            $formatted_call = $row['classification_number'] . "\n" . 
                             $row['call_number'] . "\n" . 
                             $row['date_of_publication'];
            
            $sheet->setCellValue('A' . $rowNum, $formatted_call);
            $sheet->setCellValue('B' . $rowNum, $row['accession_number']);
            $sheet->setCellValue('C' . $rowNum, $row['author_title']);
            $sheet->setCellValue('D' . $rowNum, $row['title_count']);
            $sheet->setCellValue('E' . $rowNum, $row['volume']);
            
            // Set row height to accommodate multiple lines in the call number cell
            $sheet->getRowDimension($rowNum)->setRowHeight(60);
            
            // Enable text wrapping for call number
            $sheet->getStyle('A' . $rowNum)->getAlignment()->setWrapText(true);
            
            $rowNum++;
        }
    }
}

// Set column widths
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(65);
$sheet->getColumnDimension('D')->setWidth(10);
$sheet->getColumnDimension('E')->setWidth(10);

// Create writer and output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;