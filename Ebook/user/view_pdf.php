<?php
session_start();
require_once '../includes/db.php';

// Check if book ID is provided
if (isset($_GET['id'])) {
    $book_id = $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        
        // Check if file exists
        if (empty($book['file_path']) || !file_exists("../uploads/" . $book['file_path'])) {
            header("Location: view_books.php?id=" . $book_id);
            exit();
        }
    } else {
        header("Location: books.php");
        exit();
    }
    $stmt->close();
} else {
    header("Location: books.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($book['title']); ?> - Reading View</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
            html {
        scrollbar-width: none;
        scroll-behavior: smooth;
        -ms-overflow-style: none;
    }
    
    ::-webkit-scrollbar {
        display: none;
    }
    
        /* CSS styles */
        body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .top-bar {
            background-color: #343a40;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        
        .pdf-container {
            width: 100%;
            height: calc(100vh - 56px);
            margin-top: 56px;
            position: relative;
            overflow: hidden;
        }
        
        .pdf-viewer {
            width: 100%;
            height: 100%;
            border: none;
            position: relative;
            z-index: 1;
        }
        
        .watermark-container {
            position: absolute;
            top: 0;
            left:0;
            width: 100%;
            height: 100%;
            pointer-events: none; /* Makes the overlay non-interactive */
            z-index: 10;
            display: flex;
            justify-content: center;
            justify-content: center;
            align-items: center;

        }
        
        .watermark {
            opacity: 0.18; /* Semi-transparent */
            transform: rotate(-30deg);
            text-align: center;
        }
        
        .watermark img {
            max-width: 200px;
            height: auto;
        }
        
        .watermark-text {
            font-size: 24px;
            font-weight: bold;
            color: #006400; /* Dark green color */
            margin-top: 10px;
        }
        
        .book-title {
            margin: 0;
            font-size: 1.2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 80%;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
        <a href="view_books.php?id=<?php echo $book_id; ?>" class="btn btn-outline-light btn-sm">
            <i class="fas fa-times"></i> Close Reader
        </a>
    </div>
    
    <div class="pdf-container">
        <!-- PDF Viewer -->
        <object class="pdf-viewer" 
                data="../uploads/<?php echo htmlspecialchars($book['file_path']); ?>#toolbar=0&navpanes=0&scrollbar=1"
                type="application/pdf">
            <p>It appears your browser doesn't support embedded PDFs. 
               <a href="view_books.php?id=<?php echo $book_id; ?>">Return to book details</a>.</p>
        </object>
        
        <!-- Watermark Overlay -->
        <div class="watermark-container">
            <div class="watermark">
                <img src="../assets/images/images-removebg-preview.png" alt="ISU Logo">
                <div class="watermark-text">PROPERTY OF ISU-ROXAS LIBRARY</div>
            </div>
        </div>
    </div>

    <!-- JavaScript to prevent print and download actions -->
    <script>
        // Prevent keyboard shortcuts for printing and saving
        document.addEventListener('keydown', function(e) {
            // Prevent Ctrl+P (Print)
            if (e.ctrlKey && (e.key === 'p' || e.keyCode === 80)) {
                e.preventDefault();
                return false;
            }
            
            // Prevent Ctrl+S (Save)
            if (e.ctrlKey && (e.key === 's' || e.keyCode === 83)) {
                e.preventDefault();
                return false;
            }
        });
        
        // Additional protection against right-clicks
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Create multiple watermarks throughout the document
        window.onload = function() {
            const container = document.querySelector('.pdf-container');
            const watermark = document.querySelector('.watermark').cloneNode(true);
            
            // Create additional watermarks
            for (let i = 0; i < 2; i++) {
                const newWatermark = document.createElement('div');
                newWatermark.className = 'watermark-container';
                newWatermark.style.top = `${100 + (i * 100)}%`;
                
                const watermarkClone = watermark.cloneNode(true);
                newWatermark.appendChild(watermarkClone);
                container.appendChild(newWatermark);
            }
        };
    </script>
</body>
</html>