<?php
require_once '../includes/db.php';
require_once '../includes/track_visitor.php';

// Track the visitor
trackVisitor($conn);
$totalVisitors = getTotalVisitors($conn);
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'E-Book Library'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/layout.css">

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
            <img src="../assets/images/images-removebg-preview.png" alt="" style="width: 50px; height: 50px; border-radius: 50%;">
            <a href="books.php" class="sidebar-brand">ISU-ORA</a>
            </div>
            <div class="sidebar-toggle d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </div>
        </div>

        <div class="sidebar-content">
            <div class="sidebar-nav">
                <div class="sidebar-heading">Main</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-house"></i> Home
                        </a>
                    </li>
                </ul>

                <!-- Replace the comment "number of visit view the page" with: -->
                <div class="sidebar-heading">Statistics</div>
<div class="visitor-stats">
    <div class="visitor-count">
        <i class="bi bi-people-fill"></i>
        <h5>Total Visitors</h5>
        <span class="visitor-number"><?php echo number_format($totalVisitors); ?></span>
    </div>
</div>
                <?php if ($isAdmin): ?>
                <div class="sidebar-heading">Administration</div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="add_books.php">
                            <i class="bi bi-plus-circle"></i> Add Book
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="list_books.php">
                            <i class="bi bi-book"></i> List Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Exit Admin
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="sidebar-footer">
            <p>&copy; <?php echo date('Y'); ?> ISU-ORA Library. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

<style>
    .logo {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 15px;
    }

    .visitor-stats {
    padding: 10px 15px;
}

.visitor-count {
    background: rgba(13, 110, 253, 0.1);
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.visitor-count i {
    font-size: 24px;
    color: #0d6efd;
    margin-bottom: 8px;
}

.visitor-count h5 {
    margin: 8px 0;
    font-size: 14px;
    color: #495057;
}

.visitor-number {
    display: block;
    font-size: 20px;
    font-weight: bold;
    color: #0d6efd;
    padding: 5px 10px;
    background: white;
    border-radius: 4px;
    margin-top: 5px;
}
</style>