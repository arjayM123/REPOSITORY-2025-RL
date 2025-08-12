<?php
// Helper functions for the library system
if (basename($_SERVER['PHP_SELF']) === 'library_functions.php') {
?>
<?php
/**
 * Helper functions for the library management system
 */

/**
 * Get total books count by status
 */
function getTotalBooksByStatus($pdo, $status = 'active') {
    try {
        if ($status === 'all') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books");
        } elseif ($status === 'deleted') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE is_deleted = 1");
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM books WHERE is_deleted = 0");
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get books by department with counts
 */
function getBooksByDepartment($pdo, $includeDeleted = false) {
    try {
        $whereClause = $includeDeleted ? "" : "WHERE is_deleted = 0";
        
        $stmt = $pdo->prepare("
            SELECT department, COUNT(*) as count 
            FROM books 
            $whereClause 
            AND department IS NOT NULL 
            AND department != '' 
            GROUP BY department 
            ORDER BY count DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get books by material type with counts
 */
function getBooksByMaterial($pdo, $includeDeleted = false) {
    try {
        $whereClause = $includeDeleted ? "" : "WHERE is_deleted = 0";
        
        $stmt = $pdo->prepare("
            SELECT type_of_material, COUNT(*) as count 
            FROM books 
            $whereClause 
            AND type_of_material IS NOT NULL 
            AND type_of_material != '' 
            GROUP BY type_of_material 
            ORDER BY count DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get status distribution
 */
function getStatusDistribution($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN status = 'active' OR status IS NULL THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'locked' THEN 1 ELSE 0 END) as locked
            FROM books 
            WHERE is_deleted = 0
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['active' => 0, 'locked' => 0];
    }
}

/**
 * Format file size
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * Validate image file
 */
function validateImageFile($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return "Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.";
    }
    
    if ($file['size'] > $maxSize) {
        return "File size too large. Maximum size is 5MB.";
    }
    
    return true;
}

/**
 * Generate unique filename for uploaded files
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
    
    return $basename . '_' . time() . '_' . uniqid() . '.' . $extension;
}

/**
 * Clean up old deleted books (older than 30 days)
 */
function cleanupOldDeletedBooks($pdo) {
    try {
        // Get books deleted more than 30 days ago
        $stmt = $pdo->prepare("
            SELECT id, cover_image 
            FROM books 
            WHERE is_deleted = 1 
            AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $oldBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($oldBooks as $book) {
            // Delete cover image if exists
            if (!empty($book['cover_image'])) {
                $coverPath = "../uploads/covers/" . $book['cover_image'];
                if (file_exists($coverPath)) {
                    unlink($coverPath);
                }
            }
        }
        
        // Permanently delete the books
        $stmt = $pdo->prepare("
            DELETE FROM books 
            WHERE is_deleted = 1 
            AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}
?>
<?php } ?>