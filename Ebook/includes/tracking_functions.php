<?php
// Create a new file: includes/tracking_functions.php

function getUserIP() {
    // Get user IP address
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function trackBookView($pdo, $book_id) {
    $ip_address = getUserIP();
    
    // Check if this IP has viewed this book in the last 24 hours
    $checkQuery = "
        SELECT id FROM book_views 
        WHERE book_id = ? AND ip_address = ? 
        AND view_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$book_id, $ip_address]);
    
    if ($checkStmt->rowCount() == 0) {
        // No view in last 24 hours, so count this view
        $insertQuery = "INSERT INTO book_views (book_id, ip_address) VALUES (?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$book_id, $ip_address]);
        
        // Update book view count
        $updateQuery = "UPDATE books SET view_count = view_count + 1 WHERE id = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$book_id]);
        
        return true; // View was counted
    }
    
    return false; // View was not counted (already viewed in last 24 hours)
}

function toggleBookFavorite($pdo, $book_id) {
    $ip_address = getUserIP();
    
    // Check if this IP has favorited this book in the last 24 hours
    $checkQuery = "
        SELECT id FROM book_favorites 
        WHERE book_id = ? AND ip_address = ? 
        AND favorite_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$book_id, $ip_address]);
    
    if ($checkStmt->rowCount() == 0) {
        // No favorite in last 24 hours, so count this favorite
        $insertQuery = "INSERT INTO book_favorites (book_id, ip_address) VALUES (?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$book_id, $ip_address]);
        
        // Update book favorite count
        $updateQuery = "UPDATE books SET favorite_count = favorite_count + 1 WHERE id = ?";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([$book_id]);
        
        return ['success' => true, 'action' => 'added', 'message' => 'Added to favorites!'];
    } else {
        return ['success' => false, 'action' => 'exists', 'message' => 'Already favorited in last 24 hours!'];
    }
}

function checkIfFavorited($pdo, $book_id) {
    $ip_address = getUserIP();
    
    $checkQuery = "
        SELECT id FROM book_favorites 
        WHERE book_id = ? AND ip_address = ? 
        AND favorite_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$book_id, $ip_address]);
    
    return $checkStmt->rowCount() > 0;
}
?>