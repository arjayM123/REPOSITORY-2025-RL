<?php
// Make sure we have access to the database connection
require_once '../includes/db.php';

// Get total books
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$totalBooksResult = $pdo->query($totalBooksQuery);
$totalBooks = $totalBooksResult->fetch(PDO::FETCH_ASSOC)['total'];

// Get total copies
$totalCopiesQuery = "SELECT SUM(copies) as total_copies FROM books";
$totalCopiesResult = $pdo->query($totalCopiesQuery);
$totalCopies = $totalCopiesResult->fetch(PDO::FETCH_ASSOC)['total_copies'];

// Get books by material type
$materialQuery = "SELECT type_of_material, COUNT(*) as count, SUM(copies) as total_copies 
                  FROM books 
                  WHERE type_of_material IS NOT NULL AND type_of_material != '' 
                  GROUP BY type_of_material 
                  ORDER BY count DESC";
$materialResult = $pdo->query($materialQuery);
$materialStats = $materialResult->fetchAll(PDO::FETCH_ASSOC);

// Get books by department
$departmentQuery = "SELECT department, COUNT(*) as count, SUM(copies) as total_copies 
                    FROM books 
                    WHERE department IS NOT NULL AND department != '' 
                    GROUP BY department 
                    ORDER BY count DESC";
$departmentResult = $pdo->query($departmentQuery);
$departmentStats = $departmentResult->fetchAll(PDO::FETCH_ASSOC);

// Get recent books (last 5 added)
$recentBooksQuery = "SELECT title, author, department, created_at 
                     FROM books 
                     ORDER BY created_at DESC 
                     LIMIT 5";
$recentBooksResult = $pdo->query($recentBooksQuery);
$recentBooks = $recentBooksResult->fetchAll(PDO::FETCH_ASSOC);

// Get total users
$totalUsersQuery = "SELECT COUNT(*) as total FROM users";
$totalUsersResult = $pdo->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch(PDO::FETCH_ASSOC)['total'];

// Get total visitors (unique)
$totalVisitorsQuery = "SELECT COUNT(*) as total FROM visitors";
$totalVisitorsResult = $pdo->query($totalVisitorsQuery);
$totalVisitors = $totalVisitorsResult->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin ORA</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid px-4 py-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            <div class="text-muted">
                <i class="bi bi-calendar3"></i> <?php echo date('F d, Y'); ?>
            </div>
        </div>

        <!-- Statistics Cards Row -->
        <div class="row mb-4">
            <!-- Total Books Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Books</div>
                                <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($totalBooks); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-book text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Copies Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Copies</div>
                                <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($totalCopies); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-stack text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Visitors Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2 border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Site Visitors</div>
                                <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($totalVisitors); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-eye text-warning" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Tables Row -->
        <div class="row">
            <!-- Books by Material Type -->
            <div class="col-xl-6 col-lg-7 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Books by Material Type</h6>
                        <i class="bi bi-bar-chart-fill"></i>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($materialStats)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Material Type</th>
                                            <th class="text-center">Books</th>
                                            <th class="text-center">Total Copies</th>
                                            <th class="text-center">Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($materialStats as $material): ?>
                                            <?php $percentage = ($material['count'] / $totalBooks) * 100; ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($material['type_of_material']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary rounded-pill"><?php echo $material['count']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success rounded-pill"><?php echo $material['total_copies']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar bg-info" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%" 
                                                             aria-valuenow="<?php echo $percentage; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo number_format($percentage, 1); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox display-4 text-muted"></i>
                                <p class="text-muted mt-3">No material type data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Books by Department -->
            <div class="col-xl-6 col-lg-5 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Books by Department/Course</h6>
                        <i class="bi bi-pie-chart-fill"></i>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($departmentStats)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Department</th>
                                            <th class="text-center">Books</th>
                                            <th class="text-center">Copies</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departmentStats as $dept): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary rounded-circle me-3" style="width: 12px; height: 12px;"></div>
                                                        <span class="fw-semibold"><?php echo htmlspecialchars($dept['department']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary rounded-pill"><?php echo $dept['count']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success rounded-pill"><?php echo $dept['total_copies']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-building display-4 text-muted"></i>
                                <p class="text-muted mt-3">No department data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Books Section -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Recently Added Books</h6>
                        <a href="#" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentBooks)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Department</th>
                                            <th>Date Added</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentBooks as $book): ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($book['title']); ?></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td>
                                                    <span class="badge bg-info rounded-pill">
                                                        <?php echo htmlspecialchars($book['department']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i>
                                                    <?php echo date('M d, Y', strtotime($book['created_at'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-book display-4 text-muted"></i>
                                <p class="text-muted mt-3">No recent books found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>