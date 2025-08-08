<?php
// Include database connection
include '../includes/db.php';

// Increase memory limit and execution time
ini_set('memory_limit', '256M');
set_time_limit(300);

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Require PhpWord library
require '../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

// Configure PhpWord settings
Settings::setZipClass(Settings::PCLZIP); // Alternative ZIP handler
Settings::setOutputEscapingEnabled(true);

try {
    // Create new PhpWord object
    $phpWord = new PhpWord();
    
    // Add page for document
    $section = $phpWord->addSection();
    
    // Set default font
    $phpWord->setDefaultFontName('Times New Roman');
    $phpWord->setDefaultFontSize(12);
    
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
    
    // Add header to document
    $header = $section->addHeader();
    $header->addText('ISABELA STATE UNIVERSITY', ['bold' => true], ['alignment' => 'center']);
    
    // Add title and subtitle
    $section->addText('Republic of the Philippines', ['bold' => true], ['alignment' => 'center']);
    $section->addText('ISABELA STATE UNIVERSITY', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $section->addText('LIST OF GENERAL COLLECTION', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
    
    // Add filter information
    if (isset($_GET['department']) && $_GET['department'] !== '') {
        $dept_name_query = "SELECT department_name FROM departments WHERE id = " . intval($_GET['department']);
        $dept_name_result = $conn->query($dept_name_query);
        if ($dept_name_result && $dept_name_result->num_rows > 0) {
            $dept_name = $dept_name_result->fetch_assoc()['department_name'];
            $section->addText("Department: {$dept_name}", ['italic' => true], ['alignment' => 'center']);
        }
    }
    
    if (isset($_GET['location']) && $_GET['location'] !== '') {
        $section->addText("( {$_GET['location']} )", ['italic' => true], ['alignment' => 'center']);
    }
    
    $section->addTextBreak(1);
    
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
    $section->addText("Total: " . $total_books, ['bold' => true], ['alignment' => 'right']);
    $section->addTextBreak(1);
    
    // Define table style
    $tableStyle = [
        'borderSize' => 6, 
        'borderColor' => '000000', 
        'cellMargin' => 80
    ];
    
    $firstRowStyle = ['bgColor' => 'E67E22']; // Carrot orange

 // Light gray background for headers
    
    // Process each category
    foreach ($categories as $range => $categoryName) {
        if (isset($categorized_books[$range]) && !empty($categorized_books[$range])) {
            // Add category header
            $section->addText($categoryName . ' (' . $range . ')', ['bold' => true, 'size' => 14]);
            $section->addTextBreak(1);
            
            // Create table for this category
            $table = $section->addTable($tableStyle);
            
            // Add header row
            $table->addRow(400);
            $cell1 = $table->addCell(1500, $firstRowStyle);
            $cell1->addText('CALL NO.', ['bold' => true], ['alignment' => 'center']);
            
            $cell2 = $table->addCell(1200, $firstRowStyle);
            $cell2->addText('ACCESSION NO.', ['bold' => true], ['alignment' => 'center']);
            
            $cell3 = $table->addCell(5500, $firstRowStyle);
            $cell3->addText('AUTHOR/TITLE OF BOOK', ['bold' => true], ['alignment' => 'center']);
            
            $cell4 = $table->addCell(900, $firstRowStyle);
            $cell4->addText('TITLE', ['bold' => true], ['alignment' => 'center']);
            
            $cell5 = $table->addCell(900, $firstRowStyle);
            $cell5->addText('VOLUME', ['bold' => true], ['alignment' => 'center']);
            
            // Add data rows
            $counter = 0;
            foreach ($categorized_books[$range] as $row) {
                // Limit the number of rows per category to avoid memory issues
                if ($counter >= 100) {
                    $table->addRow();
                    $cell = $table->addCell(10000, ['gridSpan' => 5]);
                    $cell->addText('... (Showing first 100 records. Download Excel for full data.)', 
                        ['italic' => true], ['alignment' => 'center']);
                    break;
                }
                
                $table->addRow();
                
                // Split the call number into separate lines
                $callCell = $table->addCell(1500);
                $callCell->addText($row['classification_number'], [], ['alignment' => 'left']);
                $callCell->addText($row['call_number'], [], ['alignment' => 'left']);
                $callCell->addText($row['date_of_publication'], [], ['alignment' => 'left']);
                
                $table->addCell(1200)->addText($row['accession_number'], [], ['alignment' => 'left']);
                
                // Ensure text doesn't contain problematic characters
                $safeAuthorTitle = htmlspecialchars($row['author_title'], ENT_QUOTES, 'UTF-8');
                $safeAuthorTitle = mb_substr($safeAuthorTitle, 0, 255); // Limit length to avoid issues
                
                $table->addCell(5500)->addText($safeAuthorTitle, [], ['alignment' => 'left']);
                $table->addCell(900)->addText($row['title_count'], [], ['alignment' => 'center']);
                $table->addCell(900)->addText($row['volume'], [], ['alignment' => 'center']);
                
                $counter++;
            }
            
            $section->addTextBreak(1);
        }
    }
    
    // Add footer
    $footer = $section->addFooter();
    $footer->addText('Generated on: ' . date('Y-m-d H:i:s'), ['size' => 8], ['alignment' => 'center']);
    
    // Save to temporary file first
    $tempFile = tempnam(sys_get_temp_dir(), 'word_export');
    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
    $objWriter->save($tempFile);
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="library_collection_' . date('Y-m-d') . '.docx"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($tempFile));
    
    // Clear output buffer
    ob_clean();
    flush();
    
    // Output file
    readfile($tempFile);
    
    // Delete temporary file
    unlink($tempFile);
    
} catch (Exception $e) {
    // Log error
    error_log('Word export error: ' . $e->getMessage());
    
    // If there's an error, redirect to an error page or show a message
    header('Content-Type: text/html; charset=utf-8');
    echo "<html><body>";
    echo "<h2>Error Generating Word Document</h2>";
    echo "<p>There was an error generating the Word document. Please try the Excel export instead.</p>";
    echo "<p>Error details: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='javascript:history.back()'>Go back</a></p>";
    echo "</body></html>";
}
exit;