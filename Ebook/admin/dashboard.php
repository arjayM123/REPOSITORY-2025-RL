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

// Get most favorited books
$mostFavoritedQuery = "SELECT b.title, b.author, b.department, COUNT(bf.book_id) as favorite_count
                       FROM books b
                       INNER JOIN book_favorites bf ON b.id = bf.book_id
                       GROUP BY b.id, b.title, b.author, b.department
                       ORDER BY favorite_count DESC
                       LIMIT 5";
$mostFavoritedResult = $pdo->query($mostFavoritedQuery);
$mostFavorited = $mostFavoritedResult->fetchAll(PDO::FETCH_ASSOC);

// Get most viewed books
$mostViewedQuery = "SELECT b.title, b.author, b.department, COUNT(bv.book_id) as view_count
                    FROM books b
                    INNER JOIN book_views bv ON b.id = bv.book_id
                    GROUP BY b.id, b.title, b.author, b.department
                    ORDER BY view_count DESC
                    LIMIT 5";
$mostViewedResult = $pdo->query($mostViewedQuery);
$mostViewed = $mostViewedResult->fetchAll(PDO::FETCH_ASSOC);

// Get total favorites count
$totalFavoritesQuery = "SELECT COUNT(*) as total FROM book_favorites";
$totalFavoritesResult = $pdo->query($totalFavoritesQuery);
$totalFavorites = $totalFavoritesResult->fetch(PDO::FETCH_ASSOC)['total'];

// Get total views count
$totalViewsQuery = "SELECT COUNT(*) as total FROM book_views";
$totalViewsResult = $pdo->query($totalViewsQuery);
$totalViews = $totalViewsResult->fetch(PDO::FETCH_ASSOC)['total'];

// Get total book requests
$totalRequestsQuery = "SELECT COUNT(*) as total FROM book_requests";
$totalRequestsResult = $pdo->query($totalRequestsQuery);
$totalRequests = $totalRequestsResult->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get all book requests
$requestsQuery = "SELECT * FROM book_requests ORDER BY requested_at DESC";
$requestsResult = $pdo->query($requestsQuery);
$bookRequests = $requestsResult->fetchAll(PDO::FETCH_ASSOC);
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

            <!-- Total Book Requests Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2 border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">Total Book Requests</div>
                                <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($totalRequests); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-journal-plus text-warning" style="font-size: 2rem;"></i>
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
                                                             style="width: <?php echo number_format($percentage, 1) . '%'; ?>%" 
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

        <!-- Most Favorite and Most Viewed Books Row -->
        <div class="row mb-4">
            <!-- Most Favorited Books -->
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-info">Most Favorited Books</h6>
                        <i class="bi bi-heart-fill text-info"></i>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mostFavorited)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th class="text-center">Department</th>
                                            <th class="text-center">Favorites</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mostFavorited as $index => $book): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-info rounded-circle me-2" style="font-size: 0.7rem;">
                                                            <?php echo $index + 1; ?>
                                                        </span>
                                                        <span class="fw-semibold"><?php echo htmlspecialchars($book['title']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary rounded-pill" style="font-size: 0.7rem;">
                                                        <?php echo htmlspecialchars($book['department']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info rounded-pill">
                                                        <i class="bi bi-heart-fill me-1"></i>
                                                        <?php echo $book['favorite_count']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-heart display-4 text-muted"></i>
                                <p class="text-muted mt-3">No favorited books yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Most Viewed Books -->
            <div class="col-xl-6 col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-warning">Most Viewed Books</h6>
                        <i class="bi bi-eye-fill text-warning"></i>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($mostViewed)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th class="text-center">Department</th>
                                            <th class="text-center">Views</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mostViewed as $index => $book): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-warning rounded-circle me-2" style="font-size: 0.7rem;">
                                                            <?php echo $index + 1; ?>
                                                        </span>
                                                        <span class="fw-semibold"><?php echo htmlspecialchars($book['title']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary rounded-pill" style="font-size: 0.7rem;">
                                                        <?php echo htmlspecialchars($book['department']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-warning rounded-pill">
                                                        <i class="bi bi-eye-fill me-1"></i>
                                                        <?php echo $book['view_count']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-eye display-4 text-muted"></i>
                                <p class="text-muted mt-3">No book views yet</p>
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

        <!-- Book Requests Section -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Book Requests</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($bookRequests)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>ID Number</th>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Copy</th>
                                            <th>Year</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Date Requested</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookRequests as $i => $req): ?>
                                            <tr>
                                                <td><?php echo $i + 1; ?></td>
                                                <td><?php echo !empty($req['student_name']) ? htmlspecialchars($req['student_name']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['id_number']) ? htmlspecialchars($req['id_number']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['book_title']) ? htmlspecialchars($req['book_title']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['book_author']) ? htmlspecialchars($req['book_author']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['book_copy']) ? htmlspecialchars($req['book_copy']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['book_year']) ? htmlspecialchars($req['book_year']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['contact']) ? htmlspecialchars($req['contact']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['address']) ? htmlspecialchars($req['address']) : 'N/A'; ?></td>
                                                <td><?php echo !empty($req['requested_at']) ? date('M d, Y H:i', strtotime($req['requested_at'])) : 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-journal-plus display-4 text-muted"></i>
                                <p class="text-muted mt-3">No book requests found</p>
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